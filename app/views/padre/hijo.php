<?php
$pageTitle  = htmlspecialchars($hijo['nombre'] . ' ' . $hijo['apellido']) . ' — ' . APP_NAME;
$noLeidos   = $mensajes_no_leidos ?? 0;
$breadcrumbs = [
    ['label' => 'Inicio',      'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis acudidos','url' => APP_URL . '/mis-hijos'],
    ['label' => $hijo['nombre'] . ' ' . $hijo['apellido'], 'url' => ''],
];

$estadosCfg = [
    'presente' => ['icon' => 'mdi-check-circle',        'color' => '#16A34A', 'bg' => '#DCFCE7', 'label' => 'Presente'],
    'tarde'    => ['icon' => 'mdi-clock-alert-outline',  'color' => '#D97706', 'bg' => '#FEF3C7', 'label' => 'Tarde'],
    'excusa'   => ['icon' => 'mdi-shield-check-outline', 'color' => '#2563EB', 'bg' => '#DBEAFE', 'label' => 'Excusa'],
    'ausente'  => ['icon' => 'mdi-close-circle',         'color' => '#DC2626', 'bg' => '#FEE2E2', 'label' => 'Ausente'],
    'incapacidad' => ['icon' => 'mdi-hospital-box-outline', 'color' => '#7C3AED', 'bg' => '#EDE9FE', 'label' => 'Incapacidad'],
];

$tipoObsCfg = [
    'academica'     => ['label' => 'Académica',    'color' => '#2563EB', 'bg' => '#DBEAFE'],
    'convivencia'   => ['label' => 'Convivencia',  'color' => '#9333EA', 'bg' => '#F3E8FF'],
    'logro'         => ['label' => 'Logro',        'color' => '#16A34A', 'bg' => '#DCFCE7'],
    'disciplinaria' => ['label' => 'Disciplinaria','color' => '#B91C1C', 'bg' => '#FEE2E2'],
    'otro'          => ['label' => 'Otro',         'color' => '#6B7280', 'bg' => '#F3F4F6'],
];

// Período activo ID
$periodo_activo_id = null;
foreach ($periodos_ids as $pid => $per) {
    if ($per['activo']) { $periodo_activo_id = $pid; break; }
}

// Período que se está mostrando actualmente (selector de períodos)
$periodo_mostrado = $periodo_mostrado_id ?? $periodo_activo_id;
$periodo_mostrado_nombre = $periodos_ids[$periodo_mostrado]['nombre'] ?? '';

// Calcular promedio general del período mostrado
$promedio_general = null;
$notas_activas = [];
foreach ($notas_tabla as $mid => $mat) {
    if (isset($mat['periodos'][$periodo_mostrado])) {
        $notas_activas[] = (float)$mat['periodos'][$periodo_mostrado]['promedio'];
    }
}
if (!empty($notas_activas)) {
    $promedio_general = round(array_sum($notas_activas) / count($notas_activas), 1);
}

// Total tareas no leídas
$_nl_total = 0;
foreach ($tareas ?? [] as $_t) {
    if (!$_t['vista']) $_nl_total++;
}
// Total observaciones no leídas
$_obs_nl = (int)($obs_no_leidas ?? 0);

ob_start();
?>

<!-- ── ENCABEZADO DEL ESTUDIANTE ──────────────────────────── -->
<div class="hijo-header-card">
  <div class="hijo-header-left">
    <div class="hijo-header-avatar">
      <?= strtoupper(substr($hijo['nombre'], 0, 1)) ?>
    </div>
    <div class="hijo-header-info">
      <h1 class="hijo-header-nombre"><?= e($hijo['nombre'] . ' ' . $hijo['apellido']) ?></h1>
      <div class="hijo-header-meta">
        <span><i class="mdi mdi-google-classroom"></i> <?= e($hijo['grado'] . ' · ' . $hijo['grupo']) ?></span>
        <?php
          $estadoMat = $hijo['estado_matricula'] ?? 'activo';
          $esActivo  = $estadoMat === 'activo';
        ?>
        <span class="hijo-estado-badge <?= $esActivo ? 'activo' : 'inactivo' ?>">
          <i class="mdi mdi-<?= $esActivo ? 'check-circle' : 'clock-outline' ?>"></i>
          <?= ucfirst($estadoMat) ?>
        </span>
      </div>
      <?php if ($promedio_general !== null): ?>
      <div class="hijo-header-prom">
        Promedio <?= e($periodo_mostrado_nombre) ?>:
        <strong class="<?= $promedio_general >= 6 ? 'nota-ok' : 'nota-fail' ?>">
          <?= number_format($promedio_general, 1) ?>
        </strong>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="hijo-header-actions">
    <a href="<?= APP_URL ?>/comunicaciones/redactar" class="btn-secondary btn-sm">
      <i class="mdi mdi-message-text-outline"></i> Mensaje
    </a>
    <?php if (count($hijos) > 1):
      // Link al otro hijo
      foreach ($hijos as $oh):
        if ((int)$oh['estudiante_id'] !== (int)$hijo['estudiante_id']):
    ?>
    <a href="<?= APP_URL ?>/mis-hijos/<?= $oh['estudiante_id'] ?>" class="btn-secondary btn-sm">
      <i class="mdi mdi-account-switch-outline"></i> <?= e($oh['nombre']) ?>
    </a>
    <?php endif; endforeach; endif; ?>
  </div>
</div>

<!-- ── TABS INTERNOS + SELECTOR DE PERÍODO ───────────────── -->
<div class="hijo-tabs-row">
<div class="hijo-tabs">
  <?php
  $tabs = [
    'resumen'      => ['icon' => 'mdi-view-dashboard-outline', 'label' => 'Resumen'],
    'aula'         => ['icon' => 'mdi-book-open-page-variant-outline', 'label' => 'Aula'],
    'notas'        => ['icon' => 'mdi-chart-timeline-variant',  'label' => 'Notas'],
    'tareas'       => ['icon' => 'mdi-pencil-box-outline',       'label' => 'Tareas'],
    'asistencia'   => ['icon' => 'mdi-calendar-check-outline',  'label' => 'Asistencia'],
    'observaciones'=> ['icon' => 'mdi-message-text-outline',     'label' => 'Observaciones'],
    'mensajes'     => ['icon' => 'mdi-email-outline',             'label' => 'Mensajes'],
    'info'         => ['icon' => 'mdi-account-details-outline',  'label' => 'Información'],
  ];
  foreach ($tabs as $key => $t):
  ?>
  <?php
    $nl_attr = '';
    if ($key === 'tareas' && $_nl_total > 0)  $nl_attr = 'data-nl="' . $_nl_total . '"';
    if ($key === 'observaciones' && $_obs_nl > 0) $nl_attr = 'data-nl="' . $_obs_nl . '"';
    if ($key === 'asistencia' && ($asist_alertas ?? 0) > 0) $nl_attr = 'data-nl="' . (int)$asist_alertas . '"';
    if ($key === 'resumen'    && ($notas_alertas ?? 0) > 0)  $nl_attr = 'data-nl="' . (int)$notas_alertas . '"';
    if ($key === 'mensajes'   && ($mensajes_hijo_no_leidos ?? 0) > 0) $nl_attr = 'data-nl="' . (int)$mensajes_hijo_no_leidos . '"';
  ?>
  <a href="?tab=<?= $key ?>"
     class="hijo-tab <?= $tab === $key ? 'active' : '' ?>"
     <?= $nl_attr ?>>
    <i class="mdi <?= $t['icon'] ?>"></i>
    <span><?= $t['label'] ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!empty($periodos_ids)): ?>
