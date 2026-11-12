<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nueva contraseña — <?= APP_NAME ?></title>
  <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <style>
    <?php $col = colegio(); ?>
    :root { --primary: <?= e($col['color_primario']) ?>; --primary-dark: <?= e($col['color_secundario']) ?>; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; }
    .login-wrapper { display: flex; width: 100%; min-height: 100vh; }

    /* ── Panel izquierdo ── */
    .login-panel {
      width: 460px; min-width: 320px;
      background: #fff;
      display: flex; flex-direction: column; justify-content: center;
      padding: 52px 44px;
      box-shadow: 2px 0 16px rgba(0,0,0,0.06);
      z-index: 2;
    }
    .login-brand {
      display: flex; flex-direction: column; align-items: center;
      margin-bottom: 28px; gap: 10px; text-align: center;
    }
    .login-brand img { width: 80px; height: 80px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,.12)); }
    .login-brand h1  { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
    .login-brand p   { font-size: .82rem; color: #888; }

    .section-title { font-size: 1.05rem; font-weight: 800; color: #1f2937; margin-bottom: 6px; text-align: center; }
    .section-sub   { font-size: .83rem; color: #6b7280; text-align: center; line-height: 1.6; margin-bottom: 20px; }

    .user-chip {
      display: flex; align-items: center; gap: 10px;
      background: #f0f4ff; border: 1px solid rgba(14,14,179,.2);
      border-radius: 10px; padding: 10px 14px; margin-bottom: 20px;
    }
    .user-chip .mdi { font-size: 1.2rem; color: var(--primary); }
    .user-chip .chip-name { font-size: .88rem; font-weight: 700; color: #1f2937; }
    .user-chip .chip-user { font-size: .78rem; color: #9ca3af; margin-left: auto; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-weight: 700; font-size: .84rem; color: #374151; margin-bottom: 6px; }
    .input-wrap { position: relative; }
    .input-wrap .icon-left {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%); font-size: 18px; color: #aaa;
    }
    .toggle-pass {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%); font-size: 18px; color: #aaa;
      cursor: pointer; background: none; border: none; padding: 0;
    }
    .input-wrap input {
      width: 100%; padding: 11px 40px 11px 40px;
      border: 1.5px solid #e5e7eb; border-radius: 8px;
      font-family: inherit; font-size: .95rem; outline: none;
      transition: border-color .2s;
    }
    .input-wrap input:focus { border-color: var(--primary); }
    .input-wrap input.input-error { border-color: #ef4444; }

    .strength-bar { height: 4px; border-radius: 4px; margin-top: 6px; background: #e5e7eb; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 4px; transition: width .3s, background .3s; width: 0; }
    .strength-label { font-size: .74rem; color: #9ca3af; margin-top: 3px; }

    .btn-primary {
      width: 100%; padding: 13px; background: var(--primary);
      color: #fff; border: none; border-radius: 8px;
      font-family: inherit; font-size: .97rem; font-weight: 700;
      cursor: pointer; transition: background .2s; margin-top: 8px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }

    .alert-error {
      background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5;
      border-radius: 8px; padding: 10px 14px; font-size: .85rem;
      display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
    }

    .login-links { margin-top: 20px; text-align: center; font-size: .84rem; color: #666; }
    .login-links a { color: var(--primary); font-weight: 700; text-decoration: none; }
    .login-links a:hover { text-decoration: underline; }

    /* ── Panel derecho ── */
    .login-cover {
      flex: 1;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: #fff; padding: 40px; text-align: center;
    }
    .cover-logo {
      width: 110px; height: 110px; object-fit: contain; margin-bottom: 24px;
      filter: brightness(0) invert(1) drop-shadow(0 4px 12px rgba(0,0,0,.2));
      opacity: .9;
    }
    .login-cover h2  { font-size: 1.9rem; font-weight: 800; margin-bottom: 8px; }
    .cover-appname   { font-size: 1rem; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; opacity: .65; margin-bottom: 14px; }
    .login-cover > p { font-size: .95rem; opacity: .85; max-width: 360px; line-height: 1.6; }
    .cover-tips { margin-top: 36px; display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 340px; }
    .cover-tip {
      display: flex; align-items: center; gap: 12px;
      background: rgba(255,255,255,.12); border-radius: 10px;
      padding: 12px 16px; text-align: left;
    }
    .cover-tip .mdi { font-size: 1.3rem; opacity: .85; flex-shrink: 0; }
    .cover-tip span  { font-size: .86rem; font-weight: 600; line-height: 1.4; }

    @media (max-width: 700px) {
      .login-cover { display: none; }
      .login-panel { width: 100%; padding: 32px 24px; }
    }
  </style>
</head>
<body>
<div class="login-wrapper">

  <!-- ── Panel izquierdo ── -->
  <div class="login-panel">
    <div class="login-brand">
      <img src="<?= APP_URL ?>/assets/img/logo-csma.png" alt="Logo">
      <div>
        <p style="font-size:.74rem;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px"><?= e($col['nombre']) ?></p>
        <h1><?= APP_NAME ?></h1>
        <p>Sistema de Gestión Académica</p>
      </div>
    </div>

    <p class="section-title">Crear nueva contraseña</p>
    <p class="section-sub">Elige una contraseña segura para proteger tu cuenta.</p>

    <!-- Chip con nombre del usuario -->
    <div class="user-chip">
      <i class="mdi mdi-account-circle-outline"></i>
      <span class="chip-name"><?= e($reset['nombre'] . ' ' . $reset['apellido']) ?></span>
      <span class="chip-user"><?= e($reset['username']) ?></span>
    </div>

    <?php if ($err = flash('error')): ?>
      <div class="alert-error">
        <i class="mdi mdi-alert-circle-outline"></i> <?= e($err) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/reset-password" id="reset-form">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="form-group">
        <label>Nueva contraseña</label>
        <div class="input-wrap">
          <i class="mdi mdi-lock-outline icon-left"></i>
          <input type="password" id="pass1" name="password_nueva"
                 required minlength="8" placeholder="Mínimo 8 caracteres"
                 oninput="checkStrength(this.value)">
          <button type="button" class="toggle-pass mdi mdi-eye-outline"
                  onclick="toggleVis('pass1', this)"></button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
        <p class="strength-label" id="strength-label">Ingresa tu nueva contraseña</p>
      </div>

      <div class="form-group">
        <label>Confirmar contraseña</label>
        <div class="input-wrap">
          <i class="mdi mdi-lock-check-outline icon-left"></i>
          <input type="password" id="pass2" name="password_confirmar"
                 required minlength="8" placeholder="Repite la contraseña"
                 oninput="checkMatch()">
          <button type="button" class="toggle-pass mdi mdi-eye-outline"
                  onclick="toggleVis('pass2', this)"></button>
        </div>
      </div>

      <button type="submit" class="btn-primary" id="btn-submit" disabled>
        <i class="mdi mdi-check-circle-outline"></i> Guardar nueva contraseña
      </button>
    </form>

    <div class="login-links">
      <a href="<?= APP_URL ?>/"><i class="mdi mdi-arrow-left"></i> Volver al inicio de sesión</a>
    </div>
  </div>

  <!-- ── Panel derecho ── -->
  <div class="login-cover">
    <img src="<?= APP_URL ?>/assets/img/logo-csma.png" alt="Logo" class="cover-logo">
    <h2>Contraseña segura</h2>
    <div class="cover-appname"><?= e($col['nombre']) ?></div>
    <p>Una buena contraseña protege tu información y la de tus estudiantes.</p>

    <div class="cover-tips">
      <div class="cover-tip">
        <i class="mdi mdi-numeric-8-circle-outline"></i>
        <span>Usa al menos 8 caracteres</span>
      </div>
      <div class="cover-tip">
        <i class="mdi mdi-format-letter-case"></i>
        <span>Combina mayúsculas y minúsculas</span>
      </div>
      <div class="cover-tip">
        <i class="mdi mdi-pound"></i>
        <span>Incluye números y símbolos especiales</span>
      </div>
      <div class="cover-tip">
        <i class="mdi mdi-shield-lock-outline"></i>
        <span>No uses la misma contraseña en otros sitios</span>
      </div>
    </div>
  </div>

</div>

<script>
function toggleVis(id, btn) {
  const inp = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.className = 'toggle-pass mdi ' + (show ? 'mdi-eye-off-outline' : 'mdi-eye-outline');
}

function checkStrength(val) {
  const fill  = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (val.length >= 8)           score++;
  if (/[A-Z]/.test(val))         score++;
  if (/[0-9]/.test(val))         score++;
  if (/[^A-Za-z0-9]/.test(val))  score++;
  const levels = [
    { w: '0%',   bg: '#e5e7eb', txt: 'Ingresa tu nueva contraseña' },
    { w: '25%',  bg: '#ef4444', txt: 'Muy débil' },
    { w: '50%',  bg: '#f97316', txt: 'Débil' },
    { w: '75%',  bg: '#eab308', txt: 'Aceptable' },
    { w: '100%', bg: '#22c55e', txt: 'Segura' },
  ];
  const l = levels[score] || levels[0];
  fill.style.width      = l.w;
  fill.style.background = l.bg;
  label.textContent     = l.txt;
  label.style.color     = l.bg === '#e5e7eb' ? '#9ca3af' : l.bg;
  checkMatch();
}

function checkMatch() {
  const p1   = document.getElementById('pass1').value;
  const p2   = document.getElementById('pass2').value;
  const btn  = document.getElementById('btn-submit');
  const inp2 = document.getElementById('pass2');
  const ok   = p1.length >= 8 && p1 === p2;
  inp2.classList.toggle('input-error', p2.length > 0 && p1 !== p2);
  btn.disabled = !ok;
}
</script>
</body>
</html>
