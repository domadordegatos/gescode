<?php
$pageTitle   = 'Autorizaciones — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => ''],
];
$noLeidos = 0;

$tipoLabel = ['salida_pedagogica' => 'Salida pedagógica', 'uso_imagen' => 'Uso de imagen', 'otro' => 'Otro'];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-file-sign"></i> Autorizaciones y consentimientos</h1>
        <p class="page-subtitle">Salidas pedagógicas, uso de imagen y otros consentimientos firmados digitalmente.</p>
    </div>
    <a href="<?= APP_URL ?>/autorizaciones/nueva" class="btn-primary">
        <i class="mdi mdi-plus"></i> Nueva autorización
    </a>
</div>

<?php if (!$autorizaciones): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay autorizaciones creadas aún.
    </div>
<?php endif; ?>

<?php foreach ($autorizaciones as $a): $pct = $a['total'] > 0 ? round($a['total_firmados'] / $a['total'] * 100) : 0; ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:99px;background:#1D4ED8;color:#fff"><?= e($tipoLabel[$a['tipo']] ?? $a['tipo']) ?></span>
            <div style="font-weight:700;font-size:.92rem;margin-top:6px"><?= e($a['titulo']) ?></div>
            <?php if ($a['fecha_evento']): ?>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">Fecha del evento: <?= date('d/m/Y', strtotime($a['fecha_evento'])) ?></div>
            <?php endif; ?>
        </div>
        <?php if (!$a['activa']): ?>
        <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:99px;background:#6B7280;color:#fff;flex-shrink:0">CERRADA</span>
        <?php endif; ?>
    </div>
    <div style="font-size:.78rem;color:var(--text-muted);margin-top:8px"><?= $a['total_firmados'] ?> de <?= $a['total'] ?> firmadas (<?= $pct ?>%)</div>
    <div style="margin-top:10px;display:flex;gap:14px">
        <a href="<?= APP_URL ?>/autorizaciones/<?= $a['id'] ?>/seguimiento" style="font-size:.82rem">
            <i class="mdi mdi-format-list-checks"></i> Ver seguimiento
        </a>
        <?php if ((int)$a['total_firmados'] === 0): ?>
        <a href="<?= APP_URL ?>/autorizaciones/<?= $a['id'] ?>/editar" style="font-size:.82rem">
            <i class="mdi mdi-pencil-outline"></i> Editar
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
