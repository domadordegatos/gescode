<?php
$pageTitle   = $asig['materia'] . ' — Planeador — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Planeador de clases', 'url' => APP_URL . '/planeador'],
    ['label' => $asig['materia'], 'url' => ''],
];
$noLeidos = (new Usuario())->totalNoLeidos(Auth::id());

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($asig['materia']) ?></h1>
        <p class="page-subtitle"><?= e($asig['grado'] . ' ' . $asig['grupo']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/planeador/asignacion/<?= $asig['id'] ?>/nueva" class="btn-primary">
        <i class="mdi mdi-plus"></i> Nueva planeación
    </a>
</div>

<?php if (!$planeaciones): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        Aún no has registrado planeaciones para esta materia.
    </div>
<?php endif; ?>

<?php foreach ($planeaciones as $p): ?>
<div class="card" style="padding:18px 20px;margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <span class="periodo-pill" style="font-size:.72rem"><?= e($p['periodo_nombre']) ?></span>
            <div style="font-weight:700;font-size:.95rem;margin-top:6px"><?= e($p['tema']) ?></div>
            <?php if ($p['fecha_inicio'] || $p['fecha_fin']): ?>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                <?= $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '' ?>
                <?= ($p['fecha_inicio'] && $p['fecha_fin']) ? ' – ' : '' ?>
                <?= $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'])) : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="<?= APP_URL ?>/planeador/<?= $p['id'] ?>/editar" class="btn-sm btn-outline">
                <i class="mdi mdi-pencil-outline"></i>
            </a>
            <form method="POST" action="<?= APP_URL ?>/planeador/<?= $p['id'] ?>/eliminar"
                  onsubmit="return confirm('¿Eliminar esta planeación?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>

    <?php if ($p['objetivo']): ?>
    <div style="margin-top:10px;font-size:.85rem"><strong>Objetivo:</strong> <?= nl2br(e($p['objetivo'])) ?></div>
    <?php endif; ?>
    <?php if ($p['metodologia']): ?>
    <div style="margin-top:6px;font-size:.85rem"><strong>Metodología:</strong> <?= nl2br(e($p['metodologia'])) ?></div>
    <?php endif; ?>
    <?php if ($p['recursos']): ?>
    <div style="margin-top:6px;font-size:.85rem"><strong>Recursos:</strong> <?= nl2br(e($p['recursos'])) ?></div>
    <?php endif; ?>
    <?php if ($p['evaluacion']): ?>
    <div style="margin-top:6px;font-size:.85rem"><strong>Evaluación:</strong> <?= nl2br(e($p['evaluacion'])) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
