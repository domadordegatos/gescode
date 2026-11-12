<?php
$pageTitle   = 'Ajustar matrículas — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => APP_URL . '/grados'],
    ['label' => $grupo['grado'] . ' ' . $grupo['nombre'], 'url' => ''],
];
$noLeidos = 0;

$estadoColor = [
    'activo' => '#16A34A', 'promovido' => '#2563EB', 'retirado' => '#DC2626',
    'reprobado' => '#D97706', 'egresado' => '#7C3AED', 'pendiente' => '#6B7280',
    'suspendido' => '#DC2626', 'inscrito' => '#0891B2',
];

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($grupo['grado'] . ' ' . $grupo['nombre']) ?></h1>
        <p class="page-subtitle"><?= e($grupo['ano']) ?> · <?= count($estudiantes) ?> matriculados</p>
    </div>
</div>

<div class="card" style="padding:0">
<?php foreach ($estudiantes as $e): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:.88rem"><?= e($e['nombre'] . ' ' . $e['apellido']) ?></div>
            <span style="font-size:.68rem;font-weight:700;padding:1px 7px;border-radius:99px;background:<?= $estadoColor[$e['estado']] ?? '#6B7280' ?>;color:#fff;text-transform:uppercase">
                <?= e($e['estado']) ?>
            </span>
        </div>
        <?php if ($e['estado'] === 'activo'): ?>
        <div style="display:flex;gap:6px">
            <?php if ($gruposGradoAnterior): ?>
            <form method="POST" action="<?= APP_URL ?>/grados/matricula/<?= $e['matricula_id'] ?>/mover" style="display:flex;gap:4px"
                  onsubmit="return confirm('¿Marcar a este estudiante como repitente y moverlo al grado anterior?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <select name="grupo_destino_id" class="form-input" style="font-size:.76rem;padding:4px 8px" required>
                    <option value="">Repetir en...</option>
                    <?php foreach ($gruposGradoAnterior as $gg): ?>
                    <option value="<?= $gg['id'] ?>"><?= e($gg['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-sm btn-outline" title="Marcar como repitente">
                    <i class="mdi mdi-rotate-left"></i>
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= APP_URL ?>/grados/matricula/<?= $e['matricula_id'] ?>/retirar" onsubmit="return confirm('¿Retirar a este estudiante del grupo?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline" title="Retirar">
                    <i class="mdi mdi-account-remove-outline"></i>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (!$estudiantes): ?>
    <div style="padding:20px;text-align:center;color:var(--text-muted)">Este grupo no tiene estudiantes.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
