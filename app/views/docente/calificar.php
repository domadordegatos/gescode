<?php
$pageTitle  = 'Calificar â€” '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$gid        = $grupo_id;
$breadcrumbs= [
  ['label'=>'Inicio',      'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos',  'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$asig['grado'].' '.$asig['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/evaluaciones'],
  ['label'=>'Calificar',   'url'=>''],
];
$grupo = $asig;
ob_start();
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Calificar evaluaciÃ³n</h1>
    <p class="page-subtitle"><?= e($ev['nombre']) ?></p>
  </div>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $ev['asignacion_id'] ?>&periodo=<?= $ev['periodo_id'] ?>"
     class="btn-secondary"><i class="mdi mdi-arrow-left"></i> Volver</a>
</div>

<div class="eval-cal-meta card" style="padding:14px 20px;margin-bottom:16px;display:flex;gap:20px;flex-wrap:wrap;align-items:center">
  <div><span class="text-muted text-sm">Indicador:</span><br>
    <strong style="font-size:.9rem"><?= e($ev['ind_desc']) ?></strong>
  </div>
  <div><span class="text-muted text-sm">CÃ³digo:</span><br>
    <strong><?= e($ev['codigo']) ?></strong>
  </div>
  <div><span class="text-muted text-sm">Fecha:</span><br>
    <strong><?= date('d/m/Y', strtotime($ev['fecha'])) ?></strong>
  </div>
  <div><span class="text-muted text-sm">% del indicador:</span><br>
    <strong><?= $ev['porcentaje'] ?>%</strong>
  </div>
  <div><span class="text-muted text-sm">Grupo:</span><br>
    <strong><?= e($asig['grado'].' '.$asig['grupo']) ?></strong>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/evaluacion/<?= $ev['id'] ?>/calificar">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

  <div class="card" style="overflow:auto">
    <table class="data-table cal-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Estudiante</th>
          <th style="width:130px;text-align:center">Nota (0 â€“ 10)</th>
          <th>Ãšltima modificaciÃ³n</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $i => $est): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= e($est['apellido'].', '.$est['nombre']) ?></strong></td>
          <td style="text-align:center">
            <input type="number"
                   name="nota[<?= $est['matricula_id'] ?>]"
                   class="nota-input <?= $est['valor']!==null ? ($est['valor']>=6?'nota-ok':'nota-fail') : '' ?>"
                   value="<?= $est['valor'] !== null ? number_format((float)$est['valor'],1,'.','') : '' ?>"
                   min="<?= notaMin() ?>" max="<?= notaMax() ?>" step="0.1" placeholder="â€”"
                   onchange="colorNota(this)">
          </td>
          <td class="text-muted text-sm">
            <?= $est['fecha_cal'] ? date('d/m/Y H:i', strtotime($est['fecha_cal'])) : 'â€”' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- EstadÃ­sticas rÃ¡pidas en tiempo real -->
  <div class="cal-stats-bar">
    <div class="cal-stat" id="stat-evaluados"><i class="mdi mdi-account-check-outline"></i> <span>0</span> evaluados</div>
    <div class="cal-stat" id="stat-perdidos"><i class="mdi mdi-account-alert-outline"></i> <span>0</span> perdidos</div>
    <div class="cal-stat" id="stat-promedio"><i class="mdi mdi-chart-line"></i> Promedio: <span>â€”</span></div>
  </div>

  <div class="form-actions" style="margin-top:16px">
    <button type="submit" class="btn-primary">
      <i class="mdi mdi-content-save-outline"></i> Guardar calificaciones
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $ev['asignacion_id'] ?>&periodo=<?= $ev['periodo_id'] ?>"
       class="btn-secondary">Cancelar</a>
  </div>
</form>

<script>
function colorNota(input) {
  const v = parseFloat(input.value);
  input.classList.remove('nota-ok','nota-fail');
  if (!isNaN(v)) input.classList.add(v >= 6 ? 'nota-ok' : 'nota-fail');
  updateStats();
}

function updateStats() {
  const inputs  = [...document.querySelectorAll('.nota-input')];
  const vals    = inputs.map(i => parseFloat(i.value)).filter(v => !isNaN(v));
  const perd    = vals.filter(v => v < 6).length;
  const prom    = vals.length ? (vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(1) : 'â€”';

  document.querySelector('#stat-evaluados span').textContent = vals.length;
  document.querySelector('#stat-perdidos span').textContent  = perd;
  document.querySelector('#stat-promedio span').textContent  = prom;
}

document.addEventListener('DOMContentLoaded', updateStats);
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>

