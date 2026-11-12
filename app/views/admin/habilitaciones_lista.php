<?php
$pageTitle   = 'Habilitación anual — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Habilitación anual', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Habilitación anual</h1>
        <p class="page-subtitle">Registra la nota de habilitación de fin de año para estudiantes que están perdiendo una materia — <?= e($ano['nombre']) ?>.</p>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <?php if (!$asignaciones): ?>
        <div class="empty-state" style="padding:40px">
            <i class="mdi mdi-book-open-variant-outline"></i>
            <p>No hay asignaciones disponibles.</p>
        </div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>Grado</th><th>Grupo</th><th>Materia</th><th>Habilitados registrados</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= e($a['grado']) ?></td>
                <td><?= e($a['grupo']) ?></td>
                <td><?= e($a['materia']) ?></td>
                <td><?= (int)$a['total_habilitados'] ?></td>
                <td style="text-align:right">
                    <a href="<?= APP_URL ?>/habilitaciones/<?= $a['asignacion_id'] ?>" class="btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-right"></i> Ver reprobados
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
