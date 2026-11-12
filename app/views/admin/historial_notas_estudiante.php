<?php
$pageTitle   = 'Historial de ' . $estudiante['nombre'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Historial de notas', 'url' => APP_URL . '/historial-notas'],
    ['label' => $estudiante['nombre'] . ' ' . $estudiante['apellido'], 'url' => ''],
];
$noLeidos = 0;

$accionCfg = [
    'creacion'    => ['label' => 'Nota registrada', 'color' => '#16A34A', 'icon' => 'mdi-plus-circle-outline'],
    'edicion'     => ['label' => 'Nota modificada', 'color' => '#D97706', 'icon' => 'mdi-pencil-outline'],
    'eliminacion' => ['label' => 'Nota eliminada',  'color' => '#DC2626', 'icon' => 'mdi-close-circle-outline'],
];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h1>
        <p class="page-subtitle"><?= e($estudiante['documento']) ?> · <?= count($historial) ?> cambio<?= count($historial) != 1 ? 's' : '' ?> registrado<?= count($historial) != 1 ? 's' : '' ?></p>
    </div>
    <a href="<?= APP_URL ?>/historial-notas" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Buscar otro
    </a>
</div>

<?php if (!$historial): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay cambios registrados en las notas de este estudiante.
    </div>
<?php endif; ?>

<?php foreach ($historial as $h): $cfg = $accionCfg[$h['accion']]; ?>
<div class="card" style="padding:14px 18px;margin-bottom:8px;border-left:4px solid <?= $cfg['color'] ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <span style="color:<?= $cfg['color'] ?>;font-weight:700;font-size:.85rem">
                <i class="mdi <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
            </span>
            <div style="font-size:.88rem;margin-top:4px">
                <?= e($h['materia']) ?> · <?= e($h['periodo']) ?> · <?= e($h['ano']) ?>
            </div>
            <?php if ($h['evaluacion'] || $h['logro']): ?>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                <?= e($h['evaluacion']) ?><?= $h['logro'] ? ' — ' . e($h['logro']) : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:.95rem;font-weight:700">
                <?= $h['valor_anterior'] !== null ? number_format((float)$h['valor_anterior'], 1) : '—' ?>
                <i class="mdi mdi-arrow-right" style="font-size:.8rem;color:var(--text-muted)"></i>
                <?= $h['valor_nuevo'] !== null ? number_format((float)$h['valor_nuevo'], 1) : '—' ?>
            </div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">
                <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
            </div>
        </div>
    </div>
    <div style="font-size:.76rem;color:var(--text-muted);margin-top:6px">
        Por <?= e($h['mod_nombre'] . ' ' . $h['mod_apellido']) ?> (<?= e(ucfirst($h['mod_rol'])) ?>)
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
