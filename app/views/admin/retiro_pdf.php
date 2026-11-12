<?php
/** @var array $retiro */
/** @var string|null $fotoBase64 */
/** @var array|null $colegio */
/** @var string $fecha_emision */

$colorPrimario = $colegio['color_primario'] ?? '#0E0EB3';
$logoUrl = !empty($colegio['logo']) ? (APP_URL . '/uploads/imagenes/' . $colegio['logo']) : null;

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
    .encabezado { display: table; width: 100%; margin-bottom: 16px; border-bottom: 2px solid <?= e($colorPrimario) ?>; padding-bottom: 10px; }
    .encabezado .logo { display: table-cell; width: 70px; vertical-align: middle; }
    .encabezado .logo img { width: 60px; }
    .encabezado .titulo { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .encabezado .titulo h1 { font-size: 15px; margin: 0; color: <?= e($colorPrimario) ?>; }
    .encabezado .titulo p { margin: 2px 0 0; font-size: 9px; color: #555; }
    .encabezado .titulo .datos-oficiales { margin-top: 3px; font-size: 7.8px; color: #777; }
    .titulo-doc { text-align: center; font-size: 14px; font-weight: bold; color: <?= e($colorPrimario) ?>; margin: 14px 0 16px; text-transform: uppercase; }
    .bloque { display: table; width: 100%; margin-bottom: 16px; }
    .bloque .foto { display: table-cell; width: 170px; vertical-align: top; }
    .bloque .foto img { width: 160px; border-radius: 6px; border: 1px solid #ccc; }
    .bloque .datos { display: table-cell; vertical-align: top; padding-left: 16px; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 4px 0; font-size: 9.5px; vertical-align: top; }
    table.info td.lbl { color: #555; width: 150px; font-weight: bold; }
    .pie { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #999; text-align: center; }
    .nota-legal { font-size: 8px; color: #888; margin-top: 16px; border-top: 1px solid #ddd; padding-top: 8px; }
</style>
</head>
<body>

<div class="encabezado">
    <?php if ($logoUrl): ?>
    <div class="logo"><img src="<?= e($logoUrl) ?>"></div>
    <?php endif; ?>
    <div class="titulo">
        <h1><?= e($colegio['nombre'] ?? 'Institución Educativa') ?></h1>
        <p>Registro de retiro anticipado</p>
        <?php if ($datosInst): ?>
        <div class="datos-oficiales"><?= e(implode(' · ', $datosInst)) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="titulo-doc">Retiro anticipado de <?= e($retiro['est_nombre'] . ' ' . $retiro['est_apellido']) ?></div>

<div class="bloque">
    <div class="foto">
        <?php if ($fotoBase64): ?>
        <img src="data:image/jpeg;base64,<?= $fotoBase64 ?>">
        <?php endif; ?>
        <div style="font-size:7.5px;color:#777;margin-top:4px">Reto solicitado:<br><strong><?= e($retiro['instruccion_reto']) ?></strong></div>
    </div>
    <div class="datos">
        <table class="info">
            <tr><td class="lbl">Persona que retira</td><td><?= e($retiro['nombre_persona']) ?></td></tr>
            <tr><td class="lbl">Documento</td><td style="font-family:monospace"><?= e($retiro['documento_persona']) ?></td></tr>
            <tr><td class="lbl">Parentesco</td><td><?= e($retiro['parentesco']) ?></td></tr>
            <?php if ($retiro['autoriza_nombre']): ?>
            <tr><td class="lbl">Autorizado por</td><td><?= e($retiro['autoriza_nombre'] . ' ' . $retiro['autoriza_apellido']) ?> (acudiente registrado)</td></tr>
            <?php endif; ?>
            <?php if ($retiro['motivo']): ?>
            <tr><td class="lbl">Motivo</td><td><?= e($retiro['motivo']) ?></td></tr>
            <?php endif; ?>
            <tr><td class="lbl">Fecha y hora</td><td><?= date('d/m/Y H:i:s', strtotime($retiro['created_at'])) ?></td></tr>
            <tr><td class="lbl">Registrado por</td><td><?= e($retiro['reg_nombre'] . ' ' . $retiro['reg_apellido']) ?></td></tr>
            <tr><td class="lbl">Dirección IP</td><td style="font-family:monospace"><?= e($retiro['ip']) ?></td></tr>
        </table>
    </div>
</div>

<div class="nota-legal">
    Este documento constituye evidencia de la identidad de la persona que retiró al estudiante, registrada mediante
    fotografía en vivo con reto aleatorio (dato biométrico, Ley 1581 de 2012) en el momento exacto del retiro.
    Documento generado el <?= e($fecha_emision) ?>.
</div>

<div class="pie">Documento generado el <?= e($fecha_emision) ?> &mdash; <?= e($colegio['nombre'] ?? '') ?> &mdash; Confidencial, uso interno</div>

</body>
</html>
