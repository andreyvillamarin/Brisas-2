<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$orderModel = new Order();
$count = $orderModel->getNewOrderCountForDespacho();

echo json_encode(['count' => $count]);
?>
