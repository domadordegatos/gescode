<?php
$gid        = $grupo['grupo_id'];
$asig_id    = $asig['asignacion_id'];
$pageTitle  = 'Nueva tarea — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs= [
  ['label'=>'Inicio',     'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos', 'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Tareas','url'=>APP_URL.'/grupos/'.$gid.'/tareas?asig='.$asig_id],
  ['label'=>'Nueva tarea','url'=>''],
];
$extraHead = '<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="' . APP_URL . '/assets/js/quill-utils.js"></script>';

// valor por defecto fecha publicación = ahora (formato datetime-local)
$ahora = date('Y-m-d\TH:i');
ob_start();
?>

<div class="page-header">
  <h1 class="page-title">Nueva tarea</h1>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas?asig=<?= $asig_id ?>" class="btn-ghost">
    <i class="mdi mdi-arrow-left"></i> Volver
  </a>
</div>

<form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas/guardar"
      enctype="multipart/form-data" id="form-tarea">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
  <input type="hidden" name="asignacion_id" value="<?= $asig_id ?>">
  <input type="hidden" name="descripcion" id="descripcion-hidden">

  <!-- ════ DATOS PRINCIPALES ════ -->
  <div class="tarea-card">

    <!-- Periodo (solo activo, bloqueado) -->
    <div class="form-group">
      <label>Periodo</label>
      <input type="text" class="form-input" value="<?= e($periodo['nombre']) ?>" disabled>
      <span class="form-hint">Las tareas se publican en el periodo activo actual.</span>
    </div>

    <!-- Contexto materia/grupo -->
    <div class="tarea-context">
      <i class="mdi mdi-google-classroom"></i>
      <strong><?= e($asig['materia']) ?></strong> ·
      <?= e($asig['grado'].' '.$asig['grupo']) ?>
    </div>

    <!-- Nombre -->
    <div class="form-group">
      <label>Nombre de la tarea <span class="form-required">*</span></label>
      <input type="text" name="nombre" class="form-input" required maxlength="200"
             placeholder="Ej. Avance semana 7" autofocus>
    </div>

    <!-- Para entregar en -->
    <div class="form-group">
      <label>Para entregar en</label>
      <div class="tarea-radios" id="tipo-entrega-group">
        <label class="tarea-radio">
          <input type="radio" name="tipo_entrega" value="ninguna" checked onchange="onTipoChange()">
          <span>No se requiere entrega</span>
        </label>
        <label class="tarea-radio">
          <input type="radio" name="tipo_entrega" value="clase" onchange="onTipoChange()">
          <span>En clase</span>
        </label>
        <label class="tarea-radio">
          <input type="radio" name="tipo_entrega" value="linea" onchange="onTipoChange()">
          <span>En línea</span>
        </label>
      </div>
    </div>

    <!-- Fecha publicación -->
    <div class="form-group">
      <label>Fecha de publicación <span class="form-required">*</span></label>
      <input type="datetime-local" name="fecha_publicacion" id="fecha-pub" class="form-input"
             value="<?= $ahora ?>" min="<?= $ahora ?>" required>
      <span class="form-hint">No puede ser anterior a la fecha y hora actual.</span>
    </div>

    <!-- Fecha límite (solo si requiere entrega) -->
    <div class="form-group" id="grupo-fecha-limite" style="display:none">
      <label>Fecha límite de entrega <span class="form-required">*</span></label>
      <input type="datetime-local" name="fecha_limite" id="fecha-limite" class="form-input"
             min="<?= $ahora ?>">
    </div>

    <!-- Tipo de evaluación -->
    <div class="form-group">
      <label>Tipo de evaluación</label>
      <select name="indicador_id" class="form-input">
        <option value="0">No crear evaluación</option>
        <?php foreach ($indicadores as $ind): ?>
          <option value="<?= $ind['id'] ?>">
            <?= e($ind['codigo'].': '.mb_strimwidth($ind['descripcion'],0,70,'…')) ?> — nota (100%)
          </option>
        <?php endforeach; ?>
      </select>
      <span class="form-hint">Si eliges un logro, se creará una evaluación con el nombre de la tarea.</span>
    </div>

    <!-- Foro -->
    <label class="tarea-check">
      <input type="checkbox" name="foro" value="1">
      <span>Habilitar foro de discusión</span>
    </label>
  </div>

  <!-- ════ DESCRIPCIÓN (editor) ════ -->
  <div class="tarea-card">
    <div class="form-group">
      <label>Descripción / instrucciones</label>
      <div id="quill-editor"></div>
      <p id="quill-word-count" style="font-size:.75rem;color:#9ca3af;text-align:right;margin-top:4px;">0 palabras · 0 caracteres</p>
    </div>

    <!-- Adjuntos -->
    <div class="form-group" style="margin-top:14px">
      <label>Archivos adjuntos <span class="form-hint">PDF, Office, imágenes, ZIP · máx. 10 MB c/u</span></label>
      <input type="file" name="adjuntos[]" id="adjunto-input" multiple style="display:none"
             accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.zip">
      <label for="adjunto-input" class="adjunto-btn">
        <i class="mdi mdi-folder-open-outline"></i> Seleccionar archivos
      </label>
      <ul class="adjunto-list" id="adjunto-list"></ul>
    </div>
  </div>

  <!-- ════ CREAR COPIA EN ════ -->
  <?php if (!empty($otros_grupos)): ?>
  <div class="tarea-card">
    <div class="tarea-section-title">
      <i class="mdi mdi-content-copy"></i> Crear copia en otros grupos de <?= e($asig['materia']) ?>
    </div>
    <p class="form-hint" style="margin-bottom:12px">
      Publica la misma tarea en otros grupos donde dictas esta materia. Cada grupo puede tener su propia forma de entrega.
    </p>

    <?php foreach ($otros_grupos as $og): ?>
    <div class="tarea-copia" data-asig="<?= $og['asignacion_id'] ?>">
      <label class="tarea-check tarea-copia-head">
        <input type="checkbox" class="copia-toggle" onchange="onCopiaToggle(this, <?= $og['asignacion_id'] ?>)">
        <span><i class="mdi mdi-account-group-outline"></i> <?= e($og['grado'].' '.$og['grupo']) ?></span>
      </label>

      <div class="tarea-copia-body" id="copia-body-<?= $og['asignacion_id'] ?>" style="display:none">
        <div class="form-group">
          <label>Para entregar en</label>
          <div class="tarea-radios">
            <label class="tarea-radio">
              <input type="radio" name="copia[<?= $og['asignacion_id'] ?>][tipo]" value="ninguna" checked
                     onchange="onCopiaTipoChange(<?= $og['asignacion_id'] ?>)">
              <span>No requiere entrega</span>
            </label>
            <label class="tarea-radio">
              <input type="radio" name="copia[<?= $og['asignacion_id'] ?>][tipo]" value="clase"
                     onchange="onCopiaTipoChange(<?= $og['asignacion_id'] ?>)">
              <span>En clase</span>
            </label>
            <label class="tarea-radio">
              <input type="radio" name="copia[<?= $og['asignacion_id'] ?>][tipo]" value="linea"
                     onchange="onCopiaTipoChange(<?= $og['asignacion_id'] ?>)">
              <span>En línea</span>
            </label>
          </div>
        </div>
        <div class="form-group copia-fecha-limite" id="copia-limite-<?= $og['asignacion_id'] ?>" style="display:none">
          <label>Fecha límite de entrega</label>
          <input type="datetime-local" name="copia[<?= $og['asignacion_id'] ?>][fecha_limite]" class="form-input" min="<?= $ahora ?>">
        </div>
        <div class="form-group">
          <label>Tipo de evaluación</label>
          <select name="copia[<?= $og['asignacion_id'] ?>][indicador_id]" class="form-input" data-copia-eval="<?= $og['asignacion_id'] ?>">
            <option value="0">No crear evaluación</option>
            <!-- Se llena por JS con los indicadores del grupo copia -->
          </select>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Aviso -->
  <div class="alert-info-box">
    <i class="mdi mdi-information-outline"></i>
    <div>Al guardar, se enviará automáticamente una notificación a los estudiantes y sus familiares.</div>
  </div>

  <div class="tarea-actions">
    <button type="submit" class="btn-primary" id="btn-guardar-tarea">
      <i class="mdi mdi-content-save-outline"></i> Guardar y publicar
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas?asig=<?= $asig_id ?>" class="btn-secondary">Cancelar</a>
  </div>
