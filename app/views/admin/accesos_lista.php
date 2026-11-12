<?php
$pageTitle   = 'Registro de accesos — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Registro de accesos', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-shield-account-outline"></i> Registro de accesos</h1>
        <p class="page-subtitle">Quién ha iniciado sesión, cuándo y desde qué dirección IP.</p>
    </div>
</div>

<?php if ($totalFallidosHoy >= 5): ?>
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:.84rem;color:#991B1B;margin-bottom:18px">
    <i class="mdi mdi-alert-outline"></i>
    <strong><?= $totalFallidosHoy ?> intentos de acceso fallidos hoy.</strong> Si no reconoces esta actividad, revisa los registros abajo.
</div>
<?php endif; ?>

<div class="card" style="padding:14px 18px;margin-bottom:14px">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <input type="text" name="q" value="<?= e($q) ?>" class="form-input" style="max-width:240px" placeholder="Buscar usuario...">
        <select name="filtro" class="form-input" style="width:auto">
            <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="exitosos" <?= $filtro === 'exitosos' ? 'selected' : '' ?>>Exitosos</option>
            <option value="fallidos" <?= $filtro === 'fallidos' ? 'selected' : '' ?>>Fallidos</option>
        </select>
        <button type="submit" class="btn-secondary btn-sm">Filtrar</button>
    </form>
</div>

<div class="card" style="padding:0">
<?php foreach ($accesos as $a): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.85rem">
                <?= $a['nombre'] ? e($a['nombre'] . ' ' . $a['apellido']) : e($a['username_intentado']) ?>
                <span style="font-size:.72rem;color:var(--text-muted);font-weight:400">(<?= e($a['username_intentado']) ?>)</span>
            </div>
            <div style="font-size:.74rem;color:var(--text-muted);font-family:monospace"><?= e($a['ip']) ?></div>
        </div>
        <div style="text-align:right">
            <?php if ($a['exitoso']): ?>
                <span style="font-size:.72rem;font-weight:700;color:var(--success)"><i class="mdi mdi-check-circle"></i> Exitoso</span>
            <?php else: ?>
                <span style="font-size:.72rem;font-weight:700;color:var(--danger)"><i class="mdi mdi-close-circle"></i> <?= e(str_replace('_', ' ', $a['motivo_fallo'])) ?></span>
            <?php endif; ?>
            <div style="font-size:.72rem;color:var(--text-muted)"><?= date('d/m/Y H:i:s', strtotime($a['created_at'])) ?></div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$accesos): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">Sin registros.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
