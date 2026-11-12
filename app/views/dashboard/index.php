<?php
$pageTitle  = 'Inicio — ' . APP_NAME;
$breadcrumbs = [['label' => 'Inicio', 'url' => APP_URL . '/dashboard']];
$noLeidos   = $mensajes_no_leidos ?? 0;
ob_start();
?>

<div class="page-header">
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

<?php $rol = Auth::rol(); ?>

<?php if (!empty($avisos_pendientes)): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;margin-bottom:22px">
  <?php foreach ($avisos_pendientes as $av): ?>
  <a href="<?= APP_URL . $av['url'] ?>" class="card" style="padding:14px 16px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;border-left:4px solid <?= $av['color'] ?>">
    <i class="mdi <?= $av['icono'] ?>" style="font-size:1.4rem;color:<?= $av['color'] ?>"></i>
    <span style="font-size:.85rem;font-weight:600"><?= e($av['texto']) ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── ADMIN / SOPORTE ────────────────────────────────────── -->
<?php if (in_array($rol, ['admin', 'soporte'])): ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#EEF2FF">
      <i class="mdi mdi-account-group-outline" style="color:#4F46E5"></i>
    </div>
    <div class="stat-body">
      <span class="stat-value"><?= $total_usuarios ?></span>
      <span class="stat-label">Usuarios totales</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#F0FDF4">
      <i class="mdi mdi-account-school-outline" style="color:#16A34A"></i>
    </div>
    <div class="stat-body">
      <span class="stat-value"><?= $total_estudiantes ?></span>
      <span class="stat-label">Estudiantes</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF7ED">
      <i class="mdi mdi-teach" style="color:#EA580C"></i>
    </div>
    <div class="stat-body">
      <span class="stat-value"><?= $total_docentes ?></span>
      <span class="stat-label">Docentes</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FDF4FF">
      <i class="mdi mdi-google-classroom" style="color:#9333EA"></i>
    </div>
    <div class="stat-body">
      <span class="stat-value"><?= $total_grupos ?></span>
      <span class="stat-label">Grupos activos</span>
    </div>
  </div>
</div>

<div class="dashboard-actions">
  <a href="<?= APP_URL ?>/usuarios/crear" class="action-btn">
    <i class="mdi mdi-account-plus-outline"></i>
    Crear Usuario
  </a>
  <a href="<?= APP_URL ?>/grados" class="action-btn">
    <i class="mdi mdi-school-outline"></i>
    Gestionar Grados
  </a>
  <a href="<?= APP_URL ?>/periodos" class="action-btn">
    <i class="mdi mdi-calendar-edit"></i>
    Gestionar Períodos
  </a>
  <a href="<?= APP_URL ?>/reportes" class="action-btn">
    <i class="mdi mdi-chart-bar"></i>
    Ver Reportes
  </a>
</div>

<!-- ── DOCENTE ────────────────────────────────────────────── -->
<?php elseif ($rol === 'docente'): ?>

<div class="section-title">Mis grupos — Año lectivo <?= date('Y') ?></div>

<?php if (empty($grupos)): ?>
  <div class="empty-state">
    <i class="mdi mdi-google-classroom"></i>
    <p>No tienes grupos asignados en este período.</p>
  </div>
<?php else:
  // Agrupar por materia
  $porMateria = [];
  foreach ($grupos as $g) {
      $mat = $g['materia'];
      if (!isset($porMateria[$mat])) {
          $porMateria[$mat] = ['total_estudiantes' => 0, 'grupos' => []];
      }
      $porMateria[$mat]['grupos'][] = $g;
      $porMateria[$mat]['total_estudiantes'] += (int)$g['total_estudiantes'];
  }
?>
  <div class="materias-table">
    <?php foreach ($porMateria as $materia => $data): ?>
    <div class="materia-row">
      <div class="materia-row-head">
        <i class="mdi mdi-book-open-page-variant-outline"></i>
        <div>
          <span class="materia-nombre"><?= htmlspecialchars($materia) ?></span>
          <span class="materia-meta">
            <?= count($data['grupos']) ?> grupo<?= count($data['grupos']) != 1 ? 's' : '' ?>
            · <?= $data['total_estudiantes'] ?> estudiante<?= $data['total_estudiantes'] != 1 ? 's' : '' ?>
          </span>
        </div>
      </div>
      <div class="materia-grupos">
        <?php foreach ($data['grupos'] as $g): ?>
        <a href="<?= APP_URL ?>/grupos/<?= $g['grupo_id'] ?>/evaluaciones"
           class="grupo-chip" title="Ver <?= htmlspecialchars($g['grado'].' '.$g['grupo']) ?>">
          <?= htmlspecialchars($g['grado'] . ' ' . $g['grupo']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ── ESTUDIANTE ─────────────────────────────────────────── -->
<?php elseif ($rol === 'estudiante'): ?>

<div class="section-title">Mis notas — Período actual</div>

<?php if (empty($notas_periodo)): ?>
  <div class="empty-state">
    <i class="mdi mdi-clipboard-list-outline"></i>
    <p>Aún no hay notas registradas en este período.</p>
  </div>
<?php else: ?>
  <div class="notas-grid">
    <?php foreach ($notas_periodo as $n): ?>
    <div class="nota-card <?= $n['promedio'] >= 6 ? 'aprobada' : 'reprobada' ?>">
      <span class="nota-materia"><?= htmlspecialchars($n['materia']) ?></span>
      <span class="nota-valor"><?= number_format($n['promedio'], 1) ?></span>
      <span class="nota-estado"><?= $n['promedio'] >= 6 ? 'Aprobado' : 'Por mejorar' ?></span>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="mt-20">
  <a href="<?= APP_URL ?>/mis-notas" class="btn-secondary">Ver historial completo</a>
</div>

<!-- ── PADRE / ACUDIENTE ──────────────────────────────────── -->
<?php elseif ($rol === 'padre'): ?>

<div class="section-title">Mis acudidos</div>

<?php if (empty($hijos)): ?>
  <div class="empty-state">
    <i class="mdi mdi-account-child-outline"></i>
    <p>No tienes estudiantes vinculados a tu cuenta. Contacta a la institución.</p>
  </div>
<?php else: ?>
  <div class="hijos-grid">
    <?php foreach ($hijos as $h): ?>
    <a href="<?= APP_URL ?>/estudiantes/<?= $h['estudiante_id'] ?>/notas" class="hijo-card">
      <div class="hijo-avatar"><?= strtoupper(substr($h['nombre'], 0, 1)) ?></div>
      <div class="hijo-info">
        <strong><?= htmlspecialchars($h['nombre'] . ' ' . $h['apellido']) ?></strong>
        <span><?= htmlspecialchars($h['grado'] . ' ' . $h['grupo']) ?></span>
      </div>
      <i class="mdi mdi-chevron-right"></i>
    </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php endif; ?>

<!-- ── MENSAJES RECIENTES (todos los roles) ─────────────────── -->
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