</form>

<script>
// Indicadores por asignación de los grupos copia (para llenar sus selects)
const INDICADORES_COPIA = <?= json_encode(array_map(fn($og)=>[
  'asignacion_id'=>(int)$og['asignacion_id']
], $otros_grupos), JSON_UNESCAPED_UNICODE) ?>;

// ── Tipo de entrega principal ──
function onTipoChange() {
  const tipo = document.querySelector('input[name="tipo_entrega"]:checked').value;
  const box  = document.getElementById('grupo-fecha-limite');
  const inp  = document.getElementById('fecha-limite');
  if (tipo === 'ninguna') {
    box.style.display = 'none'; inp.removeAttribute('required');
  } else {
    box.style.display = ''; inp.setAttribute('required', 'required');
  }
}

// ── Copia: activar/desactivar ──
function onCopiaToggle(cb, asigId) {
  document.getElementById('copia-body-' + asigId).style.display = cb.checked ? '' : 'none';
}
function onCopiaTipoChange(asigId) {
  const tipo = document.querySelector(`input[name="copia[${asigId}][tipo]"]:checked`).value;
  document.getElementById('copia-limite-' + asigId).style.display = tipo === 'ninguna' ? 'none' : '';
}

// ── Validación fecha publicación no anterior a ahora ──
document.getElementById('fecha-pub').addEventListener('change', function() {
  const ahora = new Date();
  const sel   = new Date(this.value);
  if (sel < ahora) {
    // permitir un margen de 1 min
    const pad = n => String(n).padStart(2,'0');
    const a = new Date(Date.now());
    this.value = `${a.getFullYear()}-${pad(a.getMonth()+1)}-${pad(a.getDate())}T${pad(a.getHours())}:${pad(a.getMinutes())}`;
  }
});

