<!DOCTYPE html>
<html lang="es" translate="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $col = function_exists('colegio') ? colegio() : []; ?>
  <style>
    :root {
      --primary: <?= e($col['color_primario'] ?? '#0E0EB3') ?>;
      --primary-dark: <?= e($col['color_secundario'] ?? '#1a1a6e') ?>;
    }
  </style>
  <title>Página no encontrada — <?= APP_NAME ?></title>
  <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico?v=2">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Nunito', sans-serif;
      background: #f0f2f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .error-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.10);
      padding: 56px 48px;
      max-width: 520px;
      width: 100%;
      text-align: center;
    }
    .error-code {
      font-size: 6rem;
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
    }
    .error-icon {
      font-size: 3rem;
      color: #D1D5DB;
      margin-bottom: 16px;
      display: block;
    }
    .error-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: #111827;
      margin-bottom: 10px;
    }
    .error-msg {
      font-size: .95rem;
      color: #6B7280;
      line-height: 1.6;
      margin-bottom: 32px;
    }
    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--primary);
      color: #fff;
      font-family: inherit;
      font-size: .95rem;
      font-weight: 700;
      padding: 12px 28px;
      border-radius: 10px;
      text-decoration: none;
      transition: opacity .18s;
    }
    .btn-back:hover { opacity: .88; }
    .btn-sec {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #F3F4F6;
      color: #374151;
      font-family: inherit;
      font-size: .95rem;
      font-weight: 700;
      padding: 12px 28px;
      border-radius: 10px;
      text-decoration: none;
      margin-left: 10px;
      transition: background .18s;
    }
    .btn-sec:hover { background: #E5E7EB; }
  </style>
</head>
<body>
  <div class="error-card">
    <i class="mdi mdi-compass-off-outline error-icon"></i>
    <div class="error-code">404</div>
    <h1 class="error-title">Página no encontrada</h1>
    <p class="error-msg">
      La página que buscas no existe o fue movida.<br>
      Verifica la dirección o regresa al inicio.
    </p>
    <?php if (function_exists('redirect') && class_exists('Auth') && Auth::check()): ?>
      <a href="<?= APP_URL ?>/dashboard" class="btn-back">
        <i class="mdi mdi-home-outline"></i> Ir al inicio
      </a>
      <a href="javascript:history.back()" class="btn-sec">
        <i class="mdi mdi-arrow-left"></i> Volver
      </a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/" class="btn-back">
        <i class="mdi mdi-login"></i> Ir al login
      </a>
    <?php endif; ?>
  </div>
</body>
</html>
