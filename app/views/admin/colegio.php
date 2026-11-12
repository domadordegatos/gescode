<?php
$pageTitle   = 'Configuración — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Inicio',        'url' => APP_URL . '/dashboard'],
    ['label' => 'Configuración', 'url' => ''],
];
ob_start();

$logoUrl = !empty($colegio['logo'])
    ? APP_URL . '/uploads/imagenes/' . e($colegio['logo'])
    : null;
$marcaAguaUrl = !empty($colegio['marca_agua_boletin'])
    ? APP_URL . '/uploads/imagenes/' . e($colegio['marca_agua_boletin'])
    : null;
?>

<style>
.cfg-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
}
.cfg-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
}
.cfg-section-header h2 {
    font-size: .95rem;
    font-weight: 800;
    color: var(--text);
    margin: 0;
}
.cfg-section-header .cfg-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cfg-section-header .cfg-icon i {
    font-size: 1.1rem;
    color: var(--primary);
}
.cfg-section-body { padding: 24px; }

/* Logo upload */
.logo-upload-area {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}
.logo-preview-box {
    width: 120px;
    height: 80px;
    border: 2px dashed var(--border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-2);
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}
.logo-preview-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.logo-preview-box .no-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: var(--text-muted);
    font-size: .72rem;
    font-weight: 600;
}
.logo-upload-controls { display: flex; flex-direction: column; gap: 10px; }
.logo-upload-controls label.upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 8px;
    font-size: .83rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}
