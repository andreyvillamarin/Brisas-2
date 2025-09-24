<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'despacho') {
    die('Acceso denegado.');
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$userModel = new User();
$pageTitle = 'Mi Perfil';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => $_POST['password'] ?? '',
        'role' => 'despacho' // Role is not editable here
    ];

    $userModel->update($userId, $data);
    log_event("Actualizó su perfil", "user", $userId);
    
    // Update session data if username changed
    if ($_SESSION['user_username'] !== $data['username']) {
        $_SESSION['user_username'] = $data['username'];
    }

    header('Location: perfil_despacho.php?success=1');
    exit;
}

$user = $userModel->getById($userId);

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header_despacho.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-3"><?= $pageTitle ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Perfil actualizado correctamente.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="perfil_despacho.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control">
                        <div class="form-text">Dejar en blanco para no cambiar la contraseña.</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
            </form>
        </div>
    </div>
</div>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>