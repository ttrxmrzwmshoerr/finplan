<?php
// Подключение к базе данных «finplan» через PDO
$host    = 'localhost';   // сервер MySQL из XAMPP
$db      = 'finplan';     // имя базы данных
$user    = 'root';        // пользователь MySQL по умолчанию в XAMPP
$pass    = '';            // пароль (в XAMPP по умолчанию пустой)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ошибки – исключениями
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // строки – ассоциативными массивами
    PDO::ATTR_EMULATE_PREPARES   => false,                  // настоящие подготовленные запросы
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
