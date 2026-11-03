<?php
// Общие функции веб-приложения
session_start();
require_once __DIR__ . '/../config/db.php';

// Базовый адрес сайта (папка в htdocs)
define('BASE', '/finplan');

// Экранирование вывода (защита от XSS)
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Форматирование суммы: 12 345,00 ₽
function money($value, string $symbol = '₽'): string
{
    return number_format((float)$value, 2, ',', ' ') . ' ' . $symbol;
}

// Текущий пользователь из сессии или null
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['id_role'] ?? 0) == 1;
}

// Доступ только для авторизованных пользователей
function require_login(): void
{
    if (!current_user()) {
        flash('Войдите в аккаунт, чтобы открыть эту страницу.', 'warn');
        header('Location: ' . BASE . '/login.php');
        exit;
    }
}

// Доступ только для администратора
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Доступ запрещён: требуется роль «Администратор».');
    }
}

// Одноразовые сообщения (flash)
function flash(string $text, string $type = 'ok'): void
{
    $_SESSION['flash'][] = ['text' => $text, 'type' => $type];
}

function show_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['text']) . '</div>';
    }
    unset($_SESSION['flash']);
}

// CSRF-токен для форм
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

function check_csrf(): void
{
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(400);
        exit('Неверный токен формы. Обновите страницу и повторите.');
    }
}

// Первое число текущего месяца в формате Y-m-d
function month_start(): string
{
    return date('Y-m-01');
}

// Название месяца по-русски
function month_name(string $date): string
{
    $m = ['январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];
    return $m[(int)date('n', strtotime($date)) - 1] . ' ' . date('Y', strtotime($date));
}
