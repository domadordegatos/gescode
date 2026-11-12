<?php
$pageTitle   = 'Editar autorización — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => APP_URL . '/autorizaciones'],
    ['label' => 'Editar', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Editar autorización</h1>
        <p class="page-subtitle">Aún no tiene firmas registradas, así que puedes corregirla libremente.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/autorizaciones/<?= $autorizacion['id'] ?>/actualizar" class="card" style="padding:24px;max-width:760px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Fecha del evento (opcional)</label>
        <input type="date" name="fecha_evento" class="form-input" value="<?= e($autorizacion['fecha_evento'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Título <span class="required">*</span></label>
        <input type="text" name="titulo" class="form-input" required maxlength="200" value="<?= e($autorizacion['titulo']) ?>">
    </div>

    <div class="form-group">
        <label>Texto del consentimiento <span class="required">*</span></label>
        <textarea name="texto_consentimiento" class="form-input" rows="7" required><?= e($autorizacion['texto_consentimiento']) ?></textarea>
        <small class="form-hint">[NOMBRE_HIJO] y [NOMBRE_PADRE] se reemplazan automáticamente por el nombre real al momento de la firma.</small>
    </div>

    <div class="form-actions" style="display:flex;gap:10px">
        <button type="submit" class="btn-primary"><i class="mdi mdi-content-save-outline"></i> Guardar cambios</button>
    </div>
</form>

<form method="POST" action="<?= APP_URL ?>/autorizaciones/<?= $autorizacion['id'] ?>/eliminar" style="margin-top:14px"
      onsubmit="return confirm('¿Eliminar esta autorización por completo? No tiene firmas registradas.')">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <button type="submit" class="btn-danger-outline btn-sm"><i class="mdi mdi-trash-can-outline"></i> Eliminar autorización</button>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
