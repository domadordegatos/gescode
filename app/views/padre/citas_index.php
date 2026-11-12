<?php
$pageTitle   = 'Citas con docentes — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Citas', 'url' => ''],
];
$noLeidos = $mensajes_no_leidos;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-calendar-clock-outline"></i> Citas con docentes</h1>
        <p class="page-subtitle">Reserva un horario disponible directamente con el docente.</p>
    </div>
</div>

<div style="font-weight:700;margin-bottom:10px">Mis próximas citas</div>
<?php if (!$misCitas): ?>
    <div class="card" style="padding:20px;text-align:center;color:var(--text-muted);margin-bottom:22px">No tienes citas agendadas.</div>
<?php endif; ?>
<?php foreach ($misCitas as $c): ?>
<div class="card" style="padding:14px 18px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center">
    <div>
        <div style="font-weight:700"><?= date('l d/m/Y', strtotime($c['fecha'])) ?> — <?= substr($c['hora_inicio'],0,5) ?></div>
        <div style="font-size:.8rem;color:var(--text-muted)">
            Con <?= e($c['doc_nombre'] . ' ' . $c['doc_apellido']) ?>
            <?= $c['est_nombre'] ? ' · ' . e($c['est_nombre']) : '' ?>
            <?= $c['lugar'] ? ' · ' . e($c['lugar']) : '' ?>
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/citas/reserva/<?= $c['id'] ?>/cancelar" onsubmit="return confirm('¿Cancelar esta cita?')">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-sm btn-danger-outline">Cancelar</button>
    </form>
</div>
<?php endforeach; ?>

<div style="font-weight:700;margin:22px 0 10px">Agendar nueva cita</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
<?php foreach ($docentes as $d): ?>
    <a href="<?= APP_URL ?>/citas/docente/<?= $d['id'] ?>" class="card" style="padding:16px 18px;text-decoration:none;color:inherit;display:block">
        <div style="font-weight:700"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px">Ver horarios disponibles</div>
    </a>
<?php endforeach; ?>
<?php if (!$docentes): ?>
    <div style="color:var(--text-muted)">No hay docentes vinculados a tus hijos.</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
