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

// Get filters from GET request
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$searchTerm = $_GET['search'] ?? '';

$filters = [
    'status' => 'completed',
    'date' => $dateFilter,
    'searchTerm' => $searchTerm
];
$orders = $orderModel->getOrdersBy($filters);

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header_despacho.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-3"><?= $pageTitle ?></h1>

    <div class="card">
        <div class="card-header">
            <form id="filter-form" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="date-filter" class="form-label">Fecha</label>
                    <input type="date" id="date-filter" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                </div>
                <div class="col-md-8">
                    <label for="search-filter" class="form-label">Buscar por Cliente, Ciudad o C¨®digo</label>
                    <input type="text" id="search-filter" class="form-control" placeholder="Escribe para buscar..." value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Ciudad</th>
                            <th>C¨®digo</th>
                            <th>Nota</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center py-4">No hay pedidos completados para los filtros seleccionados.</td></tr>
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
document.addEventListener('DOMContentLoaded', function() {
    const dateFilter = document.getElementById('date-filter');
    const searchFilter = document.getElementById('search-filter');
    const tableBody = document.getElementById('orders-table-body');
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal-despacho'));

    function fetchOrders() {
        const date = dateFilter.value;
        const search = searchFilter.value;
        const url = `../api/get_completed_orders.php?date=${date}&search=${search}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateTable(data);
            })
            .catch(error => console.error('Error fetching orders:', error));
    }

    function updateTable(orders) {
        tableBody.innerHTML = '';
        if (orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No hay pedidos completados para los filtros seleccionados.</td></tr>';
            return;
        }

        orders.forEach(order => {
            const orderDate = new Date(order.created_at).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const row = `
                <tr>
                    <td>${escapeHTML(order.customer_name)}</td>
                    <td>${orderDate}</td>
                    <td>${escapeHTML(order.customer_city)}</td>
                    <td>${escapeHTML(order.code || 'N/A')}</td>
                    <td>${escapeHTML(order.note || 'N/A')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-info view-details-btn-despacho" data-id="${order.id}">Ver Detalles</button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }
    
    function escapeHTML(str) {
        return str.replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    dateFilter.addEventListener('change', fetchOrders);
    searchFilter.addEventListener('input', fetchOrders);

    // Event delegation for view details buttons
    tableBody.addEventListener('click', async function(event) {
        if (event.target && event.target.classList.contains('view-details-btn-despacho')) {
            const button = event.target;
            const orderId = button.dataset.id;
            const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            let contentHtml = `
                <p><strong>Cliente:</strong> ${data.details.customer_name}</p>
                <p><strong>C¨®digo:</strong> ${data.details.code || 'N/A'}</p>
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
                const dispatchedText = isDispatched ? '<span class="badge bg-success">S¨ª</span>' : '<span class="badge bg-danger">No</span>';

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
        }
    });
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>