<div class="hijo-periodo-selector">
  <i class="mdi mdi-calendar-range-outline"></i>
  <select onchange="location.href='?tab=<?= e($tab) ?>&periodo='+this.value+'&modo=<?= e($modo_nota ?? 'valor') ?>'">
    <?php
    // Periodos ordenados de más reciente a más antiguo
    $periodos_orden = $periodos_ids;
    krsort($periodos_orden);
    foreach ($periodos_orden as $pid => $per):
    ?>
    <option value="<?= $pid ?>" <?= $pid === $periodo_mostrado ? 'selected' : '' ?>>
      <?= e($per['nombre']) ?><?= $per['activo'] ? ' (activo)' : '' ?>
    </option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>

<?php if (!empty($desempenios)): ?>
<div class="hijo-periodo-selector">
  <i class="mdi mdi-chart-box-outline"></i>
  <select onchange="location.href='?tab=<?= e($tab) ?>&periodo=<?= (int)$periodo_mostrado ?>&modo='+this.value">
    <option value="valor" <?= ($modo_nota ?? 'valor') === 'valor' ? 'selected' : '' ?>>Número</option>
    <option value="texto" <?= ($modo_nota ?? 'valor') === 'texto' ? 'selected' : '' ?>>Desempeño (texto)</option>
    <option value="abrev" <?= ($modo_nota ?? 'valor') === 'abrev' ? 'selected' : '' ?>>Abreviatura</option>
  </select>
</div>
<?php endif; ?>
</div><!-- /hijo-tabs-row -->

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: RESUMEN                                                 -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php if ($tab === 'resumen'): ?>

<div class="padre-grid" style="margin-top:16px">

<?php
// Preparar JSON de evaluaciones (ya incrustadas en $notas_tabla['evals'])
$_notas_json = [];
foreach ($notas_tabla as $_mid_j => $_mat_j) {
    if (empty($_mat_j['evals'])) continue;
    // Agrupar por logro
    $_por_logro = [];
    foreach ($_mat_j['evals'] as $e) {
        $lkey = $e['logro_cod'] ?: $e['logro'];
        if (!isset($_por_logro[$lkey])) {
            $_por_logro[$lkey] = ['logro' => $e['logro'], 'evals' => []];
        }
        $_valorEv = (float)$e['valor'];
        $_por_logro[$lkey]['evals'][] = [
            'nombre' => $e['eval_nombre'],
            'valor'  => $_valorEv,
            // Etiqueta ya formateada según el modo elegido (número/texto/abreviatura),
            // para que el panel JS no tenga que reimplementar la escala de desempeños.
            'label'  => (!empty($desempenios) && ($modo_nota ?? 'valor') !== 'valor')
                ? strip_tags(desempenioDeNota($_valorEv, $desempenios, $modo_nota))
                : number_format($_valorEv, 1),
        ];
    }
    $_notas_json[(int)$_mid_j] = array_values($_por_logro);
}
?>
  <!-- Notas del período seleccionado -->
  <div class="padre-card">
    <div class="padre-card-head">
      <i class="mdi mdi-clipboard-text-outline" style="color:var(--primary)"></i>
      <span>Notas · <?= e($periodo_mostrado_nombre) ?></span>
      <a href="?tab=notas&periodo=<?= $periodo_mostrado ?>" class="padre-card-link">Ver todas <i class="mdi mdi-arrow-right"></i></a>
    </div>
    <?php if (empty($notas_tabla) || $periodo_mostrado === null): ?>
      <div class="padre-empty"><i class="mdi mdi-clipboard-list-outline"></i><p>Sin notas en este período.</p></div>
    <?php else: ?>
      <div class="padre-notas-list">
        <?php foreach ($notas_tabla as $mid_n => $mat):
          if (!isset($mat['periodos'][$periodo_mostrado])) continue;
          $prom        = (float)$mat['periodos'][$periodo_mostrado]['promedio'];
          $ok          = $prom >= 6.0;
          $doc_n       = $docente_por_materia[$mid_n] ?? null;
          $es_nva      = !empty($mat['es_nueva']);
          $tiene_evals = !empty($mat['evals']);
        ?>
        <div class="nota-mat-row<?= $es_nva ? ' nota-mat-nueva' : '' ?><?= $tiene_evals ? ' nota-mat-clickable' : '' ?>"
             data-mat-id="<?= $mid_n ?>"
             data-mat-nombre="<?= e($mat['nombre']) ?>"
             <?php if ($tiene_evals): ?>onclick="abrirNotasPanel(<?= $mid_n ?>, this)"<?php endif; ?>>
          <div class="nota-mat-info">
            <span class="nota-mat-nombre"><?= e($mat['nombre']) ?><?php if ($doc_n): ?><span class="nota-mat-doc"> — <?= e($doc_n) ?></span><?php endif; ?></span>
          </div>
          <div class="nota-mat-right">
            <?php if ($es_nva): ?><span class="nota-nueva-dot" title="Notas actualizadas"></span><?php endif; ?>
            <?php if (!empty($desempenios) && ($modo_nota ?? 'valor') !== 'valor'): ?>
              <?= desempenioDeNota($prom, $desempenios, $modo_nota) ?>
            <?php else: ?>
              <span class="padre-nota-val <?= $ok ? 'ok' : 'fail' ?>"><?= number_format($prom, 1) ?></span>
            <?php endif; ?>
            <?php if ($tiene_evals): ?><i class="mdi mdi-chevron-right nota-mat-chev"></i><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Asistencia reciente / Panel evaluaciones (comparten posición) -->
  <div style="position:relative">

    <!-- Tarjeta de asistencia -->
    <div class="padre-card" id="asist-card-<?= $hijo['estudiante_id'] ?>">
    <div class="padre-card-head">
      <i class="mdi mdi-calendar-check-outline" style="color:#16A34A"></i>
      <span>Asistencia reciente</span>
      <a href="?tab=asistencia" class="padre-card-link">Ver todo <i class="mdi mdi-arrow-right"></i></a>
    </div>

    <?php if (!empty($resumen_asist) && array_sum($resumen_asist) > 0): ?>
    <div class="asist-resumen-row">
      <?php foreach ($resumen_asist as $est => $cnt):
        if ($cnt === 0) continue;
        $cfg = $estadosCfg[$est];
      ?>
      <div class="asist-resumen-item" style="background:<?= $cfg['bg'] ?>">
        <i class="mdi <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>"></i>
        <span class="asist-resumen-num" style="color:<?= $cfg['color'] ?>"><?= $cnt ?></span>
        <span class="asist-resumen-lbl"><?= $cfg['label'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($asistencia)): ?>
      <div class="padre-empty"><i class="mdi mdi-calendar-blank-outline"></i><p>Sin registros de asistencia.</p></div>
    <?php else: ?>
      <div class="padre-asist-list">
        <?php foreach (array_slice($asistencia, 0, 6) as $a):
          $cfg  = $estadosCfg[$a['estado']] ?? $estadosCfg['ausente'];
          $hora = !empty($a['created_at']) ? date('H:i', strtotime($a['created_at'])) : null;
          $mat  = $a['materia'] ?? null;
          $reg  = !empty($a['reg_nombre']) ? $a['reg_nombre'].' '.$a['reg_apellido'] : null;
        ?>
        <div class="padre-asist-fila">
          <span class="padre-asist-dot" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
            <i class="mdi <?= $cfg['icon'] ?>"></i>
          </span>
          <span class="padre-asist-inline">
            <span class="pa-fecha"><?= date('d M', strtotime($a['fecha'])) ?></span>
            <?php if ($hora): ?><span class="pa-sep">·</span><span class="pa-hora"><?= $hora ?></span><?php endif; ?>
            <span class="pa-sep">·</span>
            <span class="pa-estado" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
            <?php if ($mat): ?><span class="pa-sep">·</span><span class="pa-mat"><?= e($mat) ?></span><?php endif; ?>
            <?php if ($reg): ?><span class="pa-sep">·</span><span class="pa-reg"><?= e($reg) ?></span><?php endif; ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Panel de evaluaciones (superpuesto sobre la tarjeta de asistencia) -->
  <div class="notas-overlay-panel" id="notas-panel-<?= $hijo['estudiante_id'] ?>">
    <div class="obs-detail-head">
      <button class="obs-detail-close" onclick="cerrarNotasPanel(<?= $hijo['estudiante_id'] ?>)">
        <i class="mdi mdi-arrow-left"></i>
      </button>
      <span class="obs-detail-title" id="notas-panel-titulo-<?= $hijo['estudiante_id'] ?>">Evaluaciones</span>
    </div>
    <div class="obs-detail-body" id="notas-panel-body-<?= $hijo['estudiante_id'] ?>"></div>
  </div>

  </div><!-- /posición relativa -->

