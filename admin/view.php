<?php
// Просмотр представления (только чтение)
require_once 'admin_lib.php';
$views = db_objects($pdo, 'VIEW');
$v = $_GET['v'] ?? '';
if (!in_array($v, $views, true)) {
    http_response_code(404);
    exit('Представление не найдено');
}
$rows = $pdo->query("SELECT * FROM `$v`")->fetchAll();
$sql = $pdo->query("SHOW CREATE VIEW `$v`")->fetch()['Create View'] ?? '';
$title = TABLE_TITLES[$v] ?? $v;
$page = 'admin';
require '../includes/header.php';
?>
<div class="admin">
    <?php admin_menu($pdo, $v); ?>
    <div>
        <div class="section-title"><h1><?= e($title) ?></h1><span class="chip">Представление <?= e($v) ?> · только просмотр</span></div>
        <div class="card">
            <div class="table-wrap">
                <table class="t">
                    <?php if ($rows): ?>
                        <tr><?php foreach (array_keys($rows[0]) as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
                        <?php foreach ($rows as $r): ?>
                            <tr><?php foreach ($r as $val): ?><td><?= e($val) ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td class="muted">Нет данных.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require '../includes/footer.php'; ?>
