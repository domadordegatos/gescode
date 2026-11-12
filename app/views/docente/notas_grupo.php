<?php
$pageTitle   = 'Notas comparadas — '.APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo  = 'estudiantes';
$breadcrumbs = [
  ['label'=>'Inicio',    'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos','url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Notas comparadas','url'=>''],
];

/* ── Construir estructura pivot ────────────────────────────────
   Usa el primer estudiante como plantilla de columnas,
   luego mapea las notas de cada estudiante por posición.
   materias[] → indicadores[] → evaluaciones[] (columnas)
                              → notas[est_idx][eval_idx]
   ────────────────────────────────────────────────────────── */
$pivot = [];
if (!empty($estudiantes)) {
    foreach (($estudiantes[0]['materias'] ?? []) as $mi => $mat) {
        $pm = ['nombre' => $mat['nombre'], 'promedios' => [], 'indicadores' => []];
        foreach ($mat['indicadores'] as $ii => $ind) {
            $pi = [
                'codigo'      => $ind['codigo'],
                'descripcion' => $ind['descripcion'],
                'peso'        => $ind['peso'],
                'evaluaciones'=> [],   // definición de columnas
                'definitivas' => [],   // [est_idx] => valor
                'notas'       => [],   // [est_idx][eval_idx] => valor
            ];
            foreach ($ind['evaluaciones'] as $ei => $ev) {
                $pi['evaluaciones'][] = ['nombre' => $ev['nombre'], 'porcentaje' => $ev['porcentaje']];
            }
            $pm['indicadores'][] = $pi;
        }
        $pivot[] = $pm;
    }
    // Rellenar notas por estudiante
    foreach ($estudiantes as $si => $est) {
        foreach (($est['materias'] ?? []) as $mi => $mat) {
            if (!isset($pivot[$mi])) continue;
            $pivot[$mi]['promedios'][$si] = $mat['promedio'];
            foreach ($mat['indicadores'] as $ii => $ind) {
                if (!isset($pivot[$mi]['indicadores'][$ii])) continue;
                $pivot[$mi]['indicadores'][$ii]['definitivas'][$si] = $ind['definitiva'];
                foreach ($ind['evaluaciones'] as $ei => $ev) {
                    $pivot[$mi]['indicadores'][$ii]['notas'][$si][$ei] = $ev['nota'];
                }
            }
        }
    }
}

ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<!-- Header strip -->
<div class="ng-header-strip" style="margin-top:20px">
  <i class="mdi mdi-clipboard-list-outline"></i>
  <div class="ng-header-info">
    <h2>Notas comparadas · <?= count($estudiantes) ?> estudiante<?= count($estudiantes)!==1?'s':'' ?></h2>
    <small>
      <?= e($grupo['grado'].' '.$grupo['grupo']) ?> &nbsp;·&nbsp;
      <?= $periodo_sel ? e($periodo_sel['nombre']) : 'Todos los periodos' ?>
    </small>
  </div>
  <div style="margin-left:auto">
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/estudiantes"
       style="color:rgba(255,255,255,.75);font-size:.85rem;text-decoration:none;font-weight:600">
      <i class="mdi mdi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<!-- Selector de periodo -->
<?php if (!empty($periodos)):
  $uid_qs = implode('&', array_map(fn($e) => 'uid[]='.$e['usuario_id'], $estudiantes)); ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;align-items:center">
  <span style="font-size:.82rem;color:var(--text-muted);font-weight:600">Periodo:</span>
  <?php foreach (periodosVisibles($periodos) as $p): $active = ((int)$p['id'] === $periodo_id); ?>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/notas-grupo?<?= $uid_qs ?>&periodo=<?= $p['id'] ?>"
     class="periodo-chip <?= $active ? 'active' : '' ?>">
    <?php if ($p['activo']): ?><span class="periodo-chip-dot"></span>&nbsp;<?php endif; ?>
    <?= e($p['nombre']) ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($pivot)): ?>
  <div class="empty-state">
    <i class="mdi mdi-account-group-outline"></i>
    <p>No hay datos de notas para mostrar.</p>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/estudiantes" class="btn btn-primary" style="margin-top:10px">Volver</a>
  </div>
<?php else: ?>

