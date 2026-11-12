<?php
$pageTitle   = 'Retiros anticipados — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Retiro anticipado', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-exit-run"></i> Retiros anticipados</h1>
        <p class="page-subtitle">Historial de estudiantes retirados antes de la hora normal de salida.</p>
    </div>
    <a href="<?= APP_URL ?>/retiro-anticipado/nuevo" class="btn-primary">
        <i class="mdi mdi-plus"></i> Registrar retiro
    </a>
</div>

<?php if (!$retiros): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay retiros registrados aún.
    </div>
<?php endif; ?>

<?php foreach ($retiros as $r): ?>
<a href="<?= APP_URL ?>/retiro-anticipado/registro/<?= $r['id'] ?>" class="card" style="display:block;padding:16px 20px;margin-bottom:10px;text-decoration:none;color:inherit">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <div style="font-weight:700;font-size:.92rem">
                <?= e($r['est_nombre'] . ' ' . $r['est_apellido']) ?>
                <?php if ($r['anulado']): ?><span style="font-size:.65rem;font-weight:700;padding:1px 7px;border-radius:99px;background:#DC2626;color:#fff;margin-left:6px">ANULADO</span><?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px">
                Retirado por <?= e($r['nombre_persona']) ?> (<?= e($r['parentesco']) ?>)
                <?= $r['motivo'] ? ' · ' . e($r['motivo']) : '' ?>
            </div>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);text-align:right;flex-shrink:0">
            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?><br>
            Registró: <?= e($r['reg_nombre']) ?>
        </div>
    </div>
</a>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
