<?php
$pageTitle  = 'Calificar evaluaciones — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$gid        = $ind['grupo_id'];
$asig_id    = $ind['asignacion_id'];
$periodo_id = $ind['periodo_id'];
$grupo      = ['grupo_id'=>$gid,'grado'=>$ind['grado'],'grupo'=>$ind['grupo'],'materia'=>$ind['materia'],'ano'=>''];
$breadcrumbs= [
  ['label'=>'Mis Grupos',   'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$ind['grado'].' '.$ind['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id.'&periodo='.$periodo_id],
  ['label'=>'Evaluaciones', 'url'=>APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id.'&periodo='.$periodo_id],
  ['label'=>'Calificar '.e($ind['codigo']), 'url'=>''],
];
ob_start();
?>

<h1 class="cal-page-title">Calificar evaluaciones</h1>
<p class="cal-context-line">
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $asig_id ?>&periodo=<?= $periodo_id ?>"
     class="link-primary"><?= e($ind['materia']) ?></a>
  &rsaquo; <?= e($ind['grado'].' '.$ind['grupo']) ?>
  &rsaquo; <?= e($ind['periodo_nombre']) ?>
</p>

<?php if (empty($evaluaciones)): ?>
  <div class="empty-state">
    <i class="mdi mdi-clipboard-list-outline"></i>
    <p>Este indicador no tiene evaluaciones creadas todav&iacute;a.</p>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $asig_id ?>&periodo=<?= $periodo_id ?>"
       class="btn-primary" style="margin-top:12px">
      <i class="mdi mdi-arrow-left"></i> Volver a Evaluaciones
    </a>
  </div>
<?php else: ?>

<!-- Opciones -->
<div class="cal-options-bar">
  <label class="cal-opt-label">
    <input type="checkbox" id="nav-flechas"> Activar navegaci&oacute;n con flechas
  </label>
  <label class="cal-opt-label">
    Estado:
    <select class="form-select" style="width:auto;padding:4px 8px;display:inline-block" id="filtro-estado" onchange="filtrarEstado(this.value)">
      <option value="todos">Todos</option>
      <option value="activo">Activo</option>
    </select>
  </label>
</div>


<form id="form-calificar" method="POST" action="<?= APP_URL ?>/indicador/<?= $ind['id'] ?>/calificar">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

  <div class="cal-matrix-wrap">
    <table class="cal-matrix-table" id="cal-table">
      <thead>
        <tr class="cal-head-top">
          <th class="cal-th-est" rowspan="2">Estudiante</th>
          <?php $total_pct = array_sum(array_column($evaluaciones, 'porcentaje')); ?>
          <th colspan="<?= count($evaluaciones) ?>" class="cal-th-group">
            nota<br><span class="cal-pct-total"><?= number_format($total_pct, 2) ?>%</span>
          </th>
          <th class="cal-th-def" rowspan="2">Definitiva</th>
        </tr>
        <tr class="cal-head-cols">
          <?php foreach ($evaluaciones as $ev): ?>
            <th class="cal-th-eval" title="<?= e($ev['nombre']) ?>">
              <div class="cal-th-eval-inner">
                <i class="mdi mdi-account-outline cal-th-icon"></i>
                <span><?= e(mb_strimwidth($ev['nombre'], 0, 28, '...')) ?></span>
                <span class="cal-th-pct"><?= $ev['porcentaje'] ?>%</span>
              </div>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $i => $est): ?>
        <tr class="cal-row" data-estado="activo">
          <td class="cal-td-est"><?= e($est['apellido'].', '.$est['nombre']) ?></td>
          <?php foreach ($evaluaciones as $ev):
            $valor = $notas[$ev['id']][$est['matricula_id']] ?? null;
            $cls   = $valor !== null ? ($valor >= 6 ? 'nota-ok' : 'nota-fail') : '';
          ?>
          <td class="cal-td-nota">
            <input type="number"
                   name="nota[<?= $ev['id'] ?>][<?= $est['matricula_id'] ?>]"
                   class="cal-input <?= $cls ?>"
                   value="<?= $valor !== null ? number_format((float)$valor, 1, '.', '') : '' ?>"
                   min="<?= notaMin() ?>" max="<?= notaMax() ?>" step="0.1"
                   placeholder="--"
                   data-row="<?= $i ?>"
                   data-col="<?= array_search($ev, $evaluaciones) ?>"
                   onchange="onNotaChange(this, <?= $est['matricula_id'] ?>)">
          </td>
          <?php endforeach; ?>
          <td class="cal-td-def" id="def-<?= $est['matricula_id'] ?>">
            <?php if ($est['definitiva'] !== null): ?>
              <strong class="<?= $est['definitiva'] >= 6 ? 'nota-ok' : 'nota-fail' ?> cal-def-val">
                <?= number_format($est['definitiva'], 1) ?>
              </strong>
            <?php else: ?>
              <span class="text-muted cal-def-val">--</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Guardar (abajo) -->
  <div class="cal-save-bar" style="margin-top:16px">
    <button type="submit" name="accion" value="continuar" class="btn-primary">
      <i class="mdi mdi-content-save-outline"></i> Guardar y continuar
    </button>
    <button type="submit" name="accion" value="salir" class="btn-secondary">
      <i class="mdi mdi-content-save-check-outline"></i> Guardar y salir
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $asig_id ?>&periodo=<?= $periodo_id ?>"
       class="cal-cancel-link">Cancelar</a>
  </div>
