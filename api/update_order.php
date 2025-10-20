<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id']) || !isset($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$orderModel = new Order();
$successItems = $orderModel->updateOrderItems($data['order_id'], $data['items']);

$successCode = true;
if (isset($data['code'])) {
    $successCode = $orderModel->updateOrderDetails($data['order_id'], ['code' => $data['code']]);
}


if ($successItems && $successCode) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update order']);
}
?>