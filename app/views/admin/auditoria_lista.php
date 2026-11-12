<?php
$pageTitle   = 'Auditoría — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Auditoría', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-clipboard-text-clock-outline"></i> Auditoría de configuración</h1>
        <p class="page-subtitle">Cambios administrativos: materias, grados, grupos, asignaciones, usuarios y configuración del colegio.</p>
    </div>
</div>

<div class="card" style="padding:14px 18px;margin-bottom:14px">
    <form method="GET" style="display:flex;gap:10px">
        <input type="text" name="q" value="<?= e($q) ?>" class="form-input" placeholder="Buscar por acción, detalle o usuario...">
        <button type="submit" class="btn-secondary">Buscar</button>
    </form>
</div>

<div class="card" style="padding:0">
<?php foreach ($registros as $r): ?>
    <div style="padding:10px 18px;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;gap:10px">
            <span style="font-size:.7rem;font-weight:700;padding:1px 8px;border-radius:99px;background:var(--surface-2);color:var(--text-muted);font-family:monospace"><?= e($r['accion']) ?></span>
            <span style="font-size:.72rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
        </div>
        <div style="font-size:.85rem;margin-top:4px"><?= e($r['detalle']) ?></div>
        <div style="font-size:.74rem;color:var(--text-muted);margin-top:2px">Por <?= e($r['nombre'] . ' ' . $r['apellido']) ?></div>
    </div>
<?php endforeach; ?>
<?php if (!$registros): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">Sin registros de auditoría.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
