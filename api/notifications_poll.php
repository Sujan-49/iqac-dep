<?php
require_once __DIR__ . '/../include/auth.php';

$user = iqac_require_login();
header('Content-Type: application/json');

$count = 0;
$items = [];
if (iqac_table_exists($conn, 'notifications')) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0 AND (user_id = ? OR role = ? OR role IS NULL)');
    $stmt->bind_param('is', $user['id'], $user['role']);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare('SELECT title, message, link_url, created_at FROM notifications WHERE (user_id = ? OR role = ? OR role IS NULL) ORDER BY created_at DESC LIMIT 5');
    $stmt->bind_param('is', $user['id'], $user['role']);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

echo json_encode([
    'ok' => true,
    'unread_count' => $count,
    'items' => $items,
]);
