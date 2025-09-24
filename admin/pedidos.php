<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado. Esta sección es solo para administradores.');
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';
require_once APP_ROOT . '/app/models/Setting.php';

$orderModel = new Order();
$pageTitle = 'Pedidos Completados y Cancelados';

// Lógica de Búsqueda y Filtros
$selectedDate = $_GET['date'] ?? date('Y-m-d');

$filters = ['date' => $selectedDate];
if (isset($_GET['filter']) && in_array($_GET['filter'], ['completed', 'cancelled', 'archived'])) {
    $filters['status'] = $_GET['filter'];
} else {
    // Default to showing completed, cancelled, and archived
    $filters['status'] = ['completed', 'cancelled', 'archived'];
}

$orders = $orderModel->getOrdersBy($filters);

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><?= $pageTitle ?></h1>
        <div class="d-flex align-items-center">
            <div class="btn-group me-2">
                <a href="export.php?format=xlsx&<?= http_build_query($_GET) ?>&status=completed,cancelled" class="btn btn-sm btn-outline-success">Exportar a XLSX</a>
                <a href="export.php?format=pdf&<?= http_build_query($_GET) ?>&status=completed,cancelled" class="btn btn-sm btn-outline-danger">Exportar a PDF</a>
            </div>
        </div>
    </div>

    <!-- Selector de Fecha -->
    <div class="mb-3">
        <form action="pedidos.php" method="GET" id="date-filter-form" class="d-flex align-items-center">
            <label for="date-selector" class="form-label me-2 mb-0">Seleccionar fecha:</label>
            <input type="date" id="date-selector" name="date" value="<?= htmlspecialchars($selectedDate) ?>" class="form-control" style="width: auto;">
        </form>
    </div>

    <!-- Filtros -->
    <div class="mb-4">
        <strong>Filtros rápidos:</strong>
        <a href="pedidos.php?filter=completed&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Completados</a>
        <a href="pedidos.php?filter=cancelled&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Cancelados</a>
        <a href="pedidos.php?filter=archived&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Archivados</a>
        <a href="pedidos.php?date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-link btn-sm">Limpiar filtros</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th>Código</th>
                            <th>Nota</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center py-4">No se encontraron pedidos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($order['customer_city']) ?></td>
                                    <td>
                                        <?php 
                                            $status_classes = ['completed' => 'bg-success', 'cancelled' => 'bg-danger', 'archived' => 'bg-secondary'];
                                            $status_translations = ['completed' => 'Completado', 'cancelled' => 'Cancelado', 'archived' => 'Archivado'];
                                            $status_class = $status_classes[$order['status']] ?? 'bg-light text-dark';
                                            $status_text = $status_translations[$order['status']] ?? ucfirst($order['status']);
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($order['code'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['note'] ?? 'N/A') ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info view-details-btn" data-id="<?= $order['id'] ?>">Ver Detalles</button>
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

<!-- Modal para Detalles del Pedido -->
<div class="modal fade" id="order-details-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order-details-content"></div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateSelector = document.getElementById('date-selector');
    if(dateSelector) {
        dateSelector.addEventListener('change', function() {
            const form = document.getElementById('date-filter-form');
            
            // Preserve other filters like 'status'
            const urlParams = new URLSearchParams(window.location.search);
            const filter = urlParams.get('filter');
            
            if (filter) {
                // Check if a filter input already exists
                if (!form.querySelector('input[name="filter"]')) {
                    const filterInput = document.createElement('input');
                    filterInput.type = 'hidden';
                    filterInput.name = 'filter';
                    filterInput.value = filter;
                    form.appendChild(filterInput);
                }
            }
            form.submit();
        });
    }
});
</script>

<script>
// This script is dependent on assets/js/admin.js, which should be loaded by the footer.
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
