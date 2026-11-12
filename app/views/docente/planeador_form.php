<?php
$esEdicion = $planeacion !== null;
$pageTitle   = ($esEdicion ? 'Editar planeación' : 'Nueva planeación') . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Planeador de clases', 'url' => APP_URL . '/planeador'],
    ['label' => $asig['materia'], 'url' => APP_URL . '/planeador/asignacion/' . $asig['id']],
    ['label' => $esEdicion ? 'Editar' : 'Nueva', 'url' => ''],
];
$noLeidos = (new Usuario())->totalNoLeidos(Auth::id());

$accion = $esEdicion
    ? APP_URL . '/planeador/' . $planeacion['id'] . '/actualizar'
    : APP_URL . '/planeador/guardar';

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $esEdicion ? 'Editar planeación' : 'Nueva planeación' ?></h1>
        <p class="page-subtitle"><?= e($asig['materia'] . ' · ' . $asig['grado'] . ' ' . $asig['grupo']) ?></p>
    </div>
</div>

<form method="POST" action="<?= $accion ?>" class="card" style="padding:24px;max-width:720px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <?php if (!$esEdicion): ?>
    <input type="hidden" name="asignacion_id" value="<?= $asig['id'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label>Período <span class="required">*</span></label>
        <select name="periodo_id" class="form-input" required>
            <option value="">Selecciona...</option>
            <?php foreach ($periodos as $per): ?>
            <option value="<?= $per['id'] ?>" <?= ((int)($planeacion['periodo_id'] ?? 0) === (int)$per['id']) ? 'selected' : '' ?>>
                <?= e($per['nombre']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Tema / unidad <span class="required">*</span></label>
        <input type="text" name="tema" class="form-input" required maxlength="200"
               value="<?= e($planeacion['tema'] ?? '') ?>" placeholder="Ej: Ecuaciones de primer grado">
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_inicio" class="form-input" value="<?= e($planeacion['fecha_inicio'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-input" value="<?= e($planeacion['fecha_fin'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Objetivo / logro esperado</label>
        <textarea name="objetivo" class="form-input" rows="2"><?= e($planeacion['objetivo'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label>Metodología / actividades</label>
        <textarea name="metodologia" class="form-input" rows="3"><?= e($planeacion['metodologia'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label>Recursos</label>
        <textarea name="recursos" class="form-input" rows="2"><?= e($planeacion['recursos'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label>Forma de evaluación</label>
        <textarea name="evaluacion" class="form-input" rows="2"><?= e($planeacion['evaluacion'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Guardar
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
