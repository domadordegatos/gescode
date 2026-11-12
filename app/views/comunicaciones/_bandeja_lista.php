<?php if (empty($mensajes)): ?>
  <div class="empty-state">
    <i class="mdi mdi-email-open-outline"></i>
    <p>No tienes mensajes en tu bandeja de entrada.</p>
  </div>
<?php else: ?>
  <?php foreach ($mensajes as $m):
    $no_leidos_hilo = (int)($m['no_leidos_hilo'] ?? (!$m['leido'] ? 1 : 0));
    $num_mensajes   = (int)($m['num_mensajes'] ?? 1);
  ?>
  <div class="msg-item-row" style="display:flex;align-items:center;gap:6px">
    <input type="checkbox" class="msg-ck" value="<?= $m['id'] ?>" onclick="event.stopPropagation(); toggleMsgSeleccion(this);" style="margin-left:10px;flex-shrink:0">
  <a href="<?= APP_URL ?>/comunicaciones/<?= $m['id'] ?>" class="msg-item <?= $no_leidos_hilo > 0 ? 'unread' : 'leido' ?>" style="flex:1">
    <div class="msg-avatar">
      <img src="<?= APP_URL ?>/perfil/avatar/<?= (int)$m['remitente_id'] ?>" alt="<?= htmlspecialchars(substr($m['nombre'],0,1)) ?>">
    </div>
    <div class="msg-body">
      <div class="msg-meta">
        <span class="msg-from"><?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></span>
        <span class="msg-date"><?= formatDate($m['created_at']) ?></span>
      </div>
      <div class="msg-subject">
        <?= htmlspecialchars($m['asunto']) ?>
        <?php if ($num_mensajes > 1): ?>
          <span class="msg-thread-count">(<?= $num_mensajes ?>)</span>
        <?php endif; ?>
      </div>
      <div class="msg-preview-row">
        <?php
          $rolLabel = ucfirst($m['rol']);
          if ($m['rol'] === 'docente')     $rolLabel = 'Docente: ' . $m['nombre'];
          elseif ($m['rol'] === 'padre')   $rolLabel = 'Familiar';
          elseif ($m['rol'] === 'estudiante') $rolLabel = 'Estudiante';
          $preview = strip_tags($m['cuerpo'] ?? '');
          $preview = preg_replace('/\s+/', ' ', $preview);
          $preview = mb_strimwidth(trim($preview), 0, 120, '…');
        ?>
        <span class="msg-rol"><?= htmlspecialchars($rolLabel) ?></span>
        <?php if ($preview): ?>
          <span class="msg-preview-sep">·</span>
          <span class="msg-preview"><?= htmlspecialchars($preview) ?></span>
        <?php endif; ?>
        <?php if ($m['num_adjuntos'] > 0): ?>
          <span class="msg-attach-badge"><i class="mdi mdi-paperclip"></i></span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($no_leidos_hilo > 0): ?>
      <span class="unread-dot"></span>
    <?php endif; ?>
  </a>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
