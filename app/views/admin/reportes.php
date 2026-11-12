<?php
$pageTitle   = 'Estadísticas — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Estadísticas', 'url' => ''],
];
$noLeidos = 0;

if (!function_exists('barra')) {
    function barra(float $valor, float $max, string $color = 'var(--primary)'): string {
        $pct = $max > 0 ? min(100, round($valor / $max * 100)) : 0;
        return "<div style=\"background:var(--surface-2);border-radius:6px;height:8px;overflow:hidden\">
                    <div style=\"background:$color;height:100%;width:{$pct}%\"></div>
                </div>";
    }
}

$tipoObsColor = [
    'academica'     => '#2563EB', 'convivencia' => '#9333EA', 'logro' => '#16A34A',
    'disciplinaria' => '#B91C1C', 'otro' => '#6B7280',
];
$tipoObsLabel = [
    'academica'     => 'Académica', 'convivencia' => 'Convivencia', 'logro' => 'Logro',
    'disciplinaria' => 'Disciplinaria', 'otro' => 'Otro',
];
$pqrsTipoLabel = ['peticion'=>'Petición','queja'=>'Queja','reclamo'=>'Reclamo','sugerencia'=>'Sugerencia','felicitacion'=>'Felicitación'];
$pqrsEstadoLabel = ['radicado'=>'Radicado','en_tramite'=>'En trámite','resuelto'=>'Resuelto','cerrado'=>'Cerrado'];
$pqrsEstadoColor = ['radicado'=>'#6B7280','en_tramite'=>'#D97706','resuelto'=>'#16A34A','cerrado'=>'#374151'];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-chart-areaspline"></i> Estadísticas</h1>
        <p class="page-subtitle">Vista general del año lectivo activo<?= $periodoActivo ? ' · Período: ' . e($periodoActivo['nombre']) : '' ?>.</p>
    </div>
    <a href="<?= APP_URL ?>/reportes/exportar" target="_blank" class="btn-secondary">
        <i class="mdi mdi-file-pdf-box"></i> Exportar como PDF
    </a>
    <a href="<?= APP_URL ?>/reportes/exportar-excel" class="btn-secondary">
        <i class="mdi mdi-file-excel-box"></i> Exportar a Excel
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px">

<!-- Promedio por grado -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-school-outline"></i> Promedio por grado (período activo)</div>
    <?php if (!$promedioPorGrado): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin notas registradas en el período activo.</div>
    <?php endif; ?>
    <?php foreach ($promedioPorGrado as $g): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($g['grado']) ?> <span style="color:var(--text-muted)">(<?= $g['total_est'] ?> est.)</span></span>
            <span style="font-weight:700"><?= number_format((float)$g['promedio'], 1) ?></span>
        </div>
        <?= barra((float)$g['promedio'], $notaMax) ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Asistencia global -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-calendar-check-outline"></i> Asistencia (año activo)</div>
    <?php
        $totalAsist = array_sum($asistenciaGlobal);
        $colAsist = ['presente' => '#16A34A', 'tarde' => '#D97706', 'excusa' => '#2563EB', 'ausente' => '#DC2626'];
        $labAsist = ['presente' => 'Presente', 'tarde' => 'Tarde', 'excusa' => 'Excusa', 'ausente' => 'Ausente', 'incapacidad' => 'Incapacidad'];
    ?>
    <?php if ($totalAsist === 0): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin registros de asistencia aún.</div>
    <?php endif; ?>
    <?php foreach ($asistenciaGlobal as $estado => $n): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= $labAsist[$estado] ?></span><span style="font-weight:700"><?= $n ?> <span style="color:var(--text-muted);font-weight:400">(<?= $totalAsist>0?round($n/$totalAsist*100):0 ?>%)</span></span>
        </div>
        <?= barra($n, $totalAsist, $colAsist[$estado]) ?>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($flags['permite_observador_consolidado'])): ?>
<!-- Observaciones por tipo -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-timeline-text-outline"></i> Observaciones por tipo (año activo)</div>
    <?php $totalObs = array_sum($observacionesPorTipo); ?>
    <?php if ($totalObs === 0): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin observaciones registradas.</div>
    <?php endif; ?>
    <?php foreach ($observacionesPorTipo as $tipo => $n): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($tipoObsLabel[$tipo] ?? $tipo) ?></span><span style="font-weight:700"><?= $n ?></span>
        </div>
        <?= barra($n, $totalObs, $tipoObsColor[$tipo] ?? '#6B7280') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_planeador_clases'])): ?>