</div>

<script>
var _notasMap_<?= $hijo['estudiante_id'] ?> = <?= json_encode($_notas_json) ?>;
var _notaMinAprob = <?= json_encode(notaMinAprobacion($desempenios ?? [])) ?>;

function abrirNotasPanel(matId, card) {
  var hid   = <?= (int)$hijo['estudiante_id'] ?>;
  var evals = _notasMap_<?= $hijo['estudiante_id'] ?>[matId];
  if (!evals) return;

  document.querySelectorAll('.nota-mat-clickable').forEach(function(c){ c.classList.remove('nota-mat-active'); });
  card.classList.add('nota-mat-active');

  document.getElementById('notas-panel-titulo-'+hid).textContent = card.dataset.matNombre || 'Evaluaciones';

  function _esc(s){ var d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
  var html = '';
  evals.forEach(function(grupo) {
    html += '<div class="np-logro-grupo">'
          + '<div class="np-logro-titulo">' + _esc(grupo.logro) + '</div>';
    grupo.evals.forEach(function(ev) {
      var cls = ev.valor >= _notaMinAprob ? 'np-nota-ok' : 'np-nota-fail';
      html += '<div class="np-eval-fila">'
            + '<span class="np-eval-nombre">' + _esc(ev.nombre) + '</span>'
            + '<span class="' + cls + '">' + _esc(ev.label) + '</span>'
            + '</div>';
    });
    html += '</div>';
  });
  document.getElementById('notas-panel-body-'+hid).innerHTML = html;

  // Mostrar panel, ocultar asistencia
  document.getElementById('asist-card-'+hid).style.display = 'none';
  document.getElementById('notas-panel-'+hid).style.display = 'flex';

  // Badge y marcar vista
  if (card.classList.contains('nota-mat-nueva')) {
    fetch('<?= APP_URL ?>/mis-hijos/'+hid+'/nota/'+matId+'/marcar', {
      method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(function(r){ return r.json(); }).then(function(d){
      if (!d.ok) return;
      card.classList.remove('nota-mat-nueva');
      var dot = card.querySelector('.nota-nueva-dot');
      if (dot) dot.remove();
      var tabEl = document.querySelector('.hijo-tab[href="?tab=resumen"][data-nl]');
      if (tabEl) {
        var tot = parseInt(tabEl.getAttribute('data-nl')) - 1;
        if (tot <= 0) tabEl.removeAttribute('data-nl');
        else tabEl.setAttribute('data-nl', tot);
      }
    }).catch(function(){});
  }
}

function cerrarNotasPanel(hid) {
  document.getElementById('notas-panel-'+hid).style.display = 'none';
  document.getElementById('asist-card-'+hid).style.display = '';
  document.querySelectorAll('.nota-mat-clickable').forEach(function(c){ c.classList.remove('nota-mat-active'); });
}
</script>

<!-- Observaciones recientes (resumen) -->
<?php if (!empty($observaciones)): ?>
<div class="padre-card" style="margin-top:16px">
  <div class="padre-card-head">
    <i class="mdi mdi-message-alert-outline" style="color:#9333EA"></i>
    <span>Observaciones recientes</span>
    <a href="?tab=observaciones" class="padre-card-link">Ver todas <i class="mdi mdi-arrow-right"></i></a>
  </div>
  <?php foreach (array_slice($observaciones, 0, 3) as $obs):
    $tcfg = $tipoObsCfg[$obs['tipo']] ?? $tipoObsCfg['otro'];
  ?>
  <div class="obs-row-compact">
    <div class="obs-row-head">
      <span class="obs-tipo-badge" style="background:<?= $tcfg['bg'] ?>;color:<?= $tcfg['color'] ?>"><?= $tcfg['label'] ?></span>
      <span class="obs-fecha-sm"><?= date('d M', strtotime($obs['fecha'])) ?></span>
      <?php if (!empty($obs['materia'])): ?>
        <span class="obs-doc-sm" style="color:var(--primary);font-weight:600"><i class="mdi mdi-book-outline"></i> <?= e($obs['materia']) ?></span>
      <?php endif; ?>
      <span class="obs-doc-sm"><i class="mdi mdi-account-tie-outline"></i> <?= e($obs['docente_nombre'].' '.$obs['docente_apellido']) ?></span>
    </div>
    <p class="obs-desc-sm"><?= e(mb_substr($obs['descripcion'],0,130)) ?><?= mb_strlen($obs['descripcion'])>130 ? '…' : '' ?></p>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: AULA                                                    -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'aula'): ?>

<?php if (!empty($periodos_ids)): ?>
<p style="margin:14px 0 0;font-size:.78rem;color:var(--text-muted)">
  Notas y tareas de: <strong style="color:var(--text-main,#111)"><?= e($periodo_mostrado_nombre) ?></strong>
</p>
<?php endif; ?>

<?php if (empty($aula_materias)): ?>
  <div class="empty-state" style="margin-top:32px">
    <i class="mdi mdi-book-open-outline"></i>
    <p>No hay materias asignadas al grupo de este estudiante.</p>
  </div>
<?php else: ?>
<?php
// Paleta de colores rotativos para letras de materias
$aulaPaleta = [
  ['bg'=>'#EEF2FF','color'=>'#3730A3'],['bg'=>'#FEF3C7','color'=>'#92400E'],
  ['bg'=>'#DCFCE7','color'=>'#166534'],['bg'=>'#FEE2E2','color'=>'#991B1B'],
  ['bg'=>'#F3E8FF','color'=>'#6B21A8'],['bg'=>'#DBEAFE','color'=>'#1E3A8A'],
  ['bg'=>'#FFF7ED','color'=>'#9A3412'],['bg'=>'#ECFDF5','color'=>'#065F46'],
  ['bg'=>'#FDF2F8','color'=>'#831843'],['bg'=>'#F0FDF4','color'=>'#14532D'],
  ['bg'=>'#EFF6FF','color'=>'#1D4ED8'],['bg'=>'#FFFBEB','color'=>'#78350F'],
  ['bg'=>'#FDF4FF','color'=>'#7E22CE'],['bg'=>'#FFF1F2','color'=>'#881337'],
];
?>
<div class="aula-lista-2col" style="margin-top:16px">
  <?php foreach ($aula_materias as $idx => $am):
    $nota    = $am['nota_activa'];
    $nota_ok = $nota !== null && $nota >= 6.0;
    $nota_falla = $nota !== null && !$nota_ok;
    $pal     = $aulaPaleta[$idx % count($aulaPaleta)];
    $letras  = strtoupper(substr($am['materia'], 0, 2));
    $pct     = $nota !== null ? min(100, round($nota * 10)) : 0;
    $bar_color = $nota_falla ? '#DC2626' : ($nota_ok ? '#16A34A' : '#D97706');
  ?>
  <div class="aula-lista-row">
    <div class="aula-lista-letra" style="background:<?= $pal['bg'] ?>;color:<?= $pal['color'] ?>">
      <?= $letras ?>
    </div>
    <div class="aula-lista-info">
      <div class="aula-lista-mat"><?= e($am['materia']) ?></div>
      <div class="aula-lista-doc"><?= e($am['doc_nombre'].' '.$am['doc_apellido']) ?></div>
    </div>
    <div class="aula-lista-bar-wrap">
      <?php if ($nota !== null): ?>
        <div class="aula-lista-bar-bg">
          <div class="aula-lista-bar-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div>
        </div>
        <div class="aula-lista-nota" style="color:<?= $bar_color ?>"><?= number_format($nota, 1) ?></div>
      <?php else: ?>
        <div class="aula-lista-nota" style="color:var(--text-muted)">—</div>
      <?php endif; ?>
    </div>
    <div class="aula-lista-stat">
      <strong><?= $am['total_tareas'] ?></strong>
      <span>tareas</span>
      <?php if (!empty($am['no_leidas'])): ?>
        <span class="aula-badge-nl"><?= $am['no_leidas'] ?></span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: NOTAS                                                   -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'notas'): ?>

<?php if (empty($notas_tabla)): ?>
  <div class="empty-state" style="margin-top:32px">
    <i class="mdi mdi-clipboard-list-outline"></i>
    <p>No hay notas registradas para este estudiante.</p>
  </div>
<?php else: ?>

<?php
// Solo mostrar columnas hasta el período seleccionado (inclusive), en orden de número de período
$numero_mostrado = $periodos_ids[$periodo_mostrado]['numero'] ?? PHP_INT_MAX;
$periodos_visibles = array_filter($periodos_ids, fn($per) => $per['numero'] <= $numero_mostrado);
uasort($periodos_visibles, fn($a, $b) => $a['numero'] <=> $b['numero']);
?>

<p style="margin:14px 0 0;font-size:.78rem;color:var(--text-muted)">
  Mostrando hasta: <strong style="color:var(--text-main,#111)"><?= e($periodo_mostrado_nombre) ?></strong>
</p>

<div class="card" style="overflow-x:auto;margin-top:12px">
  <table class="data-table">
    <thead>
      <tr>
        <th style="min-width:160px">Materia</th>
        <?php foreach ($periodos_visibles as $pid => $per): ?>
        <th style="text-align:center;min-width:110px">
          <?= e($per['nombre']) ?>
          <?php if ($per['porcentaje'] !== null && $per['porcentaje'] !== ''): ?>
            <span class="periodo-pct-chip"><?= number_format((float)$per['porcentaje'], 0) ?>%</span>
          <?php endif; ?>
          <?php if ($per['activo']): ?><span class="dot-active" style="display:inline-block;margin-left:4px;width:7px;height:7px;border-radius:50%;background:var(--success)"></span><?php endif; ?>
        </th>
        <?php endforeach; ?>
        <th style="text-align:center;min-width:90px;background:#F0F4FF">Promedio</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($notas_tabla as $mat):
        $periodos_mat_visibles = array_intersect_key($mat['periodos'], $periodos_visibles);
        $vals_prom = array_filter(array_map(fn($p) => (float)$p['promedio'], $periodos_mat_visibles), fn($v) => $v > 0);
        $prom_mat  = count($vals_prom) ? round(array_sum($vals_prom)/count($vals_prom), 1) : null;
      ?>
      <tr>
        <td style="padding:8px 12px;font-weight:600"><?= e($mat['nombre']) ?></td>
        <?php foreach ($periodos_visibles as $pid => $per):
          $p   = $mat['periodos'][$pid] ?? null;
          $val = $p ? (float)$p['promedio'] : null;
          $ok  = $val !== null && $val >= 6.0;
        ?>
        <td style="text-align:center;padding:8px">
          <?php if ($val !== null): ?>
            <?php if (!empty($desempenios) && ($modo_nota ?? 'valor') !== 'valor'): ?>
              <?= desempenioDeNota($val, $desempenios, $modo_nota) ?>
            <?php else: ?>
              <strong class="<?= $ok ? 'nota-ok' : 'nota-fail' ?>"><?= number_format($val, 1) ?></strong>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <?php endforeach; ?>
        <td style="text-align:center;background:#F5F7FF;padding:8px">
          <?php if ($prom_mat !== null): ?>
            <?php if (!empty($desempenios) && ($modo_nota ?? 'valor') !== 'valor'): ?>
              <?= desempenioDeNota($prom_mat, $desempenios, $modo_nota) ?>
            <?php else: ?>
              <strong class="<?= $prom_mat >= 6.0 ? 'nota-ok' : 'nota-fail' ?>"><?= number_format($prom_mat, 1) ?></strong>
            <?php endif; ?>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="consol-leyenda" style="margin-top:10px">
  <span style="color:var(--success);font-weight:700">■</span> Aprobado (≥ 6.0) &nbsp;
  <span style="color:var(--danger);font-weight:700">■</span> Por mejorar (&lt; 6.0)
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: TAREAS                                                  -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'tareas'): ?>

