<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';

try {
    $productModel = new Product();
    
    if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
        $products = $productModel->getByCategory((int)$_GET['category_id']);
    } else {
        $products = $productModel->getAll();
    }
    
    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching products.']);
}
?>