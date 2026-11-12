<?php
$pageTitle   = 'Promoción de año lectivo — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => 'Promoción', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Promover a nuevo año lectivo</h1>
        <p class="page-subtitle">Año actual: <?= e($anoActivo['nombre']) ?></p>
    </div>
</div>

<div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;font-size:.82rem;color:#92400E;margin-bottom:20px;max-width:760px">
    <i class="mdi mdi-alert-outline"></i>
    <strong>Esta acción es masiva y no se puede deshacer automáticamente.</strong>
    Todos los estudiantes activos se promoverán un grado (ej: Quinto A → Sexto A); los de <strong>grado 11° (máximo nivel)</strong> se marcarán como <strong>egresados</strong>.
    Después de promover, podrás ajustar casos individuales (retirar o repetir año) desde cada grupo.
</div>

<div class="card" style="padding:20px 22px;max-width:620px;margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:10px">Vista previa de la promoción</div>
    <table style="width:100%;font-size:.85rem;border-collapse:collapse">
        <?php foreach ($preview as $p): ?>
        <tr>
            <td style="padding:5px 0"><?= e($p['grado']) ?></td>
            <td style="padding:5px 0;text-align:right">
                <?= $p['total'] ?> estudiante<?= $p['total'] != 1 ? 's' : '' ?>
                <?= (int)$p['nivel'] >= $maxNivel ? ' → se graduarán' : ' → pasan al siguiente grado' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<form method="POST" action="<?= APP_URL ?>/grados/promocion/ejecutar" class="card" style="padding:20px 22px;max-width:480px"
      onsubmit="return confirm('¿Confirmas iniciar la promoción? Esta acción afecta a todos los estudiantes activos.')">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <div class="form-group">
        <label>Nombre del nuevo año lectivo <span class="required">*</span></label>
        <input type="text" name="nombre_nuevo_ano" class="form-input" required placeholder="Ej: 2027">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-arrow-up-bold-circle-outline"></i> Iniciar promoción
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