<?php
  $_tareas_tab = $tareas_mostradas ?? $tareas;
?>
<?php if (!empty($periodos_ids)): ?>
<p style="margin:14px 0 0;font-size:.78rem;color:var(--text-muted)">
  Mostrando tareas de: <strong style="color:var(--text-main,#111)"><?= e($periodo_mostrado_nombre) ?></strong>
</p>
<?php endif; ?>
<?php if (empty($_tareas_tab)): ?>
  <div class="empty-state" style="margin-top:16px">
    <i class="mdi mdi-pencil-box-outline"></i>
    <p>No hay tareas publicadas para este período.</p>
  </div>
<?php else:
  // Agrupar por materia
  $tareas_por_mat    = [];
  $nl_por_mat_nombre = []; // nombre_materia => count_no_leidas
  foreach ($_tareas_tab as $t) {
    $tareas_por_mat[$t['materia']][] = $t;
    if (!$t['vista']) {
      $nl_por_mat_nombre[$t['materia']] = ($nl_por_mat_nombre[$t['materia']] ?? 0) + 1;
    }
  }
?>
<div style="margin-top:16px" id="tareas-container-<?= $hijo['estudiante_id'] ?>">
<?php foreach ($tareas_por_mat as $materia => $lista): ?>
<div class="tarea-grupo-card" id="mat-<?= htmlspecialchars(preg_replace('/\W+/','-', strtolower($materia))) ?>">
  <div class="tarea-grupo-head">
    <i class="mdi mdi-book-outline"></i>
    <span><?= e($materia) ?></span>
    <div class="tarea-badges-right">
      <span class="tarea-count-badge" title="Total de tareas"><?= count($lista) ?></span>
      <?php if (!empty($nl_por_mat_nombre[$materia])): ?>
        <span class="tarea-nl-badge" title="Sin visualizar"><?= $nl_por_mat_nombre[$materia] ?> sin ver</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="tarea-lista">
    <?php foreach ($lista as $t):
      $vencida  = $t['fecha_limite'] && strtotime($t['fecha_limite']) < time();
      $proxima  = $t['fecha_limite'] && strtotime($t['fecha_limite']) > time() && strtotime($t['fecha_limite']) < (time() + 86400*3);
      $entregaCfg = [
        'ninguna' => ['icon'=>'mdi-minus-circle-outline',   'color'=>'#6B7280', 'label'=>'Sin entrega'],
        'clase'   => ['icon'=>'mdi-school-outline',          'color'=>'#2563EB', 'label'=>'En clase'],
        'linea'   => ['icon'=>'mdi-upload-outline',          'color'=>'#9333EA', 'label'=>'En línea'],
      ];
      $ecfg = $entregaCfg[$t['tipo_entrega'] ?? 'ninguna'] ?? $entregaCfg['ninguna'];
    ?>
    <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>/tarea/<?= $t['id'] ?>"
       class="tarea-item tarea-item-link <?= $vencida ? 'vencida' : ($proxima ? 'proxima' : '') ?><?= !$t['vista'] ? ' tarea-no-leida' : '' ?>"
       data-tarea-id="<?= $t['id'] ?>"
       data-hijo-id="<?= $hijo['estudiante_id'] ?>"
       data-vista="<?= $t['vista'] ? '1' : '0' ?>">
      <div class="tarea-item-head">
        <span class="tarea-nombre"><?= e($t['nombre']) ?></span>
        <span class="tarea-entrega-chip" style="color:<?= $ecfg['color'] ?>">
          <i class="mdi <?= $ecfg['icon'] ?>"></i> <?= $ecfg['label'] ?>
        </span>
      </div>
      <div class="tarea-item-meta">
        <span><i class="mdi mdi-calendar-clock-outline"></i> Publicada <?= date('d/m/Y', strtotime($t['fecha_publicacion'])) ?></span>
        <?php if ($t['fecha_limite']): ?>
          <span class="<?= $vencida ? 'text-danger' : ($proxima ? 'text-warning' : '') ?>">
            <i class="mdi mdi-calendar-alert-outline"></i>
            Entrega <?= date('d/m/Y H:i', strtotime($t['fecha_limite'])) ?>
            <?php if ($vencida): ?><span class="tarea-badge-vencida">Vencida</span><?php endif; ?>
            <?php if ($proxima): ?><span class="tarea-badge-proxima">Próxima</span><?php endif; ?>
          </span>
        <?php else: ?>
          <span style="color:var(--text-muted)"><i class="mdi mdi-infinity"></i> Sin fecha límite</span>
        <?php endif; ?>
        <span style="color:var(--text-muted)">
          <i class="mdi mdi-account-tie-outline"></i> <?= e($t['doc_nombre'] . ' ' . $t['doc_apellido']) ?>
        </span>
      </div>
      <?php if (!empty($t['descripcion'])): ?>
      <div class="tarea-desc"><?= nl2br(e(mb_substr(strip_tags($t['descripcion']), 0, 200))) ?>
        <?php if (mb_strlen(strip_tags($t['descripcion'])) > 200): ?><span style="color:var(--text-muted)">…</span><?php endif; ?>
      </div>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<script>
