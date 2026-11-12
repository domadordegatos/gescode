<?php
$pageTitle   = 'Papelera de notas — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Papelera de notas', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-trash-can-outline"></i> Papelera de notas</h1>
        <p class="page-subtitle">Indicadores y evaluaciones eliminados por los docentes. Nada se borra realmente: todo queda aquí para poder restaurarse.</p>
    </div>
</div>

<?php if (!$items): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay elementos eliminados.
    </div>
<?php endif; ?>

<?php foreach ($items as $it): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:99px;background:<?= $it['tipo'] === 'indicador' ? '#DC2626' : '#D97706' ?>;color:#fff">
                <?= $it['tipo'] === 'indicador' ? 'INDICADOR COMPLETO' : 'EVALUACIÓN' ?>
            </span>
            <div style="font-weight:600;font-size:.88rem;margin-top:6px"><?= e($it['descripcion']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                Eliminado por <?= e($it['elim_nombre'] . ' ' . $it['elim_apellido']) ?> el <?= date('d/m/Y H:i', strtotime($it['created_at'])) ?>
            </div>
            <?php if ($it['restaurado']): ?>
            <div style="font-size:.76rem;color:var(--success);margin-top:2px">
                <i class="mdi mdi-check-circle"></i> Restaurado por <?= e($it['rest_nombre'] . ' ' . $it['rest_apellido']) ?> el <?= date('d/m/Y H:i', strtotime($it['restaurado_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <a href="<?= APP_URL ?>/papelera/<?= $it['id'] ?>" class="btn-sm btn-secondary" style="flex-shrink:0">
            <i class="mdi mdi-eye-outline"></i> Ver detalle
        </a>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
