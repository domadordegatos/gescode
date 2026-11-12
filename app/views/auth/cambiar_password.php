<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cambiar contraseña — <?= APP_NAME ?></title>
  <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>
<?php
$pageTitle   = 'Cambiar contraseña';
$breadcrumbs = [
  ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
  ['label' => 'Cambiar contraseña', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>
<div class="page-header">
  <h1 class="page-title">Cambiar contraseña</h1>
</div>

<div class="card" style="max-width:460px;padding:24px">
  <?php if ($err = flash('error')): ?>
    <div class="alert alert-error"><i class="mdi mdi-alert-circle"></i> <?= e($err) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= APP_URL ?>/perfil/cambiar-password">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
      <label>Contraseña actual</label>
      <input type="password" name="password_actual" class="form-input" required autocomplete="current-password">
    </div>
    <div class="form-group">
      <label>Nueva contraseña <small>(mín. 8 caracteres)</small></label>
      <input type="password" name="password_nueva" class="form-input" required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label>Confirmar nueva contraseña</label>
      <input type="password" name="password_confirmar" class="form-input" required autocomplete="new-password">
    </div>
    <div class="form-actions">
      <button type="submit" class="btn-primary"><i class="mdi mdi-lock-reset"></i> Actualizar</button>
      <a href="<?= APP_URL ?>/dashboard" class="btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
