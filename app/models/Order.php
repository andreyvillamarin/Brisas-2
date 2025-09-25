<?php
class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrdersByDate($date) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE DATE(created_at) = :order_date AND (status = 'pending' OR status = 'enviado a despacho' OR status = 'completed') ORDER BY created_at DESC");
            $stmt->execute(['order_date' => $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting orders by date: " . $e->getMessage());
            return [];
        }
    }

    public function getOrdersBy($filters) {
        try {
            $sql = "SELECT * FROM orders";
            $whereClauses = [];
            $params = [];

            if (!empty($filters['status'])) {
                if ($filters['status'] === 'pending') {
                    $whereClauses[] = "(status = :status OR status = 'enviado a despacho')";
                    $params[':status'] = 'pending';
                } elseif (is_array($filters['status'])) {
                    $status_placeholders = [];
                    foreach ($filters['status'] as $key => $value) {
                        $placeholder = ':status_' . $key;
                        $status_placeholders[] = $placeholder;
                        $params[$placeholder] = $value;
                    }
                    $whereClauses[] = 'status IN (' . implode(', ', $status_placeholders) . ')';
                } else {
                    $whereClauses[] = "status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            if (!empty($filters['customer_type'])) {
                $whereClauses[] = "customer_type = :customer_type";
                $params[':customer_type'] = $filters['customer_type'];
            }
            if (!empty($filters['date'])) {
                $whereClauses[] = "DATE(created_at) = :order_date";
                $params[':order_date'] = $filters['date'];
            }
            if (!empty($filters['searchTerm'])) {
                $whereClauses[] = "(LOWER(customer_name) LIKE :searchTerm OR LOWER(customer_city) LIKE :searchTerm OR LOWER(code) LIKE :searchTerm)";
                $params[':searchTerm'] = '%' . strtolower($filters['searchTerm']) . '%';
            }

            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting orders by filter: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus($orderId, $newStatus) {
        try {
            $sql = "UPDATE orders SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderWithItems($orderId) {
        $order = [];
        try {
            // Obtener datos del pedido
            // ***** THIS IS THE CORRECTED LINE *****
            $sqlDetails = "SELECT id, customer_type, customer_name, customer_id_number, customer_city, customer_email, mercaderista_supermarket, status, created_at, code, note 
                           FROM orders 
                           WHERE id = :id";
            $stmt = $this->db->prepare($sqlDetails);
            $stmt->execute(['id' => $orderId]);
            $order['details'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener productos del pedido
            $sqlItems = "SELECT oi.id as item_id, oi.quantity, oi.promotion_text, oi.dispatched, p.name, p.image_url 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = :order_id";
            $stmtItems = $this->db->prepare($sqlItems);
            $stmtItems->execute(['order_id' => $orderId]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (PDOException $e) {
            error_log("Error getting order with items: " . $e->getMessage());
            return false;
        }
    }

    public function searchOrders($searchTerm) {
        try {
            $sql = "SELECT * FROM orders 
                    WHERE LOWER(customer_name) LIKE :term1 
                    OR LOWER(customer_city) LIKE :term2 
                    OR LOWER(customer_id_number) LIKE :term3
                    OR LOWER(customer_type) LIKE :term4
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $term = '%' . strtolower($searchTerm) . '%';
            $stmt->execute([
                'term1' => $term,
                'term2' => $term,
                'term3' => $term,
                'term4' => $term
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching orders: " . $e->getMessage());
            return [];
        }
    }

    public function createOrder($data) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO orders (customer_type, customer_name, customer_id_number, customer_city, customer_email, mercaderista_supermarket, status) 
                    VALUES (:customer_type, :customer_name, :customer_id_number, :customer_city, :customer_email, :mercaderista_supermarket, :status)";
            
            $stmt = $this->db->prepare($sql);
            
            $customerName = $data['customer_name'] ?? ($data['mercaderista_name'] ?? 'N/A');
            $customerIdNumber = $data['customer_id_number'] ?? 'N/A';
            
            $stmt->execute([
                ':customer_type' => $data['customer_type'],
                ':customer_name' => $customerName,
                ':customer_id_number' => $customerIdNumber,
                ':customer_city' => $data['customer_city'],
                ':customer_email' => $data['customer_email'] ?: null,
                ':mercaderista_supermarket' => $data['mercaderista_supermarket'] ?? null,
                ':status' => $data['status'] ?? 'pending'
            ]);

            $orderId = $this->db->lastInsertId();

            $sqlItems = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)";
            $stmtItems = $this->db->prepare($sqlItems);

            foreach ($data['products'] as $product) {
                if (!empty($product['id']) && !empty($product['quantity'])) {
                    $stmtItems->execute([
                        ':order_id' => $orderId,
                        ':product_id' => $product['id'],
                        ':quantity' => $product['quantity']
                    ]);
                }
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating manual order: " . $e->getMessage());
            return false;
        }
    }

    public function getOrdersByDateWithDetails($date) {
        try {
            // 1. Get all orders for the date
            $orders = $this->getOrdersByDate($date);

            if (empty($orders)) {
                return [];
            }

            // 2. Get all order IDs
            $orderIds = array_column($orders, 'id');

            // 3. Fetch all items for these orders
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $sqlItems = "SELECT oi.order_id, oi.quantity, oi.promotion_text, p.name 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id IN (" . $placeholders . ")";
            
            $stmtItems = $this->db->prepare($sqlItems);
            $stmtItems->execute($orderIds);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            // 4. Group items by order_id
            $itemsByOrderId = [];
            foreach ($items as $item) {
                $itemsByOrderId[$item['order_id']][] = $item;
            }

            // 5. Attach items to orders
            foreach ($orders as &$order) {
                $order['items'] = $itemsByOrderId[$order['id']] ?? [];
            }

            return $orders;
        } catch (PDOException $e) {
            error_log("Error getting orders by date with details: " . $e->getMessage());
            return [];
        }
    }

    public function getNewOrderCountForDespacho() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE status = 'enviado a despacho'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting new order count for despacho: " . $e->getMessage());
            return 0;
        }
    }

    public function updateOrderDetails($orderId, $data) {
        if (empty($data)) {
            return false;
        }

        try {
            $sql = "UPDATE orders SET ";
            $params = [];
            $updates = [];

            // Whitelist of allowed fields to prevent SQL injection
            $allowed_fields = ['status', 'code', 'note'];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $updates[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($updates)) {
                return false; // No valid fields to update
            }

            $sql .= implode(', ', $updates);
            $sql .= " WHERE id = :id";
            $params[':id'] = $orderId;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating order details: " . $e->getMessage());
            return false;
        }
    }

    public function updateOrderItemDispatchStatus($itemId, $dispatched) {
        try {
            $sql = "UPDATE order_items SET dispatched = :dispatched WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['dispatched' => $dispatched, 'id' => $itemId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating order item dispatch status: " . $e->getMessage());
            return false;
        }
    }
}
?>