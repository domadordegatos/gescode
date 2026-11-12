<?php
$pageTitle   = 'Respaldos de base de datos — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Respaldos', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-database-arrow-down-outline"></i> Respaldos de base de datos</h1>
        <p class="page-subtitle">Se genera automáticamente una vez al día. Se conservan los últimos 30 días.</p>
    </div>
    <form method="POST" action="<?= APP_URL ?>/soporte/backups/generar">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-primary"><i class="mdi mdi-database-plus-outline"></i> Generar ahora</button>
    </form>
</div>

<?php if (!$archivos): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        Aún no hay respaldos generados. Haz clic en "Generar ahora" para crear el primero.
    </div>
<?php endif; ?>

<div class="card" style="padding:0">
<?php foreach ($archivos as $a): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.85rem"><?= date('d/m/Y H:i', $a['fecha']) ?></div>
            <div style="font-size:.76rem;color:var(--text-muted)"><?= number_format($a['tamano'] / 1024 / 1024, 2) ?> MB</div>
        </div>
        <a href="<?= APP_URL ?>/soporte/backups/<?= e($a['nombre']) ?>" class="btn-sm btn-secondary">
            <i class="mdi mdi-download-outline"></i> Descargar
        </a>
    </div>
<?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
