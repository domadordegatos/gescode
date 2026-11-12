<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> — Acceso por institución</title>
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
    .card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.10);
      padding: 56px 48px;
      max-width: 520px;
      width: 100%;
      text-align: center;
    }
    .logo {
      font-size: 1.3rem;
      font-weight: 900;
      color: #0E0EB3;
      letter-spacing: 2px;
      margin-bottom: 28px;
    }
    .icon { font-size: 3rem; color: #D1D5DB; margin-bottom: 16px; display: block; }
    h1 { font-size: 1.35rem; font-weight: 800; color: #111827; margin-bottom: 10px; }
    p { font-size: .92rem; color: #6B7280; line-height: 1.6; margin-bottom: 8px; }
    .example {
      margin: 24px 0;
      background: #F0F2FF;
      border: 1px solid #C7D2FE;
      border-radius: 10px;
      padding: 14px 20px;
      font-size: .9rem;
      color: #3730A3;
    }
    code { font-weight: 700; font-size: .95rem; }
    .footer { margin-top: 28px; font-size: .78rem; color: #9CA3AF; }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo"><?= APP_NAME ?></div>
    <i class="mdi mdi-web icon"></i>
    <h1>Ingresa a través de tu institución</h1>
    <p>Esta plataforma funciona por subdominio. Debes acceder usando la dirección específica de tu institución educativa.</p>
    <div class="example">
      <i class="mdi mdi-link-variant"></i>
      Ejemplo: <code>csma.nncoms.com</code>
    </div>
    <p>Si no conoces la dirección, contacta al administrador de tu institución.</p>
    <p class="footer"><?= APP_NAME ?> &mdash; Plataforma de gestión académica</p>
  </div>
</body>
</html>
