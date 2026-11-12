<?php
$pageTitle   = 'PQRS — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'PQRS', 'url' => ''],
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
        <h1 class="page-title"><i class="mdi mdi-file-document-edit-outline"></i> PQRS</h1>
        <p class="page-subtitle">Peticiones, quejas, reclamos y sugerencias.</p>
    </div>
    <a href="<?= APP_URL ?>/pqrs/nueva" class="btn-primary">
        <i class="mdi mdi-plus"></i> Nueva solicitud
    </a>
</div>

<?php if ($esGestor): ?>
<div class="card" style="padding:14px 18px;margin-bottom:14px">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
        <label style="font-size:.82rem;font-weight:600">Estado:</label>
        <select name="estado" class="form-input" style="width:auto" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($estados as $k => $label): ?>
            <option value="<?= $k ?>" <?= $estadoFiltro === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<?php endif; ?>

<?php if (!$items): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay solicitudes <?= $esGestor ? 'registradas' : 'de tu parte' ?> aún.
    </div>
<?php endif; ?>

<?php foreach ($items as $it): ?>
<a href="<?= APP_URL ?>/pqrs/<?= $it['id'] ?>" class="card" style="display:block;padding:16px 20px;margin-bottom:10px;text-decoration:none;color:inherit">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <div style="font-size:.75rem;color:var(--text-muted);font-family:monospace"><?= e($it['radicado']) ?></div>
            <div style="font-weight:700;font-size:.92rem;margin-top:2px"><?= e($it['asunto']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                <?= e($tipos[$it['tipo']] ?? $it['tipo']) ?>
                <?php if ($esGestor && isset($it['u_nombre'])): ?> · <?= e($it['u_nombre'] . ' ' . $it['u_apellido']) ?><?php endif; ?>
                · <?= date('d/m/Y', strtotime($it['created_at'])) ?>
            </div>
        </div>
        <span style="font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:99px;background:<?= $estadoColor[$it['estado']] ?>;color:#fff;flex-shrink:0">
            <?= e($estados[$it['estado']]) ?>
        </span>
    </div>
</a>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
