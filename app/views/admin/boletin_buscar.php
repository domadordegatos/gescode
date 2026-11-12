<?php
$pageTitle   = 'Boletines de notas — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Boletines', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-file-pdf-box"></i> Boletines de notas</h1>
        <p class="page-subtitle">Busca un estudiante para generar su boletín en PDF.</p>
    </div>
    <a href="<?= APP_URL ?>/boletines/grupos" class="btn-secondary">
        <i class="mdi mdi-google-classroom"></i> Generar por grupo completo
    </a>
</div>

<div class="card" style="padding:20px 22px;max-width:620px">
    <input type="text" id="buscador" class="form-input" placeholder="Nombre, apellido o documento del estudiante..." autocomplete="off" style="width:100%">
    <div id="resultados" style="margin-top:10px"></div>
</div>

<script>
const input = document.getElementById('buscador');
const cont  = document.getElementById('resultados');
let timeoutId = null;

input.addEventListener('input', () => {
    clearTimeout(timeoutId);
    const q = input.value.trim();
    if (q.length < 2) { cont.innerHTML = ''; return; }
    timeoutId = setTimeout(() => buscar(q), 300);
});

function buscar(q) {
    fetch('<?= APP_URL ?>/boletines/buscar?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(datos => {
            if (!datos.length) { cont.innerHTML = '<div style="padding:10px;color:var(--text-muted)">Sin resultados.</div>'; return; }
            cont.innerHTML = datos.map(d => `
                <a href="<?= APP_URL ?>/boletines/${d.estudiante_id}" style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;border-bottom:1px solid var(--border)">
                    <div>
                        <div style="font-weight:600">${escapeHtml(d.nombre)} ${escapeHtml(d.apellido)}</div>
                        <div style="font-size:.78rem;color:var(--text-muted)">${escapeHtml(d.documento || '')} · ${escapeHtml(d.grupo_actual || 'Sin grupo')}</div>
                    </div>
                    <i class="mdi mdi-chevron-right"></i>
                </a>
            `).join('');
        });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s ?? '';
    return div.innerHTML;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
