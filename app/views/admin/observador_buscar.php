<?php
$pageTitle   = 'Observador del estudiante — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Observador del estudiante', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Observador del estudiante</h1>
        <p class="page-subtitle">Busca un estudiante para ver su línea de tiempo consolidada — observaciones de docentes y anotaciones de coordinación, de todos los años.</p>
    </div>
</div>

<div class="card" style="padding:24px;max-width:600px">
    <div class="form-group" style="margin-bottom:0">
        <label>Buscar por nombre, apellido o documento</label>
        <input type="text" id="inp-buscar" class="form-input" placeholder="Ej: Gabriela Barrera" autocomplete="off">
    </div>
    <div id="resultados" style="margin-top:12px"></div>
</div>

<script>
let _t;
document.getElementById('inp-buscar').addEventListener('input', function() {
    clearTimeout(_t);
    const q = this.value.trim();
    const cont = document.getElementById('resultados');
    if (q.length < 2) { cont.innerHTML = ''; return; }
    _t = setTimeout(function() {
        fetch('<?= APP_URL ?>/observador/buscar?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.length) {
                    cont.innerHTML = '<p style="font-size:.85rem;color:var(--text-muted);padding:10px 0">Sin resultados.</p>';
                    return;
                }
                let html = '';
                data.forEach(function(e) {
                    html += '<a href="<?= APP_URL ?>/observador/' + e.estudiante_id + '" '
                          + 'style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit">'
                          + '<span><strong>' + e.apellido + ', ' + e.nombre + '</strong>'
                          + (e.documento ? ' <span style="color:var(--text-muted);font-size:.8rem">· ' + e.documento + '</span>' : '')
                          + '</span>'
                          + '<span style="font-size:.8rem;color:var(--text-muted)">' + (e.grupo_actual || '') + '</span>'
                          + '</a>';
                });
                cont.innerHTML = html;
            });
    }, 300);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
