<?php
$baseUrl = rtrim(APP_URL, '/');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> - Brisas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/admin-style.css" rel="stylesheet">
    <?php if (!empty($settingsForHeader['logo_backend_url'])): ?>
        <link rel="icon" href="../<?= htmlspecialchars($settingsForHeader['logo_backend_url']) ?>">
    <?php endif; ?>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar bg-dark text-white">
        <div class="sidebar-header">
            <a href="<?= $baseUrl ?>/admin/" class="text-white text-decoration-none d-flex align-items-center">
                <?php if (!empty($settingsForHeader['logo_backend_url'])): ?>
                    <img src="../<?= htmlspecialchars($settingsForHeader['logo_backend_url']) ?>" style="height: <?= htmlspecialchars($settingsForHeader['sidebar_logo_height'] ?? '50') ?>px;" class="me-2">
                <?php else: ?>
                    <h4 class="mb-0">Admin Brisas</h4>
                <?php endif; ?>
            </a>
        </div>
        <ul class="list-unstyled">
            <li class="<?= $currentPage == 'index.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li class="<?= $currentPage == 'pedidos.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/pedidos.php"><i class="bi bi-check-circle-fill"></i> Pedidos</a></li>
            <li class="<?= $currentPage == 'categories.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/categories.php"><i class="bi bi-tags-fill"></i> Categorías</a></li>
            <li class="<?= $currentPage == 'products.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/products.php"><i class="bi bi-box-seam-fill"></i> Productos</a></li>
            <li class="<?= $currentPage == 'promotions.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/promotions.php"><i class="bi bi-megaphone-fill"></i> Promociones</a></li>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li class="<?= $currentPage == 'establishments.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/establishments.php"><i class="bi bi-shop"></i> Establecimientos</a></li>
            <li class="<?= $currentPage == 'users.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/users.php"><i class="bi bi-people-fill"></i> Usuarios</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li class="<?= $currentPage == 'analytics.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/analytics.php"><i class="bi bi-bar-chart-fill"></i> Analítica</a></li>
            <li class="<?= $currentPage == 'event_log.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/event_log.php"><i class="bi bi-archive-fill"></i> Log de Eventos</a></li>
            <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/settings.php"><i class="bi bi-gear-fill"></i> Configuración</a></li>
            <?php endif; ?>
            <li><a href="<?= $baseUrl ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>
    <main class="main-content flex-grow-1 p-4">