// ── Adjuntos preview ──
document.getElementById('adjunto-input').addEventListener('change', function() {
  const list = document.getElementById('adjunto-list');
  list.innerHTML = '';
  Array.from(this.files).forEach(f => {
    const li = document.createElement('li');
    li.className = 'adjunto-item';
    const kb = f.size < 1048576 ? (f.size/1024).toFixed(0)+' KB' : (f.size/1048576).toFixed(1)+' MB';
    li.innerHTML = `<i class="mdi mdi-file-outline"></i> ${f.name.replace(/</g,'&lt;')} <span style="color:var(--text-muted)">(${kb})</span>`;
    list.appendChild(li);
  });
});

// ── Quill ──
const quill = new Quill('#quill-editor', {
  theme: 'snow',
  placeholder: 'Escribe las instrucciones de la tarea…',
  modules: {
    formula: true,
    toolbar: [
      [{ header: [1,2,3,false] }],
      ['bold','italic','underline','strike'],
      [{ color: [] }, { background: [] }],
      [{ align: [] }],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['blockquote','code-block'],
      ['link','image','formula'],
      ['clean']
    ]
  }
});

quillInit(quill, {
  uploadUrl:   '<?= APP_URL ?>/upload/imagen',
  menciones:   '<?= APP_URL ?>/api/menciones',
  wordCounter: '#quill-word-count',
});

// ── Envío ──
document.getElementById('form-tarea').addEventListener('submit', function(ev) {
  document.getElementById('descripcion-hidden').value = quill.root.innerHTML;
  const btn = document.getElementById('btn-guardar-tarea');
  btn.disabled = true;
  btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Publicando…';
});

onTipoChange();
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
