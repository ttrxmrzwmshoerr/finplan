<?php
// Регистрация нового пользователя
require_once 'includes/functions.php';
$errors = [];
$data = ['user_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    foreach ($data as $k => $v) {
        $data[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Серверная проверка всех полей
    if ($data['user_name'] === '') {
        $errors['user_name'] = 'Заполните это поле';
    } elseif (!preg_match('/^[A-Za-zА-Яа-яЁё\s-]{2,60}$/u', $data['user_name'])) {
        $errors['user_name'] = 'Только буквы, от 2 символов';
    }
    if ($data['email'] === '') {
        $errors['email'] = 'Заполните это поле';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите адрес в формате name@example.ru';
    }
    if ($data['phone'] !== '' && !preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $data['phone'])) {
        $errors['phone'] = 'Введите телефон полностью';
    }
    if ($password === '') {
        $errors['password'] = 'Заполните это поле';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        $errors['password'] = 'Не менее 8 символов: латинские буквы и цифры, хотя бы одна заглавная';
    }
    if ($password2 !== $password) {
        $errors['password2'] = 'Пароли не совпадают';
    }
    if (empty($_POST['agree'])) {
        $errors['agree'] = 'Необходимо согласие';
    }
    if (!$errors) {
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$data['email']]);
        if ($st->fetch()) {
            $errors['email'] = 'Пользователь с такой почтой уже зарегистрирован';
        }
    }
    if (!$errors) {
        $pdo->beginTransaction();
        $st = $pdo->prepare('INSERT INTO users (id_role, user_name, email, phone, password_hash) VALUES (2, ?, ?, ?, ?)');
        $st->execute([$data['user_name'], $data['email'], $data['phone'] ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int)$pdo->lastInsertId();
        // Каждому новому пользователю создаётся счёт «Наличные» в рублях
        $pdo->prepare("INSERT INTO accounts (id_user, id_account_type, id_currency, account_name, balance) VALUES (?, 2, 1, 'Наличные', 0)")
            ->execute([$id]);
        $pdo->commit();

        $st = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.id_user = ?');
        $st->execute([$id]);
        $user = $st->fetch();
        unset($user['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        flash('Аккаунт создан. Добавьте первые счета и операции!');
        header('Location: cabinet.php');
        exit;
    }
}

function err($errors, $k) { return isset($errors[$k]) ? 'invalid' : ''; }

$page = 'register';
$title = 'Регистрация';
require 'includes/header.php';
?>
<section class="auth">
    <div class="auth-img">Начните планировать финансы уже сегодня</div>
    <div class="auth-form">
        <h1>Регистрация</h1>
        <p class="muted">Уже есть аккаунт? <a class="accent" href="login.php"><b>Войти</b></a></p>
        <form method="post" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="field <?= err($errors, 'user_name') ?>">Имя *
                <input name="user_name" value="<?= e($data['user_name']) ?>" data-rules="required name" placeholder="Анна">
                <span class="error"><?= e($errors['user_name'] ?? '') ?></span>
            </div>
            <div class="form-row">
                <div class="field <?= err($errors, 'email') ?>">Электронная почта *
                    <input type="email" name="email" value="<?= e($data['email']) ?>" data-rules="required email" placeholder="name@example.ru">
                    <span class="error"><?= e($errors['email'] ?? '') ?></span>
                </div>
                <div class="field <?= err($errors, 'phone') ?>">Телефон
                    <input name="phone" value="<?= e($data['phone']) ?>" data-mask="phone" data-rules="phone" placeholder="+7 (___) ___-__-__" inputmode="tel">
                    <span class="error"><?= e($errors['phone'] ?? '') ?></span>
                </div>
            </div>
            <div class="field <?= err($errors, 'password') ?>">Пароль *
                <input type="password" id="password" name="password" data-rules="required password" placeholder="Не менее 8 символов">
                <span class="error"><?= e($errors['password'] ?? '') ?></span>
            </div>
            <div class="field <?= err($errors, 'password2') ?>">Повторите пароль *
                <input type="password" name="password2" data-rules="required match" data-match="password">
                <span class="error"><?= e($errors['password2'] ?? '') ?></span>
            </div>
            <div class="field check <?= err($errors, 'agree') ?>">
                <label class="check"><input type="checkbox" name="agree" value="1" required> Я согласен на обработку персональных данных</label>
                <span class="error"><?= e($errors['agree'] ?? '') ?></span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Зарегистрироваться</button>
        </form>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
