<?php
$pageTitle   = 'Nueva autorización — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => APP_URL . '/autorizaciones'],
    ['label' => 'Nueva', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Nueva autorización</h1>
        <p class="page-subtitle">El acudiente deberá firmar con su documento y una fotografía en vivo.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/autorizaciones/guardar" class="card" style="padding:24px;max-width:760px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-row-2">
        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" class="form-input">
                <option value="salida_pedagogica">Salida pedagógica</option>
                <option value="uso_imagen">Uso de imagen</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div class="form-group">
            <label>Fecha del evento (opcional)</label>
            <input type="date" name="fecha_evento" class="form-input">
        </div>
    </div>

    <div class="form-group">
        <label>Título <span class="required">*</span></label>
        <input type="text" name="titulo" class="form-input" required maxlength="200" placeholder="Ej: Salida pedagógica al Museo de los Niños">
    </div>

    <div class="form-group">
        <label>Texto del consentimiento <span class="required">*</span></label>
        <textarea name="texto_consentimiento" class="form-input" rows="7" required placeholder="Puedes usar [NOMBRE_HIJO] y [NOMBRE_PADRE], que se reemplazan automáticamente al momento de firmar.

Ejemplo:
Autorizo a mi hijo/a [NOMBRE_HIJO] a participar en la salida pedagógica al Museo de los Niños el día 15 de noviembre de 2026, entendiendo los riesgos propios de la actividad y eximiendo a la institución de responsabilidad por eventos fuera de su control."></textarea>
        <small class="form-hint">[NOMBRE_HIJO] y [NOMBRE_PADRE] se reemplazan automáticamente por el nombre real al momento de la firma.</small>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label>Dirigido a</label>
            <select name="audiencia" id="audiencia" class="form-input">
                <option value="todos">Todos los estudiantes activos</option>
                <option value="grado">Un grado específico</option>
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
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Crear autorización
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
