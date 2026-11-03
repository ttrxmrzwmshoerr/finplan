// ФинПлан — общие скрипты: маски ввода, проверка форм, калькуляторы

// ---------- Маска телефона: +7 (999) 999-99-99 ----------
function phoneMask(input) {
    input.addEventListener('input', () => {
        let d = input.value.replace(/\D/g, '');
        if (d.startsWith('8')) d = '7' + d.slice(1);
        if (!d.startsWith('7')) d = '7' + d;
        d = d.slice(0, 11);
        let s = '+7';
        if (d.length > 1) s += ' (' + d.slice(1, 4);
        if (d.length >= 4) s += ') ' + d.slice(4, 7);
        if (d.length >= 7) s += '-' + d.slice(7, 9);
        if (d.length >= 9) s += '-' + d.slice(9, 11);
        input.value = s;
    });
}

// ---------- Маска суммы: только цифры и одна запятая/точка, 2 знака ----------
function moneyMask(input) {
    input.addEventListener('input', () => {
        let v = input.value.replace(',', '.').replace(/[^\d.]/g, '');
        const parts = v.split('.');
        if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
        const [i, f] = v.split('.');
        input.value = f !== undefined ? i + '.' + f.slice(0, 2) : i;
    });
}

document.querySelectorAll('[data-mask="phone"]').forEach(phoneMask);
document.querySelectorAll('[data-mask="money"]').forEach(moneyMask);

// ---------- Проверка форм на стороне клиента ----------
const rules = {
    required: v => v.trim() !== '' || 'Заполните это поле',
    email: v => /^[^\s@]+@[^\s@]+\.[a-zа-я]{2,}$/i.test(v.trim()) || 'Введите адрес в формате name@example.ru',
    password: v => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/.test(v) ||
        'Не менее 8 символов: латинские буквы и цифры, хотя бы одна заглавная',
    phone: v => v === '' || /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/.test(v) || 'Введите телефон полностью',
    money: v => (parseFloat(v) > 0) || 'Введите сумму больше нуля',
    name: v => /^[A-Za-zА-Яа-яЁё\s-]{2,60}$/.test(v.trim()) || 'Только буквы, от 2 символов',
};

function showError(input, message) {
    const field = input.closest('.field');
    if (!field) return;
    let err = field.querySelector('.error');
    if (!err) { err = document.createElement('span'); err.className = 'error'; field.appendChild(err); }
    err.textContent = message || '';
    field.classList.toggle('invalid', !!message);
}

function validateInput(input) {
    const list = (input.dataset.rules || '').split(' ').filter(Boolean);
    if (input.type === 'checkbox') {
        const ok = !input.required || input.checked;
        showError(input, ok ? '' : 'Необходимо согласие');
        return ok;
    }
    for (const r of list) {
        if (r === 'match') {
            const other = document.getElementById(input.dataset.match);
            if (other && other.value !== input.value) { showError(input, 'Пароли не совпадают'); return false; }
            continue;
        }
        if (r !== 'required' && input.value.trim() === '') continue;
        const res = rules[r] ? rules[r](input.value) : true;
        if (res !== true) { showError(input, res); return false; }
    }
    showError(input, '');
    return true;
}

document.querySelectorAll('form[data-validate]').forEach(form => {
    const inputs = form.querySelectorAll('[data-rules], input[type="checkbox"][required]');
    inputs.forEach(i => i.addEventListener('blur', () => validateInput(i)));
    form.addEventListener('submit', ev => {
        let ok = true;
        inputs.forEach(i => { if (!validateInput(i)) ok = false; });
        if (!ok) ev.preventDefault();
    });
});

// ---------- Подтверждение удаления ----------
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', ev => { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
});

// ---------- Калькуляторы на главной ----------
const fmt = n => n.toLocaleString('ru-RU', { maximumFractionDigits: 0 }) + ' ₽';

const budgetForm = document.getElementById('tool-budget');
if (budgetForm) {
    budgetForm.addEventListener('submit', ev => {
        ev.preventDefault();
        const income = parseFloat(budgetForm.income.value);
        const out = document.getElementById('budget-result');
        if (!(income > 0)) { out.innerHTML = 'Введите доход больше нуля'; return; }
        out.innerHTML = `Обязательные расходы (50 %): <b>${fmt(income * .5)}</b><br>
            Желания (30 %): <b>${fmt(income * .3)}</b><br>Накопления и цели (20 %): <b>${fmt(income * .2)}</b>`;
    });
}

const goalForm = document.getElementById('tool-goal');
if (goalForm) {
    goalForm.addEventListener('submit', ev => {
        ev.preventDefault();
        const target = parseFloat(goalForm.target.value) || 0;
        const have = parseFloat(goalForm.have.value) || 0;
        const months = parseInt(goalForm.months.value, 10) || 0;
        const out = document.getElementById('goal-result');
        if (target <= 0 || months <= 0) { out.innerHTML = 'Укажите сумму цели и срок в месяцах'; return; }
        const left = Math.max(target - have, 0);
        out.innerHTML = `Осталось накопить: <b>${fmt(left)}</b><br>Откладывайте в месяц: <b>${fmt(Math.ceil(left / months))}</b>`;
    });
}
