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
$pageTitle = 'Dashboard de Despacho';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $orderId = $_POST['order_id'];
    $note = $_POST['note'];
    $dispatchedItems = $_POST['dispatched'] ?? [];

    // Iteramos directamente sobre los datos enviados por el formulario
    foreach ($dispatchedItems as $itemId => $isDispatched) {
        // El valor ser芍 '1' si el checkbox fue marcado, o '0' del campo oculto.
        // Aseguramos que sea un entero para la BD.
        $orderModel->updateOrderItemDispatchStatus($itemId, (int)$isDispatched);
    }

    $data = [
        'status' => 'completed',
        'note' => $note,
        'hora_completado' => date('Y-m-d H:i:s')
    ];
    $orderModel->updateOrderDetails($orderId, $data);

    // Redirigir para evitar reenv赤o del formulario y asegurar que la tabla se vea actualizada
    header('Location: dashboard_despacho.php');
    exit;
}

$filters = ['status' => 'enviado a despacho'];
if (isset($_GET['date'])) {
    $filters['date'] = $_GET['date'];
}

$orders = $orderModel->getOrdersBy($filters);

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header_despacho.php'; // Using a custom header for despacho
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
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <!-- El contenido se cargar芍 aqu赤 v赤a AJAX -->
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
                <button type="submit" form="order-details-form-despacho" name="action" value="complete" class="btn btn-primary">Completar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal-despacho'));
    const tableBody = document.getElementById('orders-table-body');

    async function loadTableContent() {
        try {
            const response = await fetch('../api/get_despacho_orders_table_body.php');
            const html = await response.text();
            tableBody.innerHTML = html;
            addEventListenersToButtons();
        } catch (error) {
            console.error('Error al cargar la tabla de pedidos:', error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar los pedidos. Intente de nuevo m芍s tarde.</td></tr>';
        }
    }

    function addEventListenersToButtons() {
        document.querySelectorAll('.view-details-btn-despacho').forEach(button => {
            button.addEventListener('click', async () => {
                const orderId = button.dataset.id;
                const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                    return;
                }

                // ... (toda la l車gica para construir el HTML del modal sigue aqu赤)
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
                    <form id="order-details-form-despacho" action="dashboard_despacho.php" method="POST">
                        <input type="hidden" name="order_id" value="${orderId}">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Cliente: ${data.details.customer_name}</h6>
                                <p><strong>Tipo:</strong> ${customerType}</p>
                                <p><strong>Ciudad:</strong> ${data.details.customer_city}</p>
                                <p><strong>ID:</strong> ${data.details.customer_id_number}</p>
                                ${data.details.mercaderista_supermarket ? `<p><strong>Supermercado:</strong> ${data.details.mercaderista_supermarket}</p>` : ''}
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p><strong>Estado:</strong> <span class="badge bg-info">${data.details.status_translated}</span></p>
                                <p><strong>Fecha:</strong> ${orderDate}</p>
                                <p><strong>Código:</strong> ${data.details.code || 'N/A'}</p>
                                ${data.details.hora_envio_despacho ? `<p><strong>Despachado:</strong> ${new Date(data.details.hora_envio_despacho).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
                                ${data.details.hora_completado ? `<p><strong>Hora completado:</strong> ${new Date(data.details.hora_completado).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
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
                    contentHtml += `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.quantity}</td>
                            <td>
                                <div class="form-check">
                                    <input type="hidden" name="dispatched[${item.item_id}]" value="0">
                                    <input class="form-check-input" type="checkbox" name="dispatched[${item.item_id}]" value="1" ${isDispatched ? 'checked' : ''}>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                contentHtml += `
                            </tbody>
                        </table>
                        <div class="mb-3">
                            <label for="order-note" class="form-label">Nota:</label>
                            <textarea id="order-note" name="note" class="form-control">${data.details.note || ''}</textarea>
                        </div>
                    </form>
                `;

                document.getElementById('order-details-content-despacho').innerHTML = contentHtml;
                orderDetailsModal.show();
            });
        });
    }

    // Cargar la tabla al iniciar la p芍gina
    loadTableContent();

    // Exponer la funci車n de carga para que pueda ser llamada desde el footer
    window.refreshDespachoTable = loadTableContent;
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
