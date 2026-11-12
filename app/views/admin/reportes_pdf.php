<?php
/** @var array|null $colegio */
/** @var array|null $periodoActivo */
/** @var array $flags */
/** @var string $fecha_emision */

$colorPrimario = $colegio['color_primario'] ?? '#0E0EB3';
$logoUrl = !empty($colegio['logo']) ? (APP_URL . '/uploads/imagenes/' . $colegio['logo']) : null;

$datosInst = array_filter([
    !empty($colegio['nit'])       ? 'NIT ' . $colegio['nit']        : null,
    !empty($colegio['dane'])      ? 'DANE ' . $colegio['dane']      : null,
    !empty($colegio['direccion']) ? $colegio['direccion'] . (!empty($colegio['ciudad']) ? ', ' . $colegio['ciudad'] : '') : null,
]);

$tipoObsLabel = ['academica'=>'Académica','convivencia'=>'Convivencia','logro'=>'Logro','disciplinaria'=>'Disciplinaria','otro'=>'Otro'];
$pqrsTipoLabel = ['peticion'=>'Petición','queja'=>'Queja','reclamo'=>'Reclamo','sugerencia'=>'Sugerencia','felicitacion'=>'Felicitación'];
$pqrsEstadoLabel = ['radicado'=>'Radicado','en_tramite'=>'En trámite','resuelto'=>'Resuelto','cerrado'=>'Cerrado'];

