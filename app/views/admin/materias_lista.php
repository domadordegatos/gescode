<?php
$pageTitle   = 'Materias — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Materias', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-book-open-page-variant-outline"></i> Materias</h1>
        <p class="page-subtitle">Catálogo de materias disponibles para asignar a docentes y grupos.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px">

<div class="card" style="padding:0">
<?php foreach ($materias as $m): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)" id="fila-<?= $m['id'] ?>">
        <div style="flex:1" id="vista-<?= $m['id'] ?>">
            <div style="font-weight:600;font-size:.88rem"><?= e($m['nombre']) ?> <?= $m['codigo'] ? '<span style="color:var(--text-muted);font-size:.76rem">(' . e($m['codigo']) . ')</span>' : '' ?></div>
            <div style="font-size:.74rem;color:var(--text-muted)"><?= $m['total_asignaciones'] ?> asignación(es) activa(s)</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <button type="button" class="btn-sm btn-outline" onclick="toggleEdicion(<?= $m['id'] ?>)"><i class="mdi mdi-pencil-outline"></i></button>
            <form method="POST" action="<?= APP_URL ?>/materias/<?= $m['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar esta materia?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/materias/<?= $m['id'] ?>/actualizar" id="edicion-<?= $m['id'] ?>" style="display:none;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div style="display:flex;gap:8px">
            <input type="text" name="nombre" class="form-input" value="<?= e($m['nombre']) ?>" required style="flex:2">
            <input type="text" name="codigo" class="form-input" value="<?= e($m['codigo'] ?? '') ?>" placeholder="Código" style="flex:1">
            <button type="submit" class="btn-sm btn-primary">Guardar</button>
        </div>
    </form>
<?php endforeach; ?>
<?php if (!$materias): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">No hay materias registradas.</div>
<?php endif; ?>
</div>

<div class="card" style="padding:20px 22px;align-self:start">
    <div style="font-weight:700;margin-bottom:12px">Nueva materia</div>
    <form method="POST" action="<?= APP_URL ?>/materias/guardar">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" class="form-input" required placeholder="Ej: Filosofía">
        </div>
        <div class="form-group">
            <label>Código (opcional)</label>
            <input type="text" name="codigo" class="form-input" placeholder="Ej: FIL-10">
        </div>
        <button type="submit" class="btn-primary" style="width:100%"><i class="mdi mdi-plus"></i> Crear materia</button>
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
