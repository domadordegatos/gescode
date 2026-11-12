<?php
$pageTitle   = 'Preferencias de notificaciones — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Mi Perfil', 'url' => APP_URL . '/perfil'],
    ['label' => 'Notificaciones', 'url' => ''],
];
ob_start();
?>

<div class="page-header" style="margin-bottom:24px">
    <div>
        <h1 class="page-title" style="font-size:1.35rem">Preferencias de notificaciones</h1>
        <p class="page-subtitle">Elige qué avisos quieres recibir en tu correo electrónico.</p>
    </div>
    <a href="<?= APP_URL ?>/perfil" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Volver al perfil
    </a>
</div>

<div class="card" style="max-width:600px">
    <form method="POST" action="<?= APP_URL ?>/perfil/notificaciones/guardar">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

        <div style="padding:24px 28px">
            <p style="margin:0 0 20px;color:var(--text-muted);font-size:.9rem">
                Correo registrado: <strong><?= e($email) ?></strong>
            </p>

            <?php foreach ($tipos as $tipo => $label): ?>
            <?php $checked = $prefs[$tipo] ?? true; ?>
            <label style="display:flex;align-items:center;gap:14px;padding:14px 0;
                          border-bottom:1px solid var(--border);cursor:pointer">
                <div style="position:relative;flex-shrink:0">
                    <input type="checkbox" name="tipos[]" value="<?= $tipo ?>"
                           <?= $checked ? 'checked' : '' ?>
                           style="width:18px;height:18px;cursor:pointer;accent-color:var(--primary)">
                </div>
                <div>
                    <div style="font-weight:600;font-size:.92rem"><?= e($label) ?></div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                        <?= e($descripciones[$tipo] ?? '') ?>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="padding:16px 28px;background:var(--surface-2);border-top:1px solid var(--border);
                    display:flex;justify-content:flex-end;gap:10px">
            <a href="<?= APP_URL ?>/perfil" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <i class="mdi mdi-content-save-outline"></i> Guardar preferencias
            </button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
