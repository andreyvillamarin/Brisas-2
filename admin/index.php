<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';
require_once APP_ROOT . '/app/models/Order.php';
require_once APP_ROOT . '/app/models/Setting.php';

$orderModel = new Order();
$pageTitle = 'Dashboard';
$headerTitle = 'Pedidos';

// Lógica de Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = $_POST['order_id'];
    $action = $_POST['action'];
    $updateData = [];

    if ($action === 'send_to_dispatch') {
        $updateData['status'] = 'enviado a despacho';
        $updateData['code'] = $_POST['code'] ?? null;
        $updateData['hora_envio_despacho'] = date('Y-m-d H:i:s');
        log_event("envió a despacho el pedido", "order", $orderId);
    } elseif ($action === 'save_details') {
        $updateData['code'] = $_POST['code'] ?? null;
        log_event("guardó detalles para el pedido", "order", $orderId);
    } elseif ($action === 'cancel_order') {
        $updateData['status'] = 'cancelled';
        log_event("canceló el pedido", "order", $orderId);
    }

    if (!empty($updateData)) {
        $orderModel->updateOrderDetails($orderId, $updateData);
    }

    // Redireccionar para evitar reenvío del formulario
    $queryParams = $_GET;
    $redirectUrl = 'index.php?' . http_build_query($queryParams);
    header('Location: ' . $redirectUrl);
    exit;
} elseif (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    if ($action === 'send_to_dispatch') {
        log_event("Envió a despacho el", "order", $id);
        $updateData = [
            'status' => 'enviado a despacho',
            'hora_envio_despacho' => date('Y-m-d H:i:s')
        ];
        $orderModel->updateOrderDetails($id, $updateData);
    } elseif ($action === 'archive') {
        log_event("Archivó el", "order", $id);
        $orderModel->updateStatus($id, 'archived');
    } elseif ($action === 'restore') {
        log_event("Restauró el", "order", $id);
        $orderModel->updateStatus($id, 'pending');
    } elseif ($action === 'cancel_order') {
        log_event("Canceló el", "order", $id);
        $orderModel->updateStatus($id, 'cancelled');
    }

    // Build a clean redirect URL
    $queryParams = $_GET;
    unset($queryParams['action'], $queryParams['id']);
    $redirectUrl = 'index.php?' . http_build_query($queryParams);
    header('Location: ' . $redirectUrl);
    exit;
}

// Lógica de Búsqueda y Filtros
$selectedDate = $_GET['date'] ?? date('Y-m-d');

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $orders = $orderModel->searchOrders($searchTerm);
    $headerTitle = "Resultados para '" . htmlspecialchars($searchTerm) . "'";
} elseif (isset($_GET['filter'])) {
    $filters = ['date' => $selectedDate];
    $allowed_statuses = ['pending', 'completed', 'archived', 'enviado a despacho'];
    if (in_array($_GET['filter'], $allowed_statuses)) {
        $filters['status'] = $_GET['filter'];
    } else {
        $filters['customer_type'] = $_GET['filter'];
    }
    $orders = $orderModel->getOrdersBy($filters);
    
    $statusTranslations = [
        'pending' => 'Pendientes',
        'completed' => 'Completados',
        'archived' => 'Archivados',
        'enviado a despacho' => 'En Despacho'
    ];
    $filterValue = $_GET['filter'];
    $displayFilter = $statusTranslations[$filterValue] ?? ucfirst($filterValue);
    
    $headerTitle = "Pedidos para " . date("d/m/Y", strtotime($selectedDate)) . " - " . htmlspecialchars($displayFilter);
} else {
    $orders = $orderModel->getOrdersByDate($selectedDate);
    $headerTitle = 'Pedidos para ' . date("d/m/Y", strtotime($selectedDate));
}

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>

