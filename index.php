<?php
// Главная страница: описание сервиса и финансовые инструменты
$page = 'index';
$title = 'Главная';
$scripts = ['rates.js'];
require 'includes/header.php';
?>
<section class="hero">
    <div>
        <div class="eyebrow">Сервис для планирования финансов</div>
        <h1>Спокойные финансы <span class="accent">по плану</span></h1>
        <p>Все счета, бюджет и цели в одном месте. «ФинПлан» посчитает остатки, предупредит о превышении лимита
           и подскажет, сколько откладывать каждый месяц.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px">
            <?php if (current_user()): ?>
                <a class="btn btn-primary" href="cabinet.php">Перейти в личный кабинет</a>
            <?php else: ?>
                <a class="btn btn-primary" href="register.php">Начать бесплатно</a>
                <a class="btn btn-ghost" href="login.php">У меня есть аккаунт</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-img" role="img" aria-label="Пара планирует бюджет на кухне"></div>
</section>

<section class="mt">
    <div class="section-title"><h2>Инструменты</h2><span class="muted">Работают без регистрации</span></div>
    <div class="grid g3">
        <div class="card">
            <h3>Бюджет 50/30/20</h3>
            <p class="muted">Распределите доход на обязательные расходы, желания и накопления.</p>
            <form id="tool-budget">
                <div class="field">Доход в месяц, ₽<input name="income" data-mask="money" inputmode="decimal" placeholder="Например, 85000"></div>
                <button class="btn btn-dark btn-block" type="submit">Рассчитать</button>
            </form>
            <div class="tool-result" id="budget-result">Введите доход, чтобы увидеть распределение</div>
        </div>
        <div class="card">
            <h3>Калькулятор цели</h3>
            <p class="muted">Узнайте, сколько откладывать каждый месяц, чтобы успеть к сроку.</p>
            <form id="tool-goal">
                <div class="field">Сумма цели, ₽<input name="target" data-mask="money" inputmode="decimal" placeholder="100000"></div>
                <div class="form-row">
                    <div class="field">Уже есть, ₽<input name="have" data-mask="money" inputmode="decimal" placeholder="0"></div>
                    <div class="field">Срок, мес.<input name="months" type="number" min="1" max="360" placeholder="10"></div>
                </div>
                <button class="btn btn-dark btn-block" type="submit">Рассчитать</button>
            </form>
            <div class="tool-result" id="goal-result">Результат появится здесь</div>
        </div>
        <div class="card">
            <h3>Конвертер валют</h3>
            <p class="muted">Курсы загружаются из открытого API в реальном времени.</p>
            <div class="field">Сумма<input id="conv-amount" data-mask="money" inputmode="decimal" value="1000"></div>
            <div class="form-row">
                <div class="field">Из<select id="conv-from"><option>RUB</option></select></div>
                <div class="field">В<select id="conv-to"><option>USD</option></select></div>
            </div>
            <div class="tool-result" id="conv-result">Загрузка курсов…</div>
            <p class="muted" id="api-status" style="font-size:13px;margin-top:8px"></p>
        </div>
    </div>
</section>

<section class="mt">
    <div class="grid g4">
        <a class="card" href="stats.php"><h3>Статистика</h3><p class="muted">Диаграммы расходов по категориям и динамика по месяцам.</p></a>
        <a class="card" href="goals.php"><h3>Цели</h3><p class="muted">Отпуск, ноутбук, подушка безопасности — прогресс и сроки.</p></a>
        <a class="card" href="tips.php"><h3>Советы</h3><p class="muted">Короткие советы по финансовой грамотности.</p></a>
        <a class="card" href="rates.php"><h3>Курсы валют</h3><p class="muted">Актуальные курсы к рублю и история в базе.</p></a>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