.logo-upload-controls label.upload-btn:hover { background: var(--primary); color: #fff; }
.logo-upload-hint { font-size: .75rem; color: var(--text-muted); }

/* Color picker row */
.color-pair { display: flex; gap: 20px; flex-wrap: wrap; }
.color-field { display: flex; flex-direction: column; gap: 6px; }
.color-input-row { display: flex; align-items: center; gap: 8px; }
.color-swatch {
    width: 44px;
    height: 38px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 2px;
    cursor: pointer;
}

/* Preview bar */
.color-preview-bar {
    margin-top: 18px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.color-preview-bar-inner {
    height: 10px;
    background: linear-gradient(90deg, var(--prev-primary) 0%, var(--prev-secondary) 100%);
    transition: background .3s;
}
.color-preview-sidebar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--surface-2);
}
.color-preview-sidebar .dot {
    width: 28px; height: 28px; border-radius: 8px;
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Configuración del colegio</h1>
        <p class="page-subtitle">Datos institucionales, apariencia y parámetros del sistema.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/colegio" enctype="multipart/form-data" id="cfg-form">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <input type="hidden" name="eliminar_logo" value="0" id="eliminar-logo-input">

    <!-- ── 1. IDENTIDAD ────────────────────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-school-outline"></i></div>
            <div>
                <h2>Identidad institucional</h2>
            </div>
        </div>
        <div class="cfg-section-body">

            <!-- Logo -->
            <div class="form-group">
                <label>Logo institucional</label>
                <div class="logo-upload-area">
                    <div class="logo-preview-box" id="logo-preview-box">
                        <?php if ($logoUrl): ?>
                            <img src="<?= $logoUrl ?>" id="logo-preview-img" alt="Logo actual">
                        <?php else: ?>
                            <div class="no-logo" id="logo-no-logo">
                                <i class="mdi mdi-image-outline" style="font-size:1.8rem"></i>
                                <span>Sin logo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="logo-upload-controls">
                        <label class="upload-btn" for="logo-file-input">
                            <i class="mdi mdi-upload-outline"></i>
                            <?= $logoUrl ? 'Cambiar logo' : 'Subir logo' ?>
                        </label>
                        <input type="file" name="logo" id="logo-file-input"
                               accept="image/png,image/jpeg,image/webp,image/svg+xml"
                               style="display:none">
                        <?php if ($logoUrl): ?>
                        <button type="button" class="btn-danger-outline btn-sm" id="btn-eliminar-logo"
                                style="font-size:.78rem;padding:6px 12px">
                            <i class="mdi mdi-trash-can-outline"></i> Eliminar logo
                        </button>
                        <?php endif; ?>
                        <span class="logo-upload-hint">PNG, JPG, WebP o SVG · Máx. 2 MB<br>Recomendado: fondo transparente, min. 200×100 px</span>
                    </div>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Nombre completo <span class="required">*</span></label>
                    <input type="text" name="nombre" class="form-input"
                           value="<?= e($colegio['nombre'] ?? '') ?>" required
                           placeholder="Ej: Colegio San Mateo Apóstol">
                </div>
                <div class="form-group">
                    <label>Nombre corto</label>
                    <input type="text" name="nombre_corto" class="form-input"
                           value="<?= e($colegio['nombre_corto'] ?? '') ?>"
                           placeholder="Ej: San Mateo">
                    <small class="form-hint">Se muestra en el sidebar y encabezados</small>
                </div>
            </div>
            <div class="form-group">
                <label>Eslogan / lema institucional</label>
                <input type="text" name="slogan" class="form-input"
                       value="<?= e($colegio['slogan'] ?? '') ?>"
                       placeholder="Ej: Formando líderes para el futuro"
                       maxlength="200">
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Rector / Director (firma en PDF)</label>
                    <input type="text" name="rector" class="form-input"
                           value="<?= e($colegio['rector'] ?? '') ?>"
                           placeholder="Ej: Mg. Carlos Rodríguez">
                </div>
                <div class="form-group">
                    <label>Cargo del firmante</label>
                    <input type="text" name="rector_cargo" class="form-input"
                           value="<?= e($colegio['rector_cargo'] ?? 'Rector(a)') ?>"
                           placeholder="Ej: Rector(a)">
                </div>
            </div>
            <small class="form-hint">Estos dos datos aparecen en el espacio de firma de los boletines y constancias en PDF.</small>
        </div>
    </div>

    <!-- ── 2. INFORMACIÓN OFICIAL ───────────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-file-document-outline"></i></div>
            <div>
                <h2>Información oficial</h2>
            </div>
        </div>
        <div class="cfg-section-body">
            <div class="form-row-2">
                <div class="form-group">
                    <label>NIT</label>
                    <input type="text" name="nit" class="form-input"
                           value="<?= e($colegio['nit'] ?? '') ?>"
                           placeholder="900.000.000-0">
                </div>
                <div class="form-group">
                    <label>Código DANE</label>
                    <input type="text" name="dane" class="form-input"
                           value="<?= e($colegio['dane'] ?? '') ?>"
                           placeholder="Ej: 150001000101" maxlength="12">
                    <small class="form-hint">Código de 12 dígitos asignado por el MEN</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2b. BOLETINES Y CONSTANCIAS ─────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-file-pdf-box"></i></div>
            <div>
                <h2>Boletines y constancias (PDF)</h2>
            </div>
        </div>
        <div class="cfg-section-body">
            <div class="form-group">
                <label>Marca de agua (opcional)</label>
                <div class="logo-upload-area">
                    <div class="logo-preview-box" id="marca-preview-box">
                        <?php if ($marcaAguaUrl): ?>
                            <img src="<?= $marcaAguaUrl ?>" id="marca-preview-img" alt="Marca de agua actual">
                        <?php else: ?>
                            <div class="no-logo" id="marca-no-logo">
                                <i class="mdi mdi-water-outline" style="font-size:1.8rem"></i>
                                <span>Sin marca de agua</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="logo-upload-controls">
                        <label class="upload-btn" for="marca-file-input">
                            <i class="mdi mdi-upload-outline"></i>
                            <?= $marcaAguaUrl ? 'Cambiar imagen' : 'Subir imagen' ?>
                        </label>
                        <input type="file" name="marca_agua" id="marca-file-input"
                               accept="image/png,image/jpeg,image/webp"
                               style="display:none">
                        <?php if ($marcaAguaUrl): ?>
                        <button type="button" class="btn-danger-outline btn-sm" id="btn-eliminar-marca"
                                style="font-size:.78rem;padding:6px 12px">
                            <i class="mdi mdi-trash-can-outline"></i> Eliminar marca de agua
                        </button>
                        <?php endif; ?>
                        <span class="logo-upload-hint">PNG, JPG o WebP · Máx. 2 MB<br>Se imprime tenue de fondo en boletines y constancias</span>
                    </div>
                </div>
            </div>
            <input type="hidden" name="eliminar_marca_agua" value="0" id="eliminar-marca-input">
        </div>
    </div>

    <!-- ── 3. CONTACTO Y UBICACIÓN ─────────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-map-marker-outline"></i></div>
            <div>
                <h2>Contacto y ubicación</h2>
            </div>
        </div>
        <div class="cfg-section-body">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Correo institucional</label>
                    <input type="email" name="email" class="form-input"
                           value="<?= e($colegio['email'] ?? '') ?>"
                           placeholder="contacto@colegio.edu.co">
                </div>
                <div class="form-group">
                    <label>Sitio web</label>
                    <input type="url" name="sitio_web" class="form-input"
                           value="<?= e($colegio['sitio_web'] ?? '') ?>"
                           placeholder="https://www.colegio.edu.co">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Teléfono principal</label>
                    <input type="tel" name="telefono" class="form-input"
                           value="<?= e($colegio['telefono'] ?? '') ?>"
                           placeholder="601 000 0000">
                </div>
                <div class="form-group">
                    <label>Teléfono secundario</label>
                    <input type="tel" name="telefono2" class="form-input"
                           value="<?= e($colegio['telefono2'] ?? '') ?>"
                           placeholder="300 000 0000">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Ciudad / Municipio</label>
                    <input type="text" name="ciudad" class="form-input"
                           value="<?= e($colegio['ciudad'] ?? '') ?>"
                           placeholder="Bogotá D.C.">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-input"
                           value="<?= e($colegio['direccion'] ?? '') ?>"
                           placeholder="Cra. 7 # 32-16">
                </div>
            </div>
        </div>
    </div>

    <!-- ── 4. APARIENCIA ───────────────────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-palette-outline"></i></div>
            <div>
                <h2>Colores institucionales</h2>
            </div>
        </div>
        <div class="cfg-section-body">
            <p class="text-muted text-sm" style="margin-bottom:20px">
                Estos colores se aplican en toda la plataforma: barra lateral, botones, encabezados y acentos.
            </p>

            <div class="color-pair">
                <div class="form-group color-field">
                    <label>Color primario</label>
                    <div class="color-input-row">
                        <input type="color" name="color_primario" id="pick-primary" class="color-swatch"
                               value="<?= e($colegio['color_primario'] ?? '#0E0EB3') ?>"
                               oninput="onPickerChange(this,'txt-primary')">
                        <input type="text" id="txt-primary" class="form-input"
                               value="<?= e($colegio['color_primario'] ?? '#0E0EB3') ?>"
                               maxlength="7" style="width:100px;font-family:monospace"
                               oninput="onHexChange(this,'pick-primary')">
                    </div>
                    <small class="form-hint">Sidebar, botones principales</small>
                </div>
                <div class="form-group color-field">
                    <label>Color secundario</label>
                    <div class="color-input-row">
                        <input type="color" name="color_secundario" id="pick-secondary" class="color-swatch"
                               value="<?= e($colegio['color_secundario'] ?? '#1a1a6e') ?>"
                               oninput="onPickerChange(this,'txt-secondary')">
                        <input type="text" id="txt-secondary" class="form-input"
                               value="<?= e($colegio['color_secundario'] ?? '#1a1a6e') ?>"
                               maxlength="7" style="width:100px;font-family:monospace"
                               oninput="onHexChange(this,'pick-secondary')">
                    </div>
                    <small class="form-hint">Degradados, acentos</small>
                </div>
            </div>

            <!-- Live preview -->
            <div class="color-preview-bar" id="color-preview-bar">
                <div class="color-preview-bar-inner" id="color-preview-gradient"></div>
                <div class="color-preview-sidebar" id="color-preview-sidebar">
                    <div class="dot" id="dot-primary" style="background:<?= e($colegio['color_primario'] ?? '#0E0EB3') ?>"></div>
                    <div class="dot" id="dot-secondary" style="background:<?= e($colegio['color_secundario'] ?? '#1a1a6e') ?>"></div>
                    <span style="font-size:.78rem;color:var(--text-muted)">Vista previa de los colores institucionales</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 5. ESCALA DE NOTAS ─────────────────────────────────── -->
    <div class="cfg-section">
        <div class="cfg-section-header">
            <div class="cfg-icon"><i class="mdi mdi-numeric"></i></div>
            <div>
                <h2>Escala de notas</h2>
            </div>
        </div>
        <div class="cfg-section-body">
            <p class="text-muted text-sm" style="margin-bottom:18px">
                Define el rango permitido para calificaciones. El sistema rechazará valores fuera de este rango.
            </p>
            <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end">
                <div class="form-group" style="margin-bottom:0">
                    <label>Nota mínima</label>
                    <input type="number" name="nota_min" class="form-input" style="width:120px"
                           min="0" max="99" step="0.1"
                           value="<?= number_format((float)($colegio['nota_min'] ?? 1.0), 1) ?>" required>
                    <small class="form-hint">Ej: 1.0</small>
                </div>
                <div style="font-size:1.5rem;color:var(--text-muted);padding-bottom:20px">–</div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Nota máxima</label>
                    <input type="number" name="nota_max" class="form-input" style="width:120px"
                           min="1" max="100" step="0.1"
                           value="<?= number_format((float)($colegio['nota_max'] ?? 10.0), 1) ?>" required>
                    <small class="form-hint">Ej: 10.0</small>
                </div>
                <div id="escala-preview" style="padding-bottom:20px"></div>
            </div>
            <div class="info-box-blue" style="margin-top:18px">
                <i class="mdi mdi-information-outline"></i>
                <span>Al cambiar este rango actualiza también los rangos de <strong>Desempeños</strong> para que coincidan.</span>
            </div>
        </div>
    </div>

    <?php if (Auth::rol() === 'soporte'): ?>
    <!-- ── 6. MÓDULOS DE PAGO (solo soporte) ─────────────────── -->
    <div class="cfg-section" style="border:2px solid #FDE68A;background:#FFFBEB">
        <div class="cfg-section-header" style="background:#FEF9C3;border-bottom-color:#FDE68A">
            <div class="cfg-icon" style="background:#FEF3C7">
                <i class="mdi mdi-star-circle-outline" style="color:#D97706"></i>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <h2>Módulos de pago</h2>
                <span style="font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:99px;background:#D97706;color:#fff">SOPORTE</span>
            </div>
        </div>
        <div class="cfg-section-body">
            <p class="text-muted text-sm" style="margin-bottom:18px">
                Activa o desactiva funciones premium por institución.
            </p>
            <label style="display:flex;align-items:center;gap:14px;cursor:pointer">
                <div style="position:relative;width:44px;height:24px;flex-shrink:0">
                    <input type="checkbox" name="notificaciones_email" value="1"
                           <?= !empty($colegio['notificaciones_email']) ? 'checked' : '' ?>
                           style="display:none" id="toggle-noti">
                    <div id="toggle-noti-track"
                         style="width:44px;height:24px;border-radius:12px;
                                background:<?= !empty($colegio['notificaciones_email']) ? 'var(--primary)' : '#D1D5DB' ?>;
                                transition:background .2s;cursor:pointer"
                         onclick="toggleNoti()">
                        <div id="toggle-noti-thumb"
                             style="width:20px;height:20px;border-radius:50%;background:#fff;margin:2px;
                                    transform:<?= !empty($colegio['notificaciones_email']) ? 'translateX(20px)' : 'translateX(0)' ?>;
                                    transition:transform .2s"></div>
                    </div>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.92rem">Notificaciones por correo electrónico</div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                        Permite que los usuarios configuren qué eventos les llegan al correo. Requiere plan activo con Resend.
                    </div>
                </div>
            </label>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── FUNCIONES FUTURAS ──────────────────────────────────── -->
    <div class="cfg-section" style="border:2px solid #DDD6FE;background:#F5F3FF">
        <div class="cfg-section-header" style="background:#EDE9FE;border-bottom-color:#DDD6FE">
            <div class="cfg-icon" style="background:#EDE9FE">
                <i class="mdi mdi-account-school-outline" style="color:#7C3AED"></i>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <h2>Cuentas de acceso para estudiantes</h2>
                <span style="font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:99px;background:#7C3AED;color:#fff">PRÓXIMAMENTE</span>
            </div>
        </div>
        <div class="cfg-section-body">
            <p class="text-muted text-sm" style="margin-bottom:18px">
                Permite definir si los estudiantes podrán tener su propio usuario para iniciar sesión
                en la plataforma (mensajería, tareas, notas, etc.). <strong>Esta función aún no está
                operativa</strong>: aunque se active aquí, los estudiantes seguirán sin poder ingresar
                hasta que el módulo se habilite por completo. Mientras tanto, los acudientes pueden
                ver y responder en nombre del estudiante desde la sección "Mensajes" en cada hijo.
            </p>
            <label style="display:flex;align-items:center;gap:14px;cursor:pointer">
                <div style="position:relative;width:44px;height:24px;flex-shrink:0">
                    <input type="checkbox" name="permite_cuentas_estudiantes" value="1"
                           <?= !empty($colegio['permite_cuentas_estudiantes']) ? 'checked' : '' ?>
                           style="display:none" id="toggle-est">
                    <div id="toggle-est-track"
                         style="width:44px;height:24px;border-radius:12px;
                                background:<?= !empty($colegio['permite_cuentas_estudiantes']) ? '#7C3AED' : '#D1D5DB' ?>;
                                transition:background .2s;cursor:pointer"
                         onclick="toggleEstudiantes()">
                        <div id="toggle-est-thumb"
                             style="width:20px;height:20px;border-radius:50%;background:#fff;margin:2px;
                                    transform:<?= !empty($colegio['permite_cuentas_estudiantes']) ? 'translateX(20px)' : 'translateX(0)' ?>;
                                    transition:transform .2s"></div>
                    </div>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.92rem">Habilitar cuentas de estudiante</div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                        Marca esta opción para dejar la preferencia lista; el acceso real de los
                        estudiantes se activará en una próxima actualización.
                    </div>
                </div>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Guardar configuración
        </button>
    </div>
</form>

<script>
// ── Logo preview & delete ─────────────────────────────────
document.getElementById('logo-file-input').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const box = document.getElementById('logo-preview-box');
        box.innerHTML = '<img src="' + e.target.result + '" id="logo-preview-img" alt="Logo nuevo" style="max-width:100%;max-height:100%;object-fit:contain">';
    };
    reader.readAsDataURL(file);
    document.getElementById('eliminar-logo-input').value = '0';
});