(function(){
  // Marcar tarea como vista al hacer clic, antes de navegar
  document.querySelectorAll('.tarea-item-link[data-vista="0"]').forEach(function(el) {
    el.addEventListener('click', function(e) {
      var tid  = el.dataset.tareaId;
      var hid  = el.dataset.hijoId;
      var href = el.href;
      e.preventDefault();
      // Marcar en servidor (fire-and-forget, navegamos igual)
      fetch('<?= APP_URL ?>/mis-hijos/' + hid + '/tarea/' + tid + '/marcar', {
        method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest'}
      }).catch(function(){}).finally(function(){ window.location.href = href; });
      // Actualizar UI inmediatamente
      el.classList.remove('tarea-no-leida');
      el.dataset.vista = '1';
      // Decrementar badge de materia
      var grupo = el.closest('.tarea-grupo-card');
      if (grupo) {
        var nlBadge = grupo.querySelector('.tarea-nl-badge');
        if (nlBadge) {
          var cnt = parseInt(nlBadge.textContent) - 1;
          if (cnt <= 0) { nlBadge.remove(); }
          else { nlBadge.textContent = cnt + ' sin ver'; }
        }
      }
      // Decrementar badge del tab (via data-nl + ::after)
      var tabEl = document.querySelector('.hijo-tab[data-nl]');
      if (tabEl) {
        var tot = parseInt(tabEl.dataset.nl) - 1;
        if (tot <= 0) { delete tabEl.dataset.nl; tabEl.removeAttribute('data-nl'); }
        else { tabEl.dataset.nl = tot; }
      }
    });
  });
})();
</script>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: ASISTENCIA                                              -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'asistencia'): ?>

<?php if (!empty($periodos_ids)): ?>
<p style="margin:14px 0 0;font-size:.78rem;color:var(--text-muted)">
  Asistencia de: <strong style="color:var(--text-main,#111)"><?= e($periodo_mostrado_nombre) ?></strong>
</p>
<?php endif; ?>

<div style="margin-top:16px">

<?php if (!empty($resumen_asist) && array_sum($resumen_asist) > 0): ?>
<div class="asist-resumen-row" style="margin-bottom:16px">
  <?php foreach ($resumen_asist as $est => $cnt):
    $cfg = $estadosCfg[$est];
  ?>
  <div class="asist-resumen-item" style="background:<?= $cfg['bg'] ?>">
    <i class="mdi <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>"></i>
    <span class="asist-resumen-num" style="color:<?= $cfg['color'] ?>"><?= $cnt ?></span>
    <span class="asist-resumen-lbl"><?= $cfg['label'] ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($asistencia)): ?>
  <div class="empty-state"><i class="mdi mdi-calendar-blank-outline"></i><p>Sin registros de asistencia.</p></div>
