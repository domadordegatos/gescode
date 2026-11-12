<?php
$pageTitle   = 'Gestión Comunidad — ' . APP_NAME;
$breadcrumbs = [
  ['label' => 'Comunidad', 'url' => APP_URL . '/comunidad'],
  ['label' => 'Gestión', 'url' => ''],
];
ob_start();
?>

<div class="com-admin-page">

  <div class="com-page-header">
    <div>
      <h1 class="page-title">Gestión de Comunidad</h1>
      <p class="com-subtitle">Crea categorías y sube archivos para toda la comunidad educativa</p>
    </div>
    <a href="<?= APP_URL ?>/comunidad" class="btn-ghost">
      <i class="mdi mdi-eye-outline"></i> Ver como usuario
    </a>
  </div>

  <!-- ── Nueva categoría ─────────────────────────────────── -->
  <details class="com-admin-panel" id="panel-nueva-cat">
    <summary class="com-admin-panel-title">
      <i class="mdi mdi-folder-plus-outline"></i> Nueva categoría
      <i class="mdi mdi-chevron-down com-chevron"></i>
    </summary>
    <form method="POST" action="<?= APP_URL ?>/comunidad/categoria/crear" class="com-form">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <div class="com-form-row">
        <div class="form-group">
          <label>Nombre <span class="form-required">*</span></label>
          <input type="text" name="nombre" class="form-input" required placeholder="Ej. ACADÉMICO">
        </div>
        <div class="form-group">
          <label>Orden <span class="form-hint">Número para ordenar (menor = primero)</span></label>
          <input type="number" name="orden" class="form-input" value="0" min="0" style="width:100px">
        </div>
      </div>
      <div class="form-group">
        <label>Descripción breve <span class="form-hint">Opcional — una sola oración</span></label>
        <input type="text" name="descripcion" class="form-input" maxlength="255"
               placeholder="Ej. Documentos del proceso académico anual">
      </div>
      <button type="submit" class="btn-primary">
        <i class="mdi mdi-check"></i> Crear categoría
      </button>
    </form>
  </details>

  <!-- ── Lista de categorías ─────────────────────────────── -->
  <?php if (empty($categorias)): ?>
    <div class="empty-state" style="margin-top:24px">
      <i class="mdi mdi-folder-open-outline"></i>
      <p>No hay categorías aún. Crea la primera arriba.</p>
    </div>
  <?php else: ?>

  <div class="com-admin-cats">
    <?php foreach ($categorias as $cat): ?>
    <div class="com-admin-cat <?= $cat['activo'] ? '' : 'com-cat-inactive' ?>">

      <!-- Cabecera categoría -->
      <div class="com-admin-cat-head">
        <div class="com-admin-cat-info">
          <i class="mdi mdi-folder<?= $cat['activo'] ? '' : '-off' ?>-outline com-folder-icon <?= $cat['activo'] ? '' : 'muted' ?>"></i>
          <div>
            <strong class="com-admin-cat-name"><?= e($cat['nombre']) ?></strong>
            <?php if ($cat['descripcion']): ?>
              <span class="com-cat-desc"><?= e($cat['descripcion']) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!$cat['activo']): ?>
            <span class="badge-tag warn">Oculta</span>
          <?php endif; ?>
        </div>
        <div class="com-admin-cat-actions">
          <button type="button" class="btn-icon" title="Editar categoría"
                  onclick="toggleEdit('edit-cat-<?= $cat['id'] ?>')">
            <i class="mdi mdi-pencil-outline"></i>
          </button>
          <form method="POST" action="<?= APP_URL ?>/comunidad/categoria/<?= $cat['id'] ?>/eliminar"
                style="display:inline"
                onsubmit="return confirm('¿Eliminar categoría y todos sus archivos?')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-icon danger" title="Eliminar categoría">
              <i class="mdi mdi-trash-can-outline"></i>
            </button>
          </form>
        </div>
      </div>

      <!-- Form editar categoría (oculto) -->
      <div id="edit-cat-<?= $cat['id'] ?>" class="com-edit-form" style="display:none">
        <form method="POST" action="<?= APP_URL ?>/comunidad/categoria/<?= $cat['id'] ?>/editar" class="com-form">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
          <div class="com-form-row">
            <div class="form-group">
              <label>Nombre</label>
              <input type="text" name="nombre" class="form-input" required value="<?= e($cat['nombre']) ?>">
            </div>
            <div class="form-group" style="flex:0 0 100px">
              <label>Orden</label>
              <input type="number" name="orden" class="form-input" value="<?= $cat['orden'] ?>" min="0">
            </div>
          </div>
          <div class="com-form-row">
            <div class="form-group" style="flex:1">
              <label>Descripción</label>
              <input type="text" name="descripcion" class="form-input" maxlength="255" value="<?= e($cat['descripcion'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex:0;align-self:flex-end;padding-bottom:4px">
              <label class="com-check-label">
                <input type="checkbox" name="activo" value="1" <?= $cat['activo'] ? 'checked' : '' ?>>
                Visible
              </label>
            </div>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn-primary btn-sm">Guardar</button>
            <button type="button" class="btn-secondary btn-sm" onclick="toggleEdit('edit-cat-<?= $cat['id'] ?>')">Cancelar</button>
          </div>
        </form>
      </div>

      <!-- Archivos existentes -->
      <?php if (!empty($cat['archivos'])): ?>
      <ul class="com-admin-file-list">
        <?php foreach ($cat['archivos'] as $arch): ?>
        <li class="com-admin-file-item">
          <i class="mdi <?= $arch['icon'] ?>" style="color:<?= $arch['color'] ?>;font-size:20px;flex-shrink:0"></i>
          <div class="com-file-info">
            <span class="com-file-name"><?= e($arch['titulo']) ?></span>
            <?php if ($arch['descripcion']): ?>
              <span class="com-file-desc"><?= e($arch['descripcion']) ?></span>
            <?php endif; ?>
            <span class="com-file-meta"><?= e($arch['archivo_orig']) ?> &middot; <?= $arch['tamano_fmt'] ?> &middot; <?= date('d/m/Y H:i', strtotime($arch['created_at'])) ?></span>
          </div>
          <a href="<?= APP_URL ?>/comunidad/archivo/<?= $arch['id'] ?>" class="btn-icon" target="_blank" title="Ver archivo">
            <i class="mdi mdi-eye-outline"></i>
          </a>
          <form method="POST" action="<?= APP_URL ?>/comunidad/archivo/<?= $arch['id'] ?>/eliminar"
                style="display:inline" onsubmit="return confirm('¿Eliminar este archivo?')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-icon danger" title="Eliminar">
              <i class="mdi mdi-trash-can-outline"></i>
            </button>
          </form>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <!-- Subir archivo -->
      <details class="com-upload-panel">
        <summary class="com-upload-toggle">
          <i class="mdi mdi-upload-outline"></i> Subir archivo a esta categoría
        </summary>
        <form method="POST" action="<?= APP_URL ?>/comunidad/archivo/subir"
              enctype="multipart/form-data" class="com-form com-upload-form">
          <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
          <input type="hidden" name="categoria_id" value="<?= $cat['id'] ?>">
          <div class="com-form-row">
            <div class="form-group" style="flex:1">
              <label>Título del archivo <span class="form-required">*</span></label>
              <input type="text" name="titulo" class="form-input" required
                     placeholder="Ej. Cronograma Año Escolar 2026">
            </div>
          </div>
          <div class="form-group">
            <label>Descripción breve <span class="form-hint">Opcional</span></label>
            <input type="text" name="descripcion" class="form-input" maxlength="255"
                   placeholder="Ej. Publicado el 09 de febrero de 2026">
          </div>
          <div class="form-group">
            <label>Archivo <span class="form-required">*</span></label>
            <div class="com-dropzone" id="dz-<?= $cat['id'] ?>">
              <i class="mdi mdi-cloud-upload-outline com-dz-icon"></i>
              <span class="com-dz-text">Haz clic o arrastra el archivo aquí</span>
              <span class="com-dz-hint">PDF, Word, Excel, imágenes — máx. 20 MB</span>
              <input type="file" name="archivo" required
                     accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip"
                     onchange="previewFile(this,'dz-<?= $cat['id'] ?>')">
            </div>
          </div>
          <button type="submit" class="btn-primary btn-sm">
            <i class="mdi mdi-upload"></i> Subir archivo
          </button>
        </form>
      </details>

    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<script>
function toggleEdit(id) {
  const el = document.getElementById(id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function previewFile(input, dzId) {
  const dz   = document.getElementById(dzId);
  const text = dz.querySelector('.com-dz-text');
  if (input.files && input.files[0]) {
    text.textContent = input.files[0].name;
    text.style.color = 'var(--primary)';
    text.style.fontWeight = '700';
  }
}

// Drag & drop visual
document.querySelectorAll('.com-dropzone').forEach(dz => {
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('drag-over');
    const inp = dz.querySelector('input[type=file]');
    inp.files = e.dataTransfer.files;
    inp.dispatchEvent(new Event('change'));
  });
});
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
