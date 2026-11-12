<?php
$pageTitle   = 'Boletines de ' . $grupo['grado'] . ' ' . $grupo['grupo'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Boletines', 'url' => APP_URL . '/boletines'],
    ['label' => $grupo['grado'] . ' ' . $grupo['grupo'], 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($grupo['grado'] . ' ' . $grupo['grupo']) ?></h1>
        <p class="page-subtitle"><?= e($grupo['ano']) ?> · <?= $totalEst ?> estudiante<?= $totalEst != 1 ? 's' : '' ?> activos</p>
    </div>
</div>

<div class="card" style="padding:20px 22px;max-width:620px">
    <div style="font-weight:600;margin-bottom:10px">Generar boletines de todo el grupo en un solo PDF</div>

    <a href="<?= APP_URL ?>/boletines/grupo/<?= $grupo['id'] ?>/pdf" target="_blank"
       style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;margin-bottom:8px">
        <div>
            <div style="font-weight:600">Consolidado del año</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Un PDF con el boletín consolidado de cada estudiante del grupo</div>
        </div>
        <i class="mdi mdi-file-pdf-box" style="color:#C026D3;font-size:1.3rem"></i>
    </a>

    <?php foreach ($periodos as $p): ?>
    <a href="<?= APP_URL ?>/boletines/grupo/<?= $grupo['id'] ?>/pdf?periodo=<?= $p['id'] ?>" target="_blank"
       style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;margin-bottom:8px">
        <div>
            <div style="font-weight:600"><?= e($p['nombre']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)">Un PDF con el boletín de este período de cada estudiante del grupo</div>
        </div>
        <i class="mdi mdi-file-pdf-box" style="color:#C026D3;font-size:1.3rem"></i>
    </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
