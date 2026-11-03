<?php
// Выход из аккаунта
require_once 'includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('Вы вышли из аккаунта.');
header('Location: index.php');
exit;
