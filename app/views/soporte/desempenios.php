<?php
$pageTitle   = 'Desempeños — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Inicio',      'url' => APP_URL . '/dashboard'],
    ['label' => 'Desempeños',  'url' => ''],
];
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Niveles de Desempeño</h1>
        <p class="page-subtitle">Define los rangos de calificación y sus abreviaturas. Marca cuál es el nivel reprobatorio.</p>
    </div>
    <button class="btn-primary" onclick="agregarFila()">
        <i class="mdi mdi-plus"></i> Agregar nivel
    </button>
</div>

<form method="POST" action="<?= APP_URL ?>/soporte/desempenios" id="form-desemp">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

    <div class="card" style="overflow:auto;margin-bottom:20px">
        <table class="data-table" id="tabla-desemp">
            <thead>
                <tr>
                    <th style="width:36px"></th>
                    <th>Nombre</th>
                    <th style="width:90px">Abreviatura</th>
                    <th style="width:110px">Rango mín</th>
                    <th style="width:110px">Rango máx</th>
                    <th style="width:100px;text-align:center">Reprobatorio</th>
                    <th style="width:44px"></th>
                </tr>
            </thead>
            <tbody id="tbody-desemp">
                <?php foreach ($desempenios as $i => $d): ?>
                <tr class="desemp-row" draggable="true">
                    <td class="drag-handle" title="Arrastrar"><i class="mdi mdi-drag-vertical" style="color:var(--text-muted);font-size:18px;cursor:grab"></i></td>
                    <td>
                        <input type="text" name="d[<?= $i ?>][nombre]"
                               class="form-input" style="min-width:180px"
                               value="<?= e($d['nombre']) ?>" placeholder="Ej: Desempeño Básico" required>
                    </td>
                    <td>
                        <input type="text" name="d[<?= $i ?>][abreviatura]"
                               class="form-input" style="text-transform:uppercase;width:80px"
                               maxlength="5" value="<?= e($d['abreviatura']) ?>" placeholder="DB" required>
                    </td>
                    <td>
                        <input type="number" name="d[<?= $i ?>][rango_min]"
                               class="form-input" style="width:90px"
                               min="0" max="10" step="0.1"
                               value="<?= $d['rango_min'] ?>" required>
                    </td>
                    <td>
                        <input type="number" name="d[<?= $i ?>][rango_max]"
                               class="form-input" style="width:90px"
                               min="0" max="10" step="0.1"
                               value="<?= $d['rango_max'] ?>" required>
                    </td>
                    <td style="text-align:center">
                        <input type="radio" name="perdido_idx" value="<?= $i ?>"
                               <?= $d['es_perdido'] ? 'checked' : '' ?>
                               style="width:18px;height:18px;accent-color:var(--danger);cursor:pointer"
                               title="Marcar como reprobatorio"
                               onchange="actualizarPerdido()">
                    </td>
                    <td>
                        <button type="button" class="eval-btn-eliminar" onclick="eliminarFila(this)" title="Eliminar">
                            <i class="mdi mdi-delete-outline"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($desempenios)): ?>
                <!-- fila vacía si no hay datos -->
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Leyenda visual -->
    <div id="leyenda-preview" class="desemp-preview-bar">
        <!-- generado por JS -->
    </div>

    <div class="info-box-blue" style="margin-top:0;margin-bottom:20px">
        <i class="mdi mdi-information-outline"></i>
        <span>Los rangos deben cubrir toda la escala (0 – 10) sin solaparse.
        El nivel marcado como <strong>Reprobatorio</strong> define la nota mínima de aprobación.</span>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Guardar desempeños
        </button>
    </div>
</form>

<script>
let contador = <?= count($desempenios) ?>;

