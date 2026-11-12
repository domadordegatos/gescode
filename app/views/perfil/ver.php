<?php
$pageTitle  = 'Mi Perfil — ' . APP_NAME;
$breadcrumbs = [['label' => 'Mi Perfil', 'url' => '']];
ob_start();
$inicial = strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1));
$p = $perfil;
?>

<div class="prf-page">

  <!-- Tarjeta cabecera -->
  <div class="prf-hero">
    <div class="prf-avatar-xl">
      <img src="<?= APP_URL ?>/perfil/avatar/<?= $user['id'] ?>" alt="<?= e($inicial) ?>">
    </div>
    <div class="prf-hero-info">
      <h1 class="prf-hero-name"><?= e($user['nombre'] . ' ' . $user['apellido']) ?></h1>
      <span class="prf-hero-role"><?= ucfirst(e($user['rol'])) ?></span>
      <?php if (!empty($p['cargo'])): ?>
        <span class="prf-hero-cargo"><?= e($p['cargo']) ?></span>
      <?php endif; ?>
      <div class="prf-hero-meta">
        <span><i class="mdi mdi-at"></i> <?= e($user['username']) ?></span>
        <?php if ($user['email']): ?>
          <span><i class="mdi mdi-email-outline"></i> <?= e($user['email']) ?></span>
        <?php endif; ?>
        <?php if (!empty($p['ciudad'])): ?>
          <span><i class="mdi mdi-map-marker-outline"></i> <?= e($p['ciudad']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="prf-hero-actions">
      <a href="<?= APP_URL ?>/perfil/editar" class="btn-primary">
        <i class="mdi mdi-pencil-outline"></i> Editar perfil
      </a>
      <a href="<?= APP_URL ?>/perfil/password" class="btn-secondary">
        <i class="mdi mdi-lock-outline"></i> Contraseña
      </a>
      <?php if (!empty($user['foto'])): ?>
      <form method="POST" action="<?= APP_URL ?>/perfil/foto/eliminar"
            onsubmit="return confirm('¿Eliminar la foto de perfil?')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-danger-outline">
          <i class="mdi mdi-image-remove-outline"></i> Quitar foto
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="prf-body">

    <!-- Columna izquierda: Datos personales -->
    <div class="prf-card">
      <div class="prf-card-header">
        <i class="mdi mdi-account-outline"></i> Información personal
      </div>
      <dl class="prf-dl">
        <div class="prf-dl-row">
          <dt>Documento</dt>
          <dd><?= !empty($p['tipo_documento']) && !empty($p['documento'])
                ? e($p['tipo_documento'] . '. ' . $p['documento'])
                : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Fecha de nacimiento</dt>
          <dd><?= !empty($p['fecha_nacimiento'])
                ? e(date('d/m/Y', strtotime($p['fecha_nacimiento'])))
                : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <?php if (!empty($p['bio'])): ?>
        <div class="prf-dl-row prf-dl-bio">
          <dt>Acerca de mí</dt>
          <dd><?= nl2br(e($p['bio'])) ?></dd>
        </div>
        <?php endif; ?>
      </dl>
    </div>

    <!-- Columna derecha: Contacto -->
    <div class="prf-card">
      <div class="prf-card-header">
        <i class="mdi mdi-phone-outline"></i> Contacto
      </div>
      <dl class="prf-dl">
        <div class="prf-dl-row">
          <dt>Correo electrónico</dt>
          <dd><?= $user['email'] ? e($user['email']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Celular</dt>
          <dd><?= !empty($p['celular']) ? e($p['celular']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Teléfono</dt>
          <dd><?= !empty($p['telefono']) ? e($p['telefono']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Dirección</dt>
          <dd><?= !empty($p['direccion']) ? e($p['direccion']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Barrio</dt>
          <dd><?= !empty($p['barrio']) ? e($p['barrio']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
        <div class="prf-dl-row">
          <dt>Ciudad</dt>
          <dd><?= !empty($p['ciudad']) ? e($p['ciudad']) : '<span class="prf-empty">Sin registrar</span>' ?></dd>
        </div>
      </dl>
    </div>

    <!-- Fila inferior: Cuenta -->
    <div class="prf-card prf-card-full">
      <div class="prf-card-header">
        <i class="mdi mdi-shield-account-outline"></i> Cuenta
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;padding:4px 0">

        <!-- Usuario -->
        <div style="display:flex;align-items:center;gap:12px;background:var(--surface-2);
                    border:1px solid var(--border);border-radius:12px;padding:14px 18px;flex:1;min-width:160px">
          <div style="width:38px;height:38px;border-radius:10px;background:var(--primary-light);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="mdi mdi-at" style="color:var(--primary);font-size:1.2rem"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Usuario</div>
            <div style="font-weight:800;font-size:.95rem;color:var(--text)"><?= e($user['username']) ?></div>
          </div>
        </div>

        <!-- Último acceso -->
        <div style="display:flex;align-items:center;gap:12px;background:var(--surface-2);
                    border:1px solid var(--border);border-radius:12px;padding:14px 18px;flex:1;min-width:180px">
          <div style="width:38px;height:38px;border-radius:10px;background:#EEF2FF;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="mdi mdi-clock-outline" style="color:#6366F1;font-size:1.2rem"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Último acceso</div>
            <div style="font-weight:800;font-size:.95rem;color:var(--text)">
              <?= $user['ultimo_login']
                  ? e(date('d/m/Y H:i', strtotime($user['ultimo_login'])))
                  : '<span style="color:var(--text-muted);font-weight:400">Sin registro</span>' ?>
            </div>
          </div>
        </div>

        <!-- Estado -->
        <div style="display:flex;align-items:center;gap:12px;background:var(--surface-2);
                    border:1px solid var(--border);border-radius:12px;padding:14px 18px;flex:1;min-width:140px">
          <div style="width:38px;height:38px;border-radius:10px;
                      background:<?= $user['activo'] ? '#DCFCE7' : '#FEE2E2' ?>;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="mdi mdi-<?= $user['activo'] ? 'check-circle-outline' : 'close-circle-outline' ?>"
               style="color:<?= $user['activo'] ? '#16A34A' : '#DC2626' ?>;font-size:1.2rem"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Estado</div>
            <div style="font-weight:800;font-size:.95rem;color:<?= $user['activo'] ? '#16A34A' : '#DC2626' ?>">
              <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div><!-- .prf-body -->
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
