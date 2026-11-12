<?php
$pageTitle   = 'Nuevo grupo — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => 'Nuevo grupo', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Nuevo grupo</h1>
        <p class="page-subtitle">Crea un grupo en el año lectivo activo (<?= e($anoActivo['nombre']) ?>), por ejemplo si necesitas abrir un paralelo nuevo.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/grados/grupo/crear" class="card" style="padding:22px 24px;max-width:480px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Grado <span class="required">*</span></label>
        <select name="grado_id" class="form-input" required>
            <option value="">Selecciona...</option>
            <?php foreach ($grados as $g): ?>
            <option value="<?= $g['id'] ?>"><?= e($g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Nombre / letra del grupo <span class="required">*</span></label>
        <input type="text" name="nombre" class="form-input" required maxlength="10" placeholder="Ej: C">
    </div>

    <?php if (count($sedes) > 1): ?>
    <div class="form-group">
        <label>Sede</label>
        <select name="sede_id" class="form-input">
            <?php foreach ($sedes as $s): ?>
            <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Director de grupo (opcional)</label>
        <select name="director_id" class="form-input">
            <option value="">— Sin asignar —</option>
            <?php foreach ($docentes as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><i class="mdi mdi-plus"></i> Crear grupo</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
