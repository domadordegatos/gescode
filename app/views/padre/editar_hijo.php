<?php
$pageTitle  = 'Editar información — ' . e($hijo['nombre'] . ' ' . $hijo['apellido']);
$noLeidos   = $mensajes_no_leidos ?? 0;
$breadcrumbs = [
    ['label' => 'Inicio',       'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis acudidos', 'url' => APP_URL . '/mis-hijos'],
    ['label' => $hijo['nombre'] . ' ' . $hijo['apellido'], 'url' => APP_URL . '/mis-hijos/' . $hijo['estudiante_id'] . '?tab=info'],
    ['label' => 'Editar información', 'url' => ''],
];
ob_start();
?>

<div class="hijo-header-card" style="margin-bottom:24px">
  <div class="hijo-header-left">
    <div class="hijo-header-avatar"><?= strtoupper(substr($hijo['nombre'],0,1)) ?></div>
    <div class="hijo-header-info">
      <h1 class="hijo-header-nombre"><?= e($hijo['nombre'] . ' ' . $hijo['apellido']) ?></h1>
      <div class="hijo-header-meta">
        <span><i class="mdi mdi-google-classroom"></i> <?= e($hijo['grado'] . ' · ' . $hijo['grupo']) ?></span>
      </div>
    </div>
  </div>
  <div class="hijo-header-actions">
    <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>?tab=info" class="btn-secondary btn-sm">
      <i class="mdi mdi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<form method="POST" action="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>/editar" class="editar-hijo-form">

  <div class="form-section-card">
    <div class="form-section-title">
      <i class="mdi mdi-card-account-details-outline"></i> Documento e identidad
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Tipo de documento</label>
        <select name="tipo_documento" class="form-control">
          <?php foreach (['TI'=>'Tarjeta de Identidad','CC'=>'Cédula de Ciudadanía','CE'=>'Cédula Extranjería','PP'=>'Pasaporte'] as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= ($info['tipo_documento']??'') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Número de documento</label>
        <input type="text" name="documento" class="form-control" value="<?= e($info['documento'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento" class="form-control"
               value="<?= e($info['fecha_nacimiento'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Género</label>
        <select name="genero" class="form-control">
          <option value="">— Seleccionar —</option>
          <option value="M" <?= ($info['genero']??'') === 'M' ? 'selected' : '' ?>>Masculino</option>
          <option value="F" <?= ($info['genero']??'') === 'F' ? 'selected' : '' ?>>Femenino</option>
          <option value="O" <?= ($info['genero']??'') === 'O' ? 'selected' : '' ?>>Otro</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Lugar de nacimiento</label>
      <input type="text" name="lugar_nacimiento" class="form-control"
             value="<?= e($info['lugar_nacimiento'] ?? '') ?>">
    </div>
  </div>

  <div class="form-section-card">
    <div class="form-section-title">
      <i class="mdi mdi-map-marker-outline"></i> Residencia
    </div>
    <div class="form-group">
      <label class="form-label">Dirección</label>
      <input type="text" name="direccion" class="form-control" value="<?= e($info['direccion'] ?? '') ?>">
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Barrio</label>
        <input type="text" name="barrio" class="form-control" value="<?= e($info['barrio'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Ciudad</label>
        <input type="text" name="ciudad" class="form-control" value="<?= e($info['ciudad'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Estrato</label>
      <select name="estrato" class="form-control">
        <option value="">— Seleccionar —</option>
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <option value="<?= $i ?>" <?= (int)($info['estrato']??0) === $i ? 'selected' : '' ?>>Estrato <?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>

  <div class="form-section-card">
    <div class="form-section-title">
      <i class="mdi mdi-hospital-box-outline"></i> Salud y contacto
    </div>
    <div class="form-group">
      <label class="form-label">EPS</label>
      <input type="text" name="eps" class="form-control" value="<?= e($info['eps'] ?? '') ?>">
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Teléfono fijo</label>
        <input type="tel" name="telefono" class="form-control" value="<?= e($info['telefono'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Celular</label>
        <input type="tel" name="celular" class="form-control" value="<?= e($info['celular'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="form-section-card">
    <div class="form-section-title">
      <i class="mdi mdi-phone-alert-outline"></i> Contacto de emergencia
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Nombre del contacto</label>
        <input type="text" name="contacto_emergencia" class="form-control"
               value="<?= e($info['contacto_emergencia'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Teléfono de emergencia</label>
        <input type="tel" name="tel_emergencia" class="form-control"
               value="<?= e($info['tel_emergencia'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="form-actions">
    <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>?tab=info" class="btn-secondary">
      Cancelar
    </a>
    <button type="submit" class="btn-primary">
      <i class="mdi mdi-content-save-outline"></i> Guardar cambios
    </button>
  </div>

</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
