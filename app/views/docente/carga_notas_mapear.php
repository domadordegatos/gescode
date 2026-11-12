<?php
$pageTitle  = 'Mapear columnas — ' . APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Inicio',     'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis clases', 'url' => APP_URL . '/mis-grupos'],
    ['label' => $asig['grado'] . ' ' . $asig['grupo'], 'url' => APP_URL . '/grupos/' . $gid . '/estudiantes'],
    ['label' => 'Cargar notas desde Excel', 'url' => ''],
];

// JSON ligero para el JS: solo las primeras filas de cada hoja (ya vienen limitadas a 200 filas,
// recortamos aún más para la vista previa en pantalla)
$hojasPreview = [];
foreach ($hojas as $nombre => $h) {
    $hojasPreview[$nombre] = array_slice($h['filas'], 0, 20);
}

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Mapear columnas</h1>
        <p class="page-subtitle"><?= e($asig['grado'] . ' ' . $asig['grupo'] . ' · ' . $asig['materia']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas?asig=<?= $asig['asignacion_id'] ?>&periodo=<?= $periodo_id ?>" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Subir otro archivo
    </a>
</div>

<form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas/emparejar" id="form-mapeo">
<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
<input type="hidden" name="asig" value="<?= (int)$asig['asignacion_id'] ?>">
<input type="hidden" name="periodo" value="<?= (int)$periodo_id ?>">

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

    <!-- Panel de configuración -->
    <div class="card" style="padding:20px;position:sticky;top:80px">

        <div class="form-group">
            <label>Hoja del archivo <span class="required">*</span></label>
            <select name="hoja" id="sel-hoja" class="form-input" onchange="cambiarHoja()" required>
                <?php foreach (array_keys($hojas) as $nombreHoja): ?>
                <option value="<?= e($nombreHoja) ?>" <?= $nombreHoja === $hojaSugerida ? 'selected' : '' ?>>
                    <?= e($nombreHoja) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <small class="form-hint">Elige la hoja que corresponde a este grupo.</small>
        </div>

        <div class="form-group">
            <label>Fila del encabezado <span class="required">*</span></label>
            <input type="number" name="fila_header" id="inp-fila-header" class="form-input" min="1" max="200" value="6" onchange="resaltarSeleccion()">
            <small class="form-hint">Número de fila donde están los títulos de columna (ej. G1, Apellido...). Los datos empiezan en la fila siguiente.</small>
        </div>

        <div class="form-group">
            <label>Columna con el nombre del estudiante <span class="required">*</span></label>
            <input type="text" name="col_nombre" id="inp-col-nombre" class="form-input" placeholder="Ej: B" maxlength="2" style="text-transform:uppercase" onchange="resaltarSeleccion()" required>
            <small class="form-hint">Haz clic en el encabezado de esa columna en la tabla de la derecha.</small>
        </div>

        <div class="form-group">
            <label>Columna con la nota a importar <span class="required">*</span></label>
            <input type="text" name="col_nota" id="inp-col-nota" class="form-input" placeholder="Ej: R" maxlength="2" style="text-transform:uppercase" onchange="resaltarSeleccion()" required>
            <small class="form-hint">Solo se importará esta columna. Puedes repetir el proceso para cargar otra.</small>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">

        <div class="form-group">
            <label>Logro / indicador destino <span class="required">*</span></label>
            <?php if (empty($indicadores)): ?>
                <div class="alert alert-error" style="font-size:.8rem">
                    No hay indicadores creados para este período. Crea uno primero en Evaluaciones.
                </div>
            <?php else: ?>
            <select name="indicador_id" id="sel-indicador" class="form-input" onchange="cambiarIndicador()" required>
                <option value="">Selecciona...</option>
                <?php foreach ($indicadores as $ind): ?>
                <option value="<?= $ind['id'] ?>"><?= e($ind['codigo'] ? $ind['codigo'].' — '.$ind['descripcion'] : $ind['descripcion']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>

        <div class="form-group" id="wrap-eval-existente" style="display:none">
            <label>Evaluación existente</label>
            <select name="eval_id" id="sel-eval" class="form-input">
                <option value="">— Crear una nueva —</option>
            </select>
        </div>

        <div id="wrap-eval-nueva">
            <div class="form-group">
                <label>Nombre de la evaluación nueva</label>
                <input type="text" name="eval_nuevo_nombre" id="inp-eval-nombre" class="form-input" placeholder="Ej: Definitiva período 3" maxlength="150">
            </div>
            <div class="form-group">
                <label>Porcentaje dentro del logro</label>
                <input type="number" name="eval_nuevo_porcentaje" class="form-input" value="100" min="1" max="100" step="0.1">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center">
                <i class="mdi mdi-arrow-right"></i> Ver emparejamiento
            </button>
        </div>
    </div>

    <!-- Vista previa de la hoja -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <strong style="font-size:.88rem">Vista previa: <span id="titulo-hoja"></span></strong>
            <span style="font-size:.78rem;color:var(--text-muted)">Clic en un encabezado de columna para asignarla</span>
        </div>
        <div style="overflow:auto;max-height:600px">
            <table class="data-table" id="tabla-preview" style="font-size:.78rem">
                <thead id="thead-preview"></thead>
                <tbody id="tbody-preview"></tbody>
            </table>
        </div>
    </div>

</div>
</form>

<style>
.col-clickable { cursor:pointer; user-select:none; }
.col-clickable:hover { background:#EEF2FF; }
.col-marcada-nombre { background:#DBEAFE !important; }
.col-marcada-nota { background:#DCFCE7 !important; }
.fila-header-marcada td { background:#FEF3C7 !important; font-weight:700; }
</style>

<script>
var HOJAS = <?= json_encode($hojasPreview, JSON_UNESCAPED_UNICODE) ?>;
var INDICADORES = <?= json_encode($indicadores, JSON_UNESCAPED_UNICODE) ?>;

function colLetras(n) {
    // genera A, B, C... Z, AA, AB... hasta n columnas
    var out = [];
    for (var i = 1; i <= n; i++) {
        var s = '', x = i;
        while (x > 0) { var m = (x - 1) % 26; s = String.fromCharCode(65 + m) + s; x = Math.floor((x - 1) / 26); }
        out.push(s);
    }
    return out;
}

function renderTabla() {
    var hojaNombre = document.getElementById('sel-hoja').value;
    var filas = HOJAS[hojaNombre] || [];
    document.getElementById('titulo-hoja').textContent = hojaNombre;

    var maxCol = 0;
    filas.forEach(function(f) { maxCol = Math.max(maxCol, Object.keys(f).length); });
    if (maxCol === 0) maxCol = 15;
    var letras = colLetras(maxCol);

    var thead = '<tr><th style="width:36px">#</th>';
    letras.forEach(function(l) {
        thead += '<th class="col-clickable" data-col="' + l + '" onclick="elegirColumna(\'' + l + '\')" title="Clic para asignar esta columna">' + l + '</th>';
    });
    thead += '</tr>';
    document.getElementById('thead-preview').innerHTML = thead;

    var tbody = '';
    filas.forEach(function(f, idx) {
        var numFila = idx + 1;
        tbody += '<tr data-fila="' + numFila + '" onclick="elegirFilaHeader(' + numFila + ')" style="cursor:pointer" title="Clic para marcar como fila de encabezado">';
        tbody += '<td style="color:var(--text-muted);font-weight:700">' + numFila + '</td>';
        letras.forEach(function(l) {
            var v = f[l];
            tbody += '<td data-col="' + l + '">' + (v === null || v === undefined ? '' : String(v).substring(0, 40)) + '</td>';
        });
        tbody += '</tr>';
    });
    document.getElementById('tbody-preview').innerHTML = tbody;

    resaltarSeleccion();
}

function elegirColumna(letra) {
    // Si el usuario ya puso la columna de nombre, lo siguiente que haga clic será la de nota
    var inpNombre = document.getElementById('inp-col-nombre');
    var inpNota   = document.getElementById('inp-col-nota');
    if (!inpNombre.value) {
        inpNombre.value = letra;
    } else if (!inpNota.value) {
        inpNota.value = letra;
    } else {
        // Ambas ya están puestas: reinicia desde nombre
        inpNombre.value = letra;
        inpNota.value = '';
    }
    resaltarSeleccion();
}

function elegirFilaHeader(numFila) {
    document.getElementById('inp-fila-header').value = numFila;
    resaltarSeleccion();
}

function resaltarSeleccion() {
    var colNombre = (document.getElementById('inp-col-nombre').value || '').toUpperCase();
    var colNota   = (document.getElementById('inp-col-nota').value || '').toUpperCase();
    var filaHeader = parseInt(document.getElementById('inp-fila-header').value || '0');

    document.querySelectorAll('#tabla-preview td[data-col], #tabla-preview th[data-col]').forEach(function(cell) {
        cell.classList.remove('col-marcada-nombre', 'col-marcada-nota');
        var c = cell.dataset.col;
        if (c && c === colNombre) cell.classList.add('col-marcada-nombre');
        if (c && c === colNota)   cell.classList.add('col-marcada-nota');
    });
    document.querySelectorAll('#tabla-preview tr[data-fila]').forEach(function(tr) {
        tr.classList.toggle('fila-header-marcada', parseInt(tr.dataset.fila) === filaHeader);
    });
}

function cambiarHoja() { renderTabla(); }

function cambiarIndicador() {
    var indId = document.getElementById('sel-indicador').value;
    var wrapExist = document.getElementById('wrap-eval-existente');
    var selEval = document.getElementById('sel-eval');
    selEval.innerHTML = '<option value="">— Crear una nueva —</option>';

    var ind = INDICADORES.find(function(i) { return String(i.id) === String(indId); });
    if (ind && ind.evaluaciones && ind.evaluaciones.length) {
        ind.evaluaciones.forEach(function(ev) {
            var opt = document.createElement('option');
            opt.value = ev.id;
            opt.textContent = ev.nombre;
            selEval.appendChild(opt);
        });
        wrapExist.style.display = '';
    } else {
        wrapExist.style.display = 'none';
    }
    toggleEvalNueva();
}

function toggleEvalNueva() {
    var selEval = document.getElementById('sel-eval');
    var usandoExistente = selEval && selEval.value !== '';
    document.getElementById('wrap-eval-nueva').style.display = usandoExistente ? 'none' : '';
}

document.addEventListener('DOMContentLoaded', function() {
    renderTabla();
    var selEval = document.getElementById('sel-eval');
    if (selEval) selEval.addEventListener('change', toggleEvalNueva);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
