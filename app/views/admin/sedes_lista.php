<?php
$pageTitle   = 'Sedes — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Sedes', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sedes</h1>
        <p class="page-subtitle">Si el colegio tiene más de una sede física, créalas aquí y luego asígnalas a cada grupo desde Grados y Grupos.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">

    <div class="card" style="padding:22px">
        <div class="section-title" style="margin-bottom:14px"><i class="mdi mdi-domain" style="color:var(--primary)"></i> Nueva sede</div>
        <form method="POST" action="<?= APP_URL ?>/sedes/guardar">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label>Nombre <span class="required">*</span></label>
                <input type="text" name="nombre" class="form-input" maxlength="100" placeholder="Ej: Sede Norte" required>
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-input" maxlength="200">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-input" maxlength="20">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Crear sede</button>
            </div>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        <?php if (!$sedes): ?>
            <div class="empty-state" style="padding:40px">
                <i class="mdi mdi-domain"></i>
                <p>No hay sedes registradas todavía.</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($sedes as $s): ?>
                <tr id="fila-sede-<?= $s['id'] ?>">
                    <td style="padding:10px 12px"><strong><?= e($s['nombre']) ?></strong></td>
                    <td style="padding:10px 12px;font-size:.85rem"><?= e($s['direccion'] ?? '—') ?></td>
                    <td style="padding:10px 12px;font-size:.85rem"><?= e($s['telefono'] ?? '—') ?></td>
                    <td style="padding:10px 12px">
                        <?= $s['activa'] ? '<span style="color:#16A34A">Activa</span>' : '<span style="color:#B91C1C">Inactiva</span>' ?>
                    </td>
                    <td style="padding:10px 12px;text-align:right;white-space:nowrap">
                        <button type="button" class="btn-icon" title="Editar" onclick="toggleEdicionSede(<?= $s['id'] ?>)"><i class="mdi mdi-pencil-outline"></i></button>
                        <form method="POST" action="<?= APP_URL ?>/sedes/<?= $s['id'] ?>/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar esta sede?');">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                            <button type="submit" class="btn-icon" title="Eliminar" style="color:var(--danger)"><i class="mdi mdi-trash-can-outline"></i></button>
                        </form>
                    </td>
                </tr>
                <tr id="fila-sede-edicion-<?= $s['id'] ?>" style="display:none">
                    <td colspan="5" style="padding:14px 12px;background:#F9FAFB">
                        <form method="POST" action="<?= APP_URL ?>/sedes/<?= $s['id'] ?>/actualizar" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                            <div class="form-group" style="margin:0"><label>Nombre</label><input type="text" name="nombre" class="form-input" value="<?= e($s['nombre']) ?>" required></div>
                            <div class="form-group" style="margin:0"><label>Dirección</label><input type="text" name="direccion" class="form-input" value="<?= e($s['direccion'] ?? '') ?>"></div>
                            <div class="form-group" style="margin:0"><label>Teléfono</label><input type="text" name="telefono" class="form-input" value="<?= e($s['telefono'] ?? '') ?>"></div>
                            <label style="display:flex;align-items:center;gap:6px;font-size:.85rem"><input type="checkbox" name="activa" <?= $s['activa'] ? 'checked' : '' ?>> Activa</label>
                            <button type="submit" class="btn-primary btn-sm">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleEdicionSede(id) {
    const fila = document.getElementById('fila-sede-edicion-' + id);
    fila.style.display = fila.style.display === 'none' ? '' : 'none';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
