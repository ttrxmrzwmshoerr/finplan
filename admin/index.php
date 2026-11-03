<?php
// Административная панель: обзор таблиц и представлений базы данных
require_once 'admin_lib.php';
$title = 'Админ-панель';
$page = 'admin';
$tables = db_objects($pdo, 'BASE TABLE');
$views = db_objects($pdo, 'VIEW');
require '../includes/header.php';
?>
<div class="admin">
    <?php admin_menu($pdo); ?>
    <div>
        <div class="section-title"><h1>Административная панель</h1><span class="muted">База данных: finplan</span></div>
        <div class="grid g4">
            <div class="card kpi"><span>Таблиц</span><b><?= count($tables) ?></b></div>
            <div class="card kpi"><span>Представлений</span><b><?= count($views) ?></b></div>
            <div class="card kpi"><span>Пользователей</span><b><?= $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?></b></div>
            <div class="card kpi"><span>Операций</span><b><?= $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn() ?></b></div>
        </div>
        <div class="grid g2 mt">
            <div class="card">
                <h3>Таблицы (добавление, изменение, удаление)</h3>
                <table class="t">
                    <tr><th>Таблица</th><th>Название в БД</th><th class="num">Записей</th></tr>
                    <?php foreach ($tables as $t): ?>
                        <tr><td><a class="accent" href="table.php?t=<?= e($t) ?>"><b><?= e(TABLE_TITLES[$t] ?? $t) ?></b></a></td><td class="muted"><?= e($t) ?></td>
                            <td class="num"><?= $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="card">
                <h3>Представления (только просмотр)</h3>
                <table class="t">
                    <tr><th>Запрос</th><th>Название в БД</th><th class="num">Строк</th></tr>
                    <?php foreach ($views as $v): ?>
                        <tr><td><a class="accent" href="view.php?v=<?= e($v) ?>"><b><?= e(TABLE_TITLES[$v] ?? $v) ?></b></a></td><td class="muted"><?= e($v) ?></td>
                            <td class="num"><?= $pdo->query("SELECT COUNT(*) FROM `$v`")->fetchColumn() ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
