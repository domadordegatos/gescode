<?php
$pageTitle  = 'Inicio — ' . APP_NAME;
$breadcrumbs = [['label' => 'Inicio', 'url' => APP_URL . '/dashboard']];
$noLeidos   = $mensajes_no_leidos ?? 0;

$estadosCfg = [
    'presente' => ['icon' => 'mdi-check-circle',        'color' => '#16A34A', 'bg' => '#DCFCE7', 'label' => 'Presente'],
    'tarde'    => ['icon' => 'mdi-clock-alert-outline',  'color' => '#D97706', 'bg' => '#FEF3C7', 'label' => 'Tarde'],
    'excusa'   => ['icon' => 'mdi-shield-check-outline', 'color' => '#2563EB', 'bg' => '#DBEAFE', 'label' => 'Excusa'],
    'ausente'  => ['icon' => 'mdi-close-circle',         'color' => '#DC2626', 'bg' => '#FEE2E2', 'label' => 'Ausente'],
];

ob_start();
?>

<div class="page-header" style="margin-bottom:0">
  <h1 class="page-title">
    <?php
      $hora = (int) date('G');
      if ($hora < 12)      echo 'Buenos días';
      elseif ($hora < 18)  echo 'Buenas tardes';
      else                 echo 'Buenas noches';
    ?>, <?= htmlspecialchars(Auth::user()['nombre']) ?>
  </h1>
  <p class="page-subtitle"><?= date('l, d \d\e F \d\e Y') ?></p>
</div>

<?php if (!empty($avisos_pendientes)): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;margin:18px 0 22px">
  <?php foreach ($avisos_pendientes as $av): ?>
  <a href="<?= APP_URL . $av['url'] ?>" class="card" style="padding:14px 16px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;border-left:4px solid <?= $av['color'] ?>">
    <i class="mdi <?= $av['icono'] ?>" style="font-size:1.4rem;color:<?= $av['color'] ?>"></i>
    <span style="font-size:.85rem;font-weight:600"><?= e($av['texto']) ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($hijos)): ?>
  <div class="empty-state" style="margin-top:32px">
    <i class="mdi mdi-account-child-outline"></i>
    <p>No tienes estudiantes vinculados a tu cuenta. Contacta a la institución.</p>
  </div>
<?php else: ?>

<!-- ── SELECTOR DE HIJO ───────────────────────────────────── -->
<?php if (count($hijos) > 1): ?>
<div class="padre-switcher">
  <?php foreach ($hijos as $h):
    $activo = ((int)$h['estudiante_id'] === (int)($hijo_sel['estudiante_id'] ?? 0));
    $ini    = strtoupper(substr($h['nombre'], 0, 1));
  ?>
  <a href="<?= APP_URL ?>/mis-hijos/<?= $h['estudiante_id'] ?>"
     class="padre-hijo-card <?= $activo ? 'activo' : '' ?>">
    <div class="phc-avatar" style="<?= $activo ? 'background:var(--primary);color:#fff' : '' ?>">
      <?= $ini ?>
    </div>
    <div class="phc-info">
      <strong><?= htmlspecialchars($h['nombre'] . ' ' . $h['apellido']) ?></strong>
      <span><?= htmlspecialchars($h['grado'] . ' · ' . $h['grupo']) ?></span>
    </div>
    <?php if ($activo): ?>
      <i class="mdi mdi-chevron-right phc-arrow"></i>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($hijo_sel): ?>
<!-- ── HIJO SELECCIONADO ──────────────────────────────────── -->
<div class="padre-hijo-header">
  <div class="padre-hijo-avatar-lg">
    <?= strtoupper(substr($hijo_sel['nombre'], 0, 1)) ?>
  </div>
  <div class="padre-hijo-info">
    <h2 class="padre-hijo-nombre"><?= htmlspecialchars($hijo_sel['nombre'] . ' ' . $hijo_sel['apellido']) ?></h2>
    <span class="padre-hijo-grado">
      <i class="mdi mdi-google-classroom"></i>
      <?= htmlspecialchars($hijo_sel['grado'] . ' · ' . $hijo_sel['grupo']) ?>
    </span>
  </div>
  <div class="padre-hijo-actions">
    <a href="<?= APP_URL ?>/comunicaciones/redactar" class="btn-secondary btn-sm">
      <i class="mdi mdi-message-text-outline"></i> Mensaje
    </a>
    <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo_sel['estudiante_id'] ?>?tab=notas" class="btn-primary btn-sm">
      <i class="mdi mdi-chart-timeline-variant"></i> Ver notas
    </a>
  </div>
</div>

