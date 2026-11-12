<?php
$pageTitle   = 'Citas con ' . $docente['nombre'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Citas', 'url' => APP_URL . '/citas'],
    ['label' => $docente['nombre'] . ' ' . $docente['apellido'], 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($docente['nombre'] . ' ' . $docente['apellido']) ?></h1>
        <p class="page-subtitle">Elige un horario disponible para reservar tu cita.</p>
    </div>
</div>

<?php if (!$bloques): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">
        Este docente no tiene horarios disponibles por el momento.
    </div>
<?php endif; ?>

<?php foreach ($bloques as $b): ?>
<div class="card" style="padding:18px 20px;margin-bottom:14px">
    <div style="font-weight:700"><?= date('l d/m/Y', strtotime($b['fecha'])) ?></div>
    <?php if ($b['lugar']): ?><div style="font-size:.8rem;color:var(--text-muted);margin-bottom:10px"><i class="mdi mdi-map-marker-outline"></i> <?= e($b['lugar']) ?></div><?php endif; ?>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
    <?php foreach ($b['slots'] as $s): ?>
        <?php if ($s['reservado']): ?>
        <div style="padding:7px 14px;border-radius:8px;background:#F3F4F6;color:#9CA3AF;font-size:.82rem;font-weight:600">
            <?= substr($s['hora_inicio'],0,5) ?>
        </div>
        <?php else: ?>
        <button type="button" class="btn-slot" data-disp="<?= $b['id'] ?>" data-hora="<?= $s['hora_inicio'] ?>" data-fecha="<?= date('d/m/Y', strtotime($b['fecha'])) ?>"
                style="padding:7px 14px;border-radius:8px;background:var(--primary-light);color:var(--primary);font-size:.82rem;font-weight:700;border:none;cursor:pointer">
            <?= substr($s['hora_inicio'],0,5) ?>
        </button>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal de confirmación -->
<div id="modal-reservar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="padding:24px;max-width:440px;width:90%">
        <div style="font-weight:700;margin-bottom:4px">Confirmar cita</div>
        <div id="modal-fecha-hora" style="font-size:.85rem;color:var(--text-muted);margin-bottom:16px"></div>
        <form method="POST" action="<?= APP_URL ?>/citas/reservar">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <input type="hidden" name="disponibilidad_id" id="input-disp">
            <input type="hidden" name="hora_inicio" id="input-hora">

            <?php if ($hijos): ?>
            <div class="form-group">
                <label>¿Sobre cuál hijo? (opcional)</label>
                <select name="estudiante_id" class="form-input">
                    <option value="">No aplica / es general</option>
                    <?php foreach ($hijos as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= e($h['nombre'] . ' ' . $h['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Motivo (opcional)</label>
                <textarea name="motivo" class="form-input" rows="2"></textarea>
            </div>

            <div class="form-actions" style="display:flex;gap:10px">
                <button type="button" onclick="cerrarModal()" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Confirmar reserva</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-slot').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('input-disp').value = btn.dataset.disp;
        document.getElementById('input-hora').value = btn.dataset.hora;
        document.getElementById('modal-fecha-hora').textContent = btn.dataset.fecha + ' a las ' + btn.dataset.hora.substring(0,5);
        document.getElementById('modal-reservar').style.display = 'flex';
    });
});
function cerrarModal() { document.getElementById('modal-reservar').style.display = 'none'; }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
