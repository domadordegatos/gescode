<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Institución no encontrada — <?= APP_NAME ?></title>
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
    .logo { font-size: 1.1rem; font-weight: 800; color: #0E0EB3; letter-spacing: 2px; margin-bottom: 32px; }
    .icon { font-size: 3rem; color: #D1D5DB; margin-bottom: 16px; display: block; }
    h1 { font-size: 1.4rem; font-weight: 800; color: #111827; margin-bottom: 10px; }
    p { font-size: .95rem; color: #6B7280; line-height: 1.6; margin-bottom: 8px; }
    .sub { font-size: .82rem; color: #9CA3AF; margin-top: 24px; }
    code { background: #F3F4F6; padding: 2px 8px; border-radius: 6px; font-size: .85rem; }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo"><?= APP_NAME ?></div>
    <i class="mdi mdi-school-off-outline icon"></i>
    <h1>Institución no encontrada</h1>
    <p>El subdominio <code><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></code> no corresponde a ninguna institución registrada en la plataforma.</p>
    <p>Verifica que la dirección sea correcta o contacta a tu institución.</p>
    <p class="sub">Si eres administrador, comunícate con el equipo de soporte de <?= APP_NAME ?>.</p>
  </div>
</body>
</html>
