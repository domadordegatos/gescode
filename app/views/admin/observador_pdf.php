<?php
/** @var array $estudiante */
/** @var array|null $colegio */
/** @var array $historialMatriculas */
/** @var array $timeline */
/** @var string $fecha_emision */

$colorPrimario = $colegio['color_primario'] ?? '#0E0EB3';
$logoUrl = !empty($colegio['logo']) ? (APP_URL . '/uploads/imagenes/' . $colegio['logo']) : null;
$marcaAguaUrl = !empty($colegio['marca_agua_boletin']) ? (APP_URL . '/uploads/imagenes/' . $colegio['marca_agua_boletin']) : null;

$datosInst = array_filter([
    !empty($colegio['nit'])       ? 'NIT ' . $colegio['nit']        : null,
    !empty($colegio['dane'])      ? 'DANE ' . $colegio['dane']      : null,
    !empty($colegio['direccion']) ? $colegio['direccion'] . (!empty($colegio['ciudad']) ? ', ' . $colegio['ciudad'] : '') : null,
    !empty($colegio['telefono'])  ? 'Tel. ' . $colegio['telefono']  : null,
]);

$tipoCfg = [
    'academica'     => ['label' => 'Académica',    'color' => '#2563EB'],
    'convivencia'   => ['label' => 'Convivencia',  'color' => '#9333EA'],
    'logro'         => ['label' => 'Logro',        'color' => '#16A34A'],
    'disciplinaria' => ['label' => 'Disciplinaria','color' => '#B91C1C'],
    'otro'          => ['label' => 'Otro',         'color' => '#6B7280'],
];
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
    .encabezado { display: table; width: 100%; margin-bottom: 14px; border-bottom: 2px solid <?= e($colorPrimario) ?>; padding-bottom: 10px; }
    .encabezado .logo { display: table-cell; width: 70px; vertical-align: middle; }
    .encabezado .logo img { width: 60px; }
    .encabezado .titulo { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .encabezado .titulo h1 { font-size: 15px; margin: 0; color: <?= e($colorPrimario) ?>; }
    .encabezado .titulo p { margin: 2px 0 0; font-size: 9px; color: #555; }
    .encabezado .titulo .datos-oficiales { margin-top: 3px; font-size: 7.8px; color: #777; }
    .datos-est { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
    .datos-est td { padding: 3px 6px; font-size: 9.5px; }
    .datos-est .lbl { font-weight: bold; color: #555; width: 110px; }
    .seccion-titulo { font-size: 11px; font-weight: bold; color: <?= e($colorPrimario) ?>; margin: 16px 0 6px; }
    table.hist { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.hist th, table.hist td { border: 1px solid #ccc; padding: 5px 7px; font-size: 9px; text-align: left; }
    table.hist th { background: <?= e($colorPrimario) ?>; color: #fff; }
    .item-obs { border: 1px solid #ddd; border-left: 4px solid #999; border-radius: 4px; padding: 7px 9px; margin-bottom: 6px; font-size: 9px; page-break-inside: avoid; }
    .item-obs .meta { font-size: 8px; color: #777; margin-top: 3px; }
    .firma { margin-top: 40px; width: 100%; }
    .firma .linea { border-top: 1px solid #333; width: 220px; margin-top: 30px; padding-top: 4px; text-align: center; font-size: 9px; }
    .pie { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #999; text-align: center; }
</style>
</head>
<body>

<div class="encabezado">
    <?php if ($logoUrl): ?>
    <div class="logo"><img src="<?= e($logoUrl) ?>"></div>
    <?php endif; ?>
    <div class="titulo">
        <h1><?= e($colegio['nombre'] ?? 'Institución Educativa') ?></h1>
        <p>Hoja de vida del estudiante &mdash; Observador consolidado</p>
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
        <td class="lbl">Fecha de nacimiento</td><td><?= $estudiante['fecha_nacimiento'] ? e(date('d/m/Y', strtotime($estudiante['fecha_nacimiento']))) : '—' ?></td>
        <td class="lbl">Género</td><td><?= e($estudiante['genero'] ?? '—') ?></td>
    </tr>
</table>

<div class="seccion-titulo">Historial académico (años cursados)</div>
<table class="hist">
    <thead><tr><th>Año lectivo</th><th>Grado / Grupo</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($historialMatriculas as $h): ?>
        <tr>
            <td><?= e($h['ano']) ?></td>
            <td><?= e($h['grado'] . ' ' . $h['grupo']) ?></td>
            <td><?= e(ucfirst($h['estado'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$historialMatriculas): ?>
        <tr><td colspan="3" style="text-align:center;color:#999">Sin matrículas registradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="seccion-titulo">Línea de tiempo de observaciones (<?= count($timeline) ?>)</div>
<?php foreach ($timeline as $o): $cfg = $tipoCfg[$o['tipo']] ?? $tipoCfg['otro']; ?>
<div class="item-obs" style="border-left-color:<?= e($cfg['color']) ?>">
    <strong style="color:<?= e($cfg['color']) ?>"><?= e($cfg['label']) ?></strong> &mdash; <?= e(date('d/m/Y', strtotime($o['fecha']))) ?>
    <div><?= nl2br(e($o['descripcion'])) ?></div>
    <div class="meta">
        <?= e($o['ano'] . ' · ' . $o['grado'] . ' ' . $o['grupo']) ?> ·
        Registrado por <?= e($o['doc_nombre'] . ' ' . $o['doc_apellido']) ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (!$timeline): ?>
<div style="color:#999;text-align:center;padding:14px">No hay observaciones registradas para este estudiante.</div>
<?php endif; ?>

<div class="firma">
    <div class="linea"><?= e($colegio['rector'] ?: '________________________') ?><br><?= e($colegio['rector_cargo'] ?: 'Rector(a)') ?></div>
</div>

<div class="pie">Documento generado el <?= e($fecha_emision) ?> &mdash; <?= e($colegio['nombre'] ?? '') ?> &mdash; Uso interno / confidencial</div>

</body>
</html>