</form>

<?php endif; ?>

<script>
const evalPcts = <?= json_encode(array_map(fn($e)=>['id'=>$e['id'],'pct'=>(float)$e['porcentaje']], $evaluaciones)) ?>;

function onNotaChange(input, matriculaId) {
  const v = parseFloat(input.value);
  input.classList.remove('nota-ok','nota-fail');
  if (!isNaN(v) && input.value !== '') {
    input.classList.add(v >= 6 ? 'nota-ok' : 'nota-fail');
  }
  recalcDefinitiva(matriculaId);
}

function recalcDefinitiva(matriculaId) {
  let ponderado = 0, pctCalif = 0;
  evalPcts.forEach(ep => {
    const input = document.querySelector(`input[name="nota[${ep.id}][${matriculaId}]"]`);
    if (input && input.value !== '') {
      const v = parseFloat(input.value);
      if (!isNaN(v)) { ponderado += v * (ep.pct / 100); pctCalif += ep.pct; }
    }
  });
  const cell = document.getElementById('def-' + matriculaId);
  const span = cell.querySelector('.cal-def-val');
  if (!span) return;
  if (pctCalif > 0) {
    const def = ponderado.toFixed(1);
    span.closest('td').querySelector('strong,span').outerHTML =
      `<strong class="cal-def-val ${ponderado >= 6 ? 'nota-ok' : 'nota-fail'}">${def}</strong>`;
  } else {
    cell.querySelector('.cal-def-val').textContent = '--';
  }
}

document.getElementById('nav-flechas').addEventListener('change', function() {
  if (!this.checked) return;
  document.querySelectorAll('.cal-input').forEach(input => {
    input.addEventListener('keydown', e => {
      const row = +input.dataset.row, col = +input.dataset.col;
      let nextRow = row, nextCol = col;
      if (e.key === 'ArrowDown'  || e.key === 'Enter') { nextRow = row + 1; e.preventDefault(); }
      if (e.key === 'ArrowUp')                          { nextRow = row - 1; e.preventDefault(); }
      if (e.key === 'ArrowRight')                       { nextCol = col + 1; e.preventDefault(); }
      if (e.key === 'ArrowLeft')                        { nextCol = col - 1; e.preventDefault(); }
      const next = document.querySelector(`.cal-input[data-row="${nextRow}"][data-col="${nextCol}"]`);
      if (next) { next.focus(); next.select(); }
    });
  });
});

function filtrarEstado(val) {
  document.querySelectorAll('.cal-row').forEach(row => {
    row.style.display = (val === 'todos' || row.dataset.estado === val) ? '' : 'none';
  });
}
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
