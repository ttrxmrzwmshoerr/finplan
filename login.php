<?php
// Авторизация пользователя
require_once 'includes/functions.php';
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Блокировка после 5 неудачных попыток на 5 минут
    if (($_SESSION['login_fail'] ?? 0) >= 5 && time() - ($_SESSION['login_fail_time'] ?? 0) < 300) {
        $errors['form'] = 'Слишком много попыток входа. Повторите через 5 минут.';
    } else {
        if ($email === '') {
            $errors['email'] = 'Заполните это поле';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите адрес в формате name@example.ru';
        }
        if ($password === '') {
            $errors['password'] = 'Заполните это поле';
        }
        if (!$errors) {
            $st = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.email = ?');
            $st->execute([$email]);
            $user = $st->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                $_SESSION['login_fail'] = 0;
                flash('Добро пожаловать, ' . $user['user_name'] . '!');
                header('Location: ' . ($user['id_role'] == 1 ? 'admin/index.php' : 'cabinet.php'));
                exit;
            }
            $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
            $_SESSION['login_fail_time'] = time();
            $errors['form'] = 'Неверная электронная почта или пароль.';
        }
    }
}

$page = 'login';
$title = 'Вход';
require 'includes/header.php';
?>
<section class="auth">
    <div class="auth-img">Отпуск, ремонт, подушка безопасности — всё по плану</div>
    <div class="auth-form">
        <h1>Вход</h1>
        <p class="muted">Нет аккаунта? <a class="accent" href="register.php"><b>Зарегистрироваться</b></a></p>
        <?php if (!empty($errors['form'])): ?><div class="alert alert-err"><?= e($errors['form']) ?></div><?php endif; ?>
        <form method="post" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="field <?= isset($errors['email']) ? 'invalid' : '' ?>">Электронная почта
                <input type="email" name="email" value="<?= e($email) ?>" data-rules="required email" placeholder="name@example.ru" autocomplete="email">
                <span class="error"><?= e($errors['email'] ?? '') ?></span>
            </div>
            <div class="field <?= isset($errors['password']) ? 'invalid' : '' ?>">Пароль
                <input type="password" name="password" data-rules="required" placeholder="Ваш пароль" autocomplete="current-password">
                <span class="error"><?= e($errors['password'] ?? '') ?></span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Войти</button>
        </form>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