<!-- ── GRID NOTAS + ASISTENCIA ───────────────────────────── -->
<div class="padre-grid">

  <!-- Notas del período actual -->
  <div class="padre-card">
    <div class="padre-card-head">
      <i class="mdi mdi-clipboard-text-outline" style="color:var(--primary)"></i>
      <span>Notas · Período actual</span>
      <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo_sel['estudiante_id'] ?>?tab=notas" class="padre-card-link">
        Ver todo <i class="mdi mdi-arrow-right"></i>
      </a>
    </div>
    <?php if (empty($notas_hijo)): ?>
      <div class="padre-empty">
        <i class="mdi mdi-clipboard-list-outline"></i>
        <p>Aún no hay notas en el período activo.</p>
      </div>
    <?php else: ?>
      <div class="padre-notas-list">
        <?php foreach ($notas_hijo as $n):
          $prom = (float)$n['promedio'];
          $ok   = $prom >= 6.0;
        ?>
        <div class="padre-nota-row">
          <span class="padre-nota-materia"><?= htmlspecialchars($n['materia']) ?></span>
          <div class="padre-nota-right">
            <span class="padre-nota-eval"><?= $n['total_eval'] ?> eval.</span>
            <span class="padre-nota-val <?= $ok ? 'ok' : 'fail' ?>"><?= number_format($prom, 1) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Asistencia reciente -->
  <div class="padre-card">
    <div class="padre-card-head">
      <i class="mdi mdi-calendar-check-outline" style="color:#16A34A"></i>
      <span>Asistencia reciente</span>
    </div>
    <?php if (empty($asistencia_hijo)): ?>
      <div class="padre-empty">
        <i class="mdi mdi-calendar-blank-outline"></i>
        <p>No hay registros de asistencia todavía.</p>
      </div>
    <?php else: ?>
      <div class="padre-asist-list">
        <?php foreach ($asistencia_hijo as $a):
          $cfg  = $estadosCfg[$a['estado']] ?? $estadosCfg['ausente'];
          $hora = !empty($a['created_at']) ? date('H:i', strtotime($a['created_at'])) : null;
          $mat  = $a['materia'] ?? null;
          $reg  = !empty($a['reg_nombre']) ? $a['reg_nombre'].' '.$a['reg_apellido'] : null;
        ?>
        <div class="padre-asist-fila">
          <span class="padre-asist-dot" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
            <i class="mdi <?= $cfg['icon'] ?>"></i>
          </span>
          <span class="padre-asist-inline">
            <span class="pa-fecha"><?= date('d M', strtotime($a['fecha'])) ?></span>
            <?php if ($hora): ?><span class="pa-sep">·</span><span class="pa-hora"><?= $hora ?></span><?php endif; ?>
            <span class="pa-sep">·</span>
            <span class="pa-estado" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
            <?php if ($mat): ?><span class="pa-sep">·</span><span class="pa-mat"><?= htmlspecialchars($mat) ?></span><?php endif; ?>
            <?php if ($reg): ?><span class="pa-sep">·</span><span class="pa-reg"><?= htmlspecialchars($reg) ?></span><?php endif; ?>
            <?php if ($a['observacion']): ?><span class="pa-sep">·</span><span class="padre-asist-obs"><?= htmlspecialchars($a['observacion']) ?></span><?php endif; ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /padre-grid -->
<?php endif; ?>

<!-- ── ACCIONES RÁPIDAS ───────────────────────────────────── -->
<div class="dashboard-actions" style="margin-top:24px">
  <a href="<?= APP_URL ?>/comunicaciones" class="action-btn">
    <i class="mdi mdi-forum-outline"></i>
    Mensajería
    <?php if ($noLeidos > 0): ?>
      <span class="action-badge"><?= $noLeidos ?></span>
    <?php endif; ?>
  </a>
  <a href="<?= APP_URL ?>/calendario" class="action-btn">
    <i class="mdi mdi-calendar-month-outline"></i>
    Agenda escolar
  </a>
  <a href="<?= APP_URL ?>/perfil" class="action-btn">
    <i class="mdi mdi-account-edit-outline"></i>
    Mi perfil
  </a>
  <?php if (count($hijos) > 1): ?>
  <a href="<?= APP_URL ?>/dashboard" class="action-btn">
    <i class="mdi mdi-account-multiple-outline"></i>
    Mis acudidos (<?= count($hijos) ?>)
  </a>
  <?php endif; ?>
</div>

<?php endif; // empty hijos ?>

<!-- ── MENSAJES RECIENTES ─────────────────────────────────── -->
<?php if (!empty($mensajes_recientes)): ?>
<div class="dash-msg-widget">
  <div class="dash-msg-header">
    <div class="dash-msg-title">
      <i class="mdi mdi-email-outline"></i>
      Mensajes recientes
    </div>
    <a href="<?= APP_URL ?>/comunicaciones" class="dash-msg-ver-todos">
      Ver todos
      <?php if ($noLeidos > 0): ?>
        <span class="dash-msg-badge"><?= $noLeidos ?> sin leer</span>
      <?php endif; ?>
      <i class="mdi mdi-arrow-right"></i>
    </a>
  </div>

  <div class="dash-msg-list">
    <?php foreach ($mensajes_recientes as $msg):
      $iniciales = strtoupper(
        substr($msg['remitente_nombre'] ?? '?', 0, 1) .
        substr($msg['remitente_apellido'] ?? '', 0, 1)
      );
      $colores = ['docente'=>'#0E0EB3','admin'=>'#7c3aed','estudiante'=>'#059669','padre'=>'#d97706','soporte'=>'#0369a1'];
      $color   = $colores[$msg['remitente_rol']] ?? '#6b7280';
      $unread  = !$msg['leido'];
    ?>
    <a href="<?= APP_URL ?>/comunicaciones/<?= $msg['id'] ?>" class="dash-msg-item <?= $unread ? 'unread' : '' ?>">
      <div class="dash-msg-avatar" style="background:<?= $color ?>">
        <?= e($iniciales) ?>
      </div>
      <div class="dash-msg-body">
        <div class="dash-msg-sender">
          <?= e($msg['remitente_nombre'] . ' ' . $msg['remitente_apellido']) ?>
          <?php if ($unread): ?><span class="dash-msg-dot"></span><?php endif; ?>
        </div>
        <div class="dash-msg-asunto"><?= e($msg['asunto']) ?></div>
      </div>
      <div class="dash-msg-time"><?= formatDate($msg['created_at']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="dash-msg-footer">
    <a href="<?= APP_URL ?>/comunicaciones/redactar" class="dash-msg-redactar">
      <i class="mdi mdi-pencil-outline"></i> Redactar mensaje
    </a>
  </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
