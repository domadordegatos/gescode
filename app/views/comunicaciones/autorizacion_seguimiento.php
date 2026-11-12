<?php
$pageTitle   = $autorizacion['titulo'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => APP_URL . '/autorizaciones'],
    ['label' => 'Seguimiento', 'url' => ''],
];
$noLeidos = 0;
$total = count($participantes);
$firmados = count(array_filter($participantes, fn($p) => $p['firmado']));

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($autorizacion['titulo']) ?></h1>
        <p class="page-subtitle">
            <?= $firmados ?> de <?= $total ?> firmadas
            <?= $total > 0 ? '(' . round($firmados / $total * 100) . '%)' : '' ?>
        </p>
    </div>
    <?php if ($autorizacion['activa']): ?>
    <form method="POST" action="<?= APP_URL ?>/autorizaciones/<?= $autorizacion['id'] ?>/cerrar" onsubmit="return confirm('¿Cerrar esta autorización? No se podrán registrar más firmas.')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-secondary"><i class="mdi mdi-lock-outline"></i> Cerrar autorización</button>
    </form>
    <?php endif; ?>
</div>

<div class="card" style="padding:0">
<?php foreach ($participantes as $p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.88rem"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></div>
            <?php if ($p['firmado']): ?>
            <div style="font-size:.75rem;color:var(--text-muted)">
                Firmada por <?= e($p['padre_nombre'] . ' ' . $p['padre_apellido']) ?> el <?= date('d/m/Y H:i', strtotime($p['firmado_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($p['firmado']): ?>
            <a href="<?= APP_URL ?>/autorizaciones/firma/<?= $p['firma_id'] ?>" style="font-size:.78rem;color:var(--success);font-weight:600">
                <i class="mdi mdi-check-circle"></i> Ver evidencia
            </a>
        <?php else: ?>
            <span style="font-size:.78rem;color:var(--text-muted)"><i class="mdi mdi-clock-outline"></i> Pendiente</span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
