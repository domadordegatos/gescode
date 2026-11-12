<?php
$gid        = $grupo['grupo_id'];
$asig_id    = $asig['asignacion_id'];
$pageTitle  = 'Editar tarea — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs= [
  ['label'=>'Inicio',     'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos', 'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Tareas','url'=>APP_URL.'/grupos/'.$gid.'/tareas?asig='.$asig_id],
  ['label'=>'Editar tarea','url'=>''],
];
$extraHead = '<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="' . APP_URL . '/assets/js/quill-utils.js"></script>';

$tipo_actual = $tarea['tipo_entrega'] ?? 'ninguna';
$limite_val  = $tarea['fecha_limite']
    ? date('Y-m-d\TH:i', strtotime($tarea['fecha_limite']))
    : '';

ob_start();
?>

<div class="page-header">
  <h1 class="page-title">Editar tarea</h1>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas?asig=<?= $asig_id ?>" class="btn-ghost">
    <i class="mdi mdi-arrow-left"></i> Volver
  </a>
</div>

<form method="POST" action="<?= APP_URL ?>/tarea/<?= $tarea['id'] ?>/editar"
      enctype="multipart/form-data" id="form-editar-tarea">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
  <input type="hidden" name="descripcion" id="descripcion-hidden">

  <!-- ════ DATOS PRINCIPALES ════ -->
  <div class="tarea-card">

    <!-- Contexto -->
    <div class="tarea-context">
      <i class="mdi mdi-google-classroom"></i>
      <strong><?= e($asig['materia']) ?></strong> ·
      <?= e($asig['grado'].' '.$asig['grupo']) ?>
    </div>

    <!-- Nombre -->
    <div class="form-group">
      <label>Nombre de la tarea <span class="form-required">*</span></label>
      <input type="text" name="nombre" class="form-input" required maxlength="200"
             value="<?= e($tarea['nombre']) ?>" autofocus>
    </div>

    <!-- Para entregar en -->
    <div class="form-group">
      <label>Para entregar en</label>
      <div class="tarea-radios">
        <?php foreach (['ninguna'=>'No se requiere entrega','clase'=>'En clase','linea'=>'En línea'] as $val=>$lbl): ?>
        <label class="tarea-radio">
          <input type="radio" name="tipo_entrega" value="<?= $val ?>"
                 <?= $tipo_actual === $val ? 'checked' : '' ?> onchange="onTipoChange()">
          <span><?= $lbl ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Fecha límite (solo si requiere entrega) -->
    <div class="form-group" id="grupo-fecha-limite"
         style="<?= $tipo_actual === 'ninguna' ? 'display:none' : '' ?>">
      <label>Fecha límite de entrega <span class="form-required">*</span></label>
      <input type="datetime-local" name="fecha_limite" id="fecha-limite" class="form-input"
             min="<?= $ahora ?>" value="<?= e($limite_val) ?>"
             <?= $tipo_actual !== 'ninguna' ? 'required' : '' ?>>
      <span class="form-hint">No puede ser anterior a la fecha y hora actuales.</span>
    </div>

    <!-- Foro -->
    <label class="tarea-check" style="margin-top:8px">
      <input type="checkbox" name="foro" value="1"
             <?= $tarea['foro_habilitado'] ? 'checked' : '' ?>>
      <span>Habilitar foro de discusión</span>
    </label>
  </div>

  <!-- ════ DESCRIPCIÓN ════ -->
  <div class="tarea-card">
    <div class="form-group">
      <label>Descripción / instrucciones</label>
      <div id="quill-editor"></div>
      <p id="quill-word-count" style="font-size:.75rem;color:#9ca3af;text-align:right;margin-top:4px;">0 palabras · 0 caracteres</p>
    </div>
  </div>

  <!-- ════ ADJUNTOS EXISTENTES ════ -->
  <?php if (!empty($adjuntos)): ?>
  <div class="tarea-card">
    <div class="tarea-section-title">
      <i class="mdi mdi-paperclip"></i> Archivos adjuntos actuales
    </div>
    <ul class="adjunto-list" style="margin-top:8px">
      <?php foreach ($adjuntos as $a): ?>
      <li class="adjunto-item" id="adj-item-<?= $a['id'] ?>">
        <i class="mdi mdi-file-outline"></i>
        <span><?= e($a['nombre_orig']) ?></span>
        <span style="color:var(--text-muted);font-size:.78rem">
          (<?= $a['tamano'] < 1048576
              ? round($a['tamano']/1024).' KB'
              : round($a['tamano']/1048576,1).' MB' ?>)
        </span>
        <label style="margin-left:auto;display:flex;align-items:center;gap:5px;
                      cursor:pointer;color:var(--danger);font-size:.82rem">
          <input type="checkbox" name="eliminar_adjunto[]" value="<?= $a['id'] ?>"
                 onchange="marcarEliminar(this, <?= $a['id'] ?>)">
          <i class="mdi mdi-trash-can-outline"></i> Eliminar
        </label>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- ════ NUEVOS ADJUNTOS ════ -->
  <div class="tarea-card">
    <div class="form-group">
      <label>Agregar nuevos archivos
        <span class="form-hint">PDF, Office, imágenes, ZIP · máx. 10 MB c/u</span>
      </label>
      <input type="file" name="adjuntos[]" id="adjunto-input" multiple style="display:none"
             accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.zip">
      <label for="adjunto-input" class="adjunto-btn">
        <i class="mdi mdi-folder-open-outline"></i> Seleccionar archivos
      </label>
      <ul class="adjunto-list" id="adjunto-list"></ul>
    </div>
  </div>

  <!-- Aviso sin notificación -->
  <div class="alert-info-box" style="background:#f0fdf4;border-color:#86efac">
    <i class="mdi mdi-bell-off-outline" style="color:#16a34a"></i>
    <div style="color:#15803d">
      Al guardar, <strong>no se enviará notificación</strong>. La fecha de publicación visible
      se actualizará automáticamente a la hora actual.
    </div>
  </div>

  <div class="tarea-actions">
    <button type="submit" class="btn-primary" id="btn-guardar">
      <i class="mdi mdi-content-save-outline"></i> Guardar cambios
    </button>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas?asig=<?= $asig_id ?>" class="btn-secondary">Cancelar</a>
  </div>
