<?php
$pageTitle   = 'Nueva solicitud — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'PQRS', 'url' => APP_URL . '/pqrs'],
    ['label' => 'Nueva', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Nueva solicitud</h1>
        <p class="page-subtitle">Tu solicitud recibirá un número de radicado y podrás seguir su estado aquí.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/pqrs/guardar" class="card" style="padding:24px;max-width:720px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Tipo <span class="required">*</span></label>
        <select name="tipo" class="form-input" required>
            <?php foreach ($tipos as $k => $label): ?>
            <option value="<?= $k ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($hijos): ?>
    <div class="form-group">
        <label>Relacionada con (opcional)</label>
        <select name="estudiante_id" class="form-input">
            <option value="">No aplica / es general</option>
            <?php foreach ($hijos as $h): ?>
            <option value="<?= $h['id'] ?>"><?= e($h['nombre'] . ' ' . $h['apellido']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Asunto <span class="required">*</span></label>
        <input type="text" name="asunto" class="form-input" required maxlength="200">
    </div>

    <div class="form-group">
        <label>Descripción <span class="required">*</span></label>
        <textarea name="descripcion" class="form-input" rows="6" required></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-send-outline"></i> Radicar solicitud
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
