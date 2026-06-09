<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        $pid = (int)($data['product_id'] ?? 0);
        $qty = max(1, (int)($data['qty'] ?? 1));
        if ($pid > 0) {
            addToCart($pid, $qty);
            echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный товар']);
        }
        break;

    case 'update':
        $cid = (int)($data['cart_id'] ?? 0);
        $qty = (int)($data['qty'] ?? 0);
        updateCartItem($cid, $qty);
        echo json_encode([
            'success'    => true,
            'total'      => formatPrice(getCartTotal()),
            'cart_count' => getCartCount()
        ]);
        break;

    case 'remove':
        $cid = (int)($data['cart_id'] ?? 0);
        removeFromCart($cid);
        echo json_encode([
            'success'    => true,
            'total'      => formatPrice(getCartTotal()),
            'cart_count' => getCartCount()
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
