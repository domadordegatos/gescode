<?php
$pageTitle   = 'Carga masiva de usuarios — ' . APP_NAME;
$breadcrumbs = [
    ['label'=>'Inicio','url'=>APP_URL.'/dashboard'],
    ['label'=>'Usuarios','url'=>APP_URL.'/usuarios'],
    ['label'=>'Carga masiva','url'=>''],
];
$noLeidos = 0;
$errores  = $_SESSION['carga_errores'] ?? [];
unset($_SESSION['carga_errores']);
$credenciales = $_SESSION['carga_credenciales'] ?? [];
unset($_SESSION['carga_credenciales']);
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Carga masiva de usuarios</h1>
        <p class="page-subtitle">Crea múltiples usuarios desde un archivo Excel o CSV</p>
    </div>
    <a href="<?= APP_URL ?>/usuarios" class="btn-secondary"><i class="mdi mdi-arrow-left"></i> Volver</a>
</div>

<?php if ($errores): ?>
<div class="alert alert-error" style="flex-direction:column;align-items:flex-start">
    <strong><i class="mdi mdi-alert-circle"></i> Errores en la carga:</strong>
    <ul style="margin:8px 0 0 1.2em;font-size:.82rem">
        <?php foreach ($errores as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($credenciales): ?>
<div class="card" style="padding:20px;margin-bottom:20px;border:2px solid #FDE68A;background:#FFFBEB">
    <div class="section-title" style="margin-bottom:6px">
        <i class="mdi mdi-key-variant" style="color:#D97706"></i> Credenciales generadas
    </div>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
        <strong>Esta es la única vez que verás estas contraseñas</strong> — no quedan guardadas en texto
        plano. Descárgalas o cópialas ahora y entrégalas de forma segura a cada usuario.
    </p>
    <div style="overflow-x:auto">
        <table class="data-table" id="tabla-credenciales">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Usuario</th>
                    <th>Contraseña</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($credenciales as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td><?= htmlspecialchars($c['rol']) ?></td>
                    <td><code><?= htmlspecialchars($c['username']) ?></code></td>
                    <td>
                        <code><?= htmlspecialchars($c['password']) ?></code>
                        <?php if (!$c['generada']): ?>
                            <span style="font-size:.7rem;color:var(--text-muted)">(definida en archivo)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="form-actions" style="margin-top:14px">
        <button type="button" class="btn-primary btn-sm" onclick="descargarCredencialesCSV()">
            <i class="mdi mdi-download-outline"></i> Descargar CSV
        </button>
    </div>
</div>
<script>
function descargarCredencialesCSV() {
    const filas = [['Nombre','Rol','Usuario','Contraseña']];
    document.querySelectorAll('#tabla-credenciales tbody tr').forEach(function(tr) {
        const celdas = tr.querySelectorAll('td');
        filas.push([
            celdas[0].innerText.trim(),
            celdas[1].innerText.trim(),
            celdas[2].innerText.trim(),
            celdas[3].querySelector('code').innerText.trim(),
        ]);
    });
    const csv = filas.map(f => f.map(function(v) {
        return '"' + String(v).replace(/"/g, '""') + '"';
    }).join(',')).join('\r\n');
    const blob = new Blob(['﻿' + csv], {type: 'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = 'credenciales_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- Subir archivo -->
    <div class="card" style="padding:24px">
        <div class="section-title" style="margin-bottom:16px">
            <i class="mdi mdi-upload" style="color:var(--primary)"></i> Subir archivo
        </div>

        <form method="POST" action="<?= APP_URL ?>/usuarios/procesar-carga" enctype="multipart/form-data">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label>Archivo CSV o Excel <span class="required">*</span></label>
                <div id="drop-zone" style="border:2px dashed var(--border);border-radius:10px;padding:32px;text-align:center;cursor:pointer;transition:border-color .2s"
                     onclick="document.getElementById('inp-archivo').click()"
                     ondragover="event.preventDefault();this.style.borderColor='var(--primary)'"
                     ondragleave="this.style.borderColor='var(--border)'"
                     ondrop="handleDrop(event)">
                    <i class="mdi mdi-file-upload-outline" style="font-size:40px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                    <div id="drop-label" style="font-weight:700;color:var(--text-muted)">
                        Arrastra tu archivo aquí o haz clic para seleccionar
                    </div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px">CSV, XLS o XLSX · Máx. 5 MB</div>
                    <input type="file" id="inp-archivo" name="archivo" accept=".csv,.xls,.xlsx"
                           style="display:none" onchange="mostrarArchivo(this)">
                </div>
                <small class="form-hint">La primera fila debe ser la cabecera. Descarga la plantilla si no tienes el formato.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="mdi mdi-account-multiple-plus-outline"></i> Procesar y crear usuarios
                </button>
            </div>
        </form>
    </div>

    <!-- Plantilla + instrucciones -->
    <div style="display:flex;flex-direction:column;gap:12px">

        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">
                <i class="mdi mdi-file-download-outline" style="color:var(--primary)"></i> Plantilla base
            </div>
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
                Descarga la plantilla con el formato correcto y las filas de ejemplo.
            </p>
            <a href="<?= APP_URL ?>/usuarios/plantilla" class="btn-primary" style="display:flex;width:100%;justify-content:center">
                <i class="mdi mdi-microsoft-excel"></i> Descargar plantilla CSV
            </a>
        </div>

        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">
                <i class="mdi mdi-format-list-bulleted" style="color:var(--primary)"></i> Columnas del archivo
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);line-height:1.9">
                <div style="display:grid;grid-template-columns:auto 1fr;gap:2px 10px">
                    <code>rol</code><span>admin / docente / estudiante / padre / soporte</span>
                    <code>nombre</code><span>Nombre(s) <strong style="color:var(--danger)">*</strong></span>
                    <code>apellido</code><span>Apellido(s) <strong style="color:var(--danger)">*</strong></span>
                    <code>email</code><span>Correo institucional</span>
                    <code>username</code><span>Si vacío, se genera automático</span>
                    <code>password</code><span>Si vacío: se genera una aleatoria por usuario</span>
                    <code>cargo</code><span>Cargo del colaborador</span>
                    <code>documento</code><span>Número documento (estudiantes)</span>
                    <code>tipo_documento</code><span>TI / CC / CE / PP</span>
                    <code>grupo_id</code><span>ID del grupo (ver tabla grupos)</span>
                    <code>fecha_nacimiento</code><span>AAAA-MM-DD</span>
                    <code>genero</code><span>M / F / O</span>
                    <code>telefono</code><span>Teléfono fijo</span>
                    <code>celular</code><span>Número celular</span>
                    <code>ciudad</code><span>Ciudad de residencia</span>
                    <code>departamento</code><span>Departamento</span>
                </div>
            </div>
        </div>

        <!-- IDs de grupos disponibles -->
        <?php if (!empty($grupos)): ?>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">
                <i class="mdi mdi-school-outline" style="color:var(--primary)"></i> IDs de grupos disponibles
            </div>
            <div style="font-size:.78rem">
                <?php foreach ($grupos as $g): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid var(--border)">
                    <span><?= e($g['grado'].' '.$g['grupo']) ?></span>
                    <code><?= $g['id'] ?></code>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