function barraPdf(float $valor, float $max, string $color): string {
    $pct = $max > 0 ? min(100, round($valor / $max * 100)) : 0;
    return "<div style=\"background:#eee;border-radius:4px;height:7px;overflow:hidden;margin-top:2px\">
                <div style=\"background:$color;height:100%;width:{$pct}%\"></div>
            </div>";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 70px 36px 50px 36px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    .encabezado { display: table; width: 100%; margin-bottom: 16px; border-bottom: 2px solid <?= e($colorPrimario) ?>; padding-bottom: 10px; }
    .encabezado .logo { display: table-cell; width: 70px; vertical-align: middle; }
    .encabezado .logo img { width: 60px; }
    .encabezado .titulo { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .encabezado .titulo h1 { font-size: 15px; margin: 0; color: <?= e($colorPrimario) ?>; }
    .encabezado .titulo p { margin: 2px 0 0; font-size: 9px; color: #555; }
    .encabezado .titulo .datos-oficiales { margin-top: 3px; font-size: 7.8px; color: #777; }
    .seccion { margin-bottom: 16px; page-break-inside: avoid; }
    .seccion-titulo { font-size: 11.5px; font-weight: bold; color: <?= e($colorPrimario) ?>; margin-bottom: 8px; }
    .fila { margin-bottom: 7px; font-size: 9.5px; }
    .fila .top { display: flex; justify-content: space-between; }
    .pie { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #999; text-align: center; }
</style>
</head>
<body>

<div class="encabezado">
    <?php if ($logoUrl): ?><div class="logo"><img src="<?= e($logoUrl) ?>"></div><?php endif; ?>
    <div class="titulo">
        <h1><?= e($colegio['nombre'] ?? 'Institución Educativa') ?></h1>
        <p>Reporte de estadísticas institucionales<?= $periodoActivo ? ' — Período: ' . e($periodoActivo['nombre']) : '' ?></p>
        <?php if ($datosInst): ?><div class="datos-oficiales"><?= e(implode(' · ', $datosInst)) ?></div><?php endif; ?>
    </div>
</div>

<div class="seccion">
    <div class="seccion-titulo">Promedio por grado (período activo)</div>
    <?php foreach ($promedioPorGrado as $g): ?>
    <div class="fila">
        <div class="top"><span><?= e($g['grado']) ?> (<?= $g['total_est'] ?> est.)</span><strong><?= number_format((float)$g['promedio'], 1) ?></strong></div>
        <?= barraPdf((float)$g['promedio'], $notaMax, $colorPrimario) ?>
    </div>
    <?php endforeach; ?>
    <?php if (!$promedioPorGrado): ?><div style="color:#999">Sin notas registradas.</div><?php endif; ?>
</div>

<div class="seccion">
    <div class="seccion-titulo">Asistencia (año activo)</div>
    <?php $totalAsist = array_sum($asistenciaGlobal); $labAsist = ['presente'=>'Presente','tarde'=>'Tarde','excusa'=>'Excusa','ausente'=>'Ausente','incapacidad'=>'Incapacidad']; ?>
    <?php foreach ($asistenciaGlobal as $estado => $n): ?>
    <div class="fila">
        <div class="top"><span><?= $labAsist[$estado] ?></span><strong><?= $n ?> (<?= $totalAsist>0?round($n/$totalAsist*100):0 ?>%)</strong></div>
        <?= barraPdf($n, $totalAsist, '#16A34A') ?>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($flags['permite_observador_consolidado']) && $observacionesPorTipo): ?>
<div class="seccion">
    <div class="seccion-titulo">Observaciones por tipo</div>
    <?php $totalObs = array_sum($observacionesPorTipo); ?>
    <?php foreach ($observacionesPorTipo as $tipo => $n): ?>
    <div class="fila"><div class="top"><span><?= e($tipoObsLabel[$tipo] ?? $tipo) ?></span><strong><?= $n ?></strong></div><?= barraPdf($n, $totalObs, '#9333EA') ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_pqrs']) && $pqrsStats): ?>
<div class="seccion">
    <div class="seccion-titulo">PQRS por estado</div>
    <?php $totalPqrs = array_sum($pqrsStats['por_estado']); ?>
    <?php foreach ($pqrsStats['por_estado'] as $estado => $n): ?>
    <div class="fila"><div class="top"><span><?= e($pqrsEstadoLabel[$estado] ?? $estado) ?></span><strong><?= $n ?></strong></div><?= barraPdf($n, $totalPqrs, '#D97706') ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_encuestas']) && $encuestasStats): ?>
<div class="seccion">
    <div class="seccion-titulo">Participación en encuestas</div>
    <?php foreach ($encuestasStats as $enc): $pct = $enc['total_invitados'] > 0 ? round($enc['total_completados'] / $enc['total_invitados'] * 100) : 0; ?>
    <div class="fila"><div class="top"><span><?= e($enc['titulo']) ?></span><strong><?= $enc['total_completados'] ?>/<?= $enc['total_invitados'] ?> (<?= $pct ?>%)</strong></div><?= barraPdf($pct, 100, '#4D7C0F') ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_autorizaciones']) && $autorizacionesStats): ?>
<div class="seccion">
    <div class="seccion-titulo">Autorizaciones firmadas</div>
    <?php foreach ($autorizacionesStats as $a): $pct = $a['total_invitados'] > 0 ? round($a['total_firmados'] / $a['total_invitados'] * 100) : 0; ?>
    <div class="fila"><div class="top"><span><?= e($a['titulo']) ?></span><strong><?= $a['total_firmados'] ?>/<?= $a['total_invitados'] ?> (<?= $pct ?>%)</strong></div><?= barraPdf($pct, 100, '#1D4ED8') ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($flags['permite_retiro_anticipado']) && $retirosStats): ?>
<div class="seccion">
    <div class="seccion-titulo">Retiros anticipados (<?= $retirosStats['total'] ?> en total)</div>
    <?php $maxRet = max(array_merge([1], array_column($retirosStats['por_mes'], 'total'))); ?>
    <?php foreach ($retirosStats['por_mes'] as $r): ?>
    <div class="fila"><div class="top"><span><?= e($r['mes']) ?></span><strong><?= $r['total'] ?></strong></div><?= barraPdf((float)$r['total'], $maxRet, '#9A3412') ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="pie">Generado el <?= e($fecha_emision) ?> &mdash; <?= e($colegio['nombre'] ?? '') ?></div>

</body>
</html>
