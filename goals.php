<?php
// Финансовые цели: просмотр, добавление, пополнение, удаление
$page = 'goals';
$title = 'Цели';
require_once 'includes/functions.php';
$u = current_user();
$errors = [];

if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $uid = $u['id_user'];
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['goal_name'] ?? '');
        $target = (float)str_replace(',', '.', $_POST['target_amount'] ?? 0);
        $current = (float)str_replace(',', '.', $_POST['current_amount'] ?? 0);
        $deadline = $_POST['deadline'] ?? '';
        if ($name === '') $errors[] = 'Введите название цели';
        if ($target <= 0) $errors[] = 'Сумма цели должна быть больше нуля';
        if ($current < 0 || $current > $target) $errors[] = 'Накоплено не может быть больше суммы цели';
        if (!$deadline || strtotime($deadline) <= time()) $errors[] = 'Срок должен быть в будущем';
        if (!$errors) {
            $pdo->prepare('INSERT INTO goals (id_user, goal_name, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?, ?)')
                ->execute([$uid, $name, $target, $current, $deadline]);
            flash('Цель «' . $name . '» добавлена.');
            header('Location: goals.php');
            exit;
        }
    } elseif ($action === 'topup') {
        $sum = (float)str_replace(',', '.', $_POST['sum'] ?? 0);
        if ($sum > 0) {
            $pdo->prepare("UPDATE goals SET current_amount = LEAST(target_amount, current_amount + ?),
                           goal_status = IF(current_amount >= target_amount, 'done', 'active')
                           WHERE id_goal = ? AND id_user = ?")
                ->execute([$sum, (int)$_POST['id_goal'], $uid]);
            flash('Цель пополнена на ' . money($sum) . '.');
        }
        header('Location: goals.php');
        exit;
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM goals WHERE id_goal = ? AND id_user = ?')->execute([(int)$_POST['id_goal'], $uid]);
        flash('Цель удалена.');
        header('Location: goals.php');
        exit;
    }
}

if ($u) {
    // Запрос 3: финансовые цели пользователя (представление) + id для действий
    $st = $pdo->prepare('SELECT * FROM v_user_goals WHERE id_user = ? ORDER BY goal_status, deadline');
    $st->execute([$u['id_user']]);
    $goals = $st->fetchAll();
}
require 'includes/header.php';
?>
<div class="section-title"><h1>Финансовые цели</h1></div>
<?php if (!$u): ?>
    <div class="card">
        <h3>Ставьте цели и копите на них</h3>
        <p class="muted">Укажите сумму и срок — сервис покажет прогресс и рассчитает ежемесячный взнос. Войдите, чтобы начать.</p>
        <a class="btn btn-primary" href="login.php">Войти</a> <a class="btn btn-ghost" href="register.php">Регистрация</a>
    </div>
<?php else: ?>
    <?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>
    <div class="grid g3">
        <?php foreach ($goals as $g):
            $monthsLeft = max(1, (int)ceil((strtotime($g['deadline']) - time()) / (30 * 86400)));
            $perMonth = max(0, $g['target_amount'] - $g['current_amount']) / $monthsLeft; ?>
            <div class="card">
                <div style="display:flex;justify-content:space-between;gap:10px">
                    <h3><?= e($g['goal_name']) ?></h3>
                    <span class="chip" style="<?= $g['goal_status'] === 'done' ? 'background:#E3F6F1;color:#1E8C73' : '' ?>"><?= $g['goal_status'] === 'done' ? 'Достигнута' : 'В процессе' ?></span>
                </div>
                <div class="muted" style="font-size:14px">до <?= date('d.m.Y', strtotime($g['deadline'])) ?></div>
                <div style="display:flex;justify-content:space-between;margin-top:14px;align-items:baseline">
                    <b style="font-size:22px;color:var(--indigo)"><?= money($g['current_amount']) ?></b>
                    <span class="muted">из <?= money($g['target_amount']) ?></span>
                </div>
                <div class="bar" style="margin:10px 0"><div style="width:<?= min(100, $g['progress_percent']) ?>%"></div></div>
                <div style="font-size:14px"><?= $g['progress_percent'] ?> % · откладывайте <b><?= money($perMonth) ?></b> в месяц</div>
                <?php if ($g['goal_status'] !== 'done'): ?>
                    <form method="post" data-validate novalidate class="form-row" style="margin-top:14px">
                        <?= csrf_field() ?><input type="hidden" name="action" value="topup"><input type="hidden" name="id_goal" value="<?= $g['id_goal'] ?>">
                        <div class="field" style="margin:0"><input name="sum" data-mask="money" data-rules="required money" placeholder="Сумма, ₽"></div>
                        <button class="btn btn-ghost" type="submit">Пополнить</button>
                    </form>
                <?php endif; ?>
                <form method="post" style="margin-top:10px">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id_goal" value="<?= $g['id_goal'] ?>">
                    <button class="btn btn-danger btn-sm" type="submit" data-confirm="Удалить цель?">Удалить</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mt">
        <h3>Новая цель</h3>
        <form method="post" data-validate novalidate>
            <?= csrf_field() ?><input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="field">Название *<input name="goal_name" data-rules="required" placeholder="Например, отпуск"></div>
                <div class="field">Сумма цели, ₽ *<input name="target_amount" data-mask="money" data-rules="required money" placeholder="100000"></div>
                <div class="field">Уже накоплено, ₽<input name="current_amount" data-mask="money" placeholder="0"></div>
                <div class="field">Срок *<input type="date" name="deadline" data-rules="required" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
                <div class="field"><button class="btn btn-primary" type="submit">Добавить цель</button></div>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