<!-- Planeaciones por período -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-notebook-edit-outline"></i> Planeaciones registradas por período</div>
    <?php if (!$planeacionesPorPeriodo): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin planeaciones registradas aún.</div>
    <?php endif; ?>
    <?php $maxPlan = max(array_merge([1], array_column($planeacionesPorPeriodo, 'total'))); ?>
    <?php foreach ($planeacionesPorPeriodo as $p): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($p['periodo']) ?></span><span style="font-weight:700"><?= $p['total'] ?></span>
        </div>
        <?= barra((float)$p['total'], $maxPlan) ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_pqrs']) && $pqrsStats): ?>
<!-- PQRS -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-file-document-edit-outline"></i> PQRS por estado</div>
    <?php $totalPqrs = array_sum($pqrsStats['por_estado']); ?>
    <?php if ($totalPqrs === 0): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin solicitudes PQRS registradas.</div>
    <?php endif; ?>
    <?php foreach ($pqrsStats['por_estado'] as $estado => $n): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($pqrsEstadoLabel[$estado] ?? $estado) ?></span><span style="font-weight:700"><?= $n ?></span>
        </div>
        <?= barra($n, $totalPqrs, $pqrsEstadoColor[$estado] ?? '#6B7280') ?>
    </div>
    <?php endforeach; ?>

    <?php if ($pqrsStats['por_tipo']): ?>
    <div style="font-size:.78rem;color:var(--text-muted);margin-top:14px;padding-top:10px;border-top:1px solid var(--border)">
        Por tipo:
        <?php $partes = []; foreach ($pqrsStats['por_tipo'] as $tipo => $n) { $partes[] = ($pqrsTipoLabel[$tipo] ?? $tipo) . ": $n"; } ?>
        <?= e(implode(' · ', $partes)) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_circulares']) && $circularesStats): ?>
<!-- Circulares -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-bullhorn-outline"></i> Últimas circulares (tasa de lectura)</div>
    <?php foreach ($circularesStats as $c): $pct = $c['total_invitados'] > 0 ? round($c['total_leidos'] / $c['total_invitados'] * 100) : 0; ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($c['titulo']) ?></span>
            <span style="font-weight:700"><?= $c['total_leidos'] ?>/<?= $c['total_invitados'] ?> (<?= $pct ?>%)</span>
        </div>
        <?= barra($pct, 100, '#0369A1') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_encuestas']) && $encuestasStats): ?>
<!-- Encuestas -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-poll"></i> Participación en encuestas</div>
    <?php foreach ($encuestasStats as $enc): $pct = $enc['total_invitados'] > 0 ? round($enc['total_completados'] / $enc['total_invitados'] * 100) : 0; ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($enc['titulo']) ?> <?= !$enc['activa'] ? '<span style="color:var(--text-muted)">(cerrada)</span>' : '' ?></span>
            <span style="font-weight:700"><?= $enc['total_completados'] ?>/<?= $enc['total_invitados'] ?> (<?= $pct ?>%)</span>
        </div>
        <?= barra($pct, 100, '#4D7C0F') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_autorizaciones']) && $autorizacionesStats): ?>
<!-- Autorizaciones -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-file-sign"></i> Autorizaciones firmadas</div>
    <?php foreach ($autorizacionesStats as $a): $pct = $a['total_invitados'] > 0 ? round($a['total_firmados'] / $a['total_invitados'] * 100) : 0; ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($a['titulo']) ?> <?= !$a['activa'] ? '<span style="color:var(--text-muted)">(cerrada)</span>' : '' ?></span>
            <span style="font-weight:700"><?= $a['total_firmados'] ?>/<?= $a['total_invitados'] ?> (<?= $pct ?>%)</span>
        </div>
        <?= barra($pct, 100, '#1D4ED8') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_retiro_anticipado']) && $retirosStats): ?>
<!-- Retiros anticipados -->
<div class="card" style="padding:20px 22px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:12px"><i class="mdi mdi-exit-run"></i> Retiros anticipados (<?= $retirosStats['total'] ?> en total)</div>
    <?php if (!$retirosStats['por_mes']): ?>
        <div style="color:var(--text-muted);font-size:.82rem">Sin retiros registrados.</div>
    <?php endif; ?>
    <?php $maxRet = max(array_merge([1], array_column($retirosStats['por_mes'], 'total'))); ?>
    <?php foreach ($retirosStats['por_mes'] as $r): ?>
    <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px">
            <span><?= e($r['mes']) ?></span><span style="font-weight:700"><?= $r['total'] ?></span>
        </div>
        <?= barra((float)$r['total'], $maxRet, '#9A3412') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
