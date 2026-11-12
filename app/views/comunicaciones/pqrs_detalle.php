<?php
$pageTitle   = $pqrs['radicado'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'PQRS', 'url' => APP_URL . '/pqrs'],
    ['label' => $pqrs['radicado'], 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

$estadoColor = [
    'radicado'   => '#6B7280',
    'en_tramite' => '#D97706',
    'resuelto'   => '#16A34A',
    'cerrado'    => '#374151',
];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($pqrs['asunto']) ?></h1>
        <p class="page-subtitle" style="font-family:monospace"><?= e($pqrs['radicado']) ?></p>
    </div>
    <span style="font-size:.72rem;font-weight:700;padding:4px 12px;border-radius:99px;background:<?= $estadoColor[$pqrs['estado']] ?>;color:#fff">
        <?= e($estados[$pqrs['estado']]) ?>
    </span>
</div>

<div class="card" style="padding:20px 22px;max-width:760px;margin-bottom:16px">
    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px">
        <?= e($tipos[$pqrs['tipo']]) ?> · Radicado el <?= date('d/m/Y H:i', strtotime($pqrs['created_at'])) ?>
        <?php if ($esGestor): ?> · <?= e($solicitante['nombre'] . ' ' . $solicitante['apellido']) ?><?php endif; ?>
    </div>
    <div style="font-size:.9rem;white-space:pre-wrap"><?= nl2br(e($pqrs['descripcion'])) ?></div>
</div>

<?php if ($pqrs['respuesta']): ?>
<div class="card" style="padding:20px 22px;max-width:760px;margin-bottom:16px;border-left:4px solid var(--primary)">
    <div style="font-weight:700;font-size:.85rem;margin-bottom:6px">Respuesta de la institución</div>
    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:8px">
        <?= date('d/m/Y H:i', strtotime($pqrs['respondido_at'])) ?>
    </div>
    <div style="font-size:.9rem;white-space:pre-wrap"><?= nl2br(e($pqrs['respuesta'])) ?></div>
</div>
<?php endif; ?>

<?php if ($esGestor): ?>
<div class="card" style="padding:20px 22px;max-width:760px">
    <div style="font-weight:700;font-size:.85rem;margin-bottom:12px"><?= $pqrs['respuesta'] ? 'Actualizar respuesta' : 'Responder solicitud' ?></div>
    <form method="POST" action="<?= APP_URL ?>/pqrs/<?= $pqrs['id'] ?>/responder">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Estado</label>
            <select name="estado" class="form-input">
                <?php foreach ($estados as $k => $label): ?>
                <option value="<?= $k ?>" <?= $pqrs['estado'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Respuesta</label>
            <textarea name="respuesta" class="form-input" rows="5"><?= e($pqrs['respuesta'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="mdi mdi-content-save-outline"></i> Guardar respuesta</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
