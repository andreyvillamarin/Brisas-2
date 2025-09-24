<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Establishment.php';

try {
    $establishmentModel = new Establishment();
    $establishments = $establishmentModel->getAll();
    echo json_encode($establishments);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred.']);
    error_log('API Error in get_establishments.php: ' . $e->getMessage());
}
?>
