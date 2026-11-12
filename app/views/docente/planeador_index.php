<?php
$pageTitle   = 'Planeador de clases — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Planeador de clases', 'url' => ''],
];
$noLeidos = (new Usuario())->totalNoLeidos(Auth::id());

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-notebook-edit-outline"></i> Planeador de clases</h1>
        <p class="page-subtitle">Elige una materia para ver o crear planeaciones de clase.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
<?php foreach ($grupos as $g): ?>
    <a href="<?= APP_URL ?>/planeador/asignacion/<?= $g['asignacion_id'] ?>"
       class="card" style="padding:18px 20px;text-decoration:none;color:inherit;display:block">
        <div style="font-weight:700;font-size:.95rem"><?= e($g['materia']) ?></div>
        <div style="font-size:.82rem;color:var(--text-muted);margin-top:4px"><?= e($g['grado'] . ' ' . $g['grupo']) ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px"><?= $g['total_est'] ?> estudiantes</div>
    </a>
<?php endforeach; ?>
<?php if (!$grupos): ?>
    <div style="color:var(--text-muted)">Aún no tienes materias asignadas.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
