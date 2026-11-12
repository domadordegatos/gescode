<?php
$pageTitle   = 'Boletines por grupo — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Boletines', 'url' => APP_URL . '/boletines'],
    ['label' => 'Por grupo', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Boletines por grupo</h1>
        <p class="page-subtitle">Genera en un solo PDF los boletines de todos los estudiantes de un grupo.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
<?php foreach ($grupos as $g): ?>
    <a href="<?= APP_URL ?>/boletines/grupo/<?= $g['id'] ?>" class="card" style="padding:16px 18px;text-decoration:none;color:inherit;display:block">
        <div style="font-weight:700"><?= e($g['grado'] . ' ' . $g['grupo']) ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:3px"><?= $g['total_est'] ?> estudiantes</div>
    </a>
<?php endforeach; ?>
<?php if (!$grupos): ?>
    <div style="color:var(--text-muted)">No tienes grupos disponibles.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
