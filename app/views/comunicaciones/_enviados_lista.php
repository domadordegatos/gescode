<?php if (empty($mensajes)): ?>
  <div class="empty-state">
    <i class="mdi mdi-send-outline"></i>
    <p>No has enviado mensajes aún.</p>
  </div>
<?php else: ?>
  <?php foreach ($mensajes as $m):
    $num_mensajes = (int)($m['num_mensajes'] ?? 1);
  ?>
  <a href="<?= APP_URL ?>/comunicaciones/<?= $m['id'] ?>" class="msg-item">
    <div class="msg-avatar" style="background:#6B7280">
      <i class="mdi mdi-send" style="font-size:16px"></i>
    </div>
    <div class="msg-body">
      <div class="msg-meta">
        <span class="msg-from">
          <?= e($m['asunto']) ?>
          <?php if ($num_mensajes > 1): ?><span class="msg-thread-count">(<?= $num_mensajes ?>)</span><?php endif; ?>
        </span>
        <span class="msg-date"><?= formatDate($m['created_at']) ?></span>
      </div>
      <div style="font-size:.8rem;color:var(--text-muted)">
        <?= $m['total_dest'] ?> destinatario<?= $m['total_dest'] != 1 ? 's' : '' ?> ·
        <?= $m['leidos'] ?> leído<?= $m['leidos'] != 1 ? 's' : '' ?>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
<?php endif; ?>