function agregarFila() {
    const i = contador++;
    const tbody = document.getElementById('tbody-desemp');
    const tr = document.createElement('tr');
    tr.className = 'desemp-row';
    tr.draggable = true;
    tr.innerHTML = `
        <td class="drag-handle" title="Arrastrar"><i class="mdi mdi-drag-vertical" style="color:var(--text-muted);font-size:18px;cursor:grab"></i></td>
        <td><input type="text" name="d[${i}][nombre]" class="form-input" style="min-width:180px" placeholder="Ej: Desempeño Alto" required></td>
        <td><input type="text" name="d[${i}][abreviatura]" class="form-input" style="text-transform:uppercase;width:80px" maxlength="5" placeholder="DA" required></td>
        <td><input type="number" name="d[${i}][rango_min]" class="form-input" style="width:90px" min="0" max="10" step="0.1" value="0" required></td>
        <td><input type="number" name="d[${i}][rango_max]" class="form-input" style="width:90px" min="0" max="10" step="0.1" value="0" required></td>
        <td style="text-align:center">
            <input type="radio" name="perdido_idx" value="${i}"
                   style="width:18px;height:18px;accent-color:var(--danger);cursor:pointer"
                   title="Marcar como reprobatorio"
                   onchange="actualizarPerdido()">
        </td>
        <td><button type="button" class="eval-btn-eliminar" onclick="eliminarFila(this)" title="Eliminar"><i class="mdi mdi-delete-outline"></i></button></td>
    `;
    tbody.appendChild(tr);
    actualizarPerdido();
}

function eliminarFila(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    renumerarFilas();
    actualizarPerdido();
}

function renumerarFilas() {
    document.querySelectorAll('#tbody-desemp tr').forEach((tr, i) => {
        tr.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/d\[\d+\]/, `d[${i}]`);
        });
        const radio = tr.querySelector('input[type=radio]');
        if (radio) radio.value = i;
    });
    contador = document.querySelectorAll('#tbody-desemp tr').length;
}

function actualizarPerdido() {
    // Sincronizar campo oculto es_perdido
    document.querySelectorAll('#tbody-desemp tr').forEach((tr, i) => {
        const radio = tr.querySelector('input[type=radio]');
        // Asegurar que el campo hidden exista
        let hidden = tr.querySelector('input[name$="[es_perdido]"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            tr.appendChild(hidden);
        }
        hidden.name = `d[${i}][es_perdido]`;
        hidden.value = (radio && radio.checked) ? '1' : '';
    });
    actualizarLeyenda();
}

function actualizarLeyenda() {
    const bar = document.getElementById('leyenda-preview');
    const filas = document.querySelectorAll('#tbody-desemp tr');
    let html = '';
    filas.forEach(tr => {
        const nombre = tr.querySelector('input[name$="[nombre]"]')?.value || '';
        const abrev  = tr.querySelector('input[name$="[abreviatura]"]')?.value || '';
        const min    = tr.querySelector('input[name$="[rango_min]"]')?.value || '';
        const max    = tr.querySelector('input[name$="[rango_max]"]')?.value || '';
        const perd   = tr.querySelector('input[type=radio]')?.checked;
        if (!nombre) return;
        const color = perd ? '#DC2626' : '#15803D';
        const bg    = perd ? '#FEE2E2' : '#DCFCE7';
        html += `<div class="desemp-preview-chip" style="border-color:${color};background:${bg};color:${color}">
            <strong>${abrev}</strong> ${nombre} <span style="opacity:.7">(${min} – ${max})</span>
            ${perd ? '<i class="mdi mdi-close-circle-outline" title="Reprobatorio"></i>' : ''}
        </div>`;
    });
    bar.innerHTML = html || '<span style="color:var(--text-muted);font-size:.85rem">Agrega niveles para ver la vista previa.</span>';
}

// Init
document.addEventListener('DOMContentLoaded', actualizarLeyenda);

// Actualizar leyenda en tiempo real
document.getElementById('tbody-desemp').addEventListener('input', actualizarLeyenda);
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
