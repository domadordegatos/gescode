<?php
$pageTitle  = 'Editar perfil — ' . APP_NAME;
$breadcrumbs = [
  ['label' => 'Mi Perfil', 'url' => APP_URL . '/perfil'],
  ['label' => 'Editar', 'url' => ''],
];
ob_start();
$p = $perfil;
?>

<div class="prf-edit-page">

  <div class="prf-edit-header">
    <h1 class="page-title">Editar perfil</h1>
    <a href="<?= APP_URL ?>/perfil" class="btn-ghost">
      <i class="mdi mdi-arrow-left"></i> Volver
    </a>
  </div>

  <?php if ($err = flash('error')): ?>
    <div class="alert alert-error"><i class="mdi mdi-alert-circle"></i> <?= e($err) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= APP_URL ?>/perfil/guardar" class="prf-edit-form" id="form-perfil">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <input type="hidden" name="foto_b64" id="foto_b64">

    <!-- SECCIÓN: Foto de perfil -->
    <div class="prf-section">
      <div class="prf-section-title">
        <i class="mdi mdi-camera-outline"></i> Foto de perfil
      </div>
      <div class="prf-photo-row">
        <div class="prf-photo-preview" id="foto-preview">
          <img src="<?= APP_URL ?>/perfil/avatar/<?= Auth::id() ?>" alt="" id="foto-img">
        </div>
        <div class="prf-photo-info">
          <label class="prf-photo-btn" for="foto-input">
            <i class="mdi mdi-upload-outline"></i> Elegir imagen
          </label>
          <input type="file" id="foto-input" accept="image/*" style="display:none">
          <p class="form-hint" style="margin-top:6px">JPG, PNG o WEBP &mdash; se comprime automáticamente antes de subir.<br>
            <span id="foto-size-info" style="color:var(--success);font-weight:600"></span>
          </p>
          <button type="button" id="foto-remove" class="prf-photo-remove" style="display:none">
            <i class="mdi mdi-trash-can-outline"></i> Quitar foto
          </button>
        </div>
        <!-- Canvas oculto para compresión -->
        <canvas id="foto-canvas" style="display:none"></canvas>
      </div>
    </div>

    <!-- SECCIÓN: Datos personales -->
    <div class="prf-section">
      <div class="prf-section-title">
        <i class="mdi mdi-account-outline"></i> Datos personales
      </div>
      <div class="prf-grid2">
        <div class="form-group">
          <label>Tipo de documento</label>
          <select name="tipo_documento" class="form-input">
            <?php foreach (['CC'=>'Cédula de Ciudadanía','TI'=>'Tarjeta de Identidad','CE'=>'Cédula Extranjería','PP'=>'Pasaporte'] as $val=>$lbl): ?>
              <option value="<?= $val ?>" <?= ($p['tipo_documento'] ?? 'CC') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Número de documento</label>
          <input type="text" name="documento" class="form-input"
                 value="<?= e($p['documento'] ?? '') ?>" placeholder="Ej. 1118000000">
        </div>
        <div class="form-group">
          <label>Fecha de nacimiento</label>
          <input type="date" name="fecha_nacimiento" class="form-input"
                 value="<?= e($p['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Cargo / Especialidad</label>
          <input type="text" name="cargo" class="form-input"
                 value="<?= e($p['cargo'] ?? '') ?>" placeholder="Ej. Docente Básica Secundaria">
        </div>
      </div>
      <div class="form-group" style="margin-top:4px">
        <label>Acerca de mí <span class="form-hint">Opcional</span></label>
        <textarea name="bio" class="form-input" rows="3"
                  placeholder="Breve descripción personal..."><?= e($p['bio'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- SECCIÓN: Contacto -->
    <div class="prf-section">
      <div class="prf-section-title">
        <i class="mdi mdi-phone-outline"></i> Información de contacto
      </div>
      <div class="prf-grid2">
        <div class="form-group">
          <label>Correo electrónico <span class="form-required">*</span></label>
          <input type="email" name="email" class="form-input" required
                 value="<?= e($user['email'] ?? '') ?>" placeholder="correo@ejemplo.com">
          <span class="form-hint">Obligatorio — se usa para notificaciones</span>
        </div>
        <div class="form-group">
          <label>Celular</label>
          <input type="tel" name="celular" class="form-input"
                 value="<?= e($p['celular'] ?? '') ?>" placeholder="3XX XXX XXXX">
        </div>
        <div class="form-group">
          <label>Teléfono fijo</label>
          <input type="tel" name="telefono" class="form-input"
                 value="<?= e($p['telefono'] ?? '') ?>" placeholder="608 XXX XXXX">
        </div>
        <div class="form-group">
          <label>Ciudad</label>
          <input type="text" name="ciudad" class="form-input"
                 value="<?= e($p['ciudad'] ?? '') ?>" placeholder="Ej. Yopal">
        </div>
        <div class="form-group">
          <label>Barrio</label>
          <input type="text" name="barrio" class="form-input"
                 value="<?= e($p['barrio'] ?? '') ?>" placeholder="Nombre del barrio">
        </div>
        <div class="form-group">
          <label>Dirección</label>
          <input type="text" name="direccion" class="form-input"
                 value="<?= e($p['direccion'] ?? '') ?>" placeholder="Calle / Carrera / Manzana">
        </div>
      </div>
    </div>

    <div class="prf-edit-actions">
      <button type="submit" class="btn-primary">
        <i class="mdi mdi-content-save-outline"></i> Guardar cambios
      </button>
      <a href="<?= APP_URL ?>/perfil" class="btn-secondary">Cancelar</a>
    </div>
  </form>

</div>

<script>
(function () {
  const input    = document.getElementById('foto-input');
  const canvas   = document.getElementById('foto-canvas');
  const preview  = document.getElementById('foto-preview');
  const b64Field = document.getElementById('foto_b64');
  const sizeInfo = document.getElementById('foto-size-info');
  const removeBtn = document.getElementById('foto-remove');
  const MAX_PX   = 280;   // máx. dimensión en px
  const QUALITY  = 0.72;  // calidad JPEG 0-1

  input.addEventListener('change', function () {
    const file = this.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function (e) {
      const img = new Image();
      img.onload = function () {
        // Calcular nuevas dimensiones manteniendo proporción
        let w = img.width, h = img.height;
        if (w > h) { if (w > MAX_PX) { h = Math.round(h * MAX_PX / w); w = MAX_PX; } }
        else        { if (h > MAX_PX) { w = Math.round(w * MAX_PX / h); h = MAX_PX; } }

        canvas.width  = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, w, h);

        const b64 = canvas.toDataURL('image/jpeg', QUALITY);
        b64Field.value = b64;

        // Actualizar preview
        let imgEl = preview.querySelector('img');
        if (!imgEl) {
          preview.innerHTML = '';
          imgEl = document.createElement('img');
          imgEl.id = 'foto-img';
          preview.appendChild(imgEl);
        }
        imgEl.src = b64;

        // Mostrar tamaño comprimido
        const kb = Math.round((b64.length * 3/4) / 1024);
        const orig = Math.round(file.size / 1024);
        sizeInfo.textContent = `Original: ${orig} KB → Comprimida: ${kb} KB (${Math.round(kb/orig*100)}%)`;

        removeBtn.style.display = 'inline-flex';
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  removeBtn.addEventListener('click', function () {
    b64Field.value = 'REMOVE';
    preview.innerHTML = `<span class="prf-photo-iniciales"><?= strtoupper(substr($user['nombre'],0,1).substr($user['apellido'],0,1)) ?></span>`;
    sizeInfo.textContent = '';
    input.value = '';
    this.style.display = 'none';
  });
})();
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
