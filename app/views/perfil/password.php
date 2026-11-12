<?php
$pageTitle  = 'Cambiar contraseña — ' . APP_NAME;
$breadcrumbs = [
  ['label' => 'Mi Perfil', 'url' => APP_URL . '/perfil'],
  ['label' => 'Contraseña', 'url' => ''],
];
ob_start();
?>

<div class="prf-edit-page" style="max-width:520px">

  <div class="prf-edit-header">
    <h1 class="page-title">Cambiar contraseña</h1>
    <a href="<?= APP_URL ?>/perfil" class="btn-ghost">
      <i class="mdi mdi-arrow-left"></i> Volver
    </a>
  </div>

  <?php if ($err = flash('error')): ?>
    <div class="alert alert-error"><i class="mdi mdi-alert-circle"></i> <?= e($err) ?></div>
  <?php endif; ?>

  <div class="prf-section">
    <div class="prf-section-title">
      <i class="mdi mdi-lock-outline"></i> Nueva contraseña
    </div>
    <form method="POST" action="<?= APP_URL ?>/perfil/password" class="prf-edit-form">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>Contraseña actual</label>
        <div class="input-pw-wrap">
          <input type="password" name="password_actual" id="pw-actual" class="form-input"
                 required autocomplete="current-password" placeholder="••••••••">
          <button type="button" class="pw-toggle" onclick="togglePw('pw-actual',this)">
            <i class="mdi mdi-eye-outline"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label>Nueva contraseña <span class="form-hint">Mínimo 8 caracteres</span></label>
        <div class="input-pw-wrap">
          <input type="password" name="password_nueva" id="pw-nueva" class="form-input"
                 required minlength="8" autocomplete="new-password" placeholder="••••••••"
                 oninput="checkStrength(this.value)">
          <button type="button" class="pw-toggle" onclick="togglePw('pw-nueva',this)">
            <i class="mdi mdi-eye-outline"></i>
          </button>
        </div>
        <div class="pw-strength-bar"><div id="pw-bar"></div></div>
        <span id="pw-hint" class="form-hint"></span>
      </div>

      <div class="form-group">
        <label>Confirmar nueva contraseña</label>
        <div class="input-pw-wrap">
          <input type="password" name="password_confirmar" id="pw-conf" class="form-input"
                 required autocomplete="new-password" placeholder="••••••••">
          <button type="button" class="pw-toggle" onclick="togglePw('pw-conf',this)">
            <i class="mdi mdi-eye-outline"></i>
          </button>
        </div>
      </div>

      <div class="prf-edit-actions">
        <button type="submit" class="btn-primary">
          <i class="mdi mdi-lock-reset"></i> Actualizar contraseña
        </button>
        <a href="<?= APP_URL ?>/perfil" class="btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  btn.querySelector('i').className = isText ? 'mdi mdi-eye-outline' : 'mdi mdi-eye-off-outline';
}

function checkStrength(val) {
  const bar  = document.getElementById('pw-bar');
  const hint = document.getElementById('pw-hint');
  let score  = 0;
  if (val.length >= 8)  score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const labels = ['','Débil','Regular','Buena','Fuerte'];
  const colors = ['','#DC2626','#D97706','#2563EB','#16A34A'];
  bar.style.width  = (score * 25) + '%';
  bar.style.background = colors[score] || '#E5E7EB';
  hint.textContent = labels[score] || '';
  hint.style.color = colors[score] || '';
}
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
