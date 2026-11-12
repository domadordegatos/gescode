<?php
$pageTitle   = 'Editar indicador — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$gid         = $ind['grupo_id'];
$tab_activo  = 'evaluaciones';
$breadcrumbs = [
    ['label' => 'Inicio',       'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis Grupos',   'url' => APP_URL . '/mis-grupos'],
    ['label' => $ind['grado'] . ' ' . $ind['grupo'], 'url' => APP_URL . '/grupos/' . $gid . '/evaluaciones?asig=' . $ind['asignacion_id'] . '&periodo=' . $ind['periodo_id']],
    ['label' => 'Editar indicador', 'url' => ''],
];

// Mapeo desempeños → campos BD
$desemp_map = []; // orden descendente = Superior primero
foreach ($desempenios as $d) {
    $desemp_map[] = $d;
}

// $grupo requerido por _grupo_tabs.php
$grupo = [
    'grupo_id' => $ind['grupo_id'],
    'grado'    => $ind['grado'],
    'grupo'    => $ind['grupo'],
    'materia'  => $ind['materia'],
    'ano'      => $ind['ano'] ?? '',
];

$grupo_extra_title  = ' <span class="sep">·</span> <span style="font-size:.82rem;font-weight:600;opacity:.85">Editar indicador</span>';
$grupo_extra_action = '<a href="' . APP_URL . '/grupos/' . $gid . '/evaluaciones?asig=' . $ind['asignacion_id'] . '&periodo=' . $ind['periodo_id'] . '"
   style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:6px;
          background:rgba(255,255,255,.15);color:#fff;font-size:.78rem;font-weight:700"
   onmouseover="this.style.background=\'rgba(255,255,255,.28)\'"
   onmouseout="this.style.background=\'rgba(255,255,255,.15)\'">
   <i class="mdi mdi-arrow-left"></i> Volver
</a>';

// Campo BD por posición de desempeño (desc→asc: Superior, Alto, Básico, Inferior)
$red_fields = ['desc_superior', 'desc_alto', 'desc_basico', 'desc_inferior'];

ob_start();
?>

<?php include __DIR__ . '/_grupo_tabs.php'; ?>