<div class="container-fluid">
    <!-- Buscador y Selector de Fecha -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <!-- Selector de Fecha -->
                <div class="col-md-4">
                    <form action="index.php" method="GET" id="date-filter-form">
                        <label for="date-selector" class="form-label">Ver pedidos para:</label>
                        <input type="date" class="form-control form-control-lg" id="date-selector" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                    </form>
                </div>
                <!-- Buscador -->
                <div class="col-md-8">
                     <form action="index.php" method="GET">
                        <label for="search-input" class="form-label">Buscar en todos los pedidos:</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control form-control-lg" name="search" placeholder="Buscar por nombre, ciudad, cédula...">
                            <button type="submit" class="btn btn-primary btn-lg">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-4">
        <strong>Filtros rápidos:</strong>
        <a href="index.php?filter=pending&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Pendientes</a>
        <a href="index.php?filter=enviado a despacho&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">En Despacho</a>
        <a href="index.php?filter=completed&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Completados</a>
        <a href="index.php?filter=Cliente+Salsamentaria&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Cliente Salsamentaria</a>
        <a href="index.php?filter=Mercaderista&date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-outline-secondary btn-sm">Mercaderistas</a>
        <a href="index.php?date=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-link btn-sm">Limpiar filtros</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><?= $headerTitle ?></h1>
        <div class="d-flex align-items-center">
            <div class="btn-group me-2">
                <a href="export.php?format=xlsx&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-success">Exportar a XLSX</a>
                <a href="export.php?format=pdf&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-danger">Exportar a PDF</a>
            </div>
            <a href="new_order.php" class="btn btn-primary">Crear Pedido Manual</a>
        </div>
    </div>

    <!-- Buscador y Filtros -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th><th>Fecha</th><th>Tipo</th><th>Ciudad</th><th>Estado</th><th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center py-4">No se encontraron pedidos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            $type_translations = [
                                                'distribuidor_salsamentaria' => 'Cliente Salsamentaria',
                                                'Cliente Salsamentaria' => 'Cliente Salsamentaria',
                                                'Mercaderista' => 'Mercaderista'
                                            ];
                                            $type_text = $type_translations[$order['customer_type']] ?? htmlspecialchars($order['customer_type']);
                                            echo $type_text;
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_city']) ?></td>
                                    <td>
                                        <?php 
                                            $status_classes = [
                                                'pending' => 'bg-warning', 
                                                'completed' => 'bg-success', 
                                                'archived' => 'bg-secondary',
                                                'enviado a despacho' => 'bg-primary'
                                            ];
                                            $status_translations = [
                                                'pending' => 'Pendiente',
                                                'completed' => 'Completado',
                                                'archived' => 'Archivado',
                                                'enviado a despacho' => 'En Despacho'
                                            ];
                                            $status_class = $status_classes[$order['status']] ?? 'bg-light text-dark';
                                            $status_text = $status_translations[$order['status']] ?? ucfirst($order['status']);
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info view-details-btn" data-id="<?= $order['id'] ?>">Ver Detalles</button>
                                        
                                        <?php if ($order['status'] !== 'archived' && $order['status'] !== 'cancelled'): ?>
                                            <a href="?action=archive&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" class="btn btn-sm btn-secondary">Archivar</a>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'pending' || $order['status'] === 'enviado a despacho' || $order['status'] === 'completed'): ?>
                                            <a href="?action=cancel_order&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" onclick="return confirm('¿Estás seguro de que quieres cancelar este pedido?');" class="btn btn-sm btn-danger">Cancelar Pedido</a>
                                        <?php endif; ?>

                                        <?php if ($order['status'] === 'pending' || $order['status'] === 'completed'): ?>
                                            <a href="?action=send_to_dispatch&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" class="btn btn-sm btn-primary">Enviar a Despacho</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'archived'): ?>
                                            <a href="?action=restore&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" class="btn btn-sm btn-warning">Restaurar</a>
                                        <?php endif; ?>
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
            <div class="modal-footer">
                <div id="modal-download-buttons" class="me-auto"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('date-selector').addEventListener('change', function() {
    document.getElementById('date-filter-form').submit();
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
