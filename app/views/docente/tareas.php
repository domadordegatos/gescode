<?php
$gid        = $grupo['grupo_id'];
$pageTitle  = 'Tareas — '.$grupo['grado'].' '.$grupo['grupo'].' — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo = 'tareas';
$asig_id    = $asig['asignacion_id'];
$breadcrumbs= [
  ['label'=>'Inicio',     'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos', 'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Tareas','url'=>''],
];

$tipo_labels = [
  'ninguna' => 'No se requiere entrega',
  'clase'   => 'En clase',
  'linea'   => 'En línea',
];

ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<div class="tareas-toolbar">
  <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas/nueva?asig=<?= $asig_id ?>" class="btn-primary">
    <i class="mdi mdi-plus"></i> Agregar tarea
  </a>
  <span class="tareas-periodo">
    <i class="mdi mdi-calendar-range"></i> <?= e($periodo['nombre']) ?>
  </span>
</div>

<?php if (empty($tareas)): ?>
  <div class="empty-state">
    <i class="mdi mdi-book-edit-outline"></i>
    <p>Aún no has publicado tareas en este periodo.</p>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas/nueva?asig=<?= $asig_id ?>" class="btn-primary" style="margin-top:12px">
      <i class="mdi mdi-plus"></i> Crear la primera tarea
    </a>
  </div>
<?php else: ?>

<div class="tareas-table-wrap">
  <table class="tareas-table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Creada</th>
        <th>Publicación</th>
        <th>Para entregar en</th>
        <th>Evaluación</th>
        <th class="ta-center">Notificó</th>
        <th class="ta-center">Archivos</th>
        <th class="ta-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tareas as $t): ?>
      <tr>
        <td>
          <i class="mdi mdi-book-edit-outline tarea-row-icon"></i>
          <a href="<?= APP_URL ?>/tarea/<?= $t['id'] ?>" class="tarea-nombre-link">
            <?= e($t['nombre']) ?>
          </a>
        </td>
        <td class="ta-muted"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
        <td class="ta-muted"><?= date('d/m/Y H:i', strtotime($t['fecha_publicacion'])) ?></td>
        <td>
          <span class="tarea-badge tarea-badge--<?= $t['tipo_entrega'] ?>">
            <?= e($tipo_labels[$t['tipo_entrega']] ?? $t['tipo_entrega']) ?>
          </span>
          <?php if ($t['tipo_entrega'] !== 'ninguna' && $t['fecha_limite']): ?>
            <div class="tarea-limite">hasta <?= date('d/m/Y H:i', strtotime($t['fecha_limite'])) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($t['eval_nombre']): ?>
            <span class="tarea-eval-tag"><i class="mdi mdi-star-outline"></i> <?= e($t['eval_nombre']) ?></span>
          <?php else: ?>
            <span class="ta-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="ta-center">
          <span class="tarea-notif"><i class="mdi mdi-bell-outline"></i> <?= (int)$t['num_notif'] ?></span>
        </td>
        <td class="ta-center">
          <?php if ((int)$t['num_adjuntos'] > 0): ?>
            <span class="tarea-files"><i class="mdi mdi-paperclip"></i> <?= (int)$t['num_adjuntos'] ?></span>
          <?php else: ?>
            <span class="ta-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="ta-center" style="white-space:nowrap">
          <a href="<?= APP_URL ?>/tarea/<?= $t['id'] ?>/editar"
             class="btn-icon" title="Editar tarea">
            <i class="mdi mdi-pencil-outline"></i>
          </a>
          <form method="POST" action="<?= APP_URL ?>/tarea/<?= $t['id'] ?>/eliminar"
                style="display:inline" onsubmit="return confirm('¿Eliminar esta tarea? Esta acción no se puede deshacer.')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-icon danger" title="Eliminar tarea">
              <i class="mdi mdi-trash-can-outline"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
