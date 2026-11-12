<?php
$pageTitle   = $part['titulo'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Encuestas', 'url' => APP_URL . '/encuestas'],
    ['label' => $part['titulo'], 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($part['titulo']) ?></h1>
        <?php if ($part['descripcion']): ?>
        <p class="page-subtitle"><?= e($part['descripcion']) ?></p>
        <?php endif; ?>
        <?php if ($part['anonima']): ?>
        <p style="font-size:.78rem;color:var(--text-muted);margin-top:4px"><i class="mdi mdi-incognito"></i> Esta encuesta es anónima.</p>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/encuestas/<?= $encuestaId ?>/responder" class="card" style="padding:24px;max-width:720px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <?php foreach ($preguntas as $p): ?>
    <div class="form-group" style="margin-bottom:22px">
        <label style="font-weight:700"><?= e($p['texto']) ?></label>

        <?php if ($p['tipo'] === 'opcion_unica'): ?>
            <?php foreach ($p['opciones'] as $op): ?>
            <label style="display:block;margin-top:6px;font-size:.88rem">
                <input type="radio" name="respuesta[<?= $p['id'] ?>]" value="<?= e($op) ?>"> <?= e($op) ?>
            </label>
            <?php endforeach; ?>

        <?php elseif ($p['tipo'] === 'opcion_multiple'): ?>
            <?php foreach ($p['opciones'] as $op): ?>
            <label style="display:block;margin-top:6px;font-size:.88rem">
                <input type="checkbox" name="respuesta[<?= $p['id'] ?>][]" value="<?= e($op) ?>"> <?= e($op) ?>
            </label>
            <?php endforeach; ?>

        <?php elseif ($p['tipo'] === 'escala'): ?>
            <div style="display:flex;gap:16px;margin-top:8px">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <label style="display:flex;flex-direction:column;align-items:center;font-size:.82rem">
                    <input type="radio" name="respuesta[<?= $p['id'] ?>]" value="<?= $i ?>"> <?= $i ?>
                </label>
                <?php endfor; ?>
            </div>

        <?php else: ?>
            <textarea name="respuesta[<?= $p['id'] ?>]" class="form-input" rows="3" style="margin-top:8px"></textarea>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-send-outline"></i> Enviar respuestas
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
