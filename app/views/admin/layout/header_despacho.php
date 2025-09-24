<?php
$baseUrl = rtrim(APP_URL, '/');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Despacho' ?> - Brisas</title>
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
            <a href="<?= $baseUrl ?>/admin/dashboard_despacho.php" class="text-white text-decoration-none d-flex align-items-center">
                <?php if (!empty($settingsForHeader['logo_backend_url'])): ?>
                    <img src="../<?= htmlspecialchars($settingsForHeader['logo_backend_url']) ?>" style="height: <?= htmlspecialchars($settingsForHeader['sidebar_logo_height'] ?? '50') ?>px;" class="me-2">
                <?php else: ?>
                    <h4 class="mb-0">Despacho Brisas</h4>
                <?php endif; ?>
            </a>
        </div>
        <ul class="list-unstyled">
            <li class="<?= $currentPage == 'dashboard_despacho.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/dashboard_despacho.php"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li class="<?= $currentPage == 'pedidos_completados_despacho.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/pedidos_completados_despacho.php"><i class="bi bi-check-all"></i> Pedidos Completados</a></li>
            <li class="<?= $currentPage == 'perfil_despacho.php' ? 'active' : '' ?>"><a href="<?= $baseUrl ?>/admin/perfil_despacho.php"><i class="bi bi-person-fill"></i> Mi Perfil</a></li>
            <li><a href="<?= $baseUrl ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>
    <main class="main-content flex-grow-1 p-4">
        <div class="d-flex justify-content-end mb-3">
            <div class="notification-bell">
                <i class="bi bi-bell-fill"></i>
                <span class="badge bg-danger rounded-pill notification-count">0</span>
            </div>
        </div>
        <audio id="notification-sound" src="../assets/notification.mp3" preload="auto"></audio>
