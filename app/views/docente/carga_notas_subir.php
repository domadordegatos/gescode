<?php
$pageTitle  = 'Cargar notas desde Excel — ' . $asig['grado'] . ' ' . $asig['grupo'] . ' — ' . APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Inicio',     'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis clases', 'url' => APP_URL . '/mis-grupos'],
    ['label' => $asig['grado'] . ' ' . $asig['grupo'], 'url' => APP_URL . '/grupos/' . $gid . '/estudiantes'],
    ['label' => 'Cargar notas desde Excel', 'url' => ''],
];
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cargar notas desde Excel</h1>
        <p class="page-subtitle"><?= e($asig['grado'] . ' ' . $asig['grupo'] . ' · ' . $asig['materia']) ?></p>
    </div>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/evaluaciones?asig=<?= $asig['asignacion_id'] ?>&periodo=<?= $periodo_id ?>" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Volver
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <div class="card" style="padding:24px">
        <div class="section-title" style="margin-bottom:16px">
            <i class="mdi mdi-upload" style="color:var(--primary)"></i> Subir archivo
        </div>

        <form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas/subir" enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <input type="hidden" name="asig" value="<?= (int)$asig['asignacion_id'] ?>">
            <input type="hidden" name="periodo" value="<?= (int)$periodo_id ?>">

            <div class="form-group">
                <label>Archivo Excel (.xlsx o .xls) <span class="required">*</span></label>
                <div id="drop-zone" style="border:2px dashed var(--border);border-radius:10px;padding:32px;text-align:center;cursor:pointer;transition:border-color .2s"
                     onclick="document.getElementById('inp-archivo').click()"
                     ondragover="event.preventDefault();this.style.borderColor='var(--primary)'"
                     ondragleave="this.style.borderColor='var(--border)'"
                     ondrop="handleDrop(event)">
                    <i class="mdi mdi-file-excel-outline" style="font-size:40px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                    <div id="drop-label" style="font-weight:700;color:var(--text-muted)">
                        Arrastra tu archivo aquí o haz clic para seleccionar
                    </div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px">XLSX o XLS · Máx. 8 MB</div>
                    <input type="file" id="inp-archivo" name="archivo" accept=".xlsx,.xls"
                           style="display:none" onchange="mostrarArchivo(this)">
                </div>
                <small class="form-hint">
                    En el siguiente paso eliges qué hoja corresponde a este grupo, qué columna tiene los
                    nombres y cuál es la nota que quieres importar.
                </small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="mdi mdi-file-upload-outline"></i> Continuar
                </button>
            </div>
        </form>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">
                <i class="mdi mdi-file-download-outline" style="color:var(--primary)"></i> ¿No tienes el formato listo?
            </div>
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
                Descarga una plantilla con la lista de estudiantes de este grupo ya puesta, diligénciala
                a mano o en el programa que prefieras, y vuelve a subirla aquí.
            </p>
            <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas/plantilla?asig=<?= $asig['asignacion_id'] ?>"
               class="btn-primary" style="display:flex;width:100%;justify-content:center">
                <i class="mdi mdi-microsoft-excel"></i> Descargar plantilla
            </a>
        </div>

        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">
                <i class="mdi mdi-information-outline" style="color:var(--primary)"></i> Cómo funciona
            </div>
            <ol style="font-size:.8rem;color:var(--text-muted);margin:0;padding-left:1.2em;line-height:1.9">
                <li>Subes tu archivo (puede tener varias hojas, una por grupo).</li>
                <li>Eliges la hoja y las columnas de nombre y nota.</li>
                <li>Indicas a qué evaluación va esa nota.</li>
                <li>Revisas el emparejamiento de nombres antes de guardar nada.</li>
                <li>Confirmas — solo entonces se escriben las notas.</li>
            </ol>
        </div>
    </div>

</div>

<script>
function mostrarArchivo(inp) {
    if (inp.files[0]) {
        document.getElementById('drop-label').textContent = inp.files[0].name;
        document.getElementById('drop-zone').style.borderColor = 'var(--primary)';
    }
}
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('drop-zone').style.borderColor = 'var(--border)';
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('inp-archivo').files = dt.files;
    mostrarArchivo(document.getElementById('inp-archivo'));
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