<?php else: ?>
  <div class="card">
    <table class="data-table">
      <thead>
        <tr>
          <th style="white-space:nowrap">Fecha</th>
          <th style="white-space:nowrap">Hora</th>
          <th style="text-align:center">Estado</th>
          <th>Materia</th>
          <th>Docente</th>
          <th>Observación</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($asistencia as $a):
          $cfg  = $estadosCfg[$a['estado']] ?? $estadosCfg['ausente'];
          $hora = !empty($a['created_at']) ? date('H:i', strtotime($a['created_at'])) : '—';
          $reg  = !empty($a['reg_nombre']) ? e($a['reg_nombre'].' '.$a['reg_apellido']) : '—';
          $mat  = !empty($a['materia']) ? e($a['materia']) : '—';
        ?>
        <tr>
          <td style="padding:7px 12px;white-space:nowrap;font-weight:600">
            <?= date('d/m/Y', strtotime($a['fecha'])) ?>
          </td>
          <td style="padding:7px 10px;white-space:nowrap;color:var(--text-muted);font-size:.83rem">
            <?= $hora ?>
          </td>
          <td style="text-align:center;padding:7px 8px">
            <span class="asist-estado-chip" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
              <i class="mdi <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
            </span>
          </td>
          <td style="padding:7px 12px;font-size:.83rem;font-weight:600;color:var(--text)"><?= $mat ?></td>
          <td style="padding:7px 12px;font-size:.83rem;color:var(--text-muted)"><?= $reg ?></td>
          <td style="padding:7px 12px;color:var(--text-muted);font-size:.83rem">
            <?= $a['observacion'] ? e($a['observacion']) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

</div>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: OBSERVACIONES                                           -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'observaciones'): ?>

<?php
// Preparar datos de observaciones para el panel
$obs_json = [];
foreach ($observaciones as $obs) {
    $tcfg_o = $tipoObsCfg[$obs['tipo']] ?? $tipoObsCfg['otro'];
    $obs_json[$obs['id']] = [
        'id'       => $obs['id'],
        'tipo'     => $tcfg_o['label'],
        'color'    => $tcfg_o['color'],
        'bg'       => $tcfg_o['bg'],
        'fecha'    => date('d/m/Y', strtotime($obs['fecha'])),
        'materia'  => $obs['materia'] ?? '',
        'docente'  => $obs['docente_nombre'].' '.$obs['docente_apellido'],
        'desc'     => $obs['descripcion'],
        'vista'    => (bool)$obs['vista'],
    ];
}
?>

<!-- Layout: lista + panel deslizante -->
<div class="obs-split" id="obs-split-<?= $hijo['estudiante_id'] ?>" style="margin-top:16px">

  <!-- Lista de cards (columna izquierda) -->
  <div class="obs-split-lista" id="obs-lista-<?= $hijo['estudiante_id'] ?>">
  <?php if (empty($observaciones)): ?>
    <div class="empty-state"><i class="mdi mdi-message-text-outline"></i><p>No hay observaciones registradas.</p></div>
  <?php else: ?>
    <?php foreach ($observaciones as $obs):
      $tcfg   = $tipoObsCfg[$obs['tipo']] ?? $tipoObsCfg['otro'];
      $no_vista = !$obs['vista'];
    ?>
    <div class="obs-card-v2<?= $no_vista ? ' obs-no-vista' : '' ?> obs-card-clickable"
         data-obs-id="<?= $obs['id'] ?>"
         data-hijo-id="<?= $hijo['estudiante_id'] ?>"
         onclick="abrirObsPanel(<?= $obs['id'] ?>, this)">
      <div class="obs-card-v2-side" style="background:<?= $tcfg['color'] ?>"></div>
      <div class="obs-card-v2-body">
        <div class="obs-card-v2-head">
          <span class="obs-tipo-badge" style="background:<?= $tcfg['bg'] ?>;color:<?= $tcfg['color'] ?>"><?= $tcfg['label'] ?></span>
          <span class="obs-v2-fecha"><?= date('d/m/Y', strtotime($obs['fecha'])) ?></span>
          <?php if (!empty($obs['materia'])): ?>
            <span class="obs-v2-meta" style="color:var(--primary);font-weight:600">
              <i class="mdi mdi-book-outline"></i> <?= e($obs['materia']) ?>
            </span>
          <?php endif; ?>
          <span class="obs-v2-meta">
            <i class="mdi mdi-account-tie-outline"></i>
            <?= e($obs['docente_nombre'].' '.$obs['docente_apellido']) ?>
          </span>
          <?php if ($no_vista): ?>
            <span class="obs-nueva-chip">Nueva</span>
          <?php endif; ?>
        </div>
        <!-- Preview corto del texto -->
        <p class="obs-card-v2-desc obs-desc-preview">
          <?= e(mb_substr(strip_tags($obs['descripcion']), 0, 100)) ?><?= mb_strlen(strip_tags($obs['descripcion'])) > 100 ? '…' : '' ?>
        </p>
      </div>
      <div class="obs-card-chevron"><i class="mdi mdi-chevron-right"></i></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <!-- Panel de detalle deslizante -->
  <div class="obs-detail-panel" id="obs-detail-panel-<?= $hijo['estudiante_id'] ?>">
    <div class="obs-detail-head">
      <button class="obs-detail-close" onclick="cerrarObsPanel()">
        <i class="mdi mdi-arrow-left"></i>
      </button>
      <span class="obs-detail-title">Observación</span>
    </div>
    <div class="obs-detail-body" id="obs-detail-body-<?= $hijo['estudiante_id'] ?>">
      <!-- contenido dinámico -->
    </div>
  </div>

</div>

<script>
var _obsData_<?= $hijo['estudiante_id'] ?>  = <?= json_encode(array_values($obs_json)) ?>;
var _obsMap_<?= $hijo['estudiante_id'] ?>   = {};
_obsData_<?= $hijo['estudiante_id'] ?>.forEach(function(o){ _obsMap_<?= $hijo['estudiante_id'] ?>[o.id] = o; });

