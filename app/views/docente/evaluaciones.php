<?php
$gid        = $grupo['grupo_id'];
$pageTitle  = 'Evaluaciones — '.$grupo['grado'].' '.$grupo['grupo'].' — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo = 'evaluaciones';
$breadcrumbs= [
  ['label'=>'Inicio',      'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis clases',  'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Evaluaciones','url'=>''],
];
$periodo_nombre = '';
foreach ($periodos as $p) { if ((int)$p['id']==$periodo_id) { $periodo_nombre=$p['nombre']; break; } }

$tipos_fi = [
  'notas'          => 'Registro de notas',
  'observaciones'  => 'Registro de observaciones',
  'planeaciones'   => 'Modificación de planeaciones',
  'recuperaciones' => 'Registro de recuperaciones',
  'habilitaciones' => 'Registro de habilitaciones',
];
$fi_colors = ['activa'=>'#16A34A','pendiente'=>'#D97706','cerrada'=>'#9CA3AF','no_definida'=>'#DC2626'];
$fi_icons  = ['activa'=>'mdi-check-circle','pendiente'=>'mdi-clock-outline','cerrada'=>'mdi-cancel','no_definida'=>'mdi-alert-circle-outline'];

// Paleta de colores para tipos (cíclica)
$tipo_paleta = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'];

ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<!-- SUB-BARRA -->
<div class="eval-subbar">
  <div class="eval-subbar-actions">
    <button class="eval-sb-btn btn-sb-primary" onclick="openModal('modal-indicador')">
      <i class="mdi mdi-plus-box-outline"></i> Agregar indicador
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/consolidado-indicadores?asig=<?= $asig_id ?>&periodo=<?= $periodo_id ?>"
       class="eval-sb-btn btn-sb-neutral">
      <i class="mdi mdi-table-large"></i> Consolidado indicadores
    </a>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/consolidado-periodo?asig=<?= $asig_id ?>"
       class="eval-sb-btn btn-sb-neutral">
      <i class="mdi mdi-calendar-month-outline"></i> Consolidado período
    </a>
    <button class="eval-sb-btn btn-sb-neutral" onclick="openModal('modal-fechas')">
      <i class="mdi mdi-calendar-clock"></i> Fechas
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas?asig=<?= $asig_id ?>&periodo=<?= $periodo_id ?>"
       class="eval-sb-btn btn-sb-neutral">
      <i class="mdi mdi-file-excel-outline"></i> Cargar desde Excel
    </a>
  </div>
  <div class="eval-subbar-filters">
    <select class="cp-select" onchange="location.href='?asig=<?= $asig_id ?>&periodo='+this.value">
      <?php foreach (periodosVisibles($periodos) as $p): ?>
        <option value="<?= $p['id'] ?>" <?= (int)$p['id']===$periodo_id?'selected':'' ?>>
          <?= e($p['nombre']) ?><?= $p['activo']?' ●':'' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (count($materias_grupo) > 1): ?>
    <select class="cp-select" onchange="location.href=this.value">
      <?php foreach ($materias_grupo as $a): ?>
        <option value="<?= APP_URL ?>/grupos/<?= $a['grupo_id'] ?>/evaluaciones?asig=<?= $a['asignacion_id'] ?>&periodo=<?= $periodo_id ?>"
                <?= (int)$a['asignacion_id']===$asig_id?'selected':'' ?>><?= e($a['materia']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <?php if (count($grupos_grado) > 1): ?>
    <select class="cp-select" onchange="location.href=this.value">
      <?php foreach ($grupos_grado as $g): ?>
        <option value="<?= APP_URL ?>/grupos/<?= $g['grupo_id'] ?>/evaluaciones?asig=<?= $g['asignacion_id'] ?>&periodo=<?= $periodo_id ?>"
                <?= (int)$g['grupo_id']===$gid?'selected':'' ?>><?= e($grupo['grado'].' '.$g['grupo']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL: Fechas importantes -->
<div class="modal-overlay" id="modal-fechas" style="display:none"
     onclick="if(event.target===this)closeModal('modal-fechas')">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header">
      <h3><i class="mdi mdi-calendar-clock"></i> Fechas — <?= e($periodo_nombre) ?></h3>
      <button onclick="closeModal('modal-fechas')" class="modal-close">&times;</button>
    </div>
    <?php foreach ($tipos_fi as $tipo => $label):
      $fi=$fechas_imp[$tipo]??null; $estado=Evaluacion::estadoFecha($fi);
      $color=$fi_colors[$estado]; $icon=$fi_icons[$estado]; ?>
    <div class="fecha-fi-row-inline" style="padding:8px 0">
      <i class="mdi <?= $icon ?>" style="color:<?= $color ?>;font-size:16px;flex-shrink:0"></i>
      <span class="fecha-fi-label-sm"><?= $label ?></span>
      <span class="fecha-fi-val-sm" style="color:<?= $color ?>">
        <?= $fi ? strftime_es($fi['fecha_inicio']).' → '.strftime_es($fi['fecha_fin']) : 'No definidas' ?>
      </span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- INDICADORES -->
<h2 class="eval-section-title">Evaluaciones</h2>

<?php if (empty($indicadores)): ?>
  <div class="empty-state">
    <i class="mdi mdi-clipboard-list-outline"></i>
    <p>No hay indicadores de logro para este período.</p>
    <button class="btn-primary" style="margin-top:12px" onclick="openModal('modal-indicador')">
      <i class="mdi mdi-plus"></i> Agregar primer indicador
    </button>
  </div>
<?php else: ?>

<?php foreach ($indicadores as $ind):
  $totalMat  = $ind['tipos'][0]['total'] ?? 0;
  $numTipos  = count($ind['tipos']);
  $numEvals  = count($ind['evaluaciones']);
  $conProm   = array_filter($ind['evaluaciones'], fn($e) => !$e['sin_calificar']);
  $promGlobal= count($conProm) ? round(array_sum(array_column($conProm,'promedio'))/count($conProm),1) : null;
?>
<div class="ind-block">
  <!-- Cabecera del indicador -->
  <div class="ind-header">
    <div class="ind-header-left" onclick="toggleInd(<?= $ind['id'] ?>)" style="cursor:pointer;flex:1;min-width:0">
      <span class="ind-num-icon"><i class="mdi mdi-bookmark-outline"></i></span>
      <div>
        <span class="ind-codigo"><?= e($ind['codigo']) ?>:</span>
        <span class="ind-desc"><?= e($ind['descripcion']) ?></span>
        <div class="ind-meta">
          <span class="ind-peso-tag">peso: <?= number_format($ind['peso'],2) ?></span>
          <span class="ind-meta-sep">·</span>
          <span class="text-muted" style="font-size:.75rem"><?= $numTipos ?> tipo<?= $numTipos!=1?'s':'' ?> · <?= $numEvals ?> eval<?= $numEvals!=1?'s':'' ?></span>
          <?php if ($promGlobal !== null): ?>
            <span class="ind-meta-sep">·</span>
            <strong class="<?= $promGlobal>=6?'nota-ok':'nota-fail' ?>" style="font-size:.78rem">Prom. <?= $promGlobal ?></strong>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Acciones en el header -->
    <div class="ind-header-actions">
      <a href="<?= APP_URL ?>/indicador/<?= $ind['id'] ?>/calificar"
         class="ind-action-btn btn-indigo-sm">
        <i class="mdi mdi-pencil-box-multiple-outline"></i> Calificar
      </a>
      <button class="ind-action-btn btn-teal-sm"
              onclick="abrirModalTipo(<?= $ind['id'] ?>, '<?= e(addslashes($ind['codigo'])) ?>')">
        <i class="mdi mdi-folder-plus-outline"></i> Agregar tipo
      </button>
      <a href="<?= APP_URL ?>/indicador/<?= $ind['id'] ?>/editar" class="ind-action-btn btn-orange-sm">
        <i class="mdi mdi-pencil-outline"></i> Editar
      </a>
      <form method="POST" action="<?= APP_URL ?>/indicador/<?= $ind['id'] ?>/eliminar" style="display:inline"
            onsubmit="return confirm('¿Eliminar este indicador y TODO su contenido?')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="ind-action-btn btn-danger-sm">
          <i class="mdi mdi-delete-outline"></i> Eliminar
        </button>
      </form>
      <i class="mdi mdi-chevron-down ind-chevron" id="chevron-<?= $ind['id'] ?>"
         onclick="toggleInd(<?= $ind['id'] ?>)" style="cursor:pointer;padding:4px 8px;flex-shrink:0"></i>
    </div><!-- /ind-header-actions -->
  </div><!-- /ind-header -->

  <!-- Contenido colapsable -->
  <div id="ind-body-<?= $ind['id'] ?>">
    <!-- Tipos con sus evaluaciones -->
    <?php if (empty($ind['tipos'])): ?>
      <div class="ind-empty">
        <i class="mdi mdi-information-outline"></i>
        Sin tipos — usa <strong>Agregar tipo</strong> para organizar las evaluaciones.
      </div>
    <?php else: ?>
      <?php foreach ($ind['tipos'] as $ti => $tipo):
        $color = $tipo_paleta[$ti % count($tipo_paleta)];
      ?>
      <div class="tipo-card" style="--tipo-color:<?= $color ?>">
        <!-- Cabecera del tipo -->
        <div class="tipo-card-head">
          <div class="tipo-card-left">
            <span class="tipo-color-bar"></span>
            <span class="tipo-nombre"><?= e($tipo['nombre']) ?></span>
            <span class="tipo-pct-badge"><?= number_format($tipo['porcentaje'],1) ?>%</span>
            <?php if ($tipo['promedio'] !== null): ?>
              <span class="tipo-prom <?= $tipo['promedio']>=6?'ok':'fail' ?>">
                Prom. <?= $tipo['promedio'] ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="tipo-card-actions">
            <button class="tipo-btn-agregar"
                    data-ind-id="<?= $ind['id'] ?>"
                    data-tipo-id="<?= $tipo['id'] ?>"
                    data-tipo-nombre="<?= e($tipo['nombre']) ?>"
                    data-ind-codigo="<?= e($ind['codigo']) ?>"
                    data-ind-desc="<?= e(mb_substr($ind['descripcion'],0,80)) ?>"
                    onclick="abrirModalEval(this)">
              <i class="mdi mdi-plus"></i> Agregar evaluación
            </button>
            <?php if ($tipo['id'] !== null): ?>
            <form method="POST" action="<?= APP_URL ?>/tipo-eval/<?= $tipo['id'] ?>/eliminar"
                  style="display:inline"
                  onsubmit="return confirm('¿Eliminar el tipo «<?= e(addslashes($tipo['nombre'])) ?>»? Las evaluaciones quedarán sin tipo.')">
              <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
              <input type="hidden" name="grupo_id"  value="<?= $gid ?>">
              <input type="hidden" name="asig_id"   value="<?= $asig_id ?>">
              <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">
              <button type="submit" class="tipo-btn-eliminar" title="Eliminar tipo">
                <i class="mdi mdi-close"></i>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tabla de evaluaciones del tipo -->
        <?php if (empty($tipo['evaluaciones'])): ?>
          <div class="tipo-eval-empty">
            Sin evaluaciones — usa <strong>Agregar evaluación</strong>.
          </div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="eval-detail-table tipo-eval-table">
            <thead>
              <tr>
                <th class="col-name">Evaluación</th>
                <th>Fecha</th>
                <th>Evaluados</th>
                <th>Perdidos</th>
                <th>Promedio</th>
                <th style="width:44px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tipo['evaluaciones'] as $ev): ?>
              <tr>
                <td class="col-name">
                  <div class="eval-nombre-row">
                    <i class="mdi mdi-file-document-outline eval-tipo-icon"></i>
                    <span class="eval-nombre-text"><?= e($ev['nombre']) ?></span>
                    <?php if ($ev['porcentaje'] != 100): ?>
                      <span class="eval-pct-tag"><?= number_format($ev['porcentaje'],0) ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($ev['opcional'])): ?>
                      <span class="eval-badge-opc" title="Opcional">opc.</span>
                    <?php endif; ?>
                    <?php if (!empty($ev['privado'])): ?>
                      <span class="eval-badge-priv" title="Privado"><i class="mdi mdi-eye-off-outline"></i></span>
                    <?php endif; ?>
                    <?php if (!empty($ev['recuperacion_nombre'])): ?>
                      <span class="eval-badge-recup" title="Recuperación de «<?= e($ev['recuperacion_nombre']) ?>»">
                        <i class="mdi mdi-refresh"></i> <?= e(mb_substr($ev['recuperacion_nombre'],0,20)) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="col-fecha text-muted text-sm"><?= strftime_es($ev['fecha']) ?></td>
                <td>
                  <?php if ($ev['sin_calificar']): ?>
                    <span class="eval-stat-danger">0 de <?= $ev['total'] ?></span>
                  <?php elseif ($ev['evaluados'] < $ev['total']): ?>
                    <span class="eval-stat-warning"><?= $ev['evaluados'] ?> de <?= $ev['total'] ?></span>
                  <?php else: ?>
                    <span class="eval-stat-ok"><?= $ev['evaluados'] ?> de <?= $ev['total'] ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($ev['perdidos'] > 0): ?>
                    <span class="eval-stat-danger"><?= $ev['perdidos'] ?> (<?= $ev['pct_perdidos'] ?>%)</span>
                  <?php elseif ($ev['evaluados'] > 0): ?>
                    <span class="eval-stat-ok">0</span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($ev['sin_calificar']): ?>
                    <span class="eval-stat-na">N.A.</span>
                  <?php else: ?>
                    <strong class="<?= $ev['promedio']>=6?'nota-ok':'nota-fail' ?>">
                      <?= number_format($ev['promedio'],1) ?>
                    </strong>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" action="<?= APP_URL ?>/evaluacion/<?= $ev['id'] ?>/eliminar"
                        onsubmit="return confirm('¿Eliminar esta evaluación y sus calificaciones?')">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                    <button type="submit" class="eval-btn-eliminar" title="Eliminar">
                      <i class="mdi mdi-delete-outline"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="ind-total-row">
                <td colspan="2" class="text-muted text-sm"><?= count($tipo['evaluaciones']) ?> evaluación<?= count($tipo['evaluaciones'])!=1?'es':'' ?></td>
                <td><strong><?= $tipo['sum_eval'] ?> de <?= count($tipo['evaluaciones']) * $totalMat ?></strong></td>
                <td><strong><?= $tipo['sum_perd'] ?></strong></td>
                <td>
                  <?php if ($tipo['promedio']!==null): ?>
                    <strong class="<?= $tipo['promedio']>=6?'nota-ok':'nota-fail' ?>"><?= $tipo['promedio'] ?></strong>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php endif; ?>
      </div><!-- .tipo-card -->
      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- /ind-body -->
</div><!-- /ind-block -->
<?php endforeach; ?>
<?php endif; ?>

<!-- MODAL: Nuevo indicador -->
<div class="modal-overlay" id="modal-indicador" style="display:none"
     onclick="if(event.target===this)closeModal('modal-indicador')">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="mdi mdi-bookmark-plus-outline"></i> Nuevo indicador de logro</h3>
      <button onclick="closeModal('modal-indicador')" class="modal-close">&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/indicador/crear">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <input type="hidden" name="asig_id"    value="<?= $asig_id ?>">
      <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">
      <div class="form-group">
        <label>Descripción <span class="required">*</span></label>
        <textarea name="descripcion" class="form-textarea" rows="3"
                  placeholder="Comprende y aplica…" required></textarea>
      </div>
      <div class="form-group">
        <label>Peso relativo</label>
        <input type="number" name="peso" class="form-input" value="1.00" min="0.1" max="10" step="0.1" style="max-width:100px">
        <small class="form-hint">1.00 = peso igual para todos los indicadores.</small>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Crear indicador</button>
        <button type="button" class="btn-secondary" onclick="closeModal('modal-indicador')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Nuevo tipo de evaluación -->
<div class="modal-overlay" id="modal-tipo" style="display:none"
     onclick="if(event.target===this)closeModal('modal-tipo')">
  <div class="modal-box" style="max-width:620px;width:95vw">
    <div class="modal-header">
      <h3><i class="mdi mdi-folder-plus-outline"></i> Nuevo tipo de evaluación</h3>
      <button onclick="closeModal('modal-tipo')" class="modal-close">&times;</button>
    </div>
    <div class="modal-ctx-bar">
      <i class="mdi mdi-bookmark-outline" style="color:var(--primary)"></i>
      Indicador: <strong id="tipo-ind-label">—</strong>
    </div>
    <form method="POST" action="<?= APP_URL ?>/tipo-eval/crear">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <input type="hidden" name="indicador_id" id="tipo-ind-id" value="">
      <input type="hidden" name="grupo_id"     value="<?= $gid ?>">
      <input type="hidden" name="asig_id"      value="<?= $asig_id ?>">
      <input type="hidden" name="periodo_id"   value="<?= $periodo_id ?>">

      <div class="modal-section-title">Configuración del tipo</div>

      <div class="form-group">
        <label>Nombre <span class="required">*</span></label>
        <input type="text" name="nombre" class="form-input"
               placeholder="Ej: Cuaderno, Talleres, Evaluación escrita, Exposición…" required>
        <small class="form-hint">Categoría que agrupa evaluaciones del mismo tipo.</small>
      </div>

      <div class="form-row-2">
        <div class="form-group" style="margin-bottom:0">
          <label>Peso dentro del indicador (%)</label>
          <input type="number" name="porcentaje" class="form-input" value="100"
                 min="1" max="100" step="0.01">
          <small class="form-hint">1 tipo → 100 %. Varios tipos deben sumar 100 %.</small>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <div class="modal-checks-col" style="margin-top:22px">
            <label class="check-row">
              <input type="checkbox" name="opcional" value="1">
              <span><strong>Opcional</strong> <em>— no penaliza si está vacío</em></span>
            </label>
            <label class="check-row" style="margin-top:6px">
              <input type="checkbox" name="privado" value="1">
              <span><strong>Privado</strong> <em>— oculto para padres y estudiantes</em></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Crear evaluaciones inline -->
      <div class="modal-section-title" style="margin-top:18px">
        Evaluaciones iniciales
        <small style="font-weight:400;color:var(--text-muted)"> (opcional — puedes agregarlas después)</small>
      </div>

      <div class="form-group">
        <label>Nombre de la evaluación</label>
        <input type="text" name="eval_nombre" id="tipo-eval-nom" class="form-input"
               placeholder="Ej: Nota, Taller, Parcial…"
               oninput="tipoEvalPreview()">
        <small class="form-hint">Si dejas vacío no se crean evaluaciones ahora.</small>
      </div>

      <div class="form-row-2">
        <div class="form-group" style="margin-bottom:0">
          <label>Cantidad</label>
          <input type="number" name="eval_cantidad" id="tipo-eval-cant" class="form-input"
                 value="1" min="0" max="20" step="1" oninput="tipoEvalPreview()">
          <small class="form-hint">0 = no crear evaluaciones.</small>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>Peso por evaluación (%)</label>
          <input type="number" name="eval_porcentaje" class="form-input"
                 value="100" min="1" max="100" step="0.01">
          <small class="form-hint">Si hay varias, deben sumar 100 %.</small>
        </div>
      </div>

      <div id="tipo-eval-preview" style="display:none;margin:6px 0 4px;font-size:.77rem;color:var(--text-muted)"></div>

      <div class="modal-checks-row" style="margin-top:6px">
        <label class="check-row">
          <input type="checkbox" name="eval_opcional" value="1">
          <span>Evaluación <strong>opcional</strong></span>
        </label>
        <label class="check-row">
          <input type="checkbox" name="eval_privado" value="1">
          <span>Evaluación <strong>privada</strong></span>
        </label>
      </div>

      <div class="form-actions" style="margin-top:16px">
        <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Crear tipo</button>
        <button type="button" class="btn-secondary" onclick="closeModal('modal-tipo')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Nueva evaluación -->
<div class="modal-overlay" id="modal-evaluacion" style="display:none"
     onclick="if(event.target===this)closeModal('modal-evaluacion')">
  <div class="modal-box" style="max-width:860px;width:95vw">
    <div class="modal-header">
      <h3><i class="mdi mdi-plus-circle-outline"></i> Agregar evaluación</h3>
      <button onclick="closeModal('modal-evaluacion')" class="modal-close">&times;</button>
    </div>
    <div class="modal-ctx-bar">
      <i class="mdi mdi-bookmark-outline" style="color:var(--primary)"></i>
      <span id="eval-ind-label">—</span>
      <span style="color:var(--border)">›</span>
      <i class="mdi mdi-folder-outline" style="color:var(--text-muted)"></i>
      <strong id="eval-tipo-label">—</strong>
    </div>
    <form method="POST" action="<?= APP_URL ?>/evaluacion/crear">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <input type="hidden" name="indicador_id"  id="eval-ind-id"   value="">
      <input type="hidden" name="tipo_eval_id"  id="eval-tipo-id"  value="">
      <input type="hidden" name="grupo_id"      value="<?= $gid ?>">
      <input type="hidden" name="asig_id"       value="<?= $asig_id ?>">
      <input type="hidden" name="periodo_id"    value="<?= $periodo_id ?>">
      <input type="hidden" name="fecha"         value="<?= date('Y-m-d') ?>">

      <div class="form-group" style="margin-bottom:10px">
        <label>Nombre <span class="required">*</span></label>
        <input type="text" name="nombre" id="eval-nombre" class="form-input"
               placeholder="Ej: Taller semana 3, Parcial 1, Exposición grupal…" required
               oninput="actualizarNombrePreview()">
      </div>

      <div id="eval-nombre-preview" style="display:none;margin-bottom:10px;font-size:.78rem;color:var(--text-muted)"></div>

      <div class="form-row-2">
        <div class="form-group" style="margin-bottom:0">
          <label>Cantidad</label>
          <input type="number" name="cantidad" id="eval-cantidad" class="form-input"
                 value="1" min="1" max="20" step="1" oninput="actualizarNombrePreview()">
          <small class="form-hint">Crea N evaluaciones numeradas.</small>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>Peso (%)</label>
          <input type="number" name="porcentaje" class="form-input"
                 value="100" min="1" max="100" step="0.01">
          <small class="form-hint">Si creas varias, se reparte automáticamente.</small>
        </div>
      </div>

      <!-- Recuperación de -->
      <div class="form-group" style="margin-top:10px">
        <label>Recuperación de</label>
        <select name="recuperacion_de" id="eval-recup" class="native-select">
          <option value="">— No es de recuperación —</option>
        </select>
        <small class="form-hint">
          Vincula esta eval a una anterior como su recuperación.
          La nota final usará el valor más alto de las dos.
        </small>
      </div>

      <div class="modal-checks-row" style="margin-top:10px">
        <label class="check-row">
          <input type="checkbox" name="opcional" value="1">
          <span><strong>Opcional</strong> <em>— no penaliza si está sin calificar</em></span>
        </label>
        <label class="check-row">
          <input type="checkbox" name="privado" value="1">
          <span><strong>Privado</strong> <em>— oculto para padres y estudiantes</em></span>
        </label>
      </div>

      <div class="form-actions" style="margin-top:16px">
        <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Agregar</button>
        <button type="button" class="btn-secondary" onclick="closeModal('modal-evaluacion')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<style>
/* ── Tipo cards ─────────────────────────── */
.tipo-card {
  margin: 0 0 12px 0;
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
}
.tipo-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 10px 5px 0;
  background: var(--bg);
  gap: 10px;
}
.tipo-card-left {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}
.tipo-color-bar {
  width: 4px;
  height: 36px;
  border-radius: 2px;
  background: var(--tipo-color);
  flex-shrink: 0;
}
.tipo-nombre {
  font-weight: 700;
  font-size: .9rem;
  color: var(--tipo-color);
}
.tipo-pct-badge {
  background: color-mix(in srgb, var(--tipo-color) 12%, transparent);
  color: var(--tipo-color);
  border: 1px solid color-mix(in srgb, var(--tipo-color) 30%, transparent);
  border-radius: 20px;
  padding: 1px 9px;
  font-size: .72rem;
  font-weight: 700;
}
.tipo-prom { font-size: .75rem; font-weight: 700; }
.tipo-prom.ok   { color: #16a34a; }
.tipo-prom.fail { color: #dc2626; }
.tipo-card-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.tipo-btn-agregar {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 6px;
  font-size: .75rem; font-weight: 600; cursor: pointer;
  background: color-mix(in srgb, var(--tipo-color) 10%, transparent);
  color: var(--tipo-color);
  border: 1px solid color-mix(in srgb, var(--tipo-color) 25%, transparent);
  transition: background .15s;
}
.tipo-btn-agregar:hover { background: color-mix(in srgb, var(--tipo-color) 18%, transparent); }
.tipo-btn-eliminar {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border-radius: 6px;
  background: transparent; border: 1px solid var(--border);
  color: var(--text-muted); font-size: .9rem; cursor: pointer;
  transition: background .12s, color .12s;
}
.tipo-btn-eliminar:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
.tipo-eval-empty {
  padding: 12px 16px; font-size: .82rem; color: var(--text-muted);
  background: var(--surface);
}
.tipo-eval-table { border-top: none; }
.tipo-eval-table thead th { background: var(--surface); }
.eval-pct-tag {
  display: inline-block;
  background: var(--bg); border: 1px solid var(--border);
  border-radius: 10px; padding: 0 5px;
  font-size: .68rem; color: var(--text-muted);
  margin-left: 4px;
}
.btn-teal-sm {
  background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4;
}
.btn-teal-sm:hover { background: #ccfbf1; }
/* Modal shared helpers */
.modal-ctx-bar {
  display: flex; align-items: center; gap: 6px;
  background: var(--bg); border-radius: 6px;
  padding: 7px 12px; margin-bottom: 14px;
  font-size: .82rem; color: var(--text-muted);
}
.modal-ctx-bar strong { color: var(--primary); }
.modal-section-title {
  font-size: .75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; color: var(--text-muted);
  margin-bottom: 10px; padding-bottom: 5px;
  border-bottom: 1px solid var(--border);
}
.check-row {
  display: flex; align-items: flex-start; gap: 7px;
  cursor: pointer; font-size: .82rem;
}
.check-row input[type=checkbox] {
  accent-color: var(--primary); width: 14px; height: 14px;
  margin-top: 2px; flex-shrink: 0;
}
.check-row em { color: var(--text-muted); font-style: normal; }
.modal-checks-row {
  display: flex; gap: 20px; flex-wrap: wrap;
}
.modal-checks-col {
  display: flex; flex-direction: column; gap: 4px;
}
.eval-badge-opc, .eval-badge-priv, .eval-badge-recup {
  display: inline-flex; align-items: center; gap: 2px;
  font-size: .67rem; padding: 1px 5px; border-radius: 3px;
  font-weight: 600; white-space: nowrap;
}
.eval-badge-opc  { background:#fef9c3; color:#a16207; border:1px solid #fde68a; }
.eval-badge-priv { background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; }
.eval-badge-recup{ background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
</style>

<script>
// Mapa indicador_id -> lista plana de evaluaciones (para recuperacion_de)
const EVAL_MAP = {};
<?php foreach ($indicadores as $ind): ?>
EVAL_MAP[<?= $ind['id'] ?>] = [
  <?php foreach ($ind['evaluaciones'] as $ev): ?>
  {id: <?= $ev['id'] ?>, nombre: <?= json_encode($ev['nombre']) ?>},
  <?php endforeach; ?>
];
<?php endforeach; ?>

function toggleInd(id) {
  const body    = document.getElementById('ind-body-' + id);
  const chevron = document.getElementById('chevron-' + id);
  const open    = body.style.display !== 'none';
  body.style.display      = open ? 'none' : 'block';
  chevron.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function abrirModalTipo(indId, indCodigo) {
  document.getElementById('tipo-ind-id').value          = indId;
  document.getElementById('tipo-ind-label').textContent = indCodigo;
  // reset inline eval fields
  document.getElementById('tipo-eval-nom').value  = '';
  document.getElementById('tipo-eval-cant').value = '1';
  document.getElementById('tipo-eval-preview').style.display = 'none';
  openModal('modal-tipo');
}

function tipoEvalPreview() {
  const nombre  = document.getElementById('tipo-eval-nom').value.trim();
  const cant    = parseInt(document.getElementById('tipo-eval-cant').value) || 0;
  const preview = document.getElementById('tipo-eval-preview');
  if (!nombre || cant <= 0) { preview.style.display = 'none'; return; }
  if (cant === 1) { preview.textContent = 'Se creará: "' + nombre + '"'; }
  else {
    const ejemplos = [];
    for (let i = 1; i <= Math.min(cant, 3); i++) ejemplos.push('"' + nombre + ' ' + i + '"');
    if (cant > 3) ejemplos.push('…');
    preview.textContent = 'Se crearán: ' + ejemplos.join(', ');
  }
  preview.style.display = 'block';
}

function abrirModalEval(btn) {
  const indId = btn.dataset.indId || '';
  document.getElementById('eval-ind-id').value          = indId;
  document.getElementById('eval-tipo-id').value         = btn.dataset.tipoId    || '';
  document.getElementById('eval-ind-label').textContent = btn.dataset.indCodigo || '—';
  document.getElementById('eval-tipo-label').textContent= btn.dataset.tipoNombre|| '—';
  document.getElementById('eval-cantidad').value        = 1;
  document.getElementById('eval-nombre').value          = '';
  document.getElementById('eval-nombre-preview').style.display = 'none';

  // Poblar dropdown recuperacion_de con evals del mismo indicador
  const sel   = document.getElementById('eval-recup');
  const evals = EVAL_MAP[parseInt(indId)] || [];
  sel.innerHTML = '<option value="">— No es de recuperación —</option>';
  evals.forEach(e => {
    const opt = document.createElement('option');
    opt.value = e.id;
    opt.textContent = e.nombre;
    sel.appendChild(opt);
  });
  if (window.initCustomSelects) initCustomSelects(sel.closest('.form-group'));

  openModal('modal-evaluacion');
}

function actualizarNombrePreview() {
  const nombre   = document.getElementById('eval-nombre').value.trim();
  const cantidad = parseInt(document.getElementById('eval-cantidad').value) || 1;
  const preview  = document.getElementById('eval-nombre-preview');
  if (!nombre || cantidad <= 1) { preview.style.display = 'none'; return; }
  const ejemplos = [];
  for (let i = 1; i <= Math.min(cantidad, 3); i++) ejemplos.push('"' + nombre + ' ' + i + '"');
  if (cantidad > 3) ejemplos.push('…');
  preview.textContent = 'Se crearán: ' + ejemplos.join(', ');
  preview.style.display = 'block';
}

document.querySelectorAll('[id^="ind-body-"]').forEach(el => el.style.display = 'block');
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