<form method="POST" action="<?= APP_URL ?>/indicador/<?= $ind['id'] ?>/editar" id="form-editar">
<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">

  <!-- ── COLUMNA IZQUIERDA: contexto + datos base ── -->
  <div style="display:flex;flex-direction:column;gap:10px">

    <!-- Contexto (solo lectura) -->
    <div class="card" style="padding:14px 16px">
      <div class="text-xs fw-700" style="text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:10px">Contexto</div>
      <table style="width:100%;font-size:.83rem;border-collapse:collapse">
        <tr><td style="padding:3px 0;color:var(--text-muted);width:40%">Materia</td><td style="font-weight:700"><?= e($ind['materia']) ?></td></tr>
        <tr><td style="padding:3px 0;color:var(--text-muted)">Grupo</td><td style="font-weight:700"><?= e($ind['grado'] . ' ' . $ind['grupo']) ?></td></tr>
        <tr><td style="padding:3px 0;color:var(--text-muted)">Período</td><td style="font-weight:700"><?= e($ind['periodo_nombre']) ?></td></tr>
        <tr><td style="padding:3px 0;color:var(--text-muted)">Código</td><td style="font-weight:700"><?= e($ind['codigo']) ?></td></tr>
      </table>
    </div>

    <!-- Escala de desempeños (contexto) -->
    <div class="card" style="padding:14px 16px">
      <div class="text-xs fw-700" style="text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:8px">
        Escala de desempeños
        <a href="<?= APP_URL ?>/soporte/desempenios" style="float:right;font-weight:400;color:var(--primary);font-size:.72rem" target="_blank">Configurar</a>
      </div>
      <?php foreach ($desempenios as $d): ?>
      <div style="display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.8rem">
        <span style="background:<?= $d['es_perdido']?'#FEE2E2':'#DCFCE7' ?>;color:<?= $d['es_perdido']?'#B91C1C':'#15803D' ?>;padding:1px 7px;border-radius:20px;font-weight:700;font-size:.72rem;min-width:28px;text-align:center">
          <?= e($d['abreviatura']) ?>
        </span>
        <span><?= e($d['nombre']) ?></span>
        <span style="color:var(--text-muted);margin-left:auto"><?= number_format($d['rango_min'],1) ?> – <?= number_format($d['rango_max'],1) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Opciones -->
    <div class="card" style="padding:14px 16px">
      <div class="text-xs fw-700" style="text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:10px">Opciones</div>
      <label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;cursor:pointer;font-size:.85rem">
        <input type="checkbox" name="privado" value="1" <?= $ind['privado'] ? 'checked' : '' ?>
               style="width:16px;height:16px;margin-top:1px;accent-color:var(--primary)">
        <div>
          <strong>Privado</strong>
          <div style="font-size:.75rem;color:var(--text-muted)">Ocultar para padres de familia y estudiantes</div>
        </div>
      </label>
      <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:.85rem">
        <input type="checkbox" name="autoevaluacion" value="1" <?= $ind['autoevaluacion'] ? 'checked' : '' ?>
               style="width:16px;height:16px;margin-top:1px;accent-color:var(--primary)">
        <div>
          <strong>Autoevaluación</strong>
          <div style="font-size:.75rem;color:var(--text-muted)">El estudiante califica este indicador</div>
        </div>
      </label>
    </div>

  </div>

  <!-- ── COLUMNA DERECHA: enunciado + redacción ── -->
  <div style="display:flex;flex-direction:column;gap:10px">

    <!-- Indicador principal -->
    <div class="card" style="padding:14px 16px">
      <div class="text-xs fw-700" style="text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:10px">Indicador</div>
      <div class="form-group" style="margin-bottom:10px">
        <label style="font-size:.82rem">Enunciado <span class="required">*</span></label>
        <textarea name="descripcion" class="form-textarea" rows="3" id="enunciado-base"
                  oninput="actualizarPreview()" required><?= e($ind['descripcion']) ?></textarea>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label style="font-size:.82rem">Enunciado en segundo idioma <span style="color:var(--text-muted);font-weight:400">(opcional)</span></label>
        <textarea name="descripcion_2" class="form-textarea" rows="2"
                  placeholder="Ej: Understands and applies sequential structures…"><?= e($ind['descripcion_2'] ?? '') ?></textarea>
        <small class="form-hint">Para materias bilingües o con énfasis en inglés.</small>
      </div>
      <div class="form-group" style="margin-bottom:0;max-width:130px">
        <label style="font-size:.82rem">Peso</label>
        <input type="number" name="peso" class="form-input" value="<?= number_format((float)$ind['peso'],2) ?>"
               min="0.1" max="10" step="0.1" style="width:120px">
        <small class="form-hint">Peso 1.00 = todos iguales</small>
      </div>
    </div>

    <!-- Redacción por desempeño -->
    <div class="card" style="padding:14px 16px">
      <div class="text-xs fw-700" style="text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:10px">
        Redacción por nivel de desempeño
      </div>

      <div style="display:flex;gap:16px;margin-bottom:12px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem">
          <input type="radio" name="redaccion_tipo" value="prefijo"
                 <?= ($ind['redaccion_tipo'] ?? 'prefijo') === 'prefijo' ? 'checked' : '' ?>
                 onchange="actualizarModo()" style="accent-color:var(--primary)">
          Prefijo / Posfijo
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem">
          <input type="radio" name="redaccion_tipo" value="personalizado"
                 <?= ($ind['redaccion_tipo'] ?? '') === 'personalizado' ? 'checked' : '' ?>
                 onchange="actualizarModo()" style="accent-color:var(--primary)">
          Personalizado por nivel
        </label>
      </div>

      <!-- Modo: Prefijo/Posfijo (solo preview) -->
      <div id="modo-prefijo">
        <?php foreach ($desempenios as $i => $d):
          $color = $d['es_perdido'] ? '#B91C1C' : '#15803D';
          $bg    = $d['es_perdido'] ? '#FEF2F2' : '#F0FDF4';
        ?>
        <div style="background:<?= $bg ?>;border-radius:6px;padding:8px 12px;margin-bottom:6px;font-size:.82rem">
          <div style="font-weight:700;color:<?= $color ?>;margin-bottom:2px;font-size:.75rem">
            <?= e($d['abreviatura']) ?> — <?= e($d['nombre']) ?>
          </div>
          <div class="preview-text-<?= $i ?>" style="color:var(--text)">
            <?= e($ind['descripcion']) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Modo: Personalizado -->
      <div id="modo-personalizado" style="display:none">
        <?php foreach ($desempenios as $i => $d):
          $field = $red_fields[$i] ?? 'desc_inferior';
          $color = $d['es_perdido'] ? '#B91C1C' : '#15803D';
        ?>
        <div style="margin-bottom:10px">
          <label style="font-size:.78rem;font-weight:700;color:<?= $color ?>">
            <?= e($d['abreviatura']) ?> — <?= e($d['nombre']) ?>
            <span style="font-weight:400;color:var(--text-muted)">(<?= number_format($d['rango_min'],1) ?> – <?= number_format($d['rango_max'],1) ?>)</span>
          </label>
          <textarea name="<?= $field ?>" class="form-textarea" rows="2" style="font-size:.82rem;margin-top:3px"
                    placeholder="Texto para nivel <?= e($d['nombre']) ?>… (vacío = usa el enunciado base)"><?= e($ind[$field] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

  </div>
</div><!-- /grid -->

<div class="form-actions" style="margin-top:4px">
  <button type="submit" class="btn-primary">
    <i class="mdi mdi-content-save-outline"></i> Guardar indicador
  </button>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $ind['asignacion_id'] ?>&periodo=<?= $ind['periodo_id'] ?>"
     class="btn-secondary">Cancelar</a>
</div>

</form>

<script>
const modoInicial = '<?= $ind['redaccion_tipo'] ?? 'prefijo' ?>';

function actualizarModo() {
  const tipo = document.querySelector('input[name="redaccion_tipo"]:checked').value;
  document.getElementById('modo-prefijo').style.display      = tipo === 'prefijo'       ? 'block' : 'none';
  document.getElementById('modo-personalizado').style.display = tipo === 'personalizado' ? 'block' : 'none';
}

function actualizarPreview() {
  const texto = document.getElementById('enunciado-base').value;
  document.querySelectorAll('[class^="preview-text-"]').forEach(el => {
    el.textContent = texto;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  actualizarModo();
  // Forzar modo inicial
  if (modoInicial === 'personalizado') {
    document.querySelector('input[value="personalizado"]').checked = true;
    actualizarModo();
  }
});
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
