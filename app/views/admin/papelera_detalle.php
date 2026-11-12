<?php
$pageTitle   = 'Detalle de papelera — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Papelera de notas', 'url' => APP_URL . '/papelera'],
    ['label' => 'Detalle', 'url' => ''],
];
$noLeidos = 0;
$snap = $item['snapshot'];

if ($item['tipo'] === 'indicador') {
    $evaluaciones = $snap['evaluaciones'];
} else {
    $evaluaciones = [$snap['evaluacion'] + ['calificaciones' => $snap['calificaciones']]];
}

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($item['descripcion']) ?></h1>
        <p class="page-subtitle">Eliminado el <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
    </div>
    <?php if (!$item['restaurado']): ?>
    <form method="POST" action="<?= APP_URL ?>/papelera/<?= $item['id'] ?>/restaurar" onsubmit="return confirm('¿Restaurar este elemento? Las notas volverán a estar activas tal como estaban antes de eliminarse.')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-primary"><i class="mdi mdi-restore"></i> Restaurar</button>
    </form>
    <?php else: ?>
    <span style="font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:99px;background:#16A34A;color:#fff">YA RESTAURADO</span>
    <?php endif; ?>
</div>

<?php foreach ($evaluaciones as $ev): ?>
<div class="card" style="padding:16px 20px;margin-bottom:12px">
    <div style="font-weight:700;font-size:.9rem"><?= e($ev['nombre']) ?></div>
    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px">Fecha: <?= e($ev['fecha']) ?> · <?= count($ev['calificaciones']) ?> nota(s) registradas</div>

    <?php if ($ev['calificaciones']): ?>
    <table style="width:100%;font-size:.82rem;border-collapse:collapse">
        <thead>
            <tr style="border-bottom:1px solid var(--border)">
                <th style="text-align:left;padding:4px 0">Matrícula</th>
                <th style="text-align:right;padding:4px 0">Nota que tenía</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ev['calificaciones'] as $c): ?>
            <tr>
                <td style="padding:3px 0"><?= e($c['matricula_id']) ?></td>
                <td style="padding:3px 0;text-align:right;font-weight:600"><?= number_format((float)$c['valor'], 1) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="color:var(--text-muted);font-size:.8rem">Esta evaluación no tenía notas registradas.</div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
