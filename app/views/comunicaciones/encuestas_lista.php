<?php
$pageTitle   = 'Encuestas — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Encuestas', 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-poll"></i> Encuestas</h1>
        <p class="page-subtitle"><?= $esGestor ? 'Encuestas creadas en la institución.' : 'Encuestas pendientes y respondidas.' ?></p>
    </div>
    <?php if ($esGestor): ?>
    <a href="<?= APP_URL ?>/encuestas/nueva" class="btn-primary">
        <i class="mdi mdi-plus"></i> Nueva encuesta
    </a>
    <?php endif; ?>
</div>

<?php if (!$encuestas): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay encuestas <?= $esGestor ? 'creadas' : 'disponibles' ?> aún.
    </div>
<?php endif; ?>

<?php foreach ($encuestas as $enc): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
        <div>
            <div style="font-weight:700;font-size:.92rem"><?= e($enc['titulo']) ?></div>
            <?php if ($enc['descripcion']): ?>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:3px"><?= e($enc['descripcion']) ?></div>
            <?php endif; ?>
        </div>
        <?php if (!$enc['activa']): ?>
        <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:99px;background:#6B7280;color:#fff;flex-shrink:0">CERRADA</span>
        <?php endif; ?>
    </div>

    <?php if ($esGestor): ?>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:8px">
            <?= $enc['total_completados'] ?> de <?= $enc['total_invitados'] ?> han respondido
            <?= $enc['anonima'] ? '· Anónima' : '' ?>
        </div>
        <div style="margin-top:10px;display:flex;gap:10px">
            <a href="<?= APP_URL ?>/encuestas/<?= $enc['id'] ?>/resultados" style="font-size:.82rem">
                <i class="mdi mdi-chart-bar"></i> Ver resultados
            </a>
        </div>
    <?php else: ?>
        <div style="margin-top:10px">
            <?php if ($enc['completado']): ?>
                <span style="font-size:.78rem;color:var(--success);font-weight:600"><i class="mdi mdi-check-circle"></i> Ya respondiste</span>
            <?php elseif (!$enc['activa']): ?>
                <span style="font-size:.78rem;color:var(--text-muted)">Esta encuesta ya cerró.</span>
            <?php else: ?>
                <a href="<?= APP_URL ?>/encuestas/<?= $enc['id'] ?>/responder" class="btn-sm btn-primary">
                    <i class="mdi mdi-pencil-outline"></i> Responder
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
