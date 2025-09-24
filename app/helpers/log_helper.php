<?php
/**
 * Registra una acci��n en el log de eventos.
 * @param string $action La descripci��n de la acci��n realizada.
 */
function log_event($action, $entity_type = null, $entity_id = null) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['user_id'])) {
        return; // No se puede registrar si no hay usuario
    }

    $db = Database::getInstance()->getConnection();
    $description = $action;

    if ($entity_type && $entity_id) {
        $name = '';
        try {
            switch ($entity_type) {
                case 'user':
                    $stmt = $db->prepare("SELECT username FROM users WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $name = $stmt->fetchColumn();
                    break;
                case 'product':
                    $stmt = $db->prepare("SELECT name FROM products WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $name = $stmt->fetchColumn();
                    break;
                case 'category':
                    $stmt = $db->prepare("SELECT name FROM categories WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $name = $stmt->fetchColumn();
                    break;
                case 'establishment':
                    $stmt = $db->prepare("SELECT name FROM establishments WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $name = $stmt->fetchColumn();
                    break;
                case 'promotion':
                    $stmt = $db->prepare("SELECT promo_description FROM promotions WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $name = 'Promoci��n (' . $stmt->fetchColumn() . ')';
                    break;
                case 'order':
                    $stmt = $db->prepare("SELECT customer_name FROM orders WHERE id = :id");
                    $stmt->execute(['id' => $entity_id]);
                    $customerName = $stmt->fetchColumn();
                    if ($customerName) {
                        $description .= " Pedido de '" . $customerName . "' (ID: " . $entity_id . ")";
                    } else {
                        $description .= " Pedido ID: " . $entity_id;
                    }
                    break;
            }
            if ($name) {
                $description .= ": " . $name;
            }
        } catch (Exception $e) {
            error_log("Failed to get entity name for log: " . $e->getMessage());
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO event_log (user_id, action) VALUES (:user_id, :action)");
        $stmt->execute(['user_id' => $_SESSION['user_id'], 'action' => $description]);
    } catch (Exception $e) {
        error_log("Failed to log event: " . $e->getMessage());
    }
}
?>