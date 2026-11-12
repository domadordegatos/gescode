<?php
$pageTitle   = 'Anular registro de retiro — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Retiro anticipado', 'url' => APP_URL . '/retiro-anticipado'],
    ['label' => 'Anular', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Anular registro de retiro</h1>
        <p class="page-subtitle">El registro y la fotografía no se eliminan (quedan como evidencia), solo se marcan como anulados.</p>
    </div>
</div>

<div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;font-size:.82rem;color:#92400E;margin-bottom:18px;max-width:620px">
    <i class="mdi mdi-information-outline"></i> Usa esto cuando el registro se hizo con datos equivocados (ej. estudiante incorrecto). No se puede deshacer una anulación.
</div>

<form method="POST" action="<?= APP_URL ?>/retiro-anticipado/registro/<?= $retiro['id'] ?>/anular" class="card" style="padding:22px 24px;max-width:500px"
      onsubmit="return confirm('¿Anular este registro de retiro?')">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <div class="form-group">
        <label>Motivo de la anulación <span class="required">*</span></label>
        <input type="text" name="anulado_motivo" class="form-input" required placeholder="Ej: Se registró al estudiante equivocado">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn-danger-outline"><i class="mdi mdi-cancel"></i> Anular registro</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
