<?php
$pageTitle   = 'Seguimiento — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Circulares', 'url' => APP_URL . '/circulares'],
    ['label' => 'Seguimiento', 'url' => ''],
];
$noLeidos = 0;
$total = count($destinatarios);

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($circular['titulo']) ?></h1>
        <p class="page-subtitle">
            <?= $totalLeidos ?> de <?= $total ?> destinatarios han leído este comunicado
            <?= $total > 0 ? '(' . round($totalLeidos / $total * 100) . '%)' : '' ?>
        </p>
    </div>
</div>

<div class="card" style="padding:0">
<?php foreach ($destinatarios as $d): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.88rem"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);text-transform:capitalize"><?= e($d['rol']) ?></div>
        </div>
        <?php if ($d['leido']): ?>
            <span style="font-size:.78rem;color:var(--success);font-weight:600">
                <i class="mdi mdi-check-circle"></i> <?= date('d/m/Y H:i', strtotime($d['leido_at'])) ?>
            </span>
        <?php else: ?>
            <span style="font-size:.78rem;color:var(--text-muted)"><i class="mdi mdi-clock-outline"></i> Pendiente</span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
