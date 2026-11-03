// ФинПлан — подключение API курсов валют (Currency API, fawazahmed0)
// Документация: https://github.com/fawazahmed0/exchange-api
const API_URLS = [
    'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/rub.min.json',
    'https://latest.currency-api.pages.dev/v1/currencies/rub.min.json'   // резервный адрес
];
const SHOW = {
    usd: ['Доллар США', '$'], eur: ['Евро', '€'], cny: ['Китайский юань', '¥'], gbp: ['Фунт стерлингов', '£'],
    kzt: ['Казахстанский тенге', '₸'], try: ['Турецкая лира', '₺'], byn: ['Белорусский рубль', 'Br'],
    aed: ['Дирхам ОАЭ', 'د.إ'], jpy: ['Японская иена', '¥'], chf: ['Швейцарский франк', '₣']
};

// Загрузка курсов: пробуем основной адрес, при ошибке — резервный
async function loadRates() {
    for (const url of API_URLS) {
        try {
            const res = await fetch(url);
            if (res.ok) return await res.json();
        } catch (e) { /* пробуем следующий адрес */ }
    }
    throw new Error('API недоступно');
}

const num = (v, d = 4) => v.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: d });

async function initRates() {
    const table = document.getElementById('api-rates');
    const status = document.getElementById('api-status');
    const from = document.getElementById('conv-from');
    const to = document.getElementById('conv-to');
    const amount = document.getElementById('conv-amount');
    const result = document.getElementById('conv-result');
    let data;
    try {
        data = await loadRates();
    } catch (e) {
        if (status) status.textContent = 'Не удалось получить курсы из API. Показаны курсы из базы данных.';
        return;
    }
    // API возвращает, сколько единиц валюты стоит 1 рубль: курс рубля к валюте = 1 / значение
    const rub = data.rub;
    const rows = Object.keys(SHOW).filter(c => rub[c]).map(c => ({
        code: c.toUpperCase(), name: SHOW[c][0], symbol: SHOW[c][1], rate: 1 / rub[c]
    }));
    if (status) status.textContent = 'Данные API от ' + new Date(data.date).toLocaleDateString('ru-RU') + ' · 1 единица валюты в рублях';
    if (table) {
        table.innerHTML = rows.map(r => `<tr><td><b>${r.code}</b></td><td>${r.name}</td><td>${r.symbol}</td>
            <td class="num"><b>${num(r.rate)} ₽</b></td><td class="num">${num(100 / r.rate, 2)} ${r.symbol}</td></tr>`).join('');
    }
    // Конвертер валют
    if (from && to) {
        const opts = ['<option value="RUB">RUB — Российский рубль</option>']
            .concat(rows.map(r => `<option value="${r.code}">${r.code} — ${r.name}</option>`)).join('');
        from.innerHTML = opts; to.innerHTML = opts; to.value = 'USD';
        const rateOf = code => code === 'RUB' ? 1 : rows.find(r => r.code === code).rate;
        const convert = () => {
            const a = parseFloat((amount.value || '0').replace(',', '.'));
            const val = a * rateOf(from.value) / rateOf(to.value);
            result.innerHTML = a > 0 ? `${num(a, 2)} ${from.value} = <b>${num(val, 2)} ${to.value}</b>` : 'Введите сумму';
        };
        [from, to, amount].forEach(el => el.addEventListener('input', convert));
        convert();
    }
    // Сохранение курсов в базу (кнопка видна только администратору)
    const saveBtn = document.getElementById('save-rates');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const payload = { date: data.date, rates: Object.fromEntries(rows.map(r => [r.code, r.rate])), csrf: saveBtn.dataset.csrf };
            const res = await fetch(saveBtn.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const j = await res.json();
            document.getElementById('save-status').textContent = j.message;
            if (j.ok) setTimeout(() => location.reload(), 1200);
        });
    }
}
initRates();
