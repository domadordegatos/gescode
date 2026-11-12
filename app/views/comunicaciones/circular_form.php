<?php
$pageTitle   = 'Nueva circular — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Circulares', 'url' => APP_URL . '/circulares'],
    ['label' => 'Nueva', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Nueva circular</h1>
        <p class="page-subtitle">Comunicado oficial del colegio, con acuse de recibo opcional.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/circulares/publicar" class="card" style="padding:24px;max-width:720px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Título <span class="required">*</span></label>
        <input type="text" name="titulo" class="form-input" required maxlength="200" placeholder="Ej: Suspensión de clases por jornada pedagógica">
    </div>

    <div class="form-group">
        <label>Contenido <span class="required">*</span></label>
        <textarea name="cuerpo" class="form-input" rows="6" required></textarea>
    </div>

    <div class="form-group">
        <label>Dirigido a</label>
        <select name="audiencia" id="audiencia" class="form-input">
            <option value="todos">Todos (administrativos, docentes y padres)</option>
            <option value="padres">Solo padres / acudientes</option>
            <option value="docentes">Solo docentes</option>
            <option value="grado">Padres de un grado específico</option>
        </select>
    </div>

    <div class="form-group" id="grupo-grado" style="display:none">
        <label>Grado</label>
        <select name="grado_id" class="form-input">
            <?php foreach ($grados as $g): ?>
            <option value="<?= $g['id'] ?>"><?= e($g['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="requiere_acuse" value="1" checked>
            <span>Exigir acuse de recibo (se registra quién la leyó)</span>
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-send-outline"></i> Publicar circular
        </button>
    </div>
</form>

<script>
const sel = document.getElementById('audiencia');
const grupoGrado = document.getElementById('grupo-grado');
sel.addEventListener('change', () => {
    grupoGrado.style.display = sel.value === 'grado' ? 'block' : 'none';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
