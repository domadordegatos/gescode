<?php
/** @var array $estudiante */
/** @var array|null $colegio */
/** @var string $tipo */
/** @var string $tituloTipo */
/** @var string $textoExtra */
/** @var string $fecha_emision */
/** @var int $radicado */

$colorPrimario = $colegio['color_primario'] ?? '#0E0EB3';
$logoUrl = !empty($colegio['logo']) ? (APP_URL . '/uploads/imagenes/' . $colegio['logo']) : null;
$marcaAguaUrl = !empty($colegio['marca_agua_boletin']) ? (APP_URL . '/uploads/imagenes/' . $colegio['marca_agua_boletin']) : null;

// Datos oficiales de la institución, necesarios para que el documento tenga validez
// (NIT, código DANE, dirección, teléfonos, correo, sitio web) — solo los diligenciados.
$datosInst = array_filter([
    !empty($colegio['nit'])       ? 'NIT ' . $colegio['nit']        : null,
    !empty($colegio['dane'])      ? 'DANE ' . $colegio['dane']      : null,
    !empty($colegio['direccion']) ? $colegio['direccion'] . (!empty($colegio['ciudad']) ? ', ' . $colegio['ciudad'] : '') : null,
    !empty($colegio['telefono'])  ? 'Tel. ' . $colegio['telefono'] . (!empty($colegio['telefono2']) ? ' / ' . $colegio['telefono2'] : '') : null,
    !empty($colegio['email'])     ? $colegio['email']               : null,
    !empty($colegio['sitio_web']) ? $colegio['sitio_web']           : null,
]);

$nombreCompleto = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
$documento = trim(($estudiante['tipo_documento'] ?? '') . ' ' . ($estudiante['documento'] ?? ''));
$gradoGrupo = $estudiante['grado'] . ' ' . $estudiante['grupo'];
$ano = $estudiante['ano'];

switch ($tipo) {
    case 'notas_simple':
        $cuerpo = "Que <strong>$nombreCompleto</strong>, identificado(a) con documento <strong>$documento</strong>, "
                . "cursó/cursa el grado <strong>$gradoGrupo</strong> durante el año lectivo <strong>$ano</strong>, "
                . "encontrándose matriculado(a) y al día en su proceso académico.";
        break;
    case 'buena_conducta':
        $cuerpo = "Que <strong>$nombreCompleto</strong>, identificado(a) con documento <strong>$documento</strong>, "
                . "estudiante del grado <strong>$gradoGrupo</strong> durante el año lectivo <strong>$ano</strong>, "
                . "ha observado buena conducta y comportamiento dentro de la institución, sin registrar faltas disciplinarias graves.";
        break;
    case 'paz_y_salvo':
        $cuerpo = "Que <strong>$nombreCompleto</strong>, identificado(a) con documento <strong>$documento</strong>, "
                . "estudiante del grado <strong>$gradoGrupo</strong>, se encuentra a <strong>paz y salvo</strong> "
                . "con la institución por todo concepto, al día en sus obligaciones académicas y administrativas, durante el año lectivo <strong>$ano</strong>.";
        break;
    default: // estudio
        $cuerpo = "Que <strong>$nombreCompleto</strong>, identificado(a) con documento <strong>$documento</strong>, "
                . "se encuentra matriculado(a) en esta institución educativa, cursando actualmente el grado "
                . "<strong>$gradoGrupo</strong>, durante el año lectivo <strong>$ano</strong>.";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 90px 50px 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #222; }
    <?php if ($marcaAguaUrl): ?>
    body { background: url('<?= e($marcaAguaUrl) ?>') no-repeat center center; background-size: 55% auto; }
    <?php endif; ?>
    .encabezado { display: table; width: 100%; margin-bottom: 20px; border-bottom: 2px solid <?= e($colorPrimario) ?>; padding-bottom: 12px; }
    .encabezado .logo { display: table-cell; width: 70px; vertical-align: middle; }
    .encabezado .logo img { width: 60px; }
    .encabezado .titulo { display: table-cell; vertical-align: middle; padding-left: 10px; }
    .encabezado .titulo h1 { font-size: 15px; margin: 0; color: <?= e($colorPrimario) ?>; }
    .encabezado .titulo p { margin: 2px 0 0; font-size: 9px; color: #555; }
    .encabezado .titulo .datos-oficiales { margin-top: 3px; font-size: 7.8px; color: #777; }
    .titulo-doc { text-align: center; font-size: 17px; font-weight: bold; letter-spacing: 1px; color: <?= e($colorPrimario) ?>; margin: 34px 0 30px; text-transform: uppercase; }
    .cuerpo { line-height: 1.9; text-align: justify; margin: 0 20px; }
    .extra { line-height: 1.9; text-align: justify; margin: 18px 20px 0; font-style: italic; }
    .firma { margin-top: 60px; width: 100%; text-align: center; }
    .firma .linea { border-top: 1px solid #333; width: 240px; margin: 0 auto; padding-top: 4px; font-size: 10px; }
    .pie { position: fixed; bottom: -45px; left: 0; right: 0; font-size: 8px; color: #999; text-align: center; }
</style>
</head>
<body>

<div class="encabezado">
    <?php if ($logoUrl): ?>
    <div class="logo"><img src="<?= e($logoUrl) ?>"></div>
    <?php endif; ?>
    <div class="titulo">
        <h1><?= e($colegio['nombre'] ?? 'Institución Educativa') ?></h1>
        <?php if ($datosInst): ?>
        <div class="datos-oficiales"><?= e(implode(' · ', $datosInst)) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="titulo-doc"><?= e($tituloTipo) ?></div>

<div class="cuerpo">
    <strong>EL/LA SUSCRITO(A) RECTOR(A) O COORDINADOR(A) DE LA INSTITUCIÓN HACE CONSTAR:</strong>
    <br><br>
    <?= $cuerpo /* contiene <strong> propios de la plantilla, no entrada de usuario */ ?>
</div>

<?php if ($textoExtra !== ''): ?>
<div class="extra"><?= nl2br(e($textoExtra)) ?></div>
<?php endif; ?>

<div class="cuerpo" style="margin-top:20px">
    Se expide la presente constancia a solicitud del interesado, el día <strong><?= e($fecha_emision) ?></strong>,
    con radicado interno No. <strong><?= str_pad((string)$radicado, 6, '0', STR_PAD_LEFT) ?></strong>.
</div>

<div class="firma">
    <div class="linea"><?= e($colegio['rector'] ?: '________________________') ?><br><?= e($colegio['rector_cargo'] ?: 'Rector(a)') ?></div>
</div>

<div class="pie">Documento generado el <?= e($fecha_emision) ?> &mdash; <?= e($colegio['nombre'] ?? '') ?></div>

</body>
</html>
