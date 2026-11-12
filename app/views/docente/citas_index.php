<?php
$pageTitle   = 'Citas con acudientes — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Citas', 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-calendar-clock-outline"></i> Citas con acudientes</h1>
        <p class="page-subtitle">Publica tus horarios disponibles para que los padres reserven directamente.</p>
    </div>
</div>

<div class="card" style="padding:20px 22px;max-width:620px;margin-bottom:22px">
    <div style="font-weight:600;margin-bottom:12px">Publicar nueva disponibilidad</div>
    <form method="POST" action="<?= APP_URL ?>/citas/disponibilidad/crear">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div class="form-row-2">
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="fecha" class="form-input" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Duración por cita (minutos)</label>
                <input type="number" name="duracion_minutos" class="form-input" value="20" min="5" max="120" required>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label>Hora inicio</label>
                <input type="time" name="hora_inicio" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Hora fin</label>
                <input type="time" name="hora_fin" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label>Lugar (opcional)</label>
            <input type="text" name="lugar" class="form-input" placeholder="Ej: Sala de profesores, videollamada...">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="mdi mdi-plus"></i> Publicar</button>
        </div>
    </form>
</div>

<div style="font-weight:700;margin-bottom:10px">Mis horarios publicados</div>
<?php if (!$bloques): ?>
    <div class="card" style="padding:24px;text-align:center;color:var(--text-muted)">No tienes horarios publicados.</div>
<?php endif; ?>

<?php foreach ($bloques as $b): ?>
<div class="card" style="padding:16px 20px;margin-bottom:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <div style="font-weight:700"><?= date('l d/m/Y', strtotime($b['fecha'])) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted)">
                <?= substr($b['hora_inicio'],0,5) ?> - <?= substr($b['hora_fin'],0,5) ?>
                <?= $b['lugar'] ? ' · ' . e($b['lugar']) : '' ?>
            </div>
        </div>
        <form method="POST" action="<?= APP_URL ?>/citas/disponibilidad/<?= $b['id'] ?>/eliminar" onsubmit="return confirm('¿Eliminar este bloque? Las citas ya reservadas se cancelarán.')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-trash-can-outline"></i></button>
        </form>
    </div>

    <div style="margin-top:12px">
    <?php foreach ($b['slots'] as $s): ?>
        <?php if ($s['reservado']): $r = $s['reserva']; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-radius:8px;background:#FEE2E2;margin-bottom:6px">
            <div style="font-size:.82rem">
                <strong><?= substr($s['hora_inicio'],0,5) ?></strong> —
                <?= e($r['padre_nombre'] . ' ' . $r['padre_apellido']) ?>
                <?= $r['est_nombre'] ? ' (acudiente de ' . e($r['est_nombre'] . ' ' . $r['est_apellido']) . ')' : '' ?>
                <?php if ($r['motivo']): ?><div style="color:var(--text-muted);font-size:.76rem;margin-top:2px"><?= e($r['motivo']) ?></div><?php endif; ?>
            </div>
            <form method="POST" action="<?= APP_URL ?>/citas/reserva/<?= $r['id'] ?>/cancelar" onsubmit="return confirm('¿Cancelar esta cita?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline" style="flex-shrink:0"><i class="mdi mdi-close"></i></button>
            </form>
        </div>
        <?php else: ?>
        <div style="padding:6px 12px;border-radius:8px;background:#DCFCE7;font-size:.78rem;font-weight:600;color:#166534;display:inline-block;margin:0 6px 6px 0">
            <?= substr($s['hora_inicio'],0,5) ?> · Libre
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