const btnEliminar = document.getElementById('btn-eliminar-logo');
if (btnEliminar) {
    btnEliminar.addEventListener('click', function() {
        if (!confirm('¿Eliminar el logo actual? Se usará el logo por defecto.')) return;
        document.getElementById('eliminar-logo-input').value = '1';
        const box = document.getElementById('logo-preview-box');
        box.innerHTML = '<div class="no-logo"><i class="mdi mdi-image-outline" style="font-size:1.8rem"></i><span>Sin logo</span></div>';
        document.getElementById('logo-file-input').value = '';
        this.remove();
    });
}

// ── Marca de agua preview & delete ────────────────────────
document.getElementById('marca-file-input').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const box = document.getElementById('marca-preview-box');
        box.innerHTML = '<img src="' + e.target.result + '" id="marca-preview-img" alt="Marca de agua nueva" style="max-width:100%;max-height:100%;object-fit:contain">';
    };
    reader.readAsDataURL(file);
    document.getElementById('eliminar-marca-input').value = '0';
});

const btnEliminarMarca = document.getElementById('btn-eliminar-marca');
if (btnEliminarMarca) {
    btnEliminarMarca.addEventListener('click', function() {
        if (!confirm('¿Eliminar la marca de agua actual?')) return;
        document.getElementById('eliminar-marca-input').value = '1';
        const box = document.getElementById('marca-preview-box');
        box.innerHTML = '<div class="no-logo"><i class="mdi mdi-water-outline" style="font-size:1.8rem"></i><span>Sin marca de agua</span></div>';
        document.getElementById('marca-file-input').value = '';
        this.remove();
    });
}

