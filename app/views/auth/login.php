<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $col = colegio(); ?>
  <meta name="theme-color" content="<?= e($col['color_primario']) ?>">
  <title>Ingresar — <?= e($col['nombre_corto'] ?: $col['nombre']) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <style>
    :root { --primary: <?= e($col['color_primario']) ?>; --primary-dark: <?= e($col['color_secundario']) ?>; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; }

    .login-wrapper { display: flex; width: 100%; min-height: 100vh; }

    /* ── Panel izquierdo ── */
    .login-panel {
      width: 420px; min-width: 320px;
      background: #fff;
      display: flex; flex-direction: column; justify-content: center;
      padding: 48px 40px;
      box-shadow: 2px 0 16px rgba(0,0,0,0.06);
      z-index: 2;
    }

    .login-brand {
      display: flex; flex-direction: column; align-items: center;
      margin-bottom: 36px; gap: 14px; text-align: center;
    }
    .login-brand img {
      width: 90px; height: 90px; object-fit: contain;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.12));
    }
    .brand-school {
      font-size: .76rem; font-weight: 700; color: #666;
      text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px !important;
    }
    .login-brand h1 {
      font-size: 1.6rem; font-weight: 800; color: var(--primary); letter-spacing: -.5px;
    }
    .login-brand p {
      font-size: 0.82rem; color: #888; margin-top: 2px;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; font-weight: 600; font-size: 0.85rem; color: #444; margin-bottom: 6px;
    }
    .input-wrap { position: relative; }
    .input-wrap .mdi {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%); font-size: 18px; color: #aaa;
    }
    .input-wrap input {
      width: 100%; padding: 10px 12px 10px 38px;
      border: 1.5px solid #ddd; border-radius: 8px;
      font-family: inherit; font-size: 0.95rem; color: #222;
      transition: border-color .2s; outline: none;
    }
    .input-wrap input:focus { border-color: var(--primary); }

    .btn-primary {
      width: 100%; padding: 12px;
      background: var(--primary); color: #fff; border: none;
      border-radius: 8px; font-family: inherit;
      font-size: 1rem; font-weight: 700; cursor: pointer;
      transition: background .2s; margin-top: 8px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background: var(--primary-dark); }

    .login-links {
      margin-top: 20px; text-align: center;
      font-size: 0.85rem; color: #666;
    }
    .login-links a { color: var(--primary); font-weight: 600; text-decoration: none; }

    .alert {
      padding: 10px 14px; border-radius: 8px; font-size: 0.875rem;
      margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .alert-error   { background: #fef2f2; color: #cc0000; border: 1px solid #fca5a5; }
    .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }

    /* ── Panel derecho ── */
    .login-cover {
      flex: 1;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: #fff; padding: 40px; text-align: center;
    }
    .cover-logo {
      width: 110px; height: 110px; object-fit: contain;
      margin-bottom: 24px;
      filter: brightness(0) invert(1) drop-shadow(0 4px 12px rgba(0,0,0,.2));
      opacity: .9;
    }
    .login-cover h2 { font-size: 2rem; font-weight: 800; margin-bottom: 8px; }
    .cover-appname {
      font-size: 1.1rem; font-weight: 800; letter-spacing: 2px;
      text-transform: uppercase; opacity: .7; margin-bottom: 16px;
    }
    .login-cover p  { font-size: 1rem; opacity: .85; max-width: 380px; line-height: 1.6; }
    .cover-features {
      margin-top: 36px; display: flex; flex-direction: column;
      gap: 14px; width: 100%; max-width: 340px;
    }
    .cover-feature {
      display: flex; align-items: center; gap: 14px;
      background: rgba(255,255,255,0.12);
      border-radius: 10px; padding: 12px 16px; text-align: left;
    }
    .cover-feature .mdi { font-size: 22px; opacity: .9; }
    .cover-feature span { font-size: 0.88rem; font-weight: 600; }

    @media (max-width: 700px) {
      .login-cover  { display: none; }
      .login-panel  { width: 100%; padding: 32px 24px; }
    }
  </style>
</head>
<body>
<div class="login-wrapper">

  <!-- ── Formulario ── -->
  <div class="login-panel">
    <div class="login-brand">
      <img src="<?= $col['logo'] ? APP_URL.'/uploads/imagenes/'.e($col['logo']) : APP_URL.'/assets/img/logo-csma.png' ?>" alt="Logo">
      <div>
        <p class="brand-school"><?= e($col['nombre']) ?></p>
        <h1><?= APP_NAME ?></h1>
        <p>Sistema de Gestión Académica</p>
      </div>
    </div>

    <?php if ($ok = flash('success')): ?>
      <div class="alert alert-success">
        <i class="mdi mdi-check-circle-outline"></i>
        <?= htmlspecialchars($ok) ?>
      </div>
    <?php endif; ?>

    <?php if ($err = flash('error')): ?>
      <div class="alert alert-error">
        <i class="mdi mdi-alert-circle-outline"></i>
        <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/login" autocomplete="on">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="username">Usuario</label>
        <div class="input-wrap">
          <i class="mdi mdi-account-outline"></i>
          <input type="text" id="username" name="username"
                 placeholder="Tu nombre de usuario"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 required autofocus autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="input-wrap">
          <i class="mdi mdi-lock-outline"></i>
          <input type="password" id="password" name="password"
                 placeholder="••••••••"
                 required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn-primary">
        <i class="mdi mdi-login"></i> Ingresar
      </button>
    </form>

    <div class="login-links">
      <a href="<?= APP_URL ?>/recuperar-password">¿Olvidaste tu contraseña?</a>
    </div>
  </div>

  <!-- ── Panel derecho ── -->
  <div class="login-cover">
    <img src="<?= $col['logo'] ? APP_URL.'/uploads/imagenes/'.e($col['logo']) : APP_URL.'/assets/img/logo-csma.png' ?>" alt="Logo" class="cover-logo">
    <h2>Bienvenido</h2>
    <div class="cover-appname"><?= e($col['nombre']) ?></div>
    <p>Plataforma integral de gestión académica para toda la comunidad educativa.</p>

    <div class="cover-features">
      <div class="cover-feature">
        <i class="mdi mdi-clipboard-list-outline"></i>
        <span>Registro y seguimiento de calificaciones</span>
      </div>
      <div class="cover-feature">
        <i class="mdi mdi-email-outline"></i>
        <span>Comunicación entre docentes, padres y estudiantes</span>
      </div>
      <div class="cover-feature">
        <i class="mdi mdi-chart-bar"></i>
        <span>Reportes académicos y de convivencia</span>
      </div>
      <div class="cover-feature">
        <i class="mdi mdi-account-group-outline"></i>
        <span>Gestión de roles: docentes, padres, estudiantes</span>
      </div>
    </div>
  </div>

</div>
</body>
</html>
