<?php
$pageTitle   = 'Editar grupo — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => 'Editar grupo', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Editar <?= e($grupo['grado'] . ' ' . $grupo['nombre']) ?></h1>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/grados/grupo/<?= $grupo['id'] ?>/actualizar" class="card" style="padding:22px 24px;max-width:480px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Nombre / letra del grupo <span class="required">*</span></label>
        <input type="text" name="nombre" class="form-input" required maxlength="10" value="<?= e($grupo['nombre']) ?>">
    </div>

    <?php if (count($sedes) > 1): ?>
    <div class="form-group">
        <label>Sede</label>
        <select name="sede_id" class="form-input">
            <?php foreach ($sedes as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (int)($grupo['sede_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Director de grupo</label>
        <select name="director_id" class="form-input">
            <option value="">— Sin asignar —</option>
            <?php foreach ($docentes as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int)$grupo['director_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions" style="display:flex;gap:10px">
        <button type="submit" class="btn-primary"><i class="mdi mdi-content-save-outline"></i> Guardar cambios</button>
    </div>
</form>

<form method="POST" action="<?= APP_URL ?>/grados/grupo/<?= $grupo['id'] ?>/eliminar" style="margin-top:14px;max-width:480px"
      onsubmit="return confirm('¿Eliminar este grupo? Solo es posible si no tiene estudiantes matriculados.')">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <button type="submit" class="btn-danger-outline btn-sm"><i class="mdi mdi-trash-can-outline"></i> Eliminar grupo</button>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
