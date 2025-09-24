<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userModel = new User();
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = $userModel->findByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
        // 2FA no estив activado, iniciar sesiиоn normalmente
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        log_event("Inicio de sesiиоn exitoso");

        if ($user['role'] === 'despacho') {
            header('Location: ../admin/dashboard_despacho.php');
        } else {
            header('Location: ../admin/');
        }
        exit;
    } else {
        header('Location: ../login.php?error=1');
        exit;
    }
}
?>