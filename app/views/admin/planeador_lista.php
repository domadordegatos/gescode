<?php
$pageTitle   = 'Planeador de clases — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Planeador de clases', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-notebook-edit-outline"></i> Planeador de clases</h1>
        <p class="page-subtitle">Planeaciones registradas por los docentes (solo lectura).</p>
    </div>
</div>

<div class="card" style="padding:14px 18px;margin-bottom:14px">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
        <label style="font-size:.82rem;font-weight:600">Período:</label>
        <select name="periodo" class="form-input" style="width:auto" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($periodos as $per): ?>
            <option value="<?= $per['id'] ?>" <?= $periodoId === (int)$per['id'] ? 'selected' : '' ?>><?= e($per['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$planeaciones): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        No hay planeaciones registradas aún.
    </div>
<?php endif; ?>

<?php foreach ($planeaciones as $p): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div>
            <span class="periodo-pill" style="font-size:.72rem"><?= e($p['periodo']) ?></span>
            <div style="font-weight:700;font-size:.92rem;margin-top:6px"><?= e($p['tema']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px">
                <?= e($p['materia']) ?> · <?= e($p['grado'] . ' ' . $p['grupo']) ?> ·
                Docente: <?= e($p['doc_nombre'] . ' ' . $p['doc_apellido']) ?>
            </div>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted)">
            <?= $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : date('d/m/Y', strtotime($p['created_at'])) ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
