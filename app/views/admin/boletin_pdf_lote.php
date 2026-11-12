<?php
/** @var array $estudiantesData  lista de resultados de BoletinController::prepararDatos() */
/** @var array $grupo */

$colegio = $estudiantesData[0]['colegio'] ?? null;
$colorPrimario = $colegio['color_primario'] ?? '#0E0EB3';
$logoUrl = !empty($colegio['logo']) ? (APP_URL . '/uploads/imagenes/' . $colegio['logo']) : null;
$marcaAguaUrl = !empty($colegio['marca_agua_boletin']) ? (APP_URL . '/uploads/imagenes/' . $colegio['marca_agua_boletin']) : null;

$datosInst = array_filter([
    !empty($colegio['nit'])       ? 'NIT ' . $colegio['nit']        : null,
    !empty($colegio['dane'])      ? 'DANE ' . $colegio['dane']      : null,
    !empty($colegio['direccion']) ? $colegio['direccion'] . (!empty($colegio['ciudad']) ? ', ' . $colegio['ciudad'] : '') : null,
    !empty($colegio['telefono'])  ? 'Tel. ' . $colegio['telefono']  : null,
]);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 70px 36px 50px 36px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    <?php if ($marcaAguaUrl): ?>
    body { background: url('<?= e($marcaAguaUrl) ?>') no-repeat center center; background-size: 60% auto; }
    <?php endif; ?>
    .hoja { page-break-after: always; }
    .hoja:last-child { page-break-after: auto; }
    .encabezado { display: table; width: 100%; margin-bottom: 14px; border-bottom: 2px solid <?= e($colorPrimario) ?>; padding-bottom: 10px; }
    .encabezado .logo { display: table-cell; width: 70px; vertical-align: middle; }
    .encabezado .logo img { width: 60px; }
    .encabezado .titulo { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .encabezado .titulo h1 { font-size: 15px; margin: 0; color: <?= e($colorPrimario) ?>; }
    .encabezado .titulo p { margin: 2px 0 0; font-size: 9px; color: #555; }
    .encabezado .titulo .datos-oficiales { margin-top: 3px; font-size: 7.8px; color: #777; }
    .datos-est { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .datos-est td { padding: 3px 6px; font-size: 9.5px; }
    .datos-est .lbl { font-weight: bold; color: #555; width: 110px; }
    table.notas { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.notas th, table.notas td { border: 1px solid #ccc; padding: 5px 7px; font-size: 9.5px; }
    table.notas th { background: <?= e($colorPrimario) ?>; color: #fff; text-align: center; }
    table.notas td.materia { text-align: left; font-weight: 600; }
    table.notas td.nota { text-align: center; }
    .seccion-titulo { font-size: 11px; font-weight: bold; color: <?= e($colorPrimario) ?>; margin: 16px 0 6px; }
    .resumen-asist { width: 100%; border-collapse: collapse; }
    .resumen-asist td { border: 1px solid #ccc; padding: 6px; text-align: center; font-size: 9.5px; }
    .firma { margin-top: 40px; width: 100%; }
    .firma .linea { border-top: 1px solid #333; width: 220px; margin-top: 30px; padding-top: 4px; text-align: center; font-size: 9px; }
</style>
</head>
<body>

<?php foreach ($estudiantesData as $datos): ?>
<?php
    $estudiante = $datos['estudiante'];
    $materias = $datos['materias'];
    $periodos = $datos['periodos'];
    $periodo_id = $datos['periodo_id'];
    $desempenios = $datos['desempenios'];
    $asistencia = $datos['asistencia'];
    $fecha_emision = $datos['fecha_emision'];
    $mostrarConsolidado = ($periodo_id === null);
?>
<div class="hoja">

<div class="encabezado">
    <?php if ($logoUrl): ?>
    <div class="logo"><img src="<?= e($logoUrl) ?>"></div>
    <?php endif; ?>
    <div class="titulo">
        <h1><?= e($colegio['nombre'] ?? 'Institución Educativa') ?></h1>
        <p>Boletín de calificaciones &mdash; <?= $mostrarConsolidado ? 'Consolidado del año' : 'Período: ' . e($periodos[$periodo_id]['nombre'] ?? '') ?></p>
        <?php if ($datosInst): ?>
        <div class="datos-oficiales"><?= e(implode(' · ', $datosInst)) ?></div>
        <?php endif; ?>
    </div>
</div>

<table class="datos-est">
    <tr>
        <td class="lbl">Estudiante</td><td><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></td>
        <td class="lbl">Documento</td><td><?= e(($estudiante['tipo_documento'] ?? '') . ' ' . ($estudiante['documento'] ?? '')) ?></td>
    </tr>
    <tr>
        <td class="lbl">Grado / Grupo</td><td><?= e($estudiante['grado'] . ' ' . $estudiante['grupo']) ?></td>
        <td class="lbl">Año lectivo</td><td><?= e($estudiante['ano']) ?></td>
    </tr>
</table>

<table class="notas">
    <thead>
        <tr>
            <th style="text-align:left">Materia</th>
            <?php if ($mostrarConsolidado): ?>
                <?php foreach ($periodos as $pid => $p): ?>
                <th><?= e($p['nombre']) ?></th>
                <?php endforeach; ?>
                <th>Definitiva</th>
            <?php else: ?>
                <th>Nota</th>
                <th>Desempeño</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($materias as $mat): ?>
        <tr>
            <td class="materia"><?= e($mat['nombre']) ?></td>
            <?php if ($mostrarConsolidado): ?>
                <?php foreach ($periodos as $pid => $p): ?>
                <td class="nota"><?= isset($mat['periodos'][$pid]) ? e(number_format($mat['periodos'][$pid], 1)) : '—' ?></td>
                <?php endforeach; ?>
                <td class="nota" style="font-weight:bold"><?= $mat['promedio_final'] !== null ? e(number_format($mat['promedio_final'], 1)) : '—' ?></td>
            <?php else: ?>
                <?php $valor = $mat['periodos'][$periodo_id] ?? null; ?>
                <td class="nota" style="font-weight:bold"><?= $valor !== null ? e(number_format($valor, 1)) : '—' ?></td>
                <td class="nota"><?= e(desempenioDeNota($valor, $desempenios, 'texto')) ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$materias): ?>
        <tr><td colspan="6" style="text-align:center;color:#999">Aún no hay notas registradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="seccion-titulo">Asistencia (año lectivo)</div>
<table class="resumen-asist">
    <tr>
        <td><strong><?= $asistencia['presente'] ?></strong><br>Presente</td>
        <td><strong><?= $asistencia['tarde'] ?></strong><br>Tarde</td>
        <td><strong><?= $asistencia['excusa'] ?></strong><br>Excusa</td>
        <td><strong><?= $asistencia['ausente'] ?></strong><br>Ausente</td>
    </tr>
</table>

<div class="firma">
    <div class="linea"><?= e($colegio['rector'] ?: '________________________') ?><br><?= e($colegio['rector_cargo'] ?: 'Rector(a)') ?></div>
</div>

</div>
<?php endforeach; ?>

</body>
</html>
