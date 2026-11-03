<?php
// Шапка сайта: подключается в начале каждой страницы
require_once __DIR__ . '/functions.php';
$page = $page ?? '';
$u = current_user();
$menu = [
    'index'  => ['Главная', 'index.php'],
    'stats'  => ['Статистика', 'stats.php'],
    'goals'  => ['Цели', 'goals.php'],
    'tips'   => ['Советы', 'tips.php'],
    'rates'  => ['Курсы валют', 'rates.php'],
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ФинПлан') ?> — ФинПлан</title>
    <link rel="icon" href="<?= BASE ?>/assets/img/logo-mark.svg">
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="wrap header-row">
        <a class="logo" href="<?= BASE ?>/index.php">
            <img src="<?= BASE ?>/assets/img/logo-mark.svg" alt="Логотип ФинПлан" width="34" height="34">
            <span>Фин<span class="accent">План</span></span>
        </a>
        <button class="burger" type="button" aria-label="Открыть меню" onclick="document.body.classList.toggle('menu-open')">☰</button>
        <nav class="main-nav">
            <?php foreach ($menu as $key => [$name, $href]): ?>
                <a href="<?= BASE . '/' . $href ?>" class="<?= $page === $key ? 'active' : '' ?>"><?= $name ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <?php if ($u): ?>
                <?php if (is_admin()): ?><a class="btn btn-ghost" href="<?= BASE ?>/admin/index.php">Админ-панель</a><?php endif; ?>
                <a class="btn btn-ghost <?= $page === 'cabinet' ? 'is-active' : '' ?>" href="<?= BASE ?>/cabinet.php">Личный кабинет</a>
                <a class="btn btn-primary" href="<?= BASE ?>/logout.php">Выйти</a>
            <?php else: ?>
                <a class="btn btn-ghost <?= $page === 'login' ? 'is-active' : '' ?>" href="<?= BASE ?>/login.php">Войти</a>
                <a class="btn btn-primary" href="<?= BASE ?>/register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="wrap page">
<?php show_flash(); ?>
