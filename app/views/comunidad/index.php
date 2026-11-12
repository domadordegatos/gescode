<?php
$pageTitle   = 'Comunidad — ' . APP_NAME;
$breadcrumbs = [['label' => 'Comunidad', 'url' => '']];
ob_start();
?>

<div class="com-page">

  <div class="com-page-header">
    <div>
      <h1 class="page-title">Comunidad</h1>
      <p class="com-subtitle">Documentos e información institucional compartida por el colegio</p>
    </div>
    <?php if (in_array(Auth::rol(), ['soporte','admin'])): ?>
      <a href="<?= APP_URL ?>/comunidad/admin" class="btn-primary">
        <i class="mdi mdi-cog-outline"></i> Gestionar
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($categorias)): ?>
    <div class="empty-state">
      <i class="mdi mdi-folder-open-outline"></i>
      <p>No hay contenido publicado todavía.</p>
    </div>
  <?php else: ?>

  <div class="com-grid">
    <?php foreach ($categorias as $cat): ?>
    <div class="com-cat-card">

      <div class="com-cat-head">
        <div class="com-cat-icon"><i class="mdi mdi-folder-outline"></i></div>
        <div class="com-cat-meta">
          <h2 class="com-cat-name"><?= e($cat['nombre']) ?></h2>
          <?php if ($cat['descripcion']): ?>
            <p class="com-cat-desc"><?= e($cat['descripcion']) ?></p>
          <?php endif; ?>
        </div>
        <span class="com-cat-count"><?= count($cat['archivos']) ?> archivo<?= count($cat['archivos']) !== 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($cat['archivos'])): ?>
        <p class="com-empty-cat">Esta categoría aún no tiene archivos.</p>
      <?php else: ?>
      <ul class="com-file-list">
        <?php foreach ($cat['archivos'] as $arch): ?>
        <li class="com-file-item">
          <i class="mdi <?= $arch['icon'] ?> com-file-icon" style="color:<?= $arch['color'] ?>"></i>
          <div class="com-file-info">
            <a href="<?= APP_URL ?>/comunidad/archivo/<?= $arch['id'] ?>"
               class="com-file-name" target="_blank"><?= e($arch['titulo']) ?></a>
            <?php if ($arch['descripcion']): ?>
              <span class="com-file-desc"><?= e($arch['descripcion']) ?></span>
            <?php endif; ?>
            <span class="com-file-meta">
              <?= e($arch['archivo_orig']) ?> &middot; <?= $arch['tamano_fmt'] ?>
              &middot; <?= date('d/m/Y', strtotime($arch['created_at'])) ?>
            </span>
          </div>
          <a href="<?= APP_URL ?>/comunidad/archivo/<?= $arch['id'] ?>"
             class="com-file-dl" title="Abrir / descargar" target="_blank">
            <i class="mdi mdi-download-outline"></i>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
