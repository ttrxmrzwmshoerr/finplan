<?php
// Курсы валют: данные из API (JavaScript) и сохранённые курсы из базы (представление)
$page = 'rates';
$title = 'Курсы валют';
$scripts = ['rates.js'];
require_once 'includes/functions.php';
// Запрос 1: список всех валют с курсом
$dbRates = $pdo->query('SELECT * FROM v_currency_rates')->fetchAll();
require 'includes/header.php';
?>
<div class="section-title"><h1>Курсы валют</h1><span class="muted" id="api-status">Загрузка данных из API…</span></div>
<div class="grid g-rates">
    <div class="card">
        <h3>Курсы к рублю (API)</h3>
        <div class="table-wrap">
            <table class="t">
                <thead><tr><th>Код</th><th>Валюта</th><th>Знак</th><th class="num">1 ед. в рублях</th><th class="num">За 100 ₽</th></tr></thead>
                <tbody id="api-rates"><tr><td colspan="5" class="muted">Загрузка…</td></tr></tbody>
            </table>
        </div>
        <p class="muted" style="font-size:13px;margin-top:10px">Источник: открытый Currency API (fawazahmed0/exchange-api), обновляется ежедневно, без ключа доступа.</p>
    </div>
    <div>
        <div class="card">
            <h3>Конвертер</h3>
            <div class="field">Сумма<input id="conv-amount" data-mask="money" inputmode="decimal" value="1000"></div>
            <div class="form-row">
                <div class="field">Из<select id="conv-from"></select></div>
                <div class="field">В<select id="conv-to"></select></div>
            </div>
            <div class="tool-result" id="conv-result">Загрузка…</div>
        </div>
        <div class="card mt">
            <h3>Курсы в базе данных</h3>
            <table class="t">
                <tr><th>Код</th><th>Валюта</th><th class="num">Курс</th><th class="num">Дата</th></tr>
                <?php foreach ($dbRates as $r): ?>
                    <tr><td><b><?= e($r['code']) ?></b></td><td><?= e($r['currency']) ?></td>
                        <td class="num"><?= number_format($r['rate_to_rub'], 4, ',', ' ') ?> ₽</td>
                        <td class="num"><?= date('d.m.Y', strtotime($r['rate_date'])) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php if (is_admin()): ?>
                <button class="btn btn-dark btn-block" style="margin-top:14px" id="save-rates"
                        data-url="<?= BASE ?>/api/save_rates.php" data-csrf="<?= e($_SESSION['csrf'] ?? (($_SESSION['csrf'] = bin2hex(random_bytes(16))))) ?>">Обновить курсы в базе из API</button>
                <p class="muted" id="save-status" style="font-size:13px;margin-top:8px"></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
