<?php
// Общие функции административной панели
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Русские названия таблиц и представлений
const TABLE_TITLES = [
    'roles' => 'Роли', 'users' => 'Пользователи', 'currencies' => 'Валюты', 'account_types' => 'Типы счетов',
    'accounts' => 'Счета', 'categories' => 'Категории', 'transactions' => 'Операции', 'budgets' => 'Бюджеты',
    'goals' => 'Цели', 'tips' => 'Советы',
    'v_currency_rates' => '1. Валюты с курсом', 'v_budget_current_month' => '2. Бюджет на текущий месяц',
    'v_user_goals' => '3. Финансовые цели', 'v_savings_accounts' => '4. Сберегательные счета',
    'v_expenses_by_category_month' => '5. Расходы по категориям за месяц',
];

// Список таблиц или представлений базы (из INFORMATION_SCHEMA)
function db_objects(PDO $pdo, string $type): array
{
    $st = $pdo->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = ? ORDER BY TABLE_NAME');
    $st->execute([$type]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

// Описание столбцов таблицы
function table_columns(PDO $pdo, string $table): array
{
    $st = $pdo->prepare('SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA, COLUMN_DEFAULT
                         FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION');
    $st->execute([$table]);
    return $st->fetchAll();
}

// Внешние ключи таблицы: столбец => [таблица, столбец]
function table_fks(PDO $pdo, string $table): array
{
    $st = $pdo->prepare('SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL');
    $st->execute([$table]);
    $fks = [];
    foreach ($st->fetchAll() as $r) {
        $fks[$r['COLUMN_NAME']] = [$r['REFERENCED_TABLE_NAME'], $r['REFERENCED_COLUMN_NAME']];
    }
    return $fks;
}

// Варианты для выпадающего списка внешнего ключа: id => «понятное имя»
function fk_options(PDO $pdo, string $table, string $column): array
{
    $label = null;
    foreach (table_columns($pdo, $table) as $c) {
        if (in_array($c['DATA_TYPE'], ['varchar', 'char']) && $c['COLUMN_NAME'] !== 'password_hash') {
            $label = $c['COLUMN_NAME'];
            break;
        }
    }
    $label = $label ?: $column;
    return $pdo->query("SELECT `$column` AS id, `$label` AS name FROM `$table` ORDER BY `$column`")->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Первичный ключ таблицы
function primary_key(array $cols): string
{
    foreach ($cols as $c) {
        if ($c['COLUMN_KEY'] === 'PRI') return $c['COLUMN_NAME'];
    }
    return $cols[0]['COLUMN_NAME'];
}

// Боковое меню админ-панели
function admin_menu(PDO $pdo, string $active = ''): void
{
    echo '<aside class="admin-side"><a href="index.php" class="' . ($active === '' ? 'active' : '') . '">Обзор базы данных</a><h4>Таблицы</h4>';
    foreach (db_objects($pdo, 'BASE TABLE') as $t) {
        echo '<a class="' . ($active === $t ? 'active' : '') . '" href="table.php?t=' . e($t) . '">' . e(TABLE_TITLES[$t] ?? $t) . '</a>';
    }
    echo '<h4>Представления</h4>';
    foreach (db_objects($pdo, 'VIEW') as $v) {
        echo '<a class="' . ($active === $v ? 'active' : '') . '" href="view.php?v=' . e($v) . '">' . e(TABLE_TITLES[$v] ?? $v) . '</a>';
    }
    echo '</aside>';
}
