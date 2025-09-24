<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'despacho') {
    die('Acceso denegado.');
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';
require_once APP_ROOT . '/app/models/Setting.php';

$orderModel = new Order();
$pageTitle = 'Pedidos Completados';

$filters = ['status' => 'completed'];
$orders = $orderModel->getOrdersBy($filters);

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header_despacho.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-3"><?= $pageTitle ?></h1>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Ciudad</th>
                            <th>Código</th>
                            <th>Nota</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center py-4">No hay pedidos completados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($order['customer_city']) ?></td>
                                    <td><?= htmlspecialchars($order['code'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['note'] ?? 'N/A') ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info view-details-btn-despacho" data-id="<?= $order['id'] ?>">Ver Detalles</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles del Pedido (Despacho) -->
<div class="modal fade" id="order-details-modal-despacho" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order-details-content-despacho"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// This script will have its own logic for the despacho modal
document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal-despacho'));

    document.querySelectorAll('.view-details-btn-despacho').forEach(button => {
        button.addEventListener('click', async () => {
            const orderId = button.dataset.id;
            const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            let contentHtml = `
                <p><strong>Cliente:</strong> ${data.details.customer_name}</p>
                <p><strong>C車digo:</strong> ${data.details.code || 'N/A'}</p>
                <p><strong>Nota:</strong> ${data.details.note || 'N/A'}</p>
                <hr>
                <h6>Productos del Pedido:</h6>
                <table class="table">
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Despachado</th></tr></thead>
                    <tbody>
            `;

            data.items.forEach(item => {
                const isDispatched = parseInt(item.dispatched, 10) === 1;
                const rowClass = isDispatched ? '' : 'text-decoration-line-through';
                const dispatchedText = isDispatched ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';

                contentHtml += `
                    <tr class="${rowClass}">
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>${dispatchedText}</td>
                    </tr>
                `;
            });

            contentHtml += `
                        </tbody>
                    </table>
            `;

            document.getElementById('order-details-content-despacho').innerHTML = contentHtml;
            orderDetailsModal.show();
        });
    });
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>