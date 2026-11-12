<?php
$pageTitle  = 'Consolidado por Indicadores — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$gid        = $grupo['grupo_id'];
$tab_activo = 'evaluaciones';
$periodo_nombre = '';
foreach ($periodos as $p) { if ((int)$p['id']==$per_id) { $periodo_nombre=$p['nombre']; break; } }
$breadcrumbs= [
  ['label'=>'Inicio',         'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos',     'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id.'&periodo='.$per_id],
  ['label'=>'Consolidado por indicadores', 'url'=>''],
];
$indicadores = $data['indicadores'];
$estudiantes = $data['estudiantes'];
$grupo_extra_title  = ' <span class="sep">·</span> <span style="font-size:.82rem;font-weight:600;opacity:.85">Consolidado por indicadores</span>';
$grupo_extra_action = '<a href="'.APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id.'&periodo='.$per_id.'"
   style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:6px;
          background:rgba(255,255,255,.15);color:#fff;font-size:.78rem;font-weight:700;
          transition:background .15s" onmouseover="this.style.background=\'rgba(255,255,255,.28)\'"
   onmouseout="this.style.background=\'rgba(255,255,255,.15)\'">
   <i class="mdi mdi-arrow-left"></i> Volver
</a>';
ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<?php if (empty($indicadores) || empty($estudiantes)): ?>
  <div class="empty-state">
    <i class="mdi mdi-table-large"></i>
    <p>No hay datos para consolidar en este período.</p>
  </div>
<?php else: ?>

<div class="info-box-blue">
  <i class="mdi mdi-information-outline"></i>
  <strong>Período:</strong> <?= e($periodo_nombre) ?>
  &nbsp;|&nbsp; <strong>Grupo:</strong> <?= e($grupo['grado'].' '.$grupo['grupo']) ?>
  &nbsp;|&nbsp; <strong>Total estudiantes:</strong> <?= count($estudiantes) ?>
  &nbsp;|&nbsp; <strong>Indicadores:</strong> <?= count($indicadores) ?>
</div>

<div class="card" style="overflow-x:auto;margin-top:16px">
  <table class="data-table consol-table">
    <thead>
      <tr>
        <th rowspan="2" style="min-width:180px">Estudiante</th>
        <?php foreach ($indicadores as $ind): ?>
          <th style="text-align:center;min-width:90px">
            <?= e($ind['codigo']) ?><br>
            <span class="text-xs text-muted">(peso <?= $ind['peso'] ?>)</span>
          </th>
        <?php endforeach; ?>
        <th style="text-align:center;min-width:90px;background:#EEF2FF">Nota Final</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($estudiantes as $est): ?>
      <tr>
        <td><?= e($est['apellido'].', '.$est['nombre']) ?></td>
        <?php foreach ($indicadores as $ind): ?>
          <?php $nota = $est['indicadores'][$ind['id']] ?? null; ?>
          <td style="text-align:center">
            <?php if ($nota !== null): ?>
              <strong class="<?= $nota >= 6 ? 'nota-ok' : 'nota-fail' ?>">
                <?= number_format($nota, 1) ?>
              </strong>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td style="text-align:center;background:#F5F7FF">
          <?php if ($est['nota_final'] !== null): ?>
            <strong class="<?= $est['nota_final'] >= 6 ? 'nota-ok' : 'nota-fail' ?>" style="font-size:1rem">
              <?= number_format($est['nota_final'], 1) ?>
            </strong>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <!-- Promedios del grupo por indicador -->
    <tfoot>
      <tr>
        <td><strong>Promedio grupo</strong></td>
        <?php foreach ($indicadores as $ind): ?>
          <?php
            $vals = array_filter(array_map(fn($e)=>$e['indicadores'][$ind['id']]??null, $estudiantes), fn($v)=>$v!==null);
            $prom = count($vals) ? round(array_sum($vals)/count($vals),1) : null;
          ?>
          <td style="text-align:center">
            <?php if ($prom!==null): ?>
              <strong class="<?= $prom>=6?'nota-ok':'nota-fail' ?>"><?= $prom ?></strong>
            <?php else: ?>—<?php endif; ?>
          </td>
        <?php endforeach; ?>
        <?php
          $finales = array_filter(array_column($estudiantes,'nota_final'),fn($v)=>$v!==null);
          $promFin = count($finales) ? round(array_sum($finales)/count($finales),1) : null;
        ?>
        <td style="text-align:center;background:#EEF2FF">
          <?php if ($promFin!==null): ?>
            <strong class="<?= $promFin>=6?'nota-ok':'nota-fail' ?>"><?= $promFin ?></strong>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>

<!-- Leyenda de indicadores -->
<div class="consol-leyenda">
  <?php foreach ($indicadores as $ind): ?>
  <div class="leyenda-item">
    <strong><?= e($ind['codigo']) ?>:</strong> <?= e($ind['descripcion']) ?>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>

