<?php
// Статистика: расходы по категориям, бюджет месяца, динамика доходов и расходов
$page = 'stats';
$title = 'Статистика';
require_once 'includes/functions.php';
$u = current_user();

if ($u) {
    $uid = $u['id_user'];
    // Запрос 5: расходы по категориям за текущий месяц (представление)
    $st = $pdo->prepare('SELECT v.category_name, v.total_spent, v.operations, c.color
                         FROM v_expenses_by_category_month v
                         JOIN categories c ON c.category_name = v.category_name
                         WHERE v.id_user = ? ORDER BY v.total_spent DESC');
    $st->execute([$uid]);
    $byCat = $st->fetchAll();
    $totalExp = array_sum(array_column($byCat, 'total_spent'));

    // Запрос 2: бюджет на текущий месяц (представление)
    $st = $pdo->prepare('SELECT * FROM v_budget_current_month WHERE id_user = ? ORDER BY category_name');
    $st->execute([$uid]);
    $budget = $st->fetchAll();

    // Доходы и расходы за последние 6 месяцев
    $st = $pdo->prepare("SELECT DATE_FORMAT(t.transaction_date, '%Y-%m-01') AS m,
                                SUM(CASE WHEN c.category_type = 'income' THEN t.amount ELSE 0 END) AS income,
                                SUM(CASE WHEN c.category_type = 'expense' THEN t.amount ELSE 0 END) AS expense
                         FROM transactions t
                         JOIN accounts a ON a.id_account = t.id_account
                         JOIN categories c ON c.id_category = t.id_category
                         WHERE a.id_user = ? AND t.transaction_date >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
                         GROUP BY m ORDER BY m");
    $st->execute([$uid]);
    $months = [];
    foreach ($st->fetchAll() as $r) {
        $months[$r['m']] = $r;
    }
    $max = 1;
    foreach ($months as $r) {
        $max = max($max, $r['income'], $r['expense']);
    }
    $income = $months[month_start()]['income'] ?? 0;

    // Сегменты кольцевой диаграммы (conic-gradient)
    $grad = [];
    $acc = 0;
    foreach ($byCat as $c) {
        $p = $totalExp ? $c['total_spent'] / $totalExp * 100 : 0;
        $grad[] = $c['color'] . ' ' . round($acc, 2) . '% ' . round($acc + $p, 2) . '%';
        $acc += $p;
    }
}
require 'includes/header.php';
?>
<div class="section-title">
    <h1>Статистика</h1>
    <span class="muted"><?= $u ? 'Период: ' . month_name(month_start()) : '' ?></span>
</div>

<?php if (!$u): ?>
    <div class="card">
        <h3>Статистика доступна после входа</h3>
        <p class="muted">Войдите или зарегистрируйтесь, чтобы видеть диаграммы своих расходов, исполнение бюджета и динамику по месяцам.</p>
        <a class="btn btn-primary" href="login.php">Войти</a> <a class="btn btn-ghost" href="register.php">Регистрация</a>
    </div>
<?php else: ?>
    <div class="grid g3">
        <div class="card kpi"><span>Доходы за месяц</span><b class="pos"><?= money($income) ?></b></div>
        <div class="card kpi"><span>Расходы за месяц</span><b><?= money($totalExp) ?></b></div>
        <div class="card kpi"><span>Разница</span><b class="<?= $income - $totalExp >= 0 ? 'pos' : 'accent' ?>"><?= money($income - $totalExp) ?></b></div>
    </div>

    <div class="grid g2 mt">
        <div class="card">
            <h3>Расходы по категориям</h3>
            <?php if (!$byCat): ?>
                <p class="muted">В этом месяце расходов пока нет.</p>
            <?php else: ?>
                <div style="display:flex;gap:28px;align-items:center;flex-wrap:wrap;margin-top:12px">
                    <div class="donut" style="background:conic-gradient(<?= implode(',', $grad) ?>)">
                        <div class="donut-center"><b><?= number_format($totalExp, 0, ',', ' ') ?> ₽</b><span class="muted">всего</span></div>
                    </div>
                    <div class="legend" style="flex:1;min-width:200px">
                        <?php foreach ($byCat as $c): ?>
                            <div><span><span class="dot" style="background:<?= e($c['color']) ?>"></span><?= e($c['category_name']) ?></span>
                                <b><?= money($c['total_spent']) ?></b></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3>Доходы и расходы по месяцам</h3>
            <div style="display:flex;gap:18px;font-size:13px"><span><span class="dot" style="background:var(--mint)"></span>Доходы</span>
                <span><span class="dot" style="background:var(--indigo)"></span>Расходы</span></div>
            <div class="bars">
                <?php for ($i = 5; $i >= 0; $i--):
                    $m = date('Y-m-01', strtotime("first day of -$i month"));
                    $r = $months[$m] ?? ['income' => 0, 'expense' => 0]; ?>
                    <div class="col">
                        <div class="pair">
                            <div style="height:<?= round($r['income'] / $max * 100) ?>%;background:var(--mint)" title="<?= money($r['income']) ?>"></div>
                            <div style="height:<?= round($r['expense'] / $max * 100) ?>%;background:var(--indigo)" title="<?= money($r['expense']) ?>"></div>
                        </div>
                        <span class="muted" style="font-size:13px"><?= mb_substr(month_name($m), 0, 3) ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="card mt">
        <div class="section-title"><h3>Бюджет на <?= month_name(month_start()) ?></h3><a class="btn btn-ghost btn-sm" href="cabinet.php#budget">Изменить лимиты</a></div>
        <div class="table-wrap">
            <table class="t">
                <tr><th>Категория</th><th style="width:40%">Исполнение</th><th class="num">Потрачено</th><th class="num">Лимит</th><th class="num">Остаток</th></tr>
                <?php foreach ($budget as $b):
                    $p = $b['limit_amount'] > 0 ? round($b['spent'] / $b['limit_amount'] * 100) : 0;
                    $col = $p > 100 ? 'var(--coral)' : ($p >= 80 ? 'var(--sun)' : 'var(--mint)'); ?>
                    <tr>
                        <td><?= e($b['category_name']) ?></td>
                        <td><div class="bar"><div style="width:<?= min($p, 100) ?>%;background:<?= $col ?>"></div></div></td>
                        <td class="num"><?= money($b['spent']) ?></td>
                        <td class="num"><?= money($b['limit_amount']) ?></td>
                        <td class="num" style="color:<?= $b['remaining'] < 0 ? 'var(--coral)' : 'inherit' ?>"><?= money($b['remaining']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$budget): ?><tr><td colspan="5" class="muted">Лимиты на этот месяц не заданы.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
