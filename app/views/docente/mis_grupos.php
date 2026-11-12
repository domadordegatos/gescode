<?php
$pageTitle   = 'Mis Grupos — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [['label'=>'Inicio','url'=>APP_URL.'/dashboard'],['label'=>'Mis Grupos','url'=>'']];

/* Agrupar asignaciones por grupo_id — una tarjeta por grupo físico */
$por_grupo = [];
foreach ($grupos as $g) {
    $gid = $g['grupo_id'];
    if (!isset($por_grupo[$gid])) {
        $por_grupo[$gid] = [
            'grupo_id'  => $gid,
            'grado'     => $g['grado'],
            'grupo'     => $g['grupo'],
            'nivel'     => $g['nivel'],
            'total_est' => $g['total_est'],
            'materias'  => [],
        ];
    }
    $por_grupo[$gid]['materias'][] = [
        'asignacion_id' => $g['asignacion_id'],
        'materia'       => $g['materia'],
    ];
}

ob_start();
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Mis Grupos</h1>
    <p class="page-subtitle">Año lectivo <?= date('Y') ?></p>
  </div>
</div>

<?php if (empty($por_grupo)): ?>
  <div class="empty-state">
    <i class="mdi mdi-google-classroom"></i>
    <p>No tienes grupos asignados en el año lectivo activo.</p>
  </div>
<?php else: ?>
  <div class="grupos-grid">
    <?php foreach ($por_grupo as $g):
      $nivel_label = $g['nivel'] <= 0 ? 'Preescolar' : ($g['nivel'] <= 5 ? 'Primaria' : 'Bachillerato');
      $nivel_color = $g['nivel'] <= 5 ? '#059669' : '#4F46E5';
    ?>
    <div class="grupo-card" style="display:flex;flex-direction:column">

      <!-- Cabecera — clic va a estudiantes -->
      <a href="<?= APP_URL ?>/grupos/<?= $g['grupo_id'] ?>/estudiantes"
         class="grupo-card-header" style="background:<?= $nivel_color ?>;text-decoration:none">
        <span class="grupo-badge"><?= e($g['grado'].' '.$g['grupo']) ?></span>
        <span class="grupo-nivel-badge"><?= $nivel_label ?></span>
      </a>

      <!-- Cuerpo -->
      <div class="grupo-card-body" style="flex:1">
        <?php if (count($g['materias']) === 1): ?>
          <p class="grupo-materia"><?= e($g['materias'][0]['materia']) ?></p>
        <?php else: ?>
          <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
            <?php foreach ($g['materias'] as $m): ?>
            <a href="<?= APP_URL ?>/grupos/<?= $g['grupo_id'] ?>/evaluaciones?asig=<?= $m['asignacion_id'] ?>"
               class="mg-materia-chip" title="Evaluaciones de <?= e($m['materia']) ?>">
              <i class="mdi mdi-book-open-variant"></i> <?= e($m['materia']) ?>
            </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <p class="grupo-meta">
          <i class="mdi mdi-account-group-outline"></i>
          <?= $g['total_est'] ?> estudiante<?= $g['total_est'] != 1 ? 's' : '' ?>
        </p>
      </div>

      <!-- Pie -->
      <a href="<?= APP_URL ?>/grupos/<?= $g['grupo_id'] ?>/estudiantes" class="grupo-card-footer">
        <span class="tab-pill"><i class="mdi mdi-account-school-outline"></i> Estudiantes</span>
        <span class="tab-pill"><i class="mdi mdi-clipboard-list-outline"></i> Evaluaciones</span>
        <span class="tab-pill"><i class="mdi mdi-calendar-check-outline"></i> Asistencia</span>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
