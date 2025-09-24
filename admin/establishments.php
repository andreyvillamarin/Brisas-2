<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Only admins can manage establishments
if ($_SESSION['user_role'] !== 'admin') {
    // Redirect to a safe page or show an error
    header('Location: index.php');
    exit;
}


require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Establishment.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$establishmentModel = new Establishment();
$action = $_GET['action'] ?? 'list';
$pageTitle = 'Gestion de Establecimientos';

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();

// Handle POST actions (create, update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';

    switch ($_POST['form_action']) {
        case 'create':
            $newId = $establishmentModel->create($name);
            log_event("Creó el establecimiento", "establishment", $newId);
            break;
        case 'update':
            $establishmentModel->update($id, $name);
            log_event("Actualizó el establecimiento", "establishment", $id);
            break;
    }
    header('Location: establishments.php');
    exit;
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    log_event("Eliminó el establecimiento", "establishment", $id);
    $establishmentModel->delete($id);
    header('Location: establishments.php');
    exit;
}

include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Establecimientos</h1>
            <a href="establishments.php?action=new" class="btn btn-primary">Crear Nuevo Establecimiento</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $establishments = $establishmentModel->getAll(); ?>
                        <?php if (empty($establishments)): ?>
                            <tr><td colspan="2" class="text-center">No hay establecimientos creados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($establishments as $establishment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($establishment['name']) ?></td>
                                    <td>
                                        <a href="establishments.php?action=edit&id=<?= $establishment['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <a href="establishments.php?action=delete&id=<?= $establishment['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este establecimiento?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <?php
        $isEdit = $action === 'edit';
        $establishment = $isEdit ? $establishmentModel->getById($_GET['id']) : null;
        ?>
        <h1 class="h3"><?= $isEdit ? 'Editar Establecimiento' : 'Crear Nuevo Establecimiento' ?></h1>
        <div class="card">
            <div class="card-body">
                <form action="establishments.php" method="POST">
                    <input type="hidden" name="form_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $establishment['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del Establecimiento</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($establishment['name'] ?? '') ?>" required>
                    </div>

                    <a href="establishments.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear' ?> Establecimiento</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
