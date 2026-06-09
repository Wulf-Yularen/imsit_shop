<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }
header('Content-Type: application/json');

$db   = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'update_status':
        $orderId = (int)($data['order_id'] ?? 0);
        $status  = $data['status'] ?? '';
        $allowed = ['pending','processing','shipped','delivered','cancelled'];
        if ($orderId && in_array($status, $allowed)) {
            $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $orderId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
