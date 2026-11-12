<?php
$pageTitle  = e($tarea['nombre']) . ' — ' . APP_NAME;
$noLeidos   = $mensajes_no_leidos ?? 0;
$breadcrumbs = [
    ['label' => 'Inicio',       'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis acudidos', 'url' => APP_URL . '/mis-hijos'],
    ['label' => $hijo['nombre'] . ' ' . $hijo['apellido'], 'url' => APP_URL . '/mis-hijos/' . $hijo['estudiante_id'] . '?tab=tareas'],
    ['label' => $tarea['nombre'], 'url' => ''],
];

$entregaCfg = [
    'ninguna' => ['icon' => 'mdi-minus-circle-outline', 'color' => '#6B7280', 'label' => 'Sin entrega'],
    'clase'   => ['icon' => 'mdi-school-outline',        'color' => '#2563EB', 'label' => 'En clase'],
    'linea'   => ['icon' => 'mdi-upload-outline',        'color' => '#9333EA', 'label' => 'En línea'],
];
$ecfg = $entregaCfg[$tarea['tipo_entrega'] ?? 'ninguna'] ?? $entregaCfg['ninguna'];
$vencida = $tarea['fecha_limite'] && strtotime($tarea['fecha_limite']) < time();
$proxima = $tarea['fecha_limite'] && !$vencida && strtotime($tarea['fecha_limite']) < (time() + 86400 * 3);

ob_start();
?>

<!-- Encabezado de la tarea -->
<div class="hijo-header-card" style="margin-bottom:20px">
  <div class="hijo-header-left">
    <div class="hijo-header-avatar" style="background:var(--primary-light);color:var(--primary)">
      <i class="mdi mdi-pencil-box-outline" style="font-size:1.4rem"></i>
    </div>
    <div class="hijo-header-info">
      <h1 class="hijo-header-nombre" style="font-size:1.15rem"><?= e($tarea['nombre']) ?></h1>
      <div class="hijo-header-meta">
        <span style="color:var(--primary);font-weight:600">
          <i class="mdi mdi-book-outline"></i> <?= e($tarea['materia']) ?>
        </span>
        <span style="color:var(--text-muted)">
          <i class="mdi mdi-account-tie-outline"></i> <?= e($tarea['doc_nombre'] . ' ' . $tarea['doc_apellido']) ?>
        </span>
      </div>
    </div>
  </div>
  <div class="hijo-header-actions">
    <a href="<?= APP_URL ?>/mis-hijos/<?= $hijo['estudiante_id'] ?>?tab=tareas" class="btn-secondary btn-sm">
      <i class="mdi mdi-arrow-left"></i> Volver a Tareas
    </a>
  </div>
</div>

<!-- Tarjeta de detalle -->
<div class="padre-card" style="max-width:860px">

  <!-- Meta: fechas y entrega -->
  <div class="ver-tarea-meta">
    <div class="ver-tarea-meta-item">
      <i class="mdi mdi-calendar-clock-outline" style="color:var(--text-muted)"></i>
      <span>Publicada <strong><?= date('d/m/Y', strtotime($tarea['fecha_publicacion'])) ?></strong></span>
    </div>
    <?php if ($tarea['fecha_limite']): ?>
    <div class="ver-tarea-meta-item <?= $vencida ? 'text-danger' : ($proxima ? 'text-warning' : '') ?>">
      <i class="mdi mdi-calendar-alert-outline"></i>
      <span>
        Entrega <strong><?= date('d/m/Y H:i', strtotime($tarea['fecha_limite'])) ?></strong>
        <?php if ($vencida): ?>
          <span class="tarea-badge-vencida" style="margin-left:6px">Vencida</span>
        <?php elseif ($proxima): ?>
          <span class="tarea-badge-proxima" style="margin-left:6px">Próxima</span>
        <?php endif; ?>
      </span>
    </div>
    <?php else: ?>
    <div class="ver-tarea-meta-item" style="color:var(--text-muted)">
      <i class="mdi mdi-infinity"></i>
      <span>Sin fecha límite</span>
    </div>
    <?php endif; ?>
    <div class="ver-tarea-meta-item" style="color:<?= $ecfg['color'] ?>">
      <i class="mdi <?= $ecfg['icon'] ?>"></i>
      <span><?= $ecfg['label'] ?></span>
    </div>
  </div>

  <!-- Descripción -->
  <?php if (!empty($tarea['descripcion'])): ?>
  <div class="ver-tarea-desc">
    <?= nl2br(e($tarea['descripcion'])) ?>
  </div>
  <?php else: ?>
  <div class="ver-tarea-desc" style="color:var(--text-muted);font-style:italic">
    Esta tarea no tiene descripción adicional.
  </div>
  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
