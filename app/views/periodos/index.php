<?php
$pageTitle   = 'Períodos — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio',   'url' => APP_URL . '/dashboard'],
    ['label' => 'Períodos', 'url' => ''],
];
$noLeidos = 0;
ob_start();

$pct_total = round((float)$total_pct, 2);
$pct_ok    = abs($pct_total - 100) < 0.01;
$pct_over  = $pct_total > 100.01;
$bar_color = $pct_ok ? 'var(--success)' : ($pct_over ? 'var(--danger)' : 'var(--warning)');
$bar_width = min(100, $pct_total);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Períodos escolares</h1>
        <p class="page-subtitle">Año lectivo: <strong><?= e($ano['nombre']) ?></strong></p>
    </div>
    <a href="<?= APP_URL ?>/periodos/nuevo" class="btn-primary">
        <i class="mdi mdi-plus"></i> Agregar período
    </a>
</div>

<!-- Barra de porcentaje total -->
<div class="card" style="padding:18px 22px;margin-bottom:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span style="font-weight:700;font-size:.9rem">Suma de porcentajes:</span>
            <span style="font-size:1.6rem;font-weight:800;color:<?= $bar_color ?>"><?= $pct_total ?>%</span>
            <?php if ($pct_ok): ?>
                <span style="color:var(--success);font-weight:700">
                    <i class="mdi mdi-check-circle"></i> Suma correcta
                </span>
            <?php elseif ($pct_over): ?>
                <span style="color:var(--danger);font-weight:700">
                    <i class="mdi mdi-alert-circle"></i> Supera el 100% (+<?= round($pct_total-100,2) ?>%)
                </span>
            <?php else: ?>
                <span style="color:var(--warning);font-weight:700">
                    <i class="mdi mdi-alert"></i> Faltan <?= round(100-$pct_total,2) ?>%
                </span>
            <?php endif; ?>
        </div>
        <span style="font-size:.82rem;color:var(--text-muted)">
            <?= count($periodos) ?> período<?= count($periodos)!==1?'s':'' ?> · Año <?= e($ano['nombre']) ?>
        </span>
    </div>
    <div style="background:var(--border);border-radius:20px;height:10px;overflow:hidden">
        <div style="height:100%;width:<?= $bar_width ?>%;background:<?= $bar_color ?>;border-radius:20px;transition:width .4s"></div>
    </div>
    <?php if (!$pct_ok && count($periodos)): ?>
        <p style="font-size:.78rem;color:var(--text-muted);margin-top:6px">
            <i class="mdi mdi-information-outline"></i>
            Los porcentajes deben sumar exactamente 100% para que los promedios sean correctos.
        </p>
    <?php endif; ?>
</div>

<?php if (empty($periodos)): ?>
<div class="card">
    <div class="empty-state">
        <i class="mdi mdi-calendar-range"></i>
        <p>No hay períodos creados para el año <strong><?= e($ano['nombre']) ?></strong>.</p>
        <a href="<?= APP_URL ?>/periodos/nuevo" class="btn-primary" style="margin-top:16px">
            <i class="mdi mdi-plus"></i> Crear primer período
        </a>
    </div>
</div>
<?php else: ?>

<div class="periodos-list">
    <?php foreach ($periodos as $p):
        $p += ['porcentaje_ano'=>0,'permite_notas'=>0,'cierre_notas'=>null,'max_notas'=>null,'fecha_evaluaciones'=>null];
        $desde = $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : null;
        $hasta = $p['fecha_fin']    ? date('d/m/Y', strtotime($p['fecha_fin']))    : null;
        $pct   = (float)$p['porcentaje_ano'];
    ?>
    <div class="periodo-card <?= $p['activo'] ? 'periodo-card--activo' : '' ?>">

        <div class="periodo-num"><?= (int)$p['numero'] ?></div>

        <div class="periodo-main">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="periodo-nombre"><?= e($p['nombre']) ?></span>
                <?php if ($p['activo']): ?>
                    <span class="badge-pill badge-pill--primary"><i class="mdi mdi-circle" style="font-size:8px"></i> Activo</span>
                <?php endif; ?>
                <?php if ($p['permite_notas']): ?>
                    <span class="badge-pill badge-pill--success"><i class="mdi mdi-pencil-outline"></i> Notas abiertas</span>
                <?php else: ?>
                    <span class="badge-pill badge-pill--muted"><i class="mdi mdi-lock-outline"></i> Notas cerradas</span>
                <?php endif; ?>
            </div>
            <div class="periodo-dates">
                <?php if ($desde || $hasta): ?>
                    <span><i class="mdi mdi-calendar-range-outline"></i> <?= $desde ?? '—' ?> → <?= $hasta ?? '—' ?></span>
                <?php endif; ?>
                <?php if ($p['cierre_notas']): ?>
                    <span><i class="mdi mdi-calendar-lock-outline"></i> Cierre notas: <?= date('d/m/Y', strtotime($p['cierre_notas'])) ?></span>
                <?php endif; ?>
                <?php if ($p['max_notas']): ?>
                    <span><i class="mdi mdi-calendar-clock-outline"></i> Máx. subida: <?= date('d/m/Y', strtotime($p['max_notas'])) ?></span>
                <?php endif; ?>
                <?php if ($p['fecha_evaluaciones']): ?>
                    <span><i class="mdi mdi-calendar-check-outline"></i> Evaluaciones: <?= date('d/m/Y', strtotime($p['fecha_evaluaciones'])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="periodo-pct">
            <span><?= number_format($pct, 2) ?>%</span>
            <div class="periodo-pct-bar">
                <div style="width:<?= min(100,$pct) ?>%;background:var(--primary)"></div>
            </div>
        </div>

        <div class="periodo-actions">
            <form method="POST" action="<?= APP_URL ?>/periodos/<?= $p['id'] ?>/toggle-activo" style="display:inline">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-icon <?= $p['activo'] ? 'btn-icon--primary' : '' ?>"
                        title="<?= $p['activo'] ? 'Desactivar' : 'Marcar como activo' ?>">
                    <i class="mdi mdi-star<?= $p['activo'] ? '' : '-outline' ?>"></i>
                </button>
            </form>
            <form method="POST" action="<?= APP_URL ?>/periodos/<?= $p['id'] ?>/toggle-notas" style="display:inline">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-icon <?= $p['permite_notas'] ? 'btn-icon--success' : '' ?>"
                        title="<?= $p['permite_notas'] ? 'Cerrar notas' : 'Abrir notas' ?>">
                    <i class="mdi mdi-<?= $p['permite_notas'] ? 'lock-open-outline' : 'lock-outline' ?>"></i>
                </button>
            </form>
            <a href="<?= APP_URL ?>/periodos/<?= $p['id'] ?>/editar" class="btn-icon" title="Editar">
                <i class="mdi mdi-pencil-outline"></i>
            </a>
            <form method="POST" action="<?= APP_URL ?>/periodos/<?= $p['id'] ?>/eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar «<?= e($p['nombre']) ?>»?\nSe perderán todas las notas asociadas.')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar">
                    <i class="mdi mdi-trash-can-outline"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
