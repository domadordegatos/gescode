<?php
$pageTitle   = 'Exportar calendario — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Agenda', 'url' => APP_URL . '/calendario'],
    ['label' => 'Exportar', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Exportar mi agenda</h1>
</div>

<div class="card" style="max-width:640px;padding:24px">
    <p style="color:var(--text-muted)">
        Suscríbete a este enlace desde Google Calendar, Outlook o cualquier app de calendario para ver
        automáticamente tus tareas, evaluaciones y fechas institucionales. El calendario se actualiza solo —
        no necesitas volver a importarlo.
    </p>

    <div class="form-group">
        <label>Enlace de suscripción</label>
        <input type="text" class="form-input" id="ics-url" value="<?= e($urlIcs) ?>" readonly onclick="this.select()">
    </div>

    <div class="form-actions">
        <button type="button" class="btn-primary" onclick="copiarIcs()">
            <i class="mdi mdi-content-copy"></i> Copiar enlace
        </button>
        <form method="POST" action="<?= APP_URL ?>/calendario/regenerar-token" onsubmit="return confirm('Esto invalidará el enlace actual. ¿Continuar?')">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <button type="submit" class="btn-secondary"><i class="mdi mdi-refresh"></i> Generar nuevo enlace</button>
        </form>
    </div>

    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:.85rem;color:var(--text-muted)">
        <strong>Cómo agregarlo en Google Calendar:</strong>
        <ol style="margin:8px 0 0 1.2em">
            <li>Abre Google Calendar → "Otros calendarios" → "+" → "Desde URL".</li>
            <li>Pega el enlace de arriba y confirma.</li>
        </ol>
    </div>
</div>

<script>
function copiarIcs() {
    const input = document.getElementById('ics-url');
    input.select();
    navigator.clipboard?.writeText(input.value);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
