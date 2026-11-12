<?php
$pageTitle   = 'Detalle de retiro — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Retiro anticipado', 'url' => APP_URL . '/retiro-anticipado'],
    ['label' => 'Detalle', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Retiro de <?= e($retiro['est_nombre'] . ' ' . $retiro['est_apellido']) ?></h1>
        <p class="page-subtitle"><?= date('d/m/Y H:i:s', strtotime($retiro['created_at'])) ?></p>
    </div>
    <?php if ($retiro['anulado']): ?>
    <span style="font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:99px;background:#DC2626;color:#fff">ANULADO</span>
    <?php else: ?>
    <a href="<?= APP_URL ?>/retiro-anticipado/registro/<?= $retiro['id'] ?>/anular" class="btn-secondary">
        <i class="mdi mdi-cancel"></i> Anular registro
    </a>
    <?php endif; ?>
</div>

<?php if ($retiro['anulado']): ?>
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:.84rem;color:#991B1B;margin-bottom:18px;max-width:900px">
    <i class="mdi mdi-cancel"></i> <strong>Este registro fue anulado.</strong> Motivo: <?= e($retiro['anulado_motivo']) ?><br>
    <span style="font-size:.78rem">Anulado el <?= date('d/m/Y H:i', strtotime($retiro['anulado_at'])) ?></span>
</div>
<?php endif; ?>

<div style="display:flex;gap:20px;flex-wrap:wrap;max-width:900px">
    <div class="card" style="padding:16px;flex-shrink:0">
        <img src="<?= APP_URL ?>/retiro-anticipado/registro/<?= $retiro['id'] ?>/foto" style="width:280px;border-radius:10px;display:block">
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:8px;text-align:center">
            Reto solicitado: <strong><?= e($retiro['instruccion_reto']) ?></strong>
        </div>
        <a href="<?= APP_URL ?>/retiro-anticipado/registro/<?= $retiro['id'] ?>/pdf" target="_blank" class="btn-sm btn-secondary" style="margin-top:10px;width:100%;text-align:center;display:block">
            <i class="mdi mdi-file-pdf-box"></i> Exportar como PDF
        </a>
    </div>

    <div class="card" style="padding:20px 22px;flex:1;min-width:320px">
        <table style="width:100%;font-size:.85rem;border-collapse:collapse">
            <tr><td style="padding:5px 0;color:var(--text-muted);width:160px">Persona que retira</td><td><strong><?= e($retiro['nombre_persona']) ?></strong></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Documento</td><td style="font-family:monospace"><?= e($retiro['documento_persona']) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Parentesco</td><td><?= e($retiro['parentesco']) ?></td></tr>
            <?php if ($retiro['acudiente_id']): ?>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Acudiente registrado</td><td><span style="color:var(--success)"><i class="mdi mdi-check-circle"></i> Sí</span></td></tr>
            <?php elseif ($retiro['autoriza_nombre']): ?>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Autorizado por</td><td><?= e($retiro['autoriza_nombre'] . ' ' . $retiro['autoriza_apellido']) ?> (acudiente registrado)</td></tr>
            <?php endif; ?>
            <?php if ($retiro['motivo']): ?>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Motivo</td><td><?= e($retiro['motivo']) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Registrado por</td><td><?= e($retiro['reg_nombre'] . ' ' . $retiro['reg_apellido']) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Dirección IP</td><td style="font-family:monospace"><?= e($retiro['ip']) ?></td></tr>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
