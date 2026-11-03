<?php
// Личный кабинет: «Мои финансы» (счета, операции, бюджет) и «Мои цели»
$page = 'cabinet';
$title = 'Личный кабинет';
require_once 'includes/functions.php';
require_login();
$u = current_user();
$uid = $u['id_user'];
$tab = ($_GET['tab'] ?? 'finance') === 'goals' ? 'goals' : 'finance';
$errors = [];

// Счёт принадлежит текущему пользователю?
function own_account(PDO $pdo, int $id, int $uid): ?array
{
    $st = $pdo->prepare('SELECT * FROM accounts WHERE id_account = ? AND id_user = ?');
    $st->execute([$id, $uid]);
    return $st->fetch() ?: null;
}

$num = fn($v) => (float)str_replace([',', ' '], ['.', ''], (string)$v);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    // ---------- Добавление операции ----------
    if ($action === 'add_tx') {
        $acc = own_account($pdo, (int)$_POST['id_account'], $uid);
        $amount = $num($_POST['amount'] ?? 0);
        $date = $_POST['transaction_date'] ?? '';
        $st = $pdo->prepare('SELECT * FROM categories WHERE id_category = ?');
        $st->execute([(int)$_POST['id_category']]);
        $cat = $st->fetch();
        if (!$acc) $errors[] = 'Выберите счёт';
        if (!$cat) $errors[] = 'Выберите категорию';
        if ($amount <= 0) $errors[] = 'Сумма должна быть больше нуля';
        if (!$date || $date > date('Y-m-d')) $errors[] = 'Дата операции не может быть позже сегодняшней';
        if (!$errors) {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO transactions (id_account, id_category, amount, transaction_date, comment) VALUES (?, ?, ?, ?, ?)')
                ->execute([$acc['id_account'], $cat['id_category'], $amount, $date, trim($_POST['comment'] ?? '') ?: null]);
            $sign = $cat['category_type'] === 'income' ? 1 : -1;
            $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id_account = ?')->execute([$sign * $amount, $acc['id_account']]);
            $pdo->commit();
            // Предупреждение о превышении лимита бюджета
            if ($cat['category_type'] === 'expense') {
                $st = $pdo->prepare('SELECT * FROM v_budget_current_month WHERE id_user = ? AND category_name = ?');
                $st->execute([$uid, $cat['category_name']]);
                if (($b = $st->fetch()) && $b['spent'] > $b['limit_amount']) {
                    flash('Внимание: лимит по категории «' . $cat['category_name'] . '» превышен на ' . money($b['spent'] - $b['limit_amount']) . '.', 'warn');
                }
            }
            flash('Операция добавлена.');
            header('Location: cabinet.php');
            exit;
        }
    }

    // ---------- Удаление операции (с возвратом баланса) ----------
    if ($action === 'delete_tx') {
        $st = $pdo->prepare('SELECT t.*, c.category_type FROM transactions t
                             JOIN accounts a ON a.id_account = t.id_account
                             JOIN categories c ON c.id_category = t.id_category
                             WHERE t.id_transaction = ? AND a.id_user = ?');
        $st->execute([(int)$_POST['id_transaction'], $uid]);
        if ($tx = $st->fetch()) {
            $sign = $tx['category_type'] === 'income' ? -1 : 1;
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id_account = ?')->execute([$sign * $tx['amount'], $tx['id_account']]);
            $pdo->prepare('DELETE FROM transactions WHERE id_transaction = ?')->execute([$tx['id_transaction']]);
            $pdo->commit();
            flash('Операция удалена, баланс счёта пересчитан.');
        }
        header('Location: cabinet.php');
        exit;
    }

    // ---------- Добавление счёта ----------
    if ($action === 'add_account') {
        $name = trim($_POST['account_name'] ?? '');
        if ($name === '') $errors[] = 'Введите название счёта';
        if (!$errors) {
            $pdo->prepare('INSERT INTO accounts (id_user, id_account_type, id_currency, account_name, balance) VALUES (?, ?, ?, ?, ?)')
                ->execute([$uid, (int)$_POST['id_account_type'], (int)$_POST['id_currency'], $name, $num($_POST['balance'] ?? 0)]);
            flash('Счёт «' . $name . '» добавлен.');
            header('Location: cabinet.php');
            exit;
        }
    }

    // ---------- Удаление счёта ----------
    if ($action === 'delete_account') {
        $pdo->prepare('DELETE FROM accounts WHERE id_account = ? AND id_user = ?')->execute([(int)$_POST['id_account'], $uid]);
        flash('Счёт удалён вместе с его операциями.');
        header('Location: cabinet.php');
        exit;
    }

    // ---------- Установка лимита бюджета на текущий месяц ----------
    if ($action === 'set_budget') {
        $limit = $num($_POST['limit_amount'] ?? 0);
        if ($limit <= 0) {
            $errors[] = 'Лимит должен быть больше нуля';
        } else {
            $pdo->prepare('INSERT INTO budgets (id_user, id_category, budget_month, limit_amount) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)')
                ->execute([$uid, (int)$_POST['id_category'], month_start(), $limit]);
            flash('Лимит бюджета сохранён.');
            header('Location: cabinet.php#budget');
            exit;
        }
    }
}

