<?php
$pageTitle   = 'Detalle — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Papelera general', 'url' => APP_URL . '/papelera-general'],
    ['label' => 'Detalle', 'url' => ''],
];
$noLeidos = 0;
$s = $item['snapshot'];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($item['descripcion']) ?></h1>
        <p class="page-subtitle">Eliminado el <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
    </div>
    <?php if (!$item['restaurado']): ?>
    <form method="POST" action="<?= APP_URL ?>/papelera-general/<?= $item['id'] ?>/restaurar" onsubmit="return confirm('¿Restaurar este elemento?')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-primary"><i class="mdi mdi-restore"></i> Restaurar</button>
    </form>
    <?php else: ?>
    <span style="font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:99px;background:#16A34A;color:#fff">YA RESTAURADO</span>
    <?php endif; ?>
</div>

<div class="card" style="padding:20px 22px;max-width:620px">
<?php if ($item['tipo'] === 'usuario'): ?>
    <table style="width:100%;font-size:.85rem;border-collapse:collapse">
        <tr><td style="padding:5px 0;color:var(--text-muted);width:140px">Nombre</td><td><?= e($s['usuario']['nombre'] . ' ' . $s['usuario']['apellido']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted)">Usuario</td><td><?= e($s['usuario']['username']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted)">Correo</td><td><?= e($s['usuario']['email']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted)">Rol</td><td style="text-transform:capitalize"><?= e($s['usuario']['rol']) ?></td></tr>
    </table>
    <div style="font-size:.76rem;color:var(--text-muted);margin-top:14px">
        <i class="mdi mdi-information-outline"></i> Al restaurar, la contraseña vuelve a ser la misma que tenía (el usuario puede iniciar sesión normalmente). Algunos datos secundarios del perfil (cargo, último inicio de sesión) podrían no recuperarse.
    </div>

<?php elseif ($item['tipo'] === 'planeacion'): ?>
    <div style="font-weight:700;margin-bottom:6px"><?= e($s['planeacion']['tema']) ?></div>
    <?php if ($s['planeacion']['objetivo']): ?><div style="font-size:.85rem;margin-top:6px"><strong>Objetivo:</strong> <?= nl2br(e($s['planeacion']['objetivo'])) ?></div><?php endif; ?>
    <?php if ($s['planeacion']['metodologia']): ?><div style="font-size:.85rem;margin-top:6px"><strong>Metodología:</strong> <?= nl2br(e($s['planeacion']['metodologia'])) ?></div><?php endif; ?>

<?php elseif ($item['tipo'] === 'disponibilidad_cita'): ?>
    <div style="font-weight:700"><?= date('d/m/Y', strtotime($s['disponibilidad']['fecha'])) ?></div>
    <div style="font-size:.85rem;color:var(--text-muted)">
        <?= substr($s['disponibilidad']['hora_inicio'],0,5) ?> - <?= substr($s['disponibilidad']['hora_fin'],0,5) ?>
        <?= $s['disponibilidad']['lugar'] ? ' · ' . e($s['disponibilidad']['lugar']) : '' ?>
    </div>
    <div style="font-size:.85rem;margin-top:10px"><?= count($s['reservas']) ?> cita(s) reservada(s) en este bloque, también se restaurarán.</div>

<?php elseif ($item['tipo'] === 'archivo_comunidad'): ?>
    <table style="width:100%;font-size:.85rem;border-collapse:collapse">
        <tr><td style="padding:5px 0;color:var(--text-muted);width:140px">Título</td><td><?= e($s['archivo']['titulo']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted)">Archivo original</td><td><?= e($s['archivo']['archivo_orig']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--text-muted)">Tamaño</td><td><?= number_format($s['archivo']['tamano'] / 1024, 1) ?> KB</td></tr>
    </table>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
