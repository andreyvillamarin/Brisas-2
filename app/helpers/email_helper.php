<?php

function send_order_emails($orderId, $orderData, $settings) {
    // Email para el Administrador
    send_admin_notification($orderId, $orderData, $settings);

    // Email para el Cliente (si proporcionó un correo)
    if (!empty($orderData['customer_email'])) {
        send_customer_confirmation($orderId, $orderData, $settings);
    }
}

/**
 * Sends an email using the Brevo API.
 *
 * @param string $apiKey Brevo API Key.
 * @param string $subject Email subject.
 * @param string $htmlContent Email body in HTML.
 * @param array $sender Sender information ['name' => 'Sender Name', 'email' => 'sender@example.com'].
 * @param array $to Recipient information [['name' => 'Recipient Name', 'email' => 'recipient@example.com']].
 * @return bool True on success, false on failure.
 */
function send_email_with_brevo_api($apiKey, $subject, $htmlContent, $sender, $to) {
    if (empty($apiKey) || empty($sender['email']) || empty($to[0]['email'])) {
        error_log("Brevo API Error: Missing required parameters (API Key, sender email, or recipient email).");
        return false;
    }

    $url = 'https://api.brevo.com/v3/smtp/email';

    $data = [
        'sender' => $sender,
        'to' => $to,
        'subject' => $subject,
        'htmlContent' => mb_convert_encoding($htmlContent, 'UTF-8', 'UTF-8'),
    ];

    $jsonData = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);

    if ($jsonData === false) {
        error_log("Brevo API Error: json_encode failed. Error: " . json_last_error_msg());
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json',
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        $log_message = "Brevo API Error: Failed to send email.\n" .
                       "HTTP Status Code: " . $http_code . "\n" .
                       "cURL Error: " . $curl_error . "\n" .
                       "API Response: " . $response . "\n";
        error_log($log_message);
        return false;
    }
}

function send_admin_notification($orderId, $orderData, $settings) {
    if (empty($settings['admin_notification_email'])) {
        return;
    }

    $apiKey = $settings['brevo_api_key'] ?? '';
    $sender = [
        'name' => $settings['sender_name'] ?? 'Brisas Pedidos',
        'email' => $settings['sender_email'] ?? 'no-reply@brisas.com',
    ];
    $to = [
        ['email' => $settings['admin_notification_email']]
    ];
    $subject = "Nuevo Pedido Recibido";

    $translationMap = [
        'customer_type' => 'Tipo de Cliente',
        'customer_name' => 'Nombre del Cliente',
        'mercaderista_name' => 'Nombre del Mercaderista',
        'customer_id_number' => 'Cédula o NIT',
        'customer_city' => 'Ciudad',
        'customer_email' => 'Email del Cliente',
        'mercaderista_supermarket' => 'Supermercado (Mercaderista)',
    ];
    
    $body = "<h1>Nuevo Pedido</h1>";
    $body .= "<p>Se ha recibido un nuevo pedido con los siguientes detalles:</p>";
    $body .= "<ul>";
    foreach ($orderData as $key => $value) {
        if ($key !== 'cart' && $key !== 'recaptcha_token' && !empty($value)) {
            $label = $translationMap[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $body .= "<li><strong>" . $label . ":</strong> " . htmlspecialchars($value) . "</li>";
        }
    }
    $body .= "</ul><hr><h3>Productos:</h3><table border='1' cellpadding='5' cellspacing='0'><tr><th>Producto</th><th>Cantidad</th></tr>";
    foreach ($orderData['cart'] as $item) {
        $productName = htmlspecialchars($item['name']);
        $quantity = $item['quantity'];
        $promoText = '';
        if (!empty($item['promoDescription'])) {
            $promoText = "<br><small style='color: #d9534f;'><strong>Promoción:</strong> " . htmlspecialchars($item['promoDescription']) . "</small>";
        }
        $body .= "<tr><td>" . $productName . $promoText . "</td><td>{$quantity}</td></tr>";
    }
    $body .= "</table>";

    send_email_with_brevo_api($apiKey, $subject, $body, $sender, $to);
}

function send_customer_confirmation($orderId, $orderData, $settings) {
    $apiKey = $settings['brevo_api_key'] ?? '';
    $sender = [
        'name' => $settings['sender_name'] ?? 'Brisas Pedidos',
        'email' => $settings['sender_email'] ?? 'no-reply@brisas.com',
    ];
    $customerName = htmlspecialchars($orderData['customer_name'] ?? $orderData['mercaderista_name']);
    $to = [
        ['email' => $orderData['customer_email'], 'name' => $customerName]
    ];
    $subject = "Confirmación de tu Pedido Brisas";
    
    $logoUrl = APP_URL . '/' . ($settings['logo_frontend_url'] ?? '');
    $body = "<div style='font-family: sans-serif; color: #333;'><img src='{$logoUrl}' alt='Logo Brisas' style='max-height: 80px;'><h1 style='color: #aa182c;'>¡Gracias por tu pedido!</h1>";
    $body .= "<p>Hola " . $customerName . ", hemos recibido tu pedido y lo estamos procesando.</p>";
    $body .= "<h3>Resumen de tu pedido:</h3><table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    $body .= "<thead style='background-color: #f2f2f2;'><tr><th>Producto</th><th>Cantidad</th></tr></thead><tbody>";
    foreach ($orderData['cart'] as $item) {
        $productName = htmlspecialchars($item['name']);
        $quantity = $item['quantity'];
        $promoText = '';
        if (!empty($item['promoDescription'])) {
            $promoText = "<br><small style='color: #d9534f;'><strong>Promoción:</strong> " . htmlspecialchars($item['promoDescription']) . "</small>";
        }
        $body .= "<tr><td>" . $productName . $promoText . "</td><td>{$quantity}</td></tr>";
    }
    $body .= "</tbody></table><p style='margin-top: 20px;'>Gracias por preferirnos.</p><p><strong>Equipo Brisas</strong></p></div>";

    send_email_with_brevo_api($apiKey, $subject, $body, $sender, $to);
}

?>