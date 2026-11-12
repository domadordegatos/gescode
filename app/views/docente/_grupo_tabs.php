<?php
// Partial: tabs de navegación dentro de un grupo
// Requiere $grupo y $tab_activo
$gid = $grupo['grupo_id'];
$tabs = [
  'estudiantes'  => ['label'=>'Estudiantes',   'icon'=>'mdi-account-group-outline'],
  'evaluaciones' => ['label'=>'Evaluaciones',  'icon'=>'mdi-clipboard-list-outline'],
  'tareas'       => ['label'=>'Tareas',        'icon'=>'mdi-book-edit-outline'],
  'asistencia'   => ['label'=>'Asistencia',    'icon'=>'mdi-calendar-check-outline'],
  'observaciones'=> ['label'=>'Observaciones', 'icon'=>'mdi-note-text-outline'],
];
?>
<div class="grupo-info-bar">
  <div class="grupo-info-title">
    <i class="mdi mdi-google-classroom"></i>
    <strong><?= e($grupo['grado'].' '.$grupo['grupo']) ?></strong>
    <span class="sep">·</span>
    <span><?= e($grupo['materia']) ?></span>
    <span class="sep">·</span>
    <span class="text-muted"><?= e($grupo['ano']) ?></span>
    <?= $grupo_extra_title ?? '' ?>
  </div>
  <?= $grupo_extra_action ?? '' ?>
</div>

<div class="grupo-tabs <?= empty($grupo_extra_action) ? 'grupo-tabs--standalone' : '' ?>">
  <?php foreach ($tabs as $key => $t): ?>
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/<?= $key ?>"
     class="grupo-tab <?= $tab_activo === $key ? 'active' : '' ?>">
    <i class="mdi <?= $t['icon'] ?>"></i>
    <?= $t['label'] ?>
  </a>
  <?php endforeach; ?>
  <?php if (!empty($grupo_periodo_select)): ?>
  <div style="margin-left:auto;display:flex;align-items:center">
    <?= $grupo_periodo_select ?>
  </div>
  <?php endif; ?>
</div>

