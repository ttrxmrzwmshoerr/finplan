<?php
// Сохранение курсов, полученных из API, в таблицу currencies (только для администратора)
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (!is_admin() || ($in['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Доступ запрещён']);
    exit;
}

$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $in['date'] ?? '') ? $in['date'] : date('Y-m-d');
$st = $pdo->prepare('UPDATE currencies SET rate_to_rub = ?, rate_date = ? WHERE currency_code = ?');
$count = 0;
foreach (($in['rates'] ?? []) as $code => $rate) {
    if (preg_match('/^[A-Z]{3}$/', $code) && (float)$rate > 0) {
        $st->execute([round((float)$rate, 4), $date, $code]);
        $count += $st->rowCount();
    }
}
$pdo->prepare('UPDATE currencies SET rate_date = ? WHERE currency_code = "RUB"')->execute([$date]);
echo json_encode(['ok' => true, 'message' => "Обновлено курсов: $count (данные на " . date('d.m.Y', strtotime($date)) . ')']);
