<?php
// Este endpoint devuelve solo el cuerpo de la tabla de pedidos para el dashboard de despacho.
// Permite la actualización dinámica vía AJAX.

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$orderModel = new Order();
$filters = ['status' => 'enviado a despacho'];
if (isset($_GET['date'])) {
    $filters['date'] = $_GET['date'];
}
$orders = $orderModel->getOrdersBy($filters);

if (empty($orders)): ?>
    <tr><td colspan="5" class="text-center py-4">No hay pedidos para despachar.</td></tr>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
            <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
            <td><?= htmlspecialchars($order['customer_city']) ?></td>
            <td><?= htmlspecialchars($order['code'] ?? 'N/A') ?></td>
            <td class="text-end">
                <button class="btn btn-sm btn-info view-details-btn-despacho" data-id="<?= $order['id'] ?>">Ver Detalles</button>
                <button class="btn btn-sm btn-success view-details-btn-despacho" data-id="<?= $order['id'] ?>">Completar</button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>