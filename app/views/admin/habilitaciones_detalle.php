<?php
$pageTitle   = 'Habilitación — ' . $asig['materia'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Habilitación anual', 'url' => APP_URL . '/habilitaciones'],
    ['label' => $asig['materia'], 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($asig['materia']) ?> — <?= e($asig['grado'] . ' ' . $asig['grupo']) ?></h1>
        <p class="page-subtitle">Estudiantes con nota definitiva del año por debajo de <?= $umbral ?> (umbral de aprobación). Solo aparecen aquí quienes están reprobando la materia.</p>
    </div>
    <a href="<?= APP_URL ?>/habilitaciones" class="btn-secondary"><i class="mdi mdi-arrow-left"></i> Volver</a>
</div>

<?php if (!$estudiantes): ?>
<div class="card empty-state" style="padding:40px">
    <i class="mdi mdi-check-circle-outline" style="color:#16A34A"></i>
    <p>Ningún estudiante de este grupo está reprobando esta materia. No se requiere habilitación.</p>
</div>
<?php else: ?>
<form method="POST" action="<?= APP_URL ?>/habilitaciones/<?= $asig['id'] ?>/guardar">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <div class="card" style="padding:0;overflow:hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Definitiva del año</th>
                    <th>Nota de habilitación</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($estudiantes as $e): ?>
                <tr>
                    <td><?= e($e['apellido'] . ', ' . $e['nombre']) ?></td>
                    <td style="color:#B91C1C;font-weight:700"><?= number_format((float)$e['definitiva_ano'], 1) ?></td>
                    <td>
                        <input type="number" step="0.1" min="0" max="10" class="form-input" style="max-width:100px"
                               name="nota_habilitacion[<?= $e['matricula_id'] ?>]"
                               value="<?= e($e['nota_habilitacion'] ?? '') ?>">
                    </td>
                    <td>
                        <input type="text" class="form-input"
                               name="observacion[<?= $e['matricula_id'] ?>]"
                               value="<?= e($e['observacion'] ?? '') ?>" placeholder="Opcional">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form-actions" style="margin-top:16px">
        <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Guardar notas de habilitación</button>
    </div>
</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