// ── Colores ───────────────────────────────────────────────
function onPickerChange(picker, txtId) {
    document.getElementById(txtId).value = picker.value;
    updateColorPreview();
}
function onHexChange(txt, pickerId) {
    if (/^#[0-9a-fA-F]{6}$/.test(txt.value)) {
        document.getElementById(pickerId).value = txt.value;
        updateColorPreview();
    }
}
function updateColorPreview() {
    const p = document.getElementById('pick-primary').value;
    const s = document.getElementById('pick-secondary').value;
    document.getElementById('color-preview-gradient').style.background =
        'linear-gradient(90deg,' + p + ' 0%,' + s + ' 100%)';
    document.getElementById('dot-primary').style.background   = p;
    document.getElementById('dot-secondary').style.background = s;
}
document.addEventListener('DOMContentLoaded', updateColorPreview);

// ── Toggle notificaciones ─────────────────────────────────
function toggleNoti() {
    const chk   = document.getElementById('toggle-noti');
    const track = document.getElementById('toggle-noti-track');
    const thumb = document.getElementById('toggle-noti-thumb');
    chk.checked = !chk.checked;
    track.style.background = chk.checked ? 'var(--primary)' : '#D1D5DB';
    thumb.style.transform  = chk.checked ? 'translateX(20px)' : 'translateX(0)';
}

function toggleEstudiantes() {
    const chk   = document.getElementById('toggle-est');
    const track = document.getElementById('toggle-est-track');
    const thumb = document.getElementById('toggle-est-thumb');
    chk.checked = !chk.checked;
    track.style.background = chk.checked ? '#7C3AED' : '#D1D5DB';
    thumb.style.transform  = chk.checked ? 'translateX(20px)' : 'translateX(0)';
}

// ── Escala de notas preview ───────────────────────────────
function actualizarPreview() {
    const min = parseFloat(document.querySelector('[name=nota_min]').value) || 0;
    const max = parseFloat(document.querySelector('[name=nota_max]').value) || 10;
    const el  = document.getElementById('escala-preview');
    el.innerHTML = (min < max)
        ? '<div class="periodo-pill active" style="font-size:.9rem">' + min.toFixed(1) + ' — ' + max.toFixed(1) + '</div>'
        : '<div style="color:var(--danger);font-size:.82rem;font-weight:700"><i class="mdi mdi-alert"></i> Rango inválido</div>';
}
document.querySelectorAll('[name=nota_min],[name=nota_max]').forEach(el =>
    el.addEventListener('input', actualizarPreview));
document.addEventListener('DOMContentLoaded', actualizarPreview);
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
