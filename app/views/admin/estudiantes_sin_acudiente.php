<?php
$pageTitle   = 'Estudiantes sin acudiente — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Usuarios', 'url' => APP_URL . '/usuarios'],
    ['label' => 'Sin acudiente', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-account-alert-outline"></i> Estudiantes sin acudiente vinculado</h1>
        <p class="page-subtitle"><?= count($estudiantes) ?> estudiante(s) activo(s) no tienen ningún padre/acudiente vinculado en el sistema.</p>
    </div>
</div>

<?php if (!$estudiantes): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--success)">
        <i class="mdi mdi-check-circle-outline" style="font-size:1.5rem"></i>
        <div style="margin-top:8px">Todos los estudiantes activos tienen al menos un acudiente vinculado.</div>
    </div>
<?php endif; ?>

<div class="card" style="padding:0">
<?php foreach ($estudiantes as $e): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.88rem"><?= e($e['nombre'] . ' ' . $e['apellido']) ?></div>
            <div style="font-size:.76rem;color:var(--text-muted)"><?= e($e['documento'] ?? 'Sin documento') ?> · <?= e($e['grado'] . ' ' . $e['grupo']) ?></div>
        </div>
        <a href="<?= APP_URL ?>/usuarios/nuevo?rol=padre" class="btn-sm btn-primary">
            <i class="mdi mdi-link-plus"></i> Crear/vincular padre
        </a>
    </div>
<?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
