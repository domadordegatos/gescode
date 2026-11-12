<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar contraseña — <?= APP_NAME ?></title>
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
      margin-bottom: 32px; gap: 10px; text-align: center;
    }
    .login-brand img { width: 80px; height: 80px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,.12)); }
    .login-brand h1  { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
    .login-brand p   { font-size: .82rem; color: #888; }

    .section-title { font-size: 1.05rem; font-weight: 800; color: #1f2937; margin-bottom: 6px; text-align: center; }
    .section-sub   { font-size: .83rem; color: #6b7280; text-align: center; line-height: 1.6; margin-bottom: 26px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 700; font-size: .84rem; color: #444; margin-bottom: 6px; }
    .input-wrap { position: relative; }
    .input-wrap .mdi { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #aaa; }
    .input-wrap input {
      width: 100%; padding: 11px 12px 11px 40px;
      border: 1.5px solid #ddd; border-radius: 8px;
      font-family: inherit; font-size: .95rem; color: #222;
      transition: border-color .2s; outline: none;
    }
    .input-wrap input:focus { border-color: var(--primary); }
    .form-hint { font-size: .76rem; color: #9ca3af; margin-top: 5px; line-height: 1.5; }

    .btn-primary {
      width: 100%; padding: 13px; background: var(--primary); color: #fff;
      border: none; border-radius: 8px; font-family: inherit;
      font-size: .97rem; font-weight: 700; cursor: pointer;
      transition: background .2s; margin-top: 4px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background: var(--primary-dark); }

    .login-links { margin-top: 20px; text-align: center; font-size: .84rem; color: #666; }
    .login-links a { color: var(--primary); font-weight: 700; text-decoration: none; }
    .login-links a:hover { text-decoration: underline; }

    .alert-error {
      background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5;
      border-radius: 8px; padding: 10px 14px; font-size: .85rem;
      display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
    }

    /* ── Estado enviado ── */
    .sent-wrap { text-align: center; }
    .sent-icon-box {
      width: 72px; height: 72px; background: #f0fdf4; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 18px; font-size: 2rem; color: #16a34a;
      border: 2px solid #86efac;
    }
    .sent-title { font-size: 1.05rem; font-weight: 800; color: #1f2937; margin-bottom: 14px; }
    .sent-hint {
      background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px;
      padding: 14px 16px; font-size: .88rem; color: #374151; line-height: 1.7;
      margin-bottom: 10px;
    }
    .sent-hint strong { color: #15803d; }
    .sent-note { font-size: .76rem; color: #9ca3af; line-height: 1.6; }

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
    .login-cover h2   { font-size: 1.9rem; font-weight: 800; margin-bottom: 8px; }
    .cover-appname    { font-size: 1rem; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; opacity: .65; margin-bottom: 14px; }
    .login-cover > p  { font-size: .95rem; opacity: .85; max-width: 360px; line-height: 1.6; }
    .cover-steps { margin-top: 36px; display: flex; flex-direction: column; gap: 14px; width: 100%; max-width: 340px; }
    .cover-step {
      display: flex; align-items: flex-start; gap: 14px;
      background: rgba(255,255,255,.12); border-radius: 10px;
      padding: 13px 16px; text-align: left;
    }
    .step-num {
      width: 26px; height: 26px; border-radius: 50%;
      background: rgba(255,255,255,.25); display: flex; align-items: center;
      justify-content: center; font-size: .8rem; font-weight: 800; flex-shrink: 0;
    }
    .cover-step span { font-size: .86rem; font-weight: 600; line-height: 1.4; }

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

    <?php $enviado = isset($_GET['enviado']); ?>

    <?php if ($enviado): ?>
    <!-- Estado: enlace enviado -->
    <div class="sent-wrap">
      <div class="sent-icon-box"><i class="mdi mdi-email-check-outline"></i></div>
      <p class="sent-title">¡Revisa tu correo!</p>
      <?php $hint = $_SESSION['reset_hint'] ?? null; unset($_SESSION['reset_hint']); ?>
      <div class="sent-hint">
        <?php if ($hint): ?>
          Enviamos un enlace de recuperación a:<br>
          <strong><?= e($hint) ?></strong>
        <?php else: ?>
          Si el dato ingresado corresponde a una cuenta con correo registrado,
          recibirás el enlace en breve.
        <?php endif; ?>
      </div>
      <p class="sent-note">
        El enlace expira en <?= (int)(PASSWORD_RESET_TTL / 60) ?> minutos · Solo puede usarse una vez.<br>
        Si no recibes el correo, revisa la carpeta de spam.
      </p>
    </div>

    <?php else: ?>
    <!-- Formulario -->
    <p class="section-title">Recuperar contraseña</p>
    <p class="section-sub">
      Ingresa tu correo electrónico o nombre de usuario y te enviaremos
      un enlace para restablecer tu contraseña.
    </p>

    <?php if ($err = flash('error')): ?>
      <div class="alert-error"><i class="mdi mdi-alert-circle-outline"></i> <?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/recuperar-password">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label>Correo electrónico o usuario</label>
        <div class="input-wrap">
          <i class="mdi mdi-account-outline"></i>
          <input type="text" name="identificador" autofocus required
                 placeholder="correo@ejemplo.com  o  nombre.usuario"
                 value="<?= e($_POST['identificador'] ?? '') ?>">
        </div>
        <p class="form-hint">
          Solo podrás recuperar tu contraseña si tu cuenta tiene un correo electrónico registrado.
        </p>
      </div>
      <button type="submit" class="btn-primary">
        <i class="mdi mdi-send-outline"></i> Enviar enlace de recuperación
      </button>
    </form>
    <?php endif; ?>

    <div class="login-links">
      <a href="<?= APP_URL ?>/"><i class="mdi mdi-arrow-left"></i> Volver al inicio de sesión</a>
    </div>
  </div>

  <!-- ── Panel derecho ── -->
  <div class="login-cover">
    <img src="<?= APP_URL ?>/assets/img/logo-csma.png" alt="Logo" class="cover-logo">
    <h2>Recupera tu acceso</h2>
    <div class="cover-appname"><?= e($col['nombre']) ?></div>
    <p>Sigue estos pasos para restablecer tu contraseña de forma segura.</p>

    <div class="cover-steps">
      <div class="cover-step">
        <div class="step-num">1</div>
        <span>Ingresa tu correo o nombre de usuario registrado</span>
      </div>
      <div class="cover-step">
        <div class="step-num">2</div>
        <span>Revisa tu bandeja de entrada y abre el correo de recuperación</span>
      </div>
      <div class="cover-step">
        <div class="step-num">3</div>
        <span>Haz clic en el enlace y crea tu nueva contraseña segura</span>
      </div>
    </div>
  </div>

</div>
</body>
</html>
