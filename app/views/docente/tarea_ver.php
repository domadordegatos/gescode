<?php
$gid        = $grupo['grupo_id'];
$asig_id    = $asig['asignacion_id'];
$pageTitle  = e($tarea['nombre']).' — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs= [
  ['label'=>'Inicio',     'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos', 'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Tareas','url'=>APP_URL.'/grupos/'.$gid.'/tareas?asig='.$asig_id],
  ['label'=>e($tarea['nombre']),'url'=>''],
];

$tipo_labels = [
  'ninguna' => null,
  'clase'   => 'Entregar en clase',
  'linea'   => 'Entregar en línea',
];

$mime_icons = [
  'application/pdf'                                                         => 'mdi-file-pdf-box',
  'image/jpeg' => 'mdi-file-image', 'image/png' => 'mdi-file-image',
  'image/gif'  => 'mdi-file-image', 'image/webp'=> 'mdi-file-image',
  'application/zip'                                                         => 'mdi-folder-zip-outline',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'mdi-file-word',
  'application/msword'                                                      => 'mdi-file-word',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'      => 'mdi-file-excel',
  'application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'mdi-file-powerpoint',
];

ob_start();
?>

<div class="alert-info-box" style="margin-bottom:16px">
  <i class="mdi mdi-information-outline"></i>
  <div>Esta es una <strong>vista previa</strong>. Los estudiantes y familias ven esta misma información
  en su panel de comunicaciones tras recibir la notificación.</div>
</div>

<!-- Tarjeta principal de la tarea -->
<div class="tarea-view-card">

  <!-- Cabecera coloreada -->
  <div class="tarea-view-head">
    <div class="tarea-view-icon-wrap">
      <i class="mdi mdi-book-edit-outline"></i>
    </div>
    <div style="flex:1;min-width:0">
      <h1 class="tarea-view-title"><?= e($tarea['nombre']) ?></h1>
      <div class="tarea-view-meta">
        <span><i class="mdi mdi-google-classroom"></i> <?= e($asig['materia']) ?> · <?= e($asig['grado'].' '.$asig['grupo']) ?></span>
        <span><i class="mdi mdi-calendar-outline"></i> Publicado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($tarea['fecha_publicacion'])) ?></span>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0;align-self:center">
      <a href="<?= APP_URL ?>/tarea/<?= $tarea['id'] ?>/editar"
         style="display:inline-flex;align-items:center;gap:5px;font-size:.82rem;font-weight:600;
                color:rgba(255,255,255,.85);background:rgba(255,255,255,.15);
                border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:5px 12px;
                text-decoration:none;transition:background .15s"
         onmouseover="this.style.background='rgba(255,255,255,.25)'"
         onmouseout="this.style.background='rgba(255,255,255,.15)'">
        <i class="mdi mdi-pencil-outline"></i> Editar
      </a>
      <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/tareas?asig=<?= $asig_id ?>"
         style="display:inline-flex;align-items:center;gap:5px;font-size:.82rem;font-weight:600;
                color:rgba(255,255,255,.85);background:rgba(255,255,255,.15);
                border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:5px 12px;
                text-decoration:none;transition:background .15s"
         onmouseover="this.style.background='rgba(255,255,255,.25)'"
         onmouseout="this.style.background='rgba(255,255,255,.15)'">
        <i class="mdi mdi-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <!-- Chips de estado -->
  <div class="tarea-view-chips">
    <?php if ($tarea['tipo_entrega'] !== 'ninguna'): ?>
      <span class="tarea-view-chip chip-entrega">
        <i class="mdi mdi-upload-outline"></i>
        <?= $tipo_labels[$tarea['tipo_entrega']] ?? $tarea['tipo_entrega'] ?>
      </span>
      <?php if ($tarea['fecha_limite']): ?>
        <?php
          $ts     = strtotime($tarea['fecha_limite']);
          $vencio = $ts < time();
          $diff   = abs($ts - time());
          $days   = floor($diff / 86400);
          $hrs    = floor(($diff % 86400) / 3600);
          if ($vencio) {
            $timelabel = 'Venció el '.date('d/m/Y H:i', $ts);
            $chipClass = 'chip-vencida';
          } elseif ($days > 0) {
            $timelabel = 'Vence en '.$days.' día'.($days>1?'s':'');
            $chipClass = $days <= 2 ? 'chip-urgente' : 'chip-limite';
          } else {
            $timelabel = 'Vence en '.$hrs.' hora'.($hrs>1?'s':'');
            $chipClass = 'chip-urgente';
          }
        ?>
        <span class="tarea-view-chip <?= $chipClass ?>">
          <i class="mdi mdi-clock-outline"></i> <?= $timelabel ?>
        </span>
      <?php endif; ?>
    <?php else: ?>
      <span class="tarea-view-chip chip-sin-entrega">
        <i class="mdi mdi-eye-check-outline"></i> Solo lectura
      </span>
    <?php endif; ?>

    <?php if ($tarea['foro_habilitado']): ?>
      <span class="tarea-view-chip chip-foro">
        <i class="mdi mdi-forum-outline"></i> Foro habilitado
      </span>
    <?php endif; ?>
  </div>

  <!-- Descripción -->
  <?php if (!empty(trim(strip_tags($tarea['descripcion'] ?? '')))): ?>
  <div class="tarea-view-body">
    <h3 class="tarea-view-section-title">Instrucciones</h3>
    <div class="tarea-view-desc ql-editor" style="padding:0">
      <?= $tarea['descripcion'] ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Adjuntos -->
  <?php if (!empty($adjuntos)): ?>
  <div class="tarea-view-body" style="border-top:1px solid var(--border)">
    <h3 class="tarea-view-section-title">
      <i class="mdi mdi-paperclip" style="margin-right:4px"></i>
      Archivos adjuntos (<?= count($adjuntos) ?>)
    </h3>
    <div class="tarea-view-adjuntos">
      <?php foreach ($adjuntos as $a):
        $icon  = $mime_icons[$a['mime']] ?? 'mdi-file-outline';
        $size  = $a['tamano'] < 1048576
          ? round($a['tamano']/1024).' KB'
          : round($a['tamano']/1048576,1).' MB';
      ?>
      <a href="<?= APP_URL ?>/tarea/adjunto/<?= $a['id'] ?>"
         class="tarea-view-adjunto" target="_blank">
        <i class="mdi <?= $icon ?> tarea-adj-icon"></i>
        <div class="tarea-adj-info">
          <span class="tarea-adj-name"><?= e($a['nombre_orig']) ?></span>
          <span class="tarea-adj-size"><?= $size ?></span>
        </div>
        <i class="mdi mdi-download-outline tarea-adj-dl"></i>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>


<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
