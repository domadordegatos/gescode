<?php
$pageTitle   = 'Asignaciones — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => 'Asignaciones', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-account-tie-outline"></i> Asignaciones de docentes</h1>
        <p class="page-subtitle">Qué docente imparte cada materia en cada grupo.</p>
    </div>
</div>

<div class="card" style="padding:14px 18px;margin-bottom:16px">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
        <label style="font-size:.82rem;font-weight:600">Grupo:</label>
        <select name="grupo_id" class="form-input" style="width:auto" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($grupos as $g): ?>
            <option value="<?= $g['id'] ?>" <?= $grupoFiltro === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['grado'] . ' ' . $g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px">

<div class="card" style="padding:0">
<?php foreach ($asignaciones as $a): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div style="flex:1">
            <div style="font-weight:600;font-size:.88rem"><?= e($a['materia']) ?></div>
            <div style="font-size:.76rem;color:var(--text-muted)"><?= e($a['grado'] . ' ' . $a['grupo']) ?> · <?= e($a['doc_nombre'] . ' ' . $a['doc_apellido']) ?></div>
        </div>
        <div style="display:flex;gap:6px">
            <button type="button" class="btn-sm btn-outline" onclick="toggleEdicion(<?= $a['id'] ?>)"><i class="mdi mdi-pencil-outline"></i></button>
            <form method="POST" action="<?= APP_URL ?>/asignaciones/<?= $a['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar esta asignación?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/asignaciones/<?= $a['id'] ?>/actualizar" id="edicion-<?= $a['id'] ?>" style="display:none;padding:12px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div style="display:flex;gap:8px">
            <select name="docente_id" class="form-input" style="flex:1">
                <?php foreach ($docentes as $d): ?>
                <option value="<?= $d['id'] ?>" <?= (int)$a['docente_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-sm btn-primary">Cambiar docente</button>
        </div>
    </form>
<?php endforeach; ?>
<?php if (!$asignaciones): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">No hay asignaciones registradas<?= $grupoFiltro ? ' para este grupo' : '' ?>.</div>
<?php endif; ?>
</div>

<div class="card" style="padding:20px 22px;align-self:start">
    <div style="font-weight:700;margin-bottom:12px">Nueva asignación</div>
    <form method="POST" action="<?= APP_URL ?>/asignaciones/guardar">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Grupo <span class="required">*</span></label>
            <select name="grupo_id" class="form-input" required>
                <option value="">Selecciona...</option>
                <?php foreach ($grupos as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $grupoFiltro === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['grado'] . ' ' . $g['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Materia <span class="required">*</span></label>
            <select name="materia_id" class="form-input" required>
                <option value="">Selecciona...</option>
                <?php foreach ($materias as $m): ?>
                <option value="<?= $m['id'] ?>"><?= e($m['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Docente <span class="required">*</span></label>
            <select name="docente_id" class="form-input" required>
                <option value="">Selecciona...</option>
                <?php foreach ($docentes as $d): ?>
                <option value="<?= $d['id'] ?>"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="width:100%"><i class="mdi mdi-plus"></i> Crear asignación</button>
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
