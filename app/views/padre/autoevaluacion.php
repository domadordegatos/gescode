<?php
$pageTitle   = 'Autoevaluación — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autoevaluación', 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-account-check-outline"></i> Autoevaluación</h1>
        <p class="page-subtitle">Algunos docentes solicitan que el estudiante se autoevalúe. Como acudiente, registra la nota en su nombre.</p>
    </div>
</div>

<div class="seccion-titulo" style="font-weight:700;margin-bottom:10px">Pendientes</div>
<?php if (!$pendientes): ?>
    <div class="card" style="padding:24px;text-align:center;color:var(--text-muted);margin-bottom:20px">
        No hay autoevaluaciones pendientes por el momento.
    </div>
<?php endif; ?>

<?php foreach ($pendientes as $p): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="font-size:.78rem;color:var(--text-muted)"><?= e($p['hijo_nombre']) ?> · <?= e($p['materia']) ?> · <?= e($p['periodo']) ?></div>
    <div style="font-weight:700;font-size:.9rem;margin-top:2px"><?= e($p['eval_nombre']) ?></div>
    <?php if ($p['logro']): ?>
    <div style="font-size:.82rem;color:var(--text-muted);margin-top:2px"><?= e($p['logro']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/autoevaluacion/<?= $p['estudiante_id'] ?>/<?= $p['evaluacion_id'] ?>/guardar"
          style="display:flex;gap:10px;align-items:center;margin-top:10px">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <input type="number" name="valor" class="form-input" style="width:100px" step="0.1"
               min="<?= $notaMin ?>" max="<?= $notaMax ?>" required placeholder="<?= $notaMin ?>-<?= $notaMax ?>">
        <button type="submit" class="btn-sm btn-primary"><i class="mdi mdi-content-save-outline"></i> Guardar</button>
    </form>
</div>
<?php endforeach; ?>

<div class="seccion-titulo" style="font-weight:700;margin:24px 0 10px">Ya registradas</div>
<?php if (!$respondidas): ?>
    <div style="color:var(--text-muted);font-size:.85rem">Aún no has registrado ninguna.</div>
<?php endif; ?>
<?php foreach ($respondidas as $r): ?>
<div class="card" style="padding:14px 18px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
    <div>
        <div style="font-size:.78rem;color:var(--text-muted)"><?= e($r['hijo_nombre']) ?> · <?= e($r['materia']) ?> · <?= e($r['periodo']) ?></div>
        <div style="font-weight:600;font-size:.88rem"><?= e($r['eval_nombre']) ?></div>
    </div>
    <div style="font-weight:700;font-size:1.1rem;color:var(--primary)"><?= number_format((float)$r['valor'], 1) ?></div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
