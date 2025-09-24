<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once __DIR__ . '/../app/helpers/email_helper.php';

// Read the raw payload once to avoid consuming the stream twice.
$raw_payload = file_get_contents('php://input');

$data = json_decode($raw_payload, true);

if (!$data || !isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'No hay productos en el pedido.']);
    exit;
}

// Verificación de reCAPTCHA
$settingModel = new Setting();
$settings = $settingModel->getAllAsAssoc();
if (!empty($settings['google_recaptcha_secret'])) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $verifyData = [
        'secret'   => $settings['google_recaptcha_secret'],
        'response' => $data['recaptcha_token'] ?? ''
    ];
    $options = ['http' => ['method' => 'POST', 'content' => http_build_query($verifyData)]];
    $context = stream_context_create($options);
    $verifyResult = json_decode(file_get_contents($url, false, $context), true);

    if (!$verifyResult['success'] || $verifyResult['score'] < 0.5) {
        echo json_encode(['success' => false, 'message' => 'Verificación de seguridad fallida.']);
        exit;
    }
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // Insertar en la tabla 'orders'
    $sql = "INSERT INTO orders (customer_type, customer_name, customer_id_number, customer_city, customer_email, mercaderista_supermarket) 
            VALUES (:customer_type, :customer_name, :customer_id_number, :customer_city, :customer_email, :mercaderista_supermarket)";
    
    $stmt = $db->prepare($sql);

    // Ajustar los datos según el tipo de cliente
    $customerName = $data['customer_name'] ?? $data['mercaderista_name'];
    $customerIdNumber = $data['customer_id_number'] ?? 'N/A'; // Mercaderista no tiene ID
    
    // Shim para compatibilidad con el tipo de cliente antiguo en la BD
    $customerTypeForDb = trim($data['customer_type'] ?? '');
    
    $stmt->execute([
        ':customer_type' => $customerTypeForDb,
        ':customer_name' => $customerName,
        ':customer_id_number' => $customerIdNumber,
        ':customer_city' => $data['customer_city'],
        ':customer_email' => $data['customer_email'] ?: null,
        ':mercaderista_supermarket' => $data['mercaderista_supermarket'] ?? null
    ]);

    $orderId = $db->lastInsertId();

    // Insertar en la tabla 'order_items'
    $sqlItems = "INSERT INTO order_items (order_id, product_id, quantity, promotion_text) VALUES (:order_id, :product_id, :quantity, :promotion_text)";
    $stmtItems = $db->prepare($sqlItems);

    foreach ($data['cart'] as $productId => $item) {
        $stmtItems->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':quantity' => $item['quantity'],
            ':promotion_text' => $item['promoDescription'] ?? null
        ]);
    }

    $db->commit();

    // Enviar correos después de confirmar la transacción
    $settingModel = new Setting();
    $settings = $settingModel->getAllAsAssoc();
    send_order_emails($orderId, $data, $settings);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}