function abrirObsPanel(oid, card) {
  var hid = <?= (int)$hijo['estudiante_id'] ?>;
  var o = _obsMap_<?= $hijo['estudiante_id'] ?>[oid];
  if (!o) return;

  document.querySelectorAll('#obs-lista-'+hid+' .obs-card-clickable').forEach(function(c){ c.classList.remove('obs-card-active'); });
  card.classList.add('obs-card-active');

  var mats = o.materia ? '<span class="obs-det-meta" style="color:var(--primary);font-weight:600"><i class="mdi mdi-book-outline"></i> '+escH(o.materia)+'</span>' : '';
  document.getElementById('obs-detail-body-'+hid).innerHTML =
    '<div class="obs-det-header" style="border-left:4px solid '+o.color+'">'
  +   '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">'
  +     '<span class="obs-tipo-badge" style="background:'+o.bg+';color:'+o.color+'">'+escH(o.tipo)+'</span>'
  +     '<span class="obs-det-fecha"><i class="mdi mdi-calendar-outline"></i> '+escH(o.fecha)+'</span>'
  +   '</div>'
  +   '<div style="display:flex;flex-wrap:wrap;gap:6px 14px;font-size:.8rem;color:var(--text-muted)">'
  +     mats
  +     '<span class="obs-det-meta"><i class="mdi mdi-account-tie-outline"></i> '+escH(o.docente)+'</span>'
  +   '</div>'
  + '</div>'
  + '<div class="obs-det-desc">'+nl2brJS(escH(o.desc))+'</div>';

  document.getElementById('obs-split-'+hid).classList.add('panel-open');
  document.getElementById('obs-detail-panel-'+hid).classList.add('open');

  if (!o.vista) {
    fetch('<?= APP_URL ?>/mis-hijos/'+hid+'/observacion/'+oid+'/marcar', {
      method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(function(r){ return r.json(); }).then(function(d){
      if (!d.ok) return;
      o.vista = true;
      card.classList.remove('obs-no-vista');
      var chip = card.querySelector('.obs-nueva-chip');
      if (chip) chip.remove();
      var tabEl = document.querySelector('.hijo-tab.active[data-nl]');
      if (tabEl) {
        var tot = parseInt(tabEl.getAttribute('data-nl')) - 1;
        if (tot <= 0) tabEl.removeAttribute('data-nl');
        else tabEl.setAttribute('data-nl', tot);
      }
    }).catch(function(){});
  }
}

function cerrarObsPanel() {
  var hid = <?= (int)$hijo['estudiante_id'] ?>;
  document.getElementById('obs-detail-panel-'+hid).classList.remove('open');
  document.getElementById('obs-split-'+hid).classList.remove('panel-open');
  document.querySelectorAll('#obs-lista-'+hid+' .obs-card-clickable').forEach(function(c){ c.classList.remove('obs-card-active'); });
}

function escH(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function nl2brJS(s){ return s.replace(/\n/g,'<br>'); }
</script>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: MENSAJES DEL ESTUDIANTE (cuenta aún no habilitada)     -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'mensajes'): ?>

<div class="info-box-blue" style="margin-top:16px">
  <i class="mdi mdi-information-outline"></i>
  <span>
    <?= e($hijo['nombre']) ?> todavía no tiene una cuenta activa para ingresar a la plataforma.
    Mientras tanto, aquí puedes leer los mensajes que los docentes le envíen directamente
    y responder en su nombre.
  </span>
</div>

<?php
// Agrupar mensajes en hilos (estilo Gmail) usando la columna real hilo_id.
// _normAsuntoHilo solo se usa para limpiar el texto mostrado (quitar "Re:", "Re2:"...),
// nunca para decidir a qué hilo pertenece un mensaje.
function _normAsuntoHilo(string $s): string {
    $s = trim($s);
    while (preg_match('/^(re\d*)\s*:\s*/i', $s, $mm)) {
        $s = trim(substr($s, strlen($mm[0])));
    }
    return $s !== '' ? $s : trim($s);
}

$_hilos = []; // hilo_id => ['asunto'=>..., 'mensajes'=>[...]]
foreach ($mensajes_hijo as $m) {
    $clave = (int)($m['hilo_id'] ?? $m['id']);
    if (!isset($_hilos[$clave])) {
        $_hilos[$clave] = ['asunto' => _normAsuntoHilo($m['asunto']), 'mensajes' => []];
    }
    $_hilos[$clave]['mensajes'][] = $m;
}
// Most recent conversation first; dentro de cada hilo los mensajes ya vienen ASC
$_hilos_ordenados = array_values($_hilos);
usort($_hilos_ordenados, function($a, $b) {
    $fa = end($a['mensajes'])['created_at'];
    $fb = end($b['mensajes'])['created_at'];
    return strcmp($fb, $fa);
});

$_hilo_json = [];
foreach ($_hilos_ordenados as $idx => $h) {
    $_hilo_json[$idx] = [
        'asunto'   => $h['asunto'],
        'mensajes' => array_map(function($m) {
            return [
                'id'      => $m['id'],
                'cuerpo'  => $m['cuerpo'], // ya viene como HTML saneado (safeHtml)
                'fecha'   => date('d/m/Y H:i', strtotime($m['created_at'])),
                'rem'     => $m['rem_nombre'] . ' ' . $m['rem_apellido'],
                'leido'   => (bool)$m['leido'],
                'es_mio'  => (bool)$m['es_mio'],
                'adjuntos'=> (int)$m['num_adjuntos'],
            ];
        }, $h['mensajes']),
    ];
}
?>

<div class="obs-split" id="msj-split-<?= $hijo['estudiante_id'] ?>" style="margin-top:14px">

  <div class="obs-split-lista" id="msj-lista-<?= $hijo['estudiante_id'] ?>">
  <?php if (empty($_hilos_ordenados)): ?>
    <div class="empty-state"><i class="mdi mdi-email-outline"></i><p>No hay mensajes para este estudiante.</p></div>
  <?php else: ?>
    <?php foreach ($_hilos_ordenados as $idx => $h):
      $ultimo      = end($h['mensajes']);
      $no_leidos_h = 0;
      foreach ($h['mensajes'] as $mm) { if (!$mm['es_mio'] && !$mm['leido']) $no_leidos_h++; }
      $tiene_no_leidos = $no_leidos_h > 0;
      $ultimo_es_mio   = (bool)$ultimo['es_mio'];
      $sideColor = $tiene_no_leidos ? '#2563EB' : ($ultimo_es_mio ? '#16A34A' : '#9CA3AF');
      $num_msgs  = count($h['mensajes']);
    ?>
    <div class="obs-card-v2<?= $tiene_no_leidos ? ' obs-no-vista' : '' ?> obs-card-clickable"
         data-hilo-idx="<?= $idx ?>"
         data-hijo-id="<?= $hijo['estudiante_id'] ?>"
         onclick="abrirMsjPanel(<?= $idx ?>, this)">
      <div class="obs-card-v2-side" style="background:<?= $sideColor ?>"></div>
      <div class="obs-card-v2-body">
        <div class="obs-card-v2-head">
          <span class="obs-v2-meta" style="font-weight:700;color:var(--text-main,#111)">
            <?php if ($ultimo_es_mio): ?>
              <i class="mdi mdi-account-arrow-right-outline"></i> Tú (en nombre de <?= e($hijo['nombre']) ?>)
            <?php else: ?>
              <i class="mdi mdi-account-tie-outline"></i> <?= e($ultimo['rem_nombre'].' '.$ultimo['rem_apellido']) ?>
            <?php endif; ?>
          </span>
          <span class="obs-v2-fecha"><?= date('d/m/Y H:i', strtotime($ultimo['created_at'])) ?></span>
          <?php if ($num_msgs > 1): ?>
            <span class="obs-v2-meta"><i class="mdi mdi-message-reply-text-outline"></i> <?= $num_msgs ?></span>
          <?php endif; ?>
          <?php if ($tiene_no_leidos): ?><span class="obs-nueva-chip"><?= $no_leidos_h ?> sin leer</span><?php endif; ?>
        </div>
        <p class="obs-card-v2-desc obs-desc-preview" style="font-weight:600;color:var(--text-main,#111)"><?= e($h['asunto']) ?></p>
        <p class="obs-card-v2-desc obs-desc-preview">
          <?= e(mb_substr(strip_tags($ultimo['cuerpo']), 0, 100)) ?><?= mb_strlen(strip_tags($ultimo['cuerpo'])) > 100 ? '…' : '' ?>
        </p>
      </div>
      <div class="obs-card-chevron"><i class="mdi mdi-chevron-right"></i></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <div class="obs-detail-panel" id="msj-detail-panel-<?= $hijo['estudiante_id'] ?>">
    <div class="obs-detail-head">
      <button class="obs-detail-close" onclick="cerrarMsjPanel()">
        <i class="mdi mdi-arrow-left"></i>
      </button>
      <span class="obs-detail-title">Conversación</span>
    </div>
    <div class="obs-detail-body" id="msj-detail-body-<?= $hijo['estudiante_id'] ?>"></div>
  </div>

</div>

<script>
var _hilosData = <?= json_encode(array_values($_hilo_json)) ?>;

function abrirMsjPanel(idx, card) {
  var hilo = _hilosData[idx];
  if (!hilo) return;
  var hid = card.dataset.hijoId;

  document.querySelectorAll('.obs-card-clickable').forEach(function(c){ c.classList.remove('obs-card-active'); });
  card.classList.add('obs-card-active');

  document.querySelector('#msj-detail-panel-' + hid + ' .obs-detail-title').textContent = hilo.asunto;

  var html = '';
  var idsNoLeidos = [];
  hilo.mensajes.forEach(function(m) {
    if (!m.es_mio && !m.leido) idsNoLeidos.push(m.id);
    var quien = m.es_mio
      ? '<i class="mdi mdi-account-arrow-right-outline"></i> Tú'
      : '<i class="mdi mdi-account-tie-outline"></i> ' + escH(m.rem);
    html += '<div class="msj-hilo-item' + (m.es_mio ? ' msj-hilo-mio' : '') + '">'
          +   '<div class="msj-hilo-head">'
          +     '<span class="msj-hilo-rem">' + quien + '</span>'
          +     '<span class="msj-hilo-fecha">' + escH(m.fecha) + '</span>'
          +   '</div>'
          +   '<div class="msg-body-html">' + m.cuerpo + '</div>'
          + '</div>';
  });

  var ultimoId = hilo.mensajes[hilo.mensajes.length - 1].id;
  html += '<div style="border-top:1px solid var(--border,#e5e7eb);padding-top:14px;margin-top:6px">'
        +   '<form method="POST" action="<?= APP_URL ?>/mis-hijos/' + hid + '/mensaje/' + ultimoId + '/responder">'
        +     '<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">'
        +     '<label style="font-size:.78rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px">Responder en nombre del estudiante</label>'
        +     '<textarea name="cuerpo" rows="4" required placeholder="Escribe tu respuesta..." style="width:100%;border:1px solid var(--border,#d1d5db);border-radius:8px;padding:8px 10px;font-size:.85rem;font-family:inherit;resize:vertical"></textarea>'
        +     '<button type="submit" class="btn-primary btn-sm" style="margin-top:8px"><i class="mdi mdi-send-outline"></i> Enviar respuesta</button>'
        +   '</form>'
        + '</div>';

  document.getElementById('msj-detail-body-' + hid).innerHTML = html;

  document.getElementById('msj-split-' + hid).classList.add('panel-open');
  document.getElementById('msj-detail-panel-' + hid).classList.add('open');

  if (idsNoLeidos.length) {
    idsNoLeidos.forEach(function(mid) {
      fetch('<?= APP_URL ?>/mis-hijos/' + hid + '/mensaje/' + mid + '/marcar', {
        method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}
      }).catch(function(){});
    });
    hilo.mensajes.forEach(function(m){ m.leido = true; });
    card.classList.remove('obs-no-vista');
    var chip = card.querySelector('.obs-nueva-chip');
    if (chip) chip.remove();
    var sideEl = card.querySelector('.obs-card-v2-side');
    if (sideEl) sideEl.style.background = card.querySelector('.mdi-account-arrow-right-outline') ? '#16A34A' : '#9CA3AF';
    var tabEl = document.querySelector('.hijo-tab[href="?tab=mensajes"][data-nl]');
    if (tabEl) {
      var tot = parseInt(tabEl.getAttribute('data-nl')) - idsNoLeidos.length;
      if (tot <= 0) tabEl.removeAttribute('data-nl');
      else tabEl.setAttribute('data-nl', tot);
    }
  }
}

function cerrarMsjPanel() {
  var hid = <?= (int)$hijo['estudiante_id'] ?>;
  document.getElementById('msj-detail-panel-' + hid).classList.remove('open');
  document.getElementById('msj-split-' + hid).classList.remove('panel-open');
  document.querySelectorAll('.obs-card-clickable').forEach(function(c){ c.classList.remove('obs-card-active'); });
}

function escH(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>

<!-- ════════════════════════════════════════════════════════════ -->
<!-- TAB: INFORMACIÓN PERSONAL                                    -->
<!-- ════════════════════════════════════════════════════════════ -->
<?php elseif ($tab === 'info'): ?>

<div class="padre-grid" style="margin-top:16px">

  <!-- Datos personales -->
  <div class="padre-card">
    <div class="padre-card-head">
      <i class="mdi mdi-card-account-details-outline" style="color:var(--primary)"></i>
      <span>Datos personales</span>
      <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>/editar" class="padre-card-link" style="margin-left:auto">
        <i class="mdi mdi-pencil-outline"></i> Editar
      </a>
    </div>
    <div class="info-fields">
      <?php
        $campos = [
          ['Documento',        ($info['tipo_documento'] ?? '') . ' ' . ($info['documento'] ?? '—')],
          ['Fecha nacimiento',  $info['fecha_nacimiento'] ? date('d/m/Y', strtotime($info['fecha_nacimiento'])) : '—'],
          ['Género',            ['M'=>'Masculino','F'=>'Femenino','O'=>'Otro'][$info['genero'] ?? ''] ?? '—'],
          ['Lugar nacimiento',  $info['lugar_nacimiento'] ?? '—'],
          ['Dirección',         $info['direccion'] ?? '—'],
          ['Barrio',            $info['barrio'] ?? '—'],
          ['EPS',               $info['eps'] ?? '—'],
          ['Estrato',           $info['estrato'] ?? '—'],
          ['Correo',            $info['email'] ?? '—'],
        ];
        foreach ($campos as [$lbl, $val]):
      ?>
      <div class="info-field-row">
        <span class="info-field-label"><?= $lbl ?></span>
        <span class="info-field-value"><?= is_string($val) ? e($val) : $val ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Contacto de emergencia + Familiares -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <?php if (!empty($info['contacto_emergencia'])): ?>
    <div class="padre-card">
      <div class="padre-card-head">
        <i class="mdi mdi-phone-alert-outline" style="color:#DC2626"></i>
        <span>Contacto de emergencia</span>
      </div>
      <div class="info-fields">
        <div class="info-field-row">
          <span class="info-field-label">Nombre</span>
          <span class="info-field-value"><?= e($info['contacto_emergencia']) ?></span>
        </div>
        <div class="info-field-row">
          <span class="info-field-label">Teléfono</span>
          <span class="info-field-value"><?= e($info['tel_emergencia'] ?? '—') ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="padre-card">
      <div class="padre-card-head">
        <i class="mdi mdi-account-group-outline" style="color:#9333EA"></i>
        <span>Núcleo familiar</span>
      </div>
      <?php if (empty($familiares)): ?>
        <div class="padre-empty"><i class="mdi mdi-account-multiple-outline"></i><p>Sin familiares registrados.</p></div>
      <?php else: ?>
        <div style="padding:4px 0">
          <?php foreach ($familiares as $fam): ?>
          <div class="familiar-row">
            <div class="familiar-avatar"><?= strtoupper(substr($fam['nombre'], 0, 1)) ?></div>
            <div class="familiar-info">
              <strong><?= e($fam['nombre'] . ' ' . $fam['apellido']) ?></strong>
              <?php if (!empty($fam['parentesco'])): ?>
                <span><?= e(ucfirst($fam['parentesco'])) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