</form>

<script>
const DESCRIPCION_INICIAL = <?= json_encode($tarea['descripcion'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

function onTipoChange() {
  const tipo = document.querySelector('input[name="tipo_entrega"]:checked').value;
  const box  = document.getElementById('grupo-fecha-limite');
  const inp  = document.getElementById('fecha-limite');
  if (tipo === 'ninguna') {
    box.style.display = 'none';
    inp.removeAttribute('required');
  } else {
    box.style.display = '';
    inp.setAttribute('required','required');
  }
}

function marcarEliminar(cb, id) {
  const item = document.getElementById('adj-item-' + id);
  if (item) item.style.opacity = cb.checked ? '.4' : '1';
}

// Adjuntos nuevos preview
document.getElementById('adjunto-input').addEventListener('change', function() {
  const list = document.getElementById('adjunto-list');
  list.innerHTML = '';
  Array.from(this.files).forEach(f => {
    const li = document.createElement('li');
    li.className = 'adjunto-item';
    const kb = f.size < 1048576 ? Math.round(f.size/1024)+' KB' : (f.size/1048576).toFixed(1)+' MB';
    li.innerHTML = `<i class="mdi mdi-file-outline"></i> ${f.name.replace(/</g,'&lt;')} <span style="color:var(--text-muted)">(${kb})</span>`;
    list.appendChild(li);
  });
});

// Quill
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

// Cargar descripción actual
if (DESCRIPCION_INICIAL) {
  quill.clipboard.dangerouslyPasteHTML(DESCRIPCION_INICIAL);
}

// Envío
document.getElementById('form-editar-tarea').addEventListener('submit', function() {
  document.getElementById('descripcion-hidden').value = quill.root.innerHTML;
  const btn = document.getElementById('btn-guardar');
  btn.disabled = true;
  btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Guardando…';
});
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
