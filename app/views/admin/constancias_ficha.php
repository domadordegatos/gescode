<?php
$pageTitle   = 'Constancias de ' . $estudiante['nombre'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Constancias', 'url' => APP_URL . '/constancias'],
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

<div style="display:flex;gap:18px;flex-wrap:wrap">
    <div class="card" style="padding:20px 22px;flex:1;min-width:320px;max-width:480px">
        <div style="font-weight:600;margin-bottom:12px">Generar nueva constancia</div>
        <form method="POST" action="<?= APP_URL ?>/constancias/<?= $estudianteId ?>/generar" target="_blank">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

            <label class="form-label">Tipo de documento</label>
            <select name="tipo" class="form-input" style="margin-bottom:12px">
                <?php foreach ($tipos as $key => $label): ?>
                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label">Texto adicional (opcional)</label>
            <textarea name="texto_extra" class="form-input" rows="3" placeholder="Ej: para efectos de afiliación a EPS / trámite de visa / etc." style="margin-bottom:14px"></textarea>

            <button type="submit" class="btn-primary">
                <i class="mdi mdi-file-pdf-box"></i> Generar PDF
            </button>
        </form>
    </div>

    <div class="card" style="padding:20px 22px;flex:1;min-width:280px">
        <div style="font-weight:600;margin-bottom:10px">Historial reciente</div>
        <?php if (!$historial): ?>
            <div style="color:var(--text-muted);font-size:.85rem">Aún no se han generado constancias para este estudiante.</div>
        <?php else: ?>
            <?php foreach ($historial as $h): ?>
            <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem">
                <div style="font-weight:600"><?= e($tipos[$h['tipo']] ?? $h['tipo']) ?></div>
                <div style="color:var(--text-muted);font-size:.78rem">
                    <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?> · generada por <?= e($h['gen_nombre'] . ' ' . $h['gen_apellido']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
