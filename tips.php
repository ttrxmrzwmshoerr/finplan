<?php
// Советы по финансовой грамотности (из таблицы tips)
$page = 'tips';
$title = 'Советы';
require_once 'includes/functions.php';
$cat = (int)($_GET['cat'] ?? 0);
$cats = $pdo->query('SELECT DISTINCT c.id_category, c.category_name FROM tips t JOIN categories c ON c.id_category = t.id_category ORDER BY c.category_name')->fetchAll();
$sql = 'SELECT t.*, u.user_name, c.category_name FROM tips t
        JOIN users u ON u.id_user = t.id_user
        LEFT JOIN categories c ON c.id_category = t.id_category';
$params = [];
if ($cat) {
    $sql .= ' WHERE t.id_category = ?';
    $params[] = $cat;
}
$sql .= ' ORDER BY t.published_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$tips = $st->fetchAll();
require 'includes/header.php';
?>
<div class="section-title"><h1>Советы</h1><span class="muted">Короткие правила, которые помогают тратить осознанно</span></div>
<div class="tabs">
    <a class="tab <?= !$cat ? 'active' : '' ?>" href="tips.php">Все</a>
    <?php foreach ($cats as $c): ?>
        <a class="tab <?= $cat == $c['id_category'] ? 'active' : '' ?>" href="tips.php?cat=<?= $c['id_category'] ?>"><?= e($c['category_name']) ?></a>
    <?php endforeach; ?>
</div>
<div class="grid g2">
    <?php foreach ($tips as $t): ?>
        <article class="card tip">
            <div class="date"><?= date('d.m.Y', strtotime($t['published_at'])) ?> · <?= e($t['category_name'] ?? 'Общее') ?></div>
            <h3 style="margin-top:6px"><?= e($t['tip_title']) ?></h3>
            <p><?= e($t['tip_text']) ?></p>
            <div class="muted" style="font-size:13px">Автор: <?= e($t['user_name']) ?></div>
        </article>
    <?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