<?php foreach ($pivot as $mat): ?>
<!-- ══ MATERIA ══════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:28px;overflow:visible">

  <!-- Cabecera materia -->
  <div style="display:flex;align-items:center;justify-content:space-between;
              padding:14px 18px;border-bottom:2px solid var(--border);background:#f8f9fb;
              border-radius:12px 12px 0 0;">
    <span style="font-weight:700;font-size:1rem;color:var(--primary)">
      <i class="mdi mdi-book-open-variant" style="margin-right:6px"></i><?= e($mat['nombre']) ?>
    </span>
    <!-- Promedios de materia por estudiante -->
    <div style="display:flex;gap:12px;align-items:center">
      <?php foreach ($estudiantes as $si => $est):
        $prom = $mat['promedios'][$si] ?? null;
        $pc   = $prom === null ? 'sin-nota' : ($prom >= 3 ? 'aprobado' : 'reprobado');
        $pl   = $prom !== null ? number_format($prom,1) : '—';
      ?>
      <span style="font-size:.8rem;color:var(--text-muted)">
        <?= e($est['nombre'][0].''.$est['apellido'][0]) ?>
        <span class="nota-val <?= $pc ?>" style="margin-left:4px"><?= $pl ?></span>
      </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Un bloque por indicador -->
  <?php foreach ($mat['indicadores'] as $ii => $ind):
    $evCols = $ind['evaluaciones'];
    $colCount = count($evCols);
  ?>
  <div style="<?= $ii > 0 ? 'border-top:1px solid var(--border)' : '' ?>">

    <!-- Etiqueta indicador -->
    <div style="padding:10px 18px 6px;display:flex;align-items:baseline;gap:10px">
      <span style="font-size:.75rem;font-weight:700;color:var(--primary);
                   background:var(--primary-light);padding:2px 8px;border-radius:8px">
        <?= e($ind['codigo']) ?>
      </span>
      <span style="font-size:.85rem;color:var(--text);font-weight:600;flex:1">
        <?= e($ind['descripcion']) ?>
      </span>
      <span style="font-size:.78rem;color:var(--text-muted)">Peso <?= $ind['peso'] ?>%</span>
    </div>

    <!-- Tabla de evaluaciones -->
    <div style="overflow-x:auto">
      <table class="data-table ng-comp-table" style="margin:0;border-radius:0">
        <thead>
          <!-- Fila agrupadora evaluaciones -->
          <tr class="ng-comp-group-row">
            <th style="width:200px"></th>
            <?php foreach ($evCols as $ev): ?>
            <th class="ng-comp-eval-head">
              <i class="mdi mdi-account" style="font-size:1rem;display:block;margin-bottom:3px;opacity:.6"></i>
              <span class="ng-comp-eval-name" title="<?= e($ev['nombre']) ?>"><?= e($ev['nombre']) ?></span>
              <div class="ng-comp-eval-pct"><?= number_format($ev['porcentaje'],2) ?>%</div>
            </th>
            <?php endforeach; ?>
            <th class="ng-comp-def-head">DEFINITIVA</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estudiantes as $si => $est):
            $def = $ind['definitivas'][$si] ?? null;
            $dc  = $def === null ? 'sin-nota' : ($def >= 3 ? 'aprobado' : 'reprobado');
          ?>
          <tr class="ng-comp-row">
            <td class="ng-comp-student">
              <?= e($est['apellido'].', '.$est['nombre']) ?>
            </td>
            <?php foreach ($evCols as $ei => $ev):
              $nota = $ind['notas'][$si][$ei] ?? null;
              $nc   = $nota === null ? 'sin-nota' : ($nota >= 3 ? 'aprobado' : 'reprobado');
            ?>
            <td class="ng-comp-nota-cell">
              <?php if ($nota !== null): ?>
                <span class="ng-comp-nota <?= $nc ?>"><?= number_format($nota,1) ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:.8rem">—</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td class="ng-comp-def-cell">
              <span class="nota-val <?= $dc ?> lg"><?= $def !== null ? number_format($def,1) : '—' ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Pie: promedio final materia -->
  <div style="display:flex;align-items:center;gap:14px;padding:12px 18px;
              background:#eef2fb;border-top:2px solid var(--border);
              border-radius:0 0 12px 12px;flex-wrap:wrap">
    <span style="font-size:.82rem;font-weight:700;color:var(--primary);flex:1">
      Promedio final — <?= e($mat['nombre']) ?>
    </span>
    <?php foreach ($estudiantes as $si => $est):
      $prom = $mat['promedios'][$si] ?? null;
      $pc   = $prom === null ? 'sin-nota' : ($prom >= 3 ? 'aprobado' : 'reprobado');
    ?>
    <div style="display:flex;align-items:center;gap:6px">
      <span style="font-size:.82rem;color:var(--text-muted)"><?= e($est['apellido'].', '.$est['nombre']) ?></span>
      <span class="nota-val <?= $pc ?> lg"><?= $prom !== null ? number_format($prom,1) : '—' ?></span>
    </div>
    <?php endforeach; ?>
  </div>

</div><!-- /.card materia -->
<?php endforeach; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
