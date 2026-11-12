<?php
$pageTitle   = 'Circulares — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Circulares', 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-bullhorn-outline"></i> Circulares institucionales</h1>
        <p class="page-subtitle">Comunicados oficiales del colegio.</p>
    </div>
    <?php if ($puedePublicar): ?>
    <a href="<?= APP_URL ?>/circulares/nueva" class="btn-primary">
        <i class="mdi mdi-plus"></i> Nueva circular
    </a>
    <?php endif; ?>
</div>

<?php if (!$circulares): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No tienes circulares recibidas aún.
    </div>
<?php endif; ?>

<?php foreach ($circulares as $c): ?>
<div class="card circular-card" data-id="<?= $c['id'] ?>" style="padding:18px 20px;margin-bottom:12px;<?= $c['leido'] ? '' : 'border-left:4px solid var(--primary)' ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div style="font-weight:700;font-size:.95rem"><?= e($c['titulo']) ?></div>
        <?php if (!$c['leido']): ?>
        <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:99px;background:var(--primary);color:#fff;flex-shrink:0">NUEVA</span>
        <?php endif; ?>
    </div>
    <div style="font-size:.78rem;color:var(--text-muted);margin:4px 0 10px">
        <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?> · <?= e($c['pub_nombre'] . ' ' . $c['pub_apellido']) ?>
    </div>
    <div style="font-size:.88rem;white-space:pre-wrap"><?= nl2br(e($c['cuerpo'])) ?></div>

    <div style="margin-top:12px;display:flex;gap:14px;align-items:center">
        <?php if ($c['requiere_acuse']): ?>
            <?php if ($c['leido']): ?>
                <span style="font-size:.78rem;color:var(--success);font-weight:600">
                    <i class="mdi mdi-check-circle"></i> Leída el <?= date('d/m/Y H:i', strtotime($c['leido_at'])) ?>
                </span>
            <?php else: ?>
                <button class="btn-sm btn-primary btn-marcar-leida" data-id="<?= $c['id'] ?>">
                    <i class="mdi mdi-check"></i> Marcar como leída
                </button>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($puedePublicar): ?>
            <a href="<?= APP_URL ?>/circulares/<?= $c['id'] ?>/seguimiento" style="font-size:.78rem">
                <i class="mdi mdi-chart-box-outline"></i> Ver seguimiento de lectura
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.btn-marcar-leida').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        fetch('<?= APP_URL ?>/circulares/' + id + '/marcar-leida', { method: 'POST' })
            .then(r => r.json())
            .then(() => location.reload());
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
