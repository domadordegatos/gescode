<?php
$pageTitle   = 'Nueva encuesta — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Encuestas', 'url' => APP_URL . '/encuestas'],
    ['label' => 'Nueva', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Nueva encuesta</h1>
        <p class="page-subtitle">Agrega las preguntas que necesites; cada una puede tener un tipo distinto.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/encuestas/guardar" class="card" style="padding:24px;max-width:780px">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label>Título <span class="required">*</span></label>
        <input type="text" name="titulo" class="form-input" required maxlength="200" placeholder="Ej: Evaluación docente - II período">
    </div>

    <div class="form-group">
        <label>Descripción (opcional)</label>
        <textarea name="descripcion" class="form-input" rows="2"></textarea>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label>Dirigida a</label>
            <select name="audiencia" class="form-input">
                <option value="todos">Todos</option>
                <option value="padres">Solo padres / acudientes</option>
                <option value="docentes">Solo docentes</option>
            </select>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                <input type="checkbox" name="anonima" value="1" checked>
                <span>Encuesta anónima (los resultados no se asocian a la persona)</span>
            </label>
        </div>
    </div>

    <hr style="margin:20px 0;border-color:var(--border)">

    <div id="preguntas-cont"></div>

    <button type="button" id="btn-agregar-pregunta" class="btn-outline btn-sm" style="margin-top:8px">
        <i class="mdi mdi-plus"></i> Agregar pregunta
    </button>

    <div class="form-actions" style="margin-top:20px">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Crear encuesta
        </button>
    </div>
</form>

<template id="plantilla-pregunta">
    <div class="pregunta-item" style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px">
        <div class="form-group">
            <label>Pregunta</label>
            <input type="text" name="pregunta_texto[]" class="form-input" required maxlength="300">
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label>Tipo de respuesta</label>
                <select name="pregunta_tipo[]" class="form-input select-tipo">
                    <option value="opcion_unica">Selección única</option>
                    <option value="opcion_multiple">Selección múltiple</option>
                    <option value="escala">Escala (1-5)</option>
                    <option value="texto">Texto libre</option>
                </select>
            </div>
            <div class="form-group campo-opciones">
                <label>Opciones (una por línea)</label>
                <textarea name="pregunta_opciones[]" class="form-input" rows="3" placeholder="Excelente&#10;Bueno&#10;Regular&#10;Malo"></textarea>
            </div>
        </div>
        <button type="button" class="btn-sm btn-danger-outline btn-quitar-pregunta">
            <i class="mdi mdi-trash-can-outline"></i> Quitar
        </button>
    </div>
</template>

<script>
const cont = document.getElementById('preguntas-cont');
const plantilla = document.getElementById('plantilla-pregunta');

function agregarPregunta() {
    const clone = plantilla.content.cloneNode(true);
    const item = clone.querySelector('.pregunta-item');
    const selectTipo = item.querySelector('.select-tipo');
    const campoOpciones = item.querySelector('.campo-opciones');

    function toggleOpciones() {
        campoOpciones.style.display = ['opcion_unica', 'opcion_multiple'].includes(selectTipo.value) ? 'block' : 'none';
    }
    selectTipo.addEventListener('change', toggleOpciones);
    toggleOpciones();

    item.querySelector('.btn-quitar-pregunta').addEventListener('click', () => item.remove());
    cont.appendChild(item);
}

document.getElementById('btn-agregar-pregunta').addEventListener('click', agregarPregunta);
agregarPregunta(); // primera pregunta por defecto
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
