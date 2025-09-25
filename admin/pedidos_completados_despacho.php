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
                    <label for="search-filter" class="form-label">Buscar por Cliente, Ciudad o Código</label>
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
                            <th>Código</th>
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
        if (typeof str !== 'string') {
            return '';
        }
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

            const orderDate = new Date(data.details.created_at).toLocaleString('es-CO', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });

            let customerType = data.details.customer_type;
            if (!customerType || customerType.trim() === '') {
                customerType = 'No especificado';
            } else {
                const typeTranslations = {
                    'distribuidor_salsamentaria': 'Cliente Salsamentaria',
                    'Mercaderista': 'Mercaderista'
                };
                customerType = typeTranslations[customerType] || customerType;
            }

            let contentHtml = `
                <div class="row">
                    <div class="col-md-8">
                        <h6>Cliente: ${data.details.customer_name}</h6>
                        <p><strong>Tipo:</strong> ${customerType}</p>
                        <p><strong>Ciudad:</strong> ${data.details.customer_city}</p>
                        <p><strong>ID:</strong> ${data.details.customer_id_number}</p>
                        ${data.details.customer_email ? `<p><strong>Email:</strong> ${data.details.customer_email}</p>` : ''}
                        ${data.details.mercaderista_supermarket ? `<p><strong>Supermercado:</strong> ${data.details.mercaderista_supermarket}</p>` : ''}
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p><strong>Estado:</strong> <span class="badge bg-success">${data.details.status_translated || 'Completado'}</span></p>
                        <p><strong>Fecha Pedido:</strong> ${orderDate}</p>
                        <p><strong>Código:</strong> ${data.details.code || 'N/A'}</p>
                        ${data.details.hora_envio_despacho ? `<p><strong>Despachado:</strong> ${new Date(data.details.hora_envio_despacho).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
                        ${data.details.hora_completado ? `<p><strong>Completado:</strong> ${new Date(data.details.hora_completado).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
                        ${data.details.note ? `<p><strong>Nota de Despacho:</strong> ${data.details.note}</p>` : ''}
                    </div>
                </div>
                <hr>
                <h6>Productos del Pedido:</h6>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Despachado</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.items.forEach(item => {
                const isDispatched = parseInt(item.dispatched, 10) === 1;
                const rowClass = isDispatched ? '' : 'text-decoration-line-through';
                const dispatchedText = isDispatched ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';
                let nameCell = item.name;
                if (item.promotion_text) {
                    nameCell += `<br><small class="text-danger">${item.promotion_text}</small>`;
                }

                contentHtml += `
                    <tr class="${rowClass}">
                        <td>${nameCell}</td>
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