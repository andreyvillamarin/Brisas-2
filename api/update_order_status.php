<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$orderModel = new Order();
$success = $orderModel->updateStatus($data['order_id'], $data['status']);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update order status']);
}
?>