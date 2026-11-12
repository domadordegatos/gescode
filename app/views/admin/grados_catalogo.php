<?php
$pageTitle   = 'Catálogo de grados — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => 'Catálogo de grados', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-stairs"></i> Catálogo de grados</h1>
        <p class="page-subtitle">Niveles educativos del colegio (ej: Primero, Segundo...). El nivel determina el orden de promoción.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px">

<div class="card" style="padding:0">
<?php foreach ($grados as $g): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div style="flex:1">
            <div style="font-weight:600;font-size:.88rem"><?= e($g['nombre']) ?> <span style="color:var(--text-muted);font-size:.76rem">(nivel <?= $g['nivel'] ?>)</span></div>
            <div style="font-size:.74rem;color:var(--text-muted)"><?= $g['total_grupos'] ?> grupo(s) creado(s) en total</div>
        </div>
        <div style="display:flex;gap:6px">
            <button type="button" class="btn-sm btn-outline" onclick="toggleEdicion(<?= $g['id'] ?>)"><i class="mdi mdi-pencil-outline"></i></button>
            <form method="POST" action="<?= APP_URL ?>/grados/catalogo/<?= $g['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar este grado?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/grados/catalogo/<?= $g['id'] ?>/actualizar" id="edicion-<?= $g['id'] ?>" style="display:none;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div style="display:flex;gap:8px">
            <input type="text" name="nombre" class="form-input" value="<?= e($g['nombre']) ?>" required style="flex:2">
            <input type="number" name="nivel" class="form-input" value="<?= $g['nivel'] ?>" required min="1" style="flex:1" title="Nivel (orden de promoción)">
            <button type="submit" class="btn-sm btn-primary">Guardar</button>
        </div>
    </form>
<?php endforeach; ?>
</div>

<div class="card" style="padding:20px 22px;align-self:start">
    <div style="font-weight:700;margin-bottom:12px">Nuevo grado</div>
    <form method="POST" action="<?= APP_URL ?>/grados/catalogo/guardar">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" class="form-input" required placeholder="Ej: Preescolar">
        </div>
        <div class="form-group">
            <label>Nivel <span class="required">*</span></label>
            <input type="number" name="nivel" class="form-input" required min="1" placeholder="Ej: 0 o 12">
            <small class="form-hint">Determina el orden de promoción (nivel+1 al pasar de año). No puede repetirse con otro grado.</small>
        </div>
        <button type="submit" class="btn-primary" style="width:100%"><i class="mdi mdi-plus"></i> Crear grado</button>
    </form>
</div>

</div>

<script>
function toggleEdicion(id) {
    const el = document.getElementById('edicion-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
