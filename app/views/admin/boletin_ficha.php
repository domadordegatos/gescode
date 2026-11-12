<?php
$pageTitle   = 'Boletín de ' . $estudiante['nombre'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Boletines', 'url' => APP_URL . '/boletines'],
    ['label' => $estudiante['nombre'] . ' ' . $estudiante['apellido'], 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h1>
        <p class="page-subtitle"><?= e($estudiante['grado'] . ' ' . $estudiante['grupo'] . ' · ' . $estudiante['ano']) ?></p>
    </div>
</div>

<div class="card" style="padding:20px 22px;max-width:620px">
    <div style="font-weight:600;margin-bottom:10px">Generar boletín en PDF</div>

    <a href="<?= APP_URL ?>/boletines/<?= $estudianteId ?>/pdf" target="_blank"
       style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;margin-bottom:8px">
        <div>
            <div style="font-weight:600">Consolidado del año</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Todas las materias, promedios por período y definitiva</div>
        </div>
        <i class="mdi mdi-file-pdf-box" style="color:#C026D3;font-size:1.3rem"></i>
    </a>

    <?php foreach ($periodos as $p): ?>
    <a href="<?= APP_URL ?>/boletines/<?= $estudianteId ?>/pdf?periodo=<?= $p['id'] ?>" target="_blank"
       style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;margin-bottom:8px">
        <div>
            <div style="font-weight:600"><?= e($p['nombre']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)">Boletín de este período, con desempeño por materia</div>
        </div>
        <i class="mdi mdi-file-pdf-box" style="color:#C026D3;font-size:1.3rem"></i>
    </a>
    <?php endforeach; ?>
    <?php if (!$periodos): ?>
    <div style="color:var(--text-muted);font-size:.85rem">Este grupo aún no tiene períodos configurados.</div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
