<?php
$pageTitle   = $encuesta['titulo'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Encuestas', 'url' => APP_URL . '/encuestas'],
    ['label' => 'Resultados', 'url' => ''],
];
$noLeidos = 0;
$pct = $totalInvitados > 0 ? round($totalCompletados / $totalInvitados * 100) : 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($encuesta['titulo']) ?></h1>
        <p class="page-subtitle">
            <?= $totalCompletados ?> de <?= $totalInvitados ?> respuestas (<?= $pct ?>%)
            <?= $encuesta['anonima'] ? '· Resultados anónimos' : '' ?>
        </p>
    </div>
    <?php if ($encuesta['activa']): ?>
    <form method="POST" action="<?= APP_URL ?>/encuestas/<?= $encuesta['id'] ?>/cerrar" onsubmit="return confirm('¿Cerrar esta encuesta? No se podrán registrar más respuestas.')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-secondary"><i class="mdi mdi-lock-outline"></i> Cerrar encuesta</button>
    </form>
    <?php endif; ?>
</div>

<?php foreach ($preguntas as $p): ?>
<div class="card" style="padding:18px 20px;margin-bottom:14px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:10px"><?= e($p['texto']) ?></div>
    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:10px"><?= $p['total_respuestas'] ?> respuestas</div>

    <?php if (in_array($p['tipo'], ['opcion_unica', 'opcion_multiple', 'escala'], true)): ?>
        <?php $max = max(array_merge([1], $p['conteo'] ?? [])); ?>
        <?php foreach (($p['conteo'] ?? []) as $opcion => $n): ?>
        <div style="margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
                <span><?= e($opcion) ?></span><span style="color:var(--text-muted)"><?= $n ?></span>
            </div>
            <div style="background:var(--surface-2);border-radius:6px;height:8px;overflow:hidden">
                <div style="background:var(--primary);height:100%;width:<?= $max > 0 ? round($n / $max * 100) : 0 ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!($p['conteo'] ?? [])): ?>
            <div style="color:var(--text-muted);font-size:.82rem">Sin respuestas todavía.</div>
        <?php endif; ?>

    <?php else: ?>
        <?php foreach (($p['respuestas_texto'] ?? []) as $r): ?>
        <div style="padding:8px 10px;background:var(--surface-2);border-radius:6px;font-size:.85rem;margin-bottom:6px">
            <?= nl2br(e($r)) ?>
        </div>
        <?php endforeach; ?>
        <?php if (!($p['respuestas_texto'] ?? [])): ?>
            <div style="color:var(--text-muted);font-size:.82rem">Sin respuestas todavía.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
