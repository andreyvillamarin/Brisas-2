<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'despacho') {
    echo json_encode(['error' => 'Acceso denegado.']);
    exit;
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$orderModel = new Order();

$dateFilter = $_GET['date'] ?? date('Y-m-d');
$searchTerm = $_GET['search'] ?? '';

$filters = [
    'status' => 'completed',
    'date' => $dateFilter,
    'searchTerm' => $searchTerm
];

$orders = $orderModel->getOrdersBy($filters);

echo json_encode($orders);
?>