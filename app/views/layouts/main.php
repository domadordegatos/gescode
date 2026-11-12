<?php header('Content-Type: text/html; charset=utf-8'); ?><!DOCTYPE html>
<html lang="es" translate="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $col = colegio(); ?>
  <meta name="theme-color" content="<?= e($col['color_primario']) ?>">
  <style>:root{--primary:<?= e($col['color_primario']) ?>;--primary-dark:<?= e($col['color_secundario']) ?>;}</style>
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/assets/img/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/img/logo-gescode.png?v=2">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css?v=<?= filemtime(ROOT . '/public/assets/css/app.css') ?>">
  <?= $extraHead ?? '' ?>
</head>
<body>

<div id="app-container">

  <!-- ── SIDEBAR ─────────────────────────────────────────── -->
  <aside id="sidebar">
    <div id="sidebar-header">
      <button id="sidebar-toggler" title="Contraer menú">
        <i class="mdi mdi-menu"></i>
      </button>
    </div>
    <a href="<?= APP_URL ?>/dashboard" id="sidebar-brand">
      <img src="<?= $col['logo'] ? APP_URL.'/uploads/imagenes/'.e($col['logo']) : APP_URL.'/assets/img/logo-csma.png' ?>" id="sidebar-brand-img" alt="Logo">
      <div id="sidebar-brand-text">
        <span id="sidebar-brand-school"><?= e($col['nombre']) ?></span>
        <span id="sidebar-brand-name"><?= APP_NAME ?></span>
      </div>
    </a>

    <nav id="sidebar-nav">
      <?php $rol = Auth::rol(); ?>

      <a href="<?= APP_URL ?>/dashboard" class="nav-item <?= activePage('dashboard') ?>">
        <i class="mdi mdi-presentation"></i>
        <span>Pizarra</span>
      </a>
      <a href="<?= APP_URL ?>/calendario" class="nav-item <?= activePage('calendario') ?>">
        <i class="mdi mdi-calendar-month-outline"></i>
        <span>Agenda</span>
      </a>

      <?php if (in_array($rol, ['admin','docente'])): ?>
      <div class="nav-section">Gestión</div>

      <?php if ($rol === 'admin'): ?>
      <a href="<?= APP_URL ?>/grados" class="nav-item <?= activePage('grados') ?>">
        <i class="mdi mdi-school-outline"></i>
        <span>Grados y Grupos</span>
      </a>
      <a href="<?= APP_URL ?>/usuarios" class="nav-item <?= activePage('usuarios') ?>">
        <i class="mdi mdi-account-group-outline"></i>
        <span>Usuarios</span>
      </a>
      <a href="<?= APP_URL ?>/periodos" class="nav-item <?= activePage('periodos') ?>">
        <i class="mdi mdi-calendar-range-outline"></i>
        <span>Períodos</span>
      </a>
      <?php endif; ?>

      <?php if ($rol === 'admin'): ?>
      <a href="<?= APP_URL ?>/usuarios?rol=estudiante" class="nav-item <?= activePage('estudiantes') ?>">
        <i class="mdi mdi-account-school-outline"></i>
        <span>Estudiantes</span>
      </a>
      <?php endif; ?>

      <?php if ($rol === 'docente'): ?>
      <a href="<?= APP_URL ?>/mis-grupos" class="nav-item <?= activePage('mis-grupos') ?>">
        <i class="mdi mdi-book-open-outline"></i>
        <span>Mis clases</span>
      </a>
      <a href="<?= APP_URL ?>/citas" class="nav-item <?= activePage('citas') ?>">
        <i class="mdi mdi-calendar-clock-outline"></i>
        <span>Citas con acudientes</span>
      </a>
      <a href="<?= APP_URL ?>/boletines" class="nav-item <?= activePage('boletines') ?>">
        <i class="mdi mdi-file-pdf-box"></i>
        <span>Boletines de notas</span>
      </a>
      <a href="<?= APP_URL ?>/planeador" class="nav-item <?= activePage('planeador') ?>">
        <i class="mdi mdi-notebook-edit-outline"></i>
        <span>Planeador de clases</span>
      </a>
      <a href="<?= APP_URL ?>/habilitaciones" class="nav-item <?= activePage('habilitaciones') ?>">
        <i class="mdi mdi-book-alert-outline"></i>
        <span>Habilitación anual</span>
      </a>
      <?php endif; ?>

      <?php endif; ?>

      <?php if ($rol === 'secretaria'): ?>
      <div class="nav-section">Gestión</div>
      <a href="<?= APP_URL ?>/usuarios" class="nav-item <?= activePage('usuarios') ?>">
        <i class="mdi mdi-account-group-outline"></i>
        <span>Usuarios</span>
      </a>
      <a href="<?= APP_URL ?>/usuarios?rol=estudiante" class="nav-item <?= activePage('estudiantes') ?>">
        <i class="mdi mdi-account-school-outline"></i>
        <span>Estudiantes</span>
      </a>
      <a href="<?= APP_URL ?>/ficha-estudiante" class="nav-item <?= activePage('ficha-estudiante') ?>">
        <i class="mdi mdi-card-account-details-outline"></i>
        <span>Ficha de estudiante</span>
      </a>
      <a href="<?= APP_URL ?>/retiro-anticipado" class="nav-item <?= activePage('retiro-anticipado') ?>">
        <i class="mdi mdi-exit-run"></i>
        <span>Retiro anticipado</span>
      </a>
      <?php endif; ?>

      <?php if ($rol === 'estudiante'): ?>
      <div class="nav-section">Mi seguimiento</div>
      <a href="<?= APP_URL ?>/mis-notas" class="nav-item <?= activePage('mis-notas') ?>">
        <i class="mdi mdi-chart-timeline-variant"></i>
        <span>Mi rendimiento</span>
      </a>
      <?php endif; ?>

      <?php if ($rol === 'padre'): ?>
      <div class="nav-section">Mis acudidos</div>
      <?php
        $hijos_nav = $hijos ?? [];
        $uri_actual = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($hijos_nav as $h):
          $url_hijo = APP_URL . '/mis-hijos/' . $h['estudiante_id'];
          $activo_h = str_contains($uri_actual, '/mis-hijos/' . $h['estudiante_id']);
      ?>
      <a href="<?= $url_hijo ?>" class="nav-item <?= $activo_h ? 'active' : '' ?>">
        <span class="nav-hijo-avatar"><?= strtoupper(substr($h['nombre'],0,1)) ?></span>
        <span><?= htmlspecialchars($h['nombre'] . ' ' . $h['apellido']) ?></span>
      </a>
      <?php endforeach; ?>
      <a href="<?= APP_URL ?>/autoevaluacion" class="nav-item <?= activePage('autoevaluacion') ?>">
        <i class="mdi mdi-account-check-outline"></i>
        <span>Autoevaluación</span>
      </a>
      <a href="<?= APP_URL ?>/citas" class="nav-item <?= activePage('citas') ?>">
        <i class="mdi mdi-calendar-clock-outline"></i>
        <span>Citas con docentes</span>
      </a>
      <a href="<?= APP_URL ?>/autorizaciones" class="nav-item <?= activePage('autorizaciones') ?>">
        <i class="mdi mdi-file-sign"></i>
        <span>Autorizaciones</span>
      </a>
      <?php endif; ?>

      <!-- Perfil -->
      <?php
        $enPerfil = str_contains($_SERVER['REQUEST_URI'] ?? '', '/perfil');
      ?>
      <div class="nav-group <?= $enPerfil ? 'open' : '' ?>">
        <button class="nav-group-toggle nav-item" type="button">
          <i class="mdi mdi-shield-account-outline"></i>
          <span>Mi cuenta</span>
          <i class="mdi mdi-chevron-down nav-group-arrow"></i>
        </button>
        <div class="nav-group-children">
          <a href="<?= APP_URL ?>/perfil" class="nav-item nav-child <?= activePage('perfil') && !str_contains($_SERVER['REQUEST_URI'],'editar') && !str_contains($_SERVER['REQUEST_URI'],'password') ? 'active' : '' ?>">
            <i class="mdi mdi-card-account-details-outline"></i>
            <span>Mi información</span>
          </a>
          <a href="<?= APP_URL ?>/perfil/editar" class="nav-item nav-child <?= str_contains($_SERVER['REQUEST_URI'] ?? '','perfil/editar') ? 'active' : '' ?>">
            <i class="mdi mdi-account-edit-outline"></i>
            <span>Actualizar datos</span>
          </a>
          <a href="<?= APP_URL ?>/perfil/password" class="nav-item nav-child <?= str_contains($_SERVER['REQUEST_URI'] ?? '','perfil/password') ? 'active' : '' ?>">
            <i class="mdi mdi-key-outline"></i>
            <span>Seguridad</span>
          </a>
          <?php if (Auth::rol() === 'soporte' || !empty(colegio()['notificaciones_email'])): ?>
          <a href="<?= APP_URL ?>/perfil/notificaciones" class="nav-item nav-child <?= str_contains($_SERVER['REQUEST_URI'] ?? '','perfil/notificaciones') ? 'active' : '' ?>">
            <i class="mdi mdi-bell-ring-outline"></i>
            <span>Alertas</span>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="nav-section">Canal</div>
      <a href="<?= APP_URL ?>/comunicaciones" class="nav-item <?= activePage('comunicaciones') ?>">
        <i class="mdi mdi-forum-outline"></i>
        <span>Mensajería</span>
        <?php if (!empty($noLeidos) && $noLeidos > 0): ?>
          <span class="badge"><?= $noLeidos ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/circulares" class="nav-item <?= activePage('circulares') ?>">
        <i class="mdi mdi-bullhorn-outline"></i>
        <span>Circulares</span>
      </a>
      <a href="<?= APP_URL ?>/pqrs" class="nav-item <?= activePage('pqrs') ?>">
        <i class="mdi mdi-file-document-edit-outline"></i>
        <span>PQRS</span>
      </a>
      <a href="<?= APP_URL ?>/encuestas" class="nav-item <?= activePage('encuestas') ?>">
        <i class="mdi mdi-poll"></i>
        <span>Encuestas</span>
      </a>

      <!-- Comunidad -->
      <?php $enComunidad = str_contains($_SERVER['REQUEST_URI'] ?? '', '/comunidad'); ?>
      <div class="nav-group <?= $enComunidad ? 'open' : '' ?>">
        <button class="nav-group-toggle nav-item" type="button">
          <i class="mdi mdi-bookshelf"></i>
          <span>Repositorio</span>
          <i class="mdi mdi-chevron-down nav-group-arrow"></i>
        </button>
        <div class="nav-group-children">
          <a href="<?= APP_URL ?>/comunidad" class="nav-item nav-child <?= $enComunidad && !str_contains($_SERVER['REQUEST_URI'] ?? '','admin') ? 'active' : '' ?>">
            <i class="mdi mdi-folder-open-outline"></i>
            <span>Recursos</span>
          </a>
          <?php if (in_array($rol, ['soporte','admin'])): ?>
          <a href="<?= APP_URL ?>/comunidad/admin" class="nav-item nav-child <?= str_contains($_SERVER['REQUEST_URI'] ?? '','comunidad/admin') ? 'active' : '' ?>">
            <i class="mdi mdi-folder-cog-outline"></i>
            <span>Administrar</span>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (in_array($rol, ['admin','soporte'])): ?>
      <div class="nav-section">Administración</div>
      <?php if (Auth::isPrincipal()): ?>
      <a href="<?= APP_URL ?>/admin/colegio" class="nav-item <?= activePage('colegio') ?>">
        <i class="mdi mdi-office-building-outline"></i>
        <span>Mi institución</span>
      </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/soporte/desempenios" class="nav-item <?= activePage('desempenios') ?>">
        <i class="mdi mdi-medal-outline"></i>
        <span>Escala valorativa</span>
      </a>
      <?php if ($rol === 'soporte'): ?>
      <a href="<?= APP_URL ?>/soporte/funcionalidades" class="nav-item <?= activePage('funcionalidades') ?>">
        <i class="mdi mdi-toggle-switch-outline"></i>
        <span>Funcionalidades</span>
      </a>
      <a href="<?= APP_URL ?>/soporte/backups" class="nav-item <?= activePage('backups') ?>">
        <i class="mdi mdi-database-arrow-down-outline"></i>
        <span>Respaldos</span>
      </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/admin/eventos" class="nav-item <?= activePage('eventos') ?>">
        <i class="mdi mdi-calendar-star"></i>
        <span>Eventos institucionales</span>
      </a>
      <a href="<?= APP_URL ?>/observador" class="nav-item <?= activePage('observador') ?>">
        <i class="mdi mdi-timeline-text-outline"></i>
        <span>Observador del estudiante</span>
      </a>
      <a href="<?= APP_URL ?>/boletines" class="nav-item <?= activePage('boletines') ?>">
        <i class="mdi mdi-file-pdf-box"></i>
        <span>Boletines de notas</span>
      </a>
      <a href="<?= APP_URL ?>/planeador" class="nav-item <?= activePage('planeador') ?>">
        <i class="mdi mdi-notebook-edit-outline"></i>
        <span>Planeador de clases</span>
      </a>
      <a href="<?= APP_URL ?>/constancias" class="nav-item <?= activePage('constancias') ?>">
        <i class="mdi mdi-certificate-outline"></i>
        <span>Constancias y certificados</span>
      </a>
      <a href="<?= APP_URL ?>/autorizaciones" class="nav-item <?= activePage('autorizaciones') ?>">
        <i class="mdi mdi-file-sign"></i>
        <span>Autorizaciones</span>
      </a>
      <a href="<?= APP_URL ?>/retiro-anticipado" class="nav-item <?= activePage('retiro-anticipado') ?>">
        <i class="mdi mdi-exit-run"></i>
        <span>Retiro anticipado</span>
      </a>
      <a href="<?= APP_URL ?>/historial-notas" class="nav-item <?= activePage('historial-notas') ?>">
        <i class="mdi mdi-history"></i>
        <span>Historial de notas</span>
      </a>
      <a href="<?= APP_URL ?>/papelera" class="nav-item <?= activePage('papelera') ?>">
        <i class="mdi mdi-trash-can-outline"></i>
        <span>Papelera de notas</span>
      </a>
      <a href="<?= APP_URL ?>/papelera-general" class="nav-item <?= activePage('papelera-general') ?>">
        <i class="mdi mdi-delete-restore"></i>
        <span>Papelera general</span>
      </a>
      <a href="<?= APP_URL ?>/accesos" class="nav-item <?= activePage('accesos') ?>">
        <i class="mdi mdi-shield-account-outline"></i>
        <span>Registro de accesos</span>
      </a>
      <a href="<?= APP_URL ?>/reportes" class="nav-item <?= activePage('reportes') ?>">
        <i class="mdi mdi-chart-areaspline"></i>
        <span>Estadísticas</span>
      </a>
      <?php endif; ?>
    </nav>

    <div id="sidebar-footer">
      <a href="<?= APP_URL ?>/perfil" class="nav-item user-item">
        <span class="avatar-sm">
          <img src="<?= APP_URL ?>/perfil/avatar/<?= Auth::id() ?>" alt="<?= strtoupper(substr(Auth::user()['nombre'],0,1)) ?>" class="avatar-sm-img">
        </span>
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars(Auth::user()['nombre'] . ' ' . Auth::user()['apellido']) ?></span>
          <span class="user-rol"><?= ucfirst(Auth::rol()) ?></span>
        </div>
      </a>
      <a href="<?= APP_URL ?>/logout" class="nav-item logout-item" title="Cerrar sesión">
        <i class="mdi mdi-logout"></i>
        <span>Salir</span>
      </a>
    </div>
  </aside>

  <!-- ── CONTENIDO PRINCIPAL ─────────────────────────────── -->
  <main id="main-content">
    <header id="topbar">
      <div id="breadcrumbs">
        <?php if (!empty($breadcrumbs)): ?>
          <?php foreach ($breadcrumbs as $i => $bc): ?>
            <?php if ($i < count($breadcrumbs) - 1): ?>
              <a href="<?= $bc['url'] ?>"><?= htmlspecialchars($bc['label']) ?></a>
              <span class="bc-sep">›</span>
            <?php else: ?>
              <span class="bc-current"><?= htmlspecialchars($bc['label']) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </header>

    <div id="page-body">
      <!-- Flash messages -->
      <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><i class="mdi mdi-alert-circle"></i> <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?= $content ?>
    </div>
  </main>

</div><!-- #app-container -->

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?= $extraScripts ?? '' ?>
<?= $extraFoot ?? '' ?>
</body>
</html>