// ---------- Данные для страницы ----------
$st = $pdo->prepare('SELECT a.*, t.type_name, c.currency_code, c.currency_symbol, c.rate_to_rub
                     FROM accounts a
                     JOIN account_types t ON t.id_account_type = a.id_account_type
                     JOIN currencies c ON c.id_currency = a.id_currency
                     WHERE a.id_user = ? ORDER BY a.id_account');
$st->execute([$uid]);
$accounts = $st->fetchAll();
$total = 0;
foreach ($accounts as $a) {
    $total += $a['balance'] * $a['rate_to_rub'];   // общий баланс в рублях
}

$st = $pdo->prepare('SELECT t.*, a.account_name, c.category_name, c.category_type, c.color
                     FROM transactions t
                     JOIN accounts a ON a.id_account = t.id_account
                     JOIN categories c ON c.id_category = t.id_category
                     WHERE a.id_user = ? ORDER BY t.transaction_date DESC, t.id_transaction DESC LIMIT 15');
$st->execute([$uid]);
$txs = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM v_budget_current_month WHERE id_user = ? ORDER BY category_name');   // запрос 2
$st->execute([$uid]);
$budget = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM v_savings_accounts WHERE id_user = ?');   // запрос 4
$st->execute([$uid]);
$savings = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM v_user_goals WHERE id_user = ? ORDER BY goal_status, deadline');   // запрос 3
$st->execute([$uid]);
$goals = $st->fetchAll();

$st = $pdo->prepare("SELECT SUM(CASE WHEN c.category_type='income' THEN t.amount END) inc,
                            SUM(CASE WHEN c.category_type='expense' THEN t.amount END) exp
                     FROM transactions t JOIN accounts a ON a.id_account = t.id_account
                     JOIN categories c ON c.id_category = t.id_category
                     WHERE a.id_user = ? AND t.transaction_date >= ?");
$st->execute([$uid, month_start()]);
$month = $st->fetch();

$categories = $pdo->query('SELECT * FROM categories ORDER BY category_type DESC, category_name')->fetchAll();
$types = $pdo->query('SELECT * FROM account_types ORDER BY id_account_type')->fetchAll();
$currencies = $pdo->query('SELECT * FROM currencies ORDER BY id_currency')->fetchAll();

require 'includes/header.php';
?>
<div class="section-title">
    <div><h1>Личный кабинет</h1><span class="muted"><?= e($u['user_name']) ?> · <?= e($u['email']) ?></span></div>
</div>
<div class="tabs">
    <a class="tab <?= $tab === 'finance' ? 'active' : '' ?>" href="cabinet.php">Мои финансы</a>
    <a class="tab <?= $tab === 'goals' ? 'active' : '' ?>" href="cabinet.php?tab=goals">Мои цели</a>
</div>
<?php foreach ($errors as $er): ?><div class="alert alert-err"><?= e($er) ?></div><?php endforeach; ?>

<?php if ($tab === 'finance'): ?>
    <div class="grid g4">
        <div class="card kpi"><span>Общий баланс</span><b><?= money($total) ?></b></div>
        <div class="card kpi"><span>Доходы за месяц</span><b class="pos"><?= money($month['inc'] ?? 0) ?></b></div>
        <div class="card kpi"><span>Расходы за месяц</span><b><?= money($month['exp'] ?? 0) ?></b></div>
        <div class="card kpi"><span>Счетов</span><b><?= count($accounts) ?></b></div>
    </div>

    <div class="grid g-cab mt">
        <div class="card">
            <h3>Новая операция</h3>
            <form method="post" data-validate novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="add_tx">
                <div class="form-row">
                    <div class="field">Счёт *
                        <select name="id_account" data-rules="required">
                            <?php foreach ($accounts as $a): ?><option value="<?= $a['id_account'] ?>"><?= e($a['account_name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="field">Категория *
                        <select name="id_category" data-rules="required">
                            <optgroup label="Расходы"><?php foreach ($categories as $c) if ($c['category_type'] === 'expense'): ?><option value="<?= $c['id_category'] ?>"><?= e($c['category_name']) ?></option><?php endif; ?></optgroup>
                            <optgroup label="Доходы"><?php foreach ($categories as $c) if ($c['category_type'] === 'income'): ?><option value="<?= $c['id_category'] ?>"><?= e($c['category_name']) ?></option><?php endif; ?></optgroup>
                        </select></div>
                    <div class="field">Сумма, ₽ *<input name="amount" data-mask="money" data-rules="required money" inputmode="decimal" placeholder="0.00"></div>
                    <div class="field">Дата *<input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" data-rules="required"></div>
                </div>
                <div class="field">Комментарий<input name="comment" maxlength="255" placeholder="Например, Пятёрочка"></div>
                <button class="btn btn-primary" type="submit">Добавить операцию</button>
            </form>
        </div>
        <div class="card">
            <div class="section-title" style="margin-bottom:8px"><h3 style="margin:0">Мои счета</h3></div>
            <?php foreach ($accounts as $a): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)">
                    <div style="flex:1"><b style="color:var(--indigo)"><?= e($a['account_name']) ?></b><div class="muted" style="font-size:13px"><?= e($a['type_name']) ?></div></div>
                    <b><?= money($a['balance'], $a['currency_symbol']) ?></b>
                    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete_account"><input type="hidden" name="id_account" value="<?= $a['id_account'] ?>">
                        <button class="btn btn-danger btn-sm" data-confirm="Удалить счёт и все его операции?" title="Удалить">✕</button></form>
                </div>
            <?php endforeach; ?>
            <form method="post" data-validate novalidate style="margin-top:14px">
                <?= csrf_field() ?><input type="hidden" name="action" value="add_account">
                <div class="form-row">
                    <div class="field">Название *<input name="account_name" data-rules="required" placeholder="Карта, вклад…"></div>
                    <div class="field">Тип<select name="id_account_type"><?php foreach ($types as $t): ?><option value="<?= $t['id_account_type'] ?>"><?= e($t['type_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="field">Валюта<select name="id_currency"><?php foreach ($currencies as $c): ?><option value="<?= $c['id_currency'] ?>"><?= e($c['currency_code']) ?></option><?php endforeach; ?></select></div>
                    <div class="field">Остаток<input name="balance" data-mask="money" placeholder="0"></div>
                </div>
                <button class="btn btn-ghost" type="submit">Добавить счёт</button>
            </form>
        </div>
    </div>

    <div class="card mt">
        <h3>Последние операции</h3>
        <div class="table-wrap">
            <table class="t">
                <tr><th>Дата</th><th>Категория</th><th>Комментарий</th><th>Счёт</th><th class="num">Сумма</th><th></th></tr>
                <?php foreach ($txs as $t): ?>
                    <tr>
                        <td class="muted"><?= date('d.m.Y', strtotime($t['transaction_date'])) ?></td>
                        <td><span class="dot" style="background:<?= e($t['color']) ?>"></span><?= e($t['category_name']) ?></td>
                        <td><?= e($t['comment']) ?></td>
                        <td class="muted"><?= e($t['account_name']) ?></td>
                        <td class="num <?= $t['category_type'] === 'income' ? 'pos' : 'neg' ?>"><?= ($t['category_type'] === 'income' ? '+' : '−') . money($t['amount']) ?></td>
                        <td class="num"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete_tx"><input type="hidden" name="id_transaction" value="<?= $t['id_transaction'] ?>">
                            <button class="btn btn-danger btn-sm" data-confirm="Удалить операцию?">Удалить</button></form></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$txs): ?><tr><td colspan="6" class="muted">Операций пока нет.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>

    <div class="grid g-cab mt" id="budget">
        <div class="card">
            <h3>Бюджет на <?= month_name(month_start()) ?></h3>
            <?php foreach ($budget as $b):
                $p = $b['limit_amount'] > 0 ? round($b['spent'] / $b['limit_amount'] * 100) : 0;
                $col = $p > 100 ? 'var(--coral)' : ($p >= 80 ? 'var(--sun)' : 'var(--mint)'); ?>
                <div style="margin-top:12px">
                    <div style="display:flex;justify-content:space-between;font-size:15px"><span><?= e($b['category_name']) ?></span>
                        <span><?= money($b['spent']) ?> из <?= money($b['limit_amount']) ?> · <b style="color:<?= $col ?>"><?= $p ?> %</b></span></div>
                    <div class="bar" style="margin-top:6px"><div style="width:<?= min($p, 100) ?>%;background:<?= $col ?>"></div></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$budget): ?><p class="muted">Лимиты на этот месяц не заданы.</p><?php endif; ?>
            <form method="post" data-validate novalidate class="form-row" style="margin-top:18px">
                <?= csrf_field() ?><input type="hidden" name="action" value="set_budget">
                <div class="field">Категория<select name="id_category"><?php foreach ($categories as $c) if ($c['category_type'] === 'expense'): ?><option value="<?= $c['id_category'] ?>"><?= e($c['category_name']) ?></option><?php endif; ?></select></div>
                <div class="field">Лимит, ₽<input name="limit_amount" data-mask="money" data-rules="required money" placeholder="5000"></div>
                <div class="field"><button class="btn btn-dark" type="submit">Сохранить лимит</button></div>
            </form>
        </div>
        <div class="card">
            <h3>Сберегательные счета</h3>
            <?php foreach ($savings as $s): ?>
                <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line)">
                    <span><b style="color:var(--indigo)"><?= e($s['account_name']) ?></b><br><span class="muted" style="font-size:13px"><?= e($s['type_name']) ?></span></span>
                    <b><?= number_format($s['balance'], 2, ',', ' ') . ' ' . e($s['currency_code']) ?></b>
                </div>
            <?php endforeach; ?>
            <?php if (!$savings): ?><p class="muted">Сберегательных счетов нет. Добавьте счёт с типом «Сберегательный счёт» или «Вклад».</p><?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <div class="card">
        <div class="section-title"><h3 style="margin:0">Мои цели</h3><a class="btn btn-primary btn-sm" href="goals.php">Управлять целями</a></div>
        <div class="table-wrap">
            <table class="t">
                <tr><th>Цель</th><th style="width:30%">Прогресс</th><th class="num">Накоплено</th><th class="num">Цель</th><th class="num">Срок</th><th>Статус</th></tr>
                <?php foreach ($goals as $g): ?>
                    <tr>
                        <td><b style="color:var(--indigo)"><?= e($g['goal_name']) ?></b></td>
                        <td><div class="bar"><div style="width:<?= min(100, $g['progress_percent']) ?>%"></div></div><span class="muted" style="font-size:13px"><?= $g['progress_percent'] ?> %</span></td>
                        <td class="num"><?= money($g['current_amount']) ?></td>
                        <td class="num"><?= money($g['target_amount']) ?></td>
                        <td class="num"><?= date('d.m.Y', strtotime($g['deadline'])) ?></td>
                        <td><span class="chip"><?= $g['goal_status'] === 'done' ? 'Достигнута' : 'В процессе' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$goals): ?><tr><td colspan="6" class="muted">Целей пока нет. <a class="accent" href="goals.php">Добавить цель</a></td></tr><?php endif; ?>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
