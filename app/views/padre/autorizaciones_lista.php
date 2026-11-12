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
        <p class="page-subtitle">Salidas pedagógicas, uso de imagen y otros consentimientos que requieren tu firma.</p>
    </div>
</div>

<?php if (!$filas): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No tienes autorizaciones pendientes ni firmadas.
    </div>
<?php endif; ?>

<?php foreach ($filas as $f): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:99px;background:#1D4ED8;color:#fff"><?= e($tipoLabel[$f['tipo']] ?? $f['tipo']) ?></span>
            <div style="font-weight:700;font-size:.92rem;margin-top:6px"><?= e($f['titulo']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px">
                Para: <?= e($f['hijo_nombre'] . ' ' . $f['hijo_apellido']) ?>
                <?= $f['fecha_evento'] ? ' · ' . date('d/m/Y', strtotime($f['fecha_evento'])) : '' ?>
            </div>
        </div>
    </div>

    <div style="margin-top:12px">
        <?php if ($f['firmado']): ?>
            <span style="font-size:.78rem;color:var(--success);font-weight:600">
                <i class="mdi mdi-check-circle"></i> Firmada el <?= date('d/m/Y H:i', strtotime($f['firmado_at'])) ?>
            </span>
        <?php elseif (!$f['activa']): ?>
            <span style="font-size:.78rem;color:var(--text-muted)">Esta autorización ya cerró.</span>
        <?php else: ?>
            <a href="<?= APP_URL ?>/autorizaciones/firmar/<?= $f['estudiante_id'] ?>/<?= $f['autorizacion_id'] ?>" class="btn-sm btn-primary">
                <i class="mdi mdi-draw-pen"></i> Firmar ahora
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
