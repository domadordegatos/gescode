<?php
$pageTitle   = 'Redactar mensaje — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio',   'url' => APP_URL . '/dashboard'],
    ['label' => 'Mensajes', 'url' => APP_URL . '/comunicaciones'],
    ['label' => 'Redactar', 'url' => ''],
];
$noLeidos  = 0;
$extraHead = '<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="' . APP_URL . '/assets/js/quill-utils.js"></script>';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Redactar mensaje</h1>
    <a href="<?= APP_URL ?>/comunicaciones" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Volver
    </a>
</div>

<form method="POST" action="<?= APP_URL ?>/comunicaciones/enviar"
      enctype="multipart/form-data" id="compose-form">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <input type="hidden" name="cuerpo" id="cuerpo-hidden">
    <?php if (!empty($estudiante_relacionado_id)): ?>
    <input type="hidden" name="estudiante_relacionado_id" value="<?= (int)$estudiante_relacionado_id ?>">
    <?php endif; ?>
    <?php if (!empty($hilo_id)): ?>
    <input type="hidden" name="hilo_id" value="<?= (int)$hilo_id ?>">
    <?php endif; ?>
    <select name="destinatarios[]" multiple id="dest-select" style="display:none" aria-hidden="true"></select>

    <div class="compose-wrap">

        <!-- ══════════════════════════════════════════════════════
             SECCIÓN SUPERIOR: Destinatarios
        ══════════════════════════════════════════════════════ -->
        <div class="compose-card <?= !empty($reply_lock) ? 'compose-card--locked' : '' ?>">

            <!-- Chips de destinatarios seleccionados -->
            <div class="para-row <?= !empty($reply_lock) ? 'para-row--locked' : '' ?>"
                 <?= empty($reply_lock) ? 'id="para-row-trigger" style="cursor:pointer" onclick="toggleContactsPanel(event)"' : '' ?>>
                <label class="para-label" style="<?= empty($reply_lock) ? 'cursor:pointer' : '' ?>"><?= !empty($reply_lock) ? 'Respondiendo a' : 'Para' ?> <span class="required">*</span></label>
                <div class="recipient-chips" id="recipient-chips">
                    <span id="chips-placeholder" style="color:var(--text-muted);font-size:.83rem;pointer-events:none">
                        Selecciona destinatarios abajo o busca por nombre
                    </span>
                </div>
                <?php if (!empty($reply_lock)): ?>
                <span class="reply-lock-inline">
                    <i class="mdi mdi-lock-outline"></i>
                    El destinatario no se puede cambiar.
                </span>
                <?php else: ?>
                <i class="mdi mdi-chevron-down" id="para-chevron" style="color:var(--text-muted);transition:transform .2s;flex-shrink:0"></i>
                <?php endif; ?>
            </div>

            <?php if (empty($reply_lock)): ?>
            <!-- Panel desplegable de contactos -->
            <div id="contacts-panel" style="display:none;border-top:1px solid var(--border);margin:0 -22px;padding:0 22px 12px">
                <!-- Barra de búsqueda + tabs -->
                <div class="contacts-toolbar" style="padding-top:12px">
                    <div class="contacts-search-wrap">
                        <i class="mdi mdi-magnify contacts-search-icon"></i>
                        <input type="text" id="contact-search" class="contacts-search"
                               placeholder="Buscar por nombre…" autocomplete="off">
                    </div>
                    <?php if (empty($restringido)): ?>
                    <div class="ctabs">
                        <button type="button" class="ctab active" data-tab="grado">
                            <i class="mdi mdi-school-outline"></i> Por grado
                        </button>
                        <button type="button" class="ctab" data-tab="rol">
                            <i class="mdi mdi-account-group-outline"></i> Por área
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Browser de contactos -->
                <div class="contacts-browser">
                    <div id="view-grade-list"></div>
                    <div id="view-grade-detail" style="display:none"></div>
                    <div id="view-rol" style="display:none"></div>
                    <div id="view-search" style="display:none"></div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /compose-card contactos -->

        <!-- ══════════════════════════════════════════════════════
             SECCIÓN INFERIOR: Contenido del mensaje
        ══════════════════════════════════════════════════════ -->
        <div class="compose-card">

            <!-- Asunto -->
            <div class="form-group">
                <label for="asunto">Asunto <span class="required">*</span></label>
                <input type="text" id="asunto" name="asunto" class="form-input"
                       placeholder="Escribe el asunto del mensaje"
                       value="<?= htmlspecialchars($reply_asunto ?? '') ?>" required>
            </div>

            <?php if (empty($restringido) && !empty($permite_mensajes_ocultos)): ?>
            <!-- Visibilidad de destinatarios (solo cuando se envía a varios) -->
            <div class="form-group">
                <label for="visibilidad">Visibilidad de destinatarios</label>
                <select id="visibilidad" name="visibilidad" class="form-input">
                    <option value="normal">Todos pueden ver a quién más se envió</option>
                    <option value="oculto">Nadie ve la lista de destinatarios</option>
                    <option value="solo_admin">Solo administrativos ven la lista completa</option>
                </select>
                <small class="form-hint">
                    Aplica cuando el mensaje va a más de una persona. Tú (el remitente) siempre ves la lista completa.
                </small>
            </div>
            <?php endif; ?>

            <!-- Editor rico -->
            <div class="form-group">
                <label>Mensaje <span class="required">*</span></label>
                <div id="quill-editor"></div>
                <p id="quill-word-count" style="font-size:.75rem;color:#9ca3af;text-align:right;margin-top:4px;">0 palabras · 0 caracteres</p>
            </div>

            <!-- Cita del historial (estilo Gmail) -->
            <div id="cita-wrap" class="cita-wrap" style="display:none">
                <button type="button" class="cita-toggle" id="cita-toggle" onclick="toggleCita()">
                    <i class="mdi mdi-dots-horizontal"></i> Mostrar historial citado
                </button>
                <div id="cita-historial" class="cita-historial" style="display:none"></div>
            </div>

            <!-- Adjuntar información -->
            <div class="adjinfo-section">
                <button type="button" class="adjinfo-main-toggle" id="adjinfo-main-btn" onclick="toggleAdjPanel()">
                    <i class="mdi mdi-paperclip"></i>
                    <span>Adjuntar información</span>
                    <i class="mdi mdi-chevron-down" id="adjinfo-main-chevron"></i>
                </button>
                <div class="adjinfo-panel" id="adjinfo-panel" style="display:none">

                <!-- YouTube / Vimeo -->
                <div class="adjinfo-option">
                    <button type="button" class="adjinfo-toggle" onclick="toggleAdj('adj-video', this)">
                        <i class="mdi mdi-youtube"></i>
                        <span>YouTube o Vimeo</span>
                        <i class="mdi mdi-chevron-down adjinfo-chevron"></i>
                    </button>
                    <div class="adjinfo-body" id="adj-video">
                        <p class="adjinfo-hint">Pega la URL del video para insertarlo en el mensaje.</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <input type="text" id="video-url-input" class="form-input" style="flex:1;min-width:200px"
                                   placeholder="https://www.youtube.com/watch?v=...">
                            <button type="button" class="btn-primary" onclick="insertarVideo()">
                                <i class="mdi mdi-plus"></i> Insertar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HTML -->
                <div class="adjinfo-option">
                    <button type="button" class="adjinfo-toggle" onclick="toggleAdj('adj-html', this)">
                        <i class="mdi mdi-code-tags"></i>
                        <span>Texto HTML</span>
                        <i class="mdi mdi-chevron-down adjinfo-chevron"></i>
                    </button>
                    <div class="adjinfo-body" id="adj-html">
                        <p class="adjinfo-hint">Escribe o pega código HTML para insertarlo directamente en el mensaje.</p>
                        <textarea id="html-input" class="form-textarea" rows="4"
                                  placeholder="<p>Tu HTML aquí...</p>"></textarea>
                        <button type="button" class="btn-primary" style="margin-top:8px" onclick="insertarHtml()">
                            <i class="mdi mdi-plus"></i> Insertar HTML
                        </button>
                    </div>
                </div>

                <!-- Archivos -->
                <div class="adjinfo-option">
                    <button type="button" class="adjinfo-toggle" onclick="toggleAdj('adj-archivos', this)">
                        <i class="mdi mdi-paperclip"></i>
                        <span>Archivos</span>
                        <i class="mdi mdi-chevron-down adjinfo-chevron"></i>
                    </button>
                    <div class="adjinfo-body" id="adj-archivos">
                        <p class="adjinfo-hint">Adjunta archivos al mensaje (PDF, Office, imágenes, ZIP · máx. 10 MB).</p>
                        <input type="file" name="adjuntos[]" id="adjunto-input" multiple style="display:none"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip">
                        <label for="adjunto-input" class="adjunto-btn">
                            <i class="mdi mdi-folder-open-outline"></i> Seleccionar archivos
                        </label>
                        <ul class="adjunto-list" id="adjunto-list"></ul>
                    </div>
                </div>

                <!-- Google Drive -->
                <div class="adjinfo-option">
                    <button type="button" class="adjinfo-toggle" onclick="toggleAdj('adj-drive', this)">
                        <svg width="16" height="16" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0">
                            <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                            <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0-1.2 4.5h27.5z" fill="#00ac47"/>
                            <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.5l5.85 11.5z" fill="#ea4335"/>
                            <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                            <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                            <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 27h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                        </svg>
                        <span>Documento de Google Drive</span>
                        <i class="mdi mdi-chevron-down adjinfo-chevron"></i>
                    </button>
                    <div class="adjinfo-body" id="adj-drive">
                        <p class="adjinfo-hint">Pega el enlace de un documento de Google Drive para insertarlo como enlace en el mensaje.</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <input type="text" id="drive-url-input" class="form-input" style="flex:1;min-width:200px"
                                   placeholder="https://drive.google.com/...">
                            <button type="button" class="btn-primary" onclick="insertarDrive()">
                                <i class="mdi mdi-plus"></i> Insertar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formulario (próximamente) -->
                <div class="adjinfo-option adjinfo-option--disabled">
                    <div class="adjinfo-toggle adjinfo-toggle--disabled">
                        <i class="mdi mdi-form-select"></i>
                        <span>Formulario</span>
                        <span class="adjinfo-soon">Próximamente</span>
                    </div>
                </div>

                </div><!-- /adjinfo-panel -->
            </div><!-- /adjinfo-section -->

            <!-- Acciones de envío -->
            <div style="display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
                <a href="<?= APP_URL ?>/comunicaciones" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary" id="send-btn">
                    <i class="mdi mdi-send"></i> Enviar mensaje
                </button>
            </div>

        </div><!-- /compose-card mensaje -->

    </div><!-- /compose-wrap -->
</form>

<script>
// ── Datos desde PHP ──────────────────────────────────────────────
const DATA       = <?= $contactos_json ?>;
const GRADES     = DATA.grades;   // [{grado, nivel, director, estudiantes:[{id,nombre,apellido,padres:[]}]}]
const OTROS      = DATA.otros;    // [{id,nombre,apellido,rol,materias?}]
const REPLY_ID   = <?= isset($reply_id) && $reply_id ? (int)$reply_id : 'null' ?>;
const RESTRINGIDO= <?= !empty($restringido) ? 'true' : 'false' ?>;
const REPLY_CITA = <?= json_encode($reply_cuerpo ?? '', JSON_UNESCAPED_UNICODE) ?>;
const REPLY_LOCK = <?= !empty($reply_lock) ? 'true' : 'false' ?>;
const REPLY_DEST = <?= json_encode($reply_dest ?? null, JSON_UNESCAPED_UNICODE) ?>;
const PRESEL     = <?= json_encode($presel ?? [], JSON_UNESCAPED_UNICODE) ?>;

// ── Estado ───────────────────────────────────────────────────────
const selected = new Map();   // id → {id, nombre, apellido, rol}
let currentGrade = null;      // nombre del grado en drill-down

// ── DOM refs ─────────────────────────────────────────────────────
const chipsWrap   = document.getElementById('recipient-chips');
const placeholder = document.getElementById('chips-placeholder');
const destSelect  = document.getElementById('dest-select');
const searchInput = document.getElementById('contact-search');
const vGradeList  = document.getElementById('view-grade-list');
const vGradeDet   = document.getElementById('view-grade-detail');
const vRol        = document.getElementById('view-rol');
const vSearch     = document.getElementById('view-search');
const ctabs       = document.querySelectorAll('.ctab');

// ── Utils ────────────────────────────────────────────────────────
function slug(s) {
    return String(s).toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
}
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function rolLabel(r) {
    return {estudiante:'Estudiante',padre:'Familiar',docente:'Docente',
            admin:'Administrativo',soporte:'Soporte'}[r] ?? r;
}
function showOnly(el) {
    [vGradeList, vGradeDet, vRol, vSearch].forEach(v => v.style.display = 'none');
    el.style.display = '';
}

// ── Gestión de selección ─────────────────────────────────────────
function addC(c) {
    if (selected.has(c.id)) return;
    selected.set(c.id, c);
    renderChips();
    syncSelect();
    refreshCheckboxes();
}
function removeC(id) {
    selected.delete(id);
    renderChips();
    syncSelect();
    refreshCheckboxes();
}
function toggleC(c) {
    selected.has(c.id) ? removeC(c.id) : addC(c);
}

function renderChips() {
    chipsWrap.querySelectorAll('.chip').forEach(ch => ch.remove());
    placeholder.style.display = selected.size ? 'none' : '';
    selected.forEach((c, id) => {
        const chip = document.createElement('span');
        chip.className = 'chip';
        const chipLabel = c.apellido ? c.apellido + ', ' + c.nombre : c.nombre;
        chip.innerHTML = esc(chipLabel) + (REPLY_LOCK
            ? ' <i class="mdi mdi-lock-outline" style="font-size:14px;opacity:.6"></i>'
            : ` <button type="button" class="chip-remove" onclick="removeC(${id})">×</button>`);
        chipsWrap.appendChild(chip);
    });
}
function syncSelect() {
    destSelect.innerHTML = '';
    selected.forEach((_, id) => {
        const o = document.createElement('option');
        o.value = id; o.selected = true;
        destSelect.appendChild(o);
    });
}

// Actualiza checkboxes visibles para reflejar el estado de `selected`
function refreshCheckboxes() {
    document.querySelectorAll('.ck-contact[data-id]').forEach(cb => {
        cb.checked = selected.has(+cb.dataset.id);
    });
    refreshBulkCounters();
}

// Actualiza contadores "X de N" en la vista actual
function refreshBulkCounters() {
    GRADES.forEach(g => {
        const selEst = g.estudiantes.filter(e => selected.has(e.id)).length;
        const selFam = g.estudiantes.flatMap(e => e.padres).filter(p => selected.has(p.id)).length;
        const totalFam = g.estudiantes.flatMap(e => e.padres).length;
        const el1 = document.getElementById(`cnt-est-${slug(g.grado)}`);
        const el2 = document.getElementById(`cnt-fam-${slug(g.grado)}`);
        if (el1) el1.textContent = `${selEst} de ${g.estudiantes.length} estudiante${g.estudiantes.length!==1?'s':''}`;
        if (el2) el2.textContent = `${selFam} de ${totalFam} familiar${totalFam!==1?'es':''}`;
        // Bulk checkboxes
        const ckEst = document.getElementById(`ckb-est-${slug(g.grado)}`);
        const ckFam = document.getElementById(`ckb-fam-${slug(g.grado)}`);
        if (ckEst) ckEst.indeterminate = selEst > 0 && selEst < g.estudiantes.length;
        if (ckEst) ckEst.checked = selEst === g.estudiantes.length && g.estudiantes.length > 0;
        if (ckFam) { const tf = totalFam;
            ckFam.indeterminate = selFam > 0 && selFam < tf;
            ckFam.checked = tf > 0 && selFam === tf;
        }
    });
}

// ── Vista: lista de grados ───────────────────────────────────────
function buildGradeList() {
    if (!GRADES.length) {
        vGradeList.innerHTML = `<div class="no-results">
            <i class="mdi mdi-school-outline" style="font-size:32px;display:block;opacity:.3;margin-bottom:8px"></i>
            No hay estudiantes matriculados en el año activo aún
        </div>`;
        return;
    }
    let html = '<div class="grade-cards">';
    GRADES.forEach(g => {
        const totalFam = g.estudiantes.flatMap(e => e.padres).length;
        html += `
        <div class="grade-card">
            <div class="grade-card-head">
                <i class="mdi mdi-book-open-variant" style="color:var(--primary)"></i>
                <strong>${esc(g.grado)}</strong>
                <button type="button" class="lista-btn" onclick="showDetail('${esc(g.grado)}')">
                    Lista <i class="mdi mdi-chevron-right"></i>
                </button>
            </div>
            <div class="grade-card-body">
                ${g.director ? `<label class="ck-row">
                    <input type="checkbox" class="ck-contact" data-id="${g.director.id}"
                           onchange="toggleC(${JSON.stringify(g.director).replace(/"/g,'&quot;')})">
                    <span>${esc(g.director.apellido + ', ' + g.director.nombre)}<em> · Director de curso</em></span>
                </label>` : ''}
                ${g.estudiantes.length ? `<label class="ck-row">
                    <input type="checkbox" id="ckb-est-${slug(g.grado)}"
                           onchange="toggleBulk('est','${esc(g.grado)}',this)">
                    <span id="cnt-est-${slug(g.grado)}">0 de ${g.estudiantes.length} estudiante${g.estudiantes.length!==1?'s':''}</span>
                </label>` : ''}
                ${totalFam ? `<label class="ck-row">
                    <input type="checkbox" id="ckb-fam-${slug(g.grado)}"
                           onchange="toggleBulk('fam','${esc(g.grado)}',this)">
                    <span id="cnt-fam-${slug(g.grado)}">0 de ${totalFam} familiar${totalFam!==1?'es':''}</span>
                </label>` : ''}
            </div>
        </div>`;
    });
    html += '</div>';
    // Docentes / otros al final como sección compacta
    if (OTROS.length) {
        const roles = {};
        OTROS.forEach(o => { (roles[o.rol] = roles[o.rol] || []).push(o); });
        html += '<div class="otros-section">';
        Object.entries(roles).forEach(([rol, items]) => {
            html += `<div class="otros-group">
                <span class="otros-label">${esc(rolLabel(rol))}</span>
                ${items.map(o => `<label class="ck-row ck-row--inline">
                    <input type="checkbox" class="ck-contact" data-id="${o.id}"
                           onchange="toggleC(${JSON.stringify(o).replace(/"/g,'&quot;')})">
                    <span>${esc(o.apellido + ', ' + o.nombre)}</span>
                </label>`).join('')}
            </div>`;
        });
        html += '</div>';
    }
    vGradeList.innerHTML = html;
    refreshBulkCounters();
}

// ── Vista: detalle de un grado ───────────────────────────────────
function showDetail(gradoNombre) {
    currentGrade = gradoNombre;
    const g = GRADES.find(g => g.grado === gradoNombre);
    if (!g) return;
    const totalFam = g.estudiantes.flatMap(e => e.padres).length;

    let html = `
    <div class="detail-header">
        <button type="button" class="detail-back" onclick="backToList()">
            <i class="mdi mdi-chevron-left"></i> Volver
        </button>
        <strong>${esc(g.grado)}</strong>
    </div>
    <div class="detail-bulk">
        ${g.director ? `<label class="ck-row">
            <input type="checkbox" class="ck-contact" data-id="${g.director.id}"
                   onchange="toggleC(${JSON.stringify(g.director).replace(/"/g,'&quot;')})">
            <span>0 de 1 director${g.director?'':'es'}</span>
        </label>` : ''}
        ${g.estudiantes.length ? `<label class="ck-row">
            <input type="checkbox" id="ckb-est-${slug(g.grado)}"
                   onchange="toggleBulk('est','${esc(g.grado)}',this)">
            <span id="cnt-est-${slug(g.grado)}">0 de ${g.estudiantes.length} estudiante${g.estudiantes.length!==1?'s':''}</span>
        </label>` : ''}
        ${totalFam ? `<label class="ck-row">
            <input type="checkbox" id="ckb-fam-${slug(g.grado)}"
                   onchange="toggleBulk('fam','${esc(g.grado)}',this)">
            <span id="cnt-fam-${slug(g.grado)}">0 de ${totalFam} familiar${totalFam!==1?'es':''}</span>
        </label>` : ''}
    </div>
    <div class="detail-list">`;

    // Director primero
    if (g.director) {
        html += `<div class="dl-contact">
            <label class="dl-person">
                <input type="checkbox" class="ck-contact" data-id="${g.director.id}"
                       onchange="toggleC(${JSON.stringify(g.director).replace(/"/g,'&quot;')})">
                <span class="dl-avatar dl-avatar--docente">${esc((g.director.apellido[0]??'').toUpperCase())}</span>
                <div>
                    <div class="dl-name">${esc(g.director.apellido + ', ' + g.director.nombre)}</div>
                    <div class="dl-rol">Director de curso</div>
                </div>
            </label>
        </div>`;
    }

    // Estudiantes con sus padres
    g.estudiantes.forEach(e => {
        const eObj = {id: e.id, nombre: e.nombre, apellido: e.apellido, rol: 'estudiante'};
        html += `<div class="dl-contact">
            <label class="dl-person">
                <input type="checkbox" class="ck-contact" data-id="${e.id}"
                       onchange="toggleC(${JSON.stringify(eObj).replace(/"/g,'&quot;')})">
                <span class="dl-avatar dl-avatar--estudiante">${esc((e.apellido[0]??'').toUpperCase())}</span>
                <div>
                    <div class="dl-name">${esc(e.apellido + ', ' + e.nombre)}</div>
                    <div class="dl-rol">Estudiante</div>
                </div>
            </label>`;
        if (e.padres.length) {
            html += '<div class="dl-padres">';
            e.padres.forEach(p => {
                const pObj = {id: p.id, nombre: p.nombre, apellido: p.apellido, rol: 'padre'};
                html += `<label class="dl-padre">
                    <input type="checkbox" class="ck-contact" data-id="${p.id}"
                           onchange="toggleC(${JSON.stringify(pObj).replace(/"/g,'&quot;')})">
                    <span>${esc(p.apellido + ', ' + p.nombre)}</span>
                    ${p.parentesco ? `<em>(${esc(p.parentesco)})</em>` : ''}
                </label>`;
            });
            html += '</div>';
        }
        html += '</div>';
    });
    html += '</div>';

    vGradeDet.innerHTML = html;
    showOnly(vGradeDet);
    refreshCheckboxes();
}

function backToList() {
    currentGrade = null;
    showOnly(vGradeList);
    refreshBulkCounters();
}

// ── Selección masiva ─────────────────────────────────────────────
function toggleBulk(tipo, gradoNombre, cb) {
    const g = GRADES.find(g => g.grado === gradoNombre);
    if (!g) return;
    const add = cb.checked; // capturar antes del loop — refreshBulkCounters modifica cb.checked durante addC
    if (tipo === 'est') {
        g.estudiantes.forEach(e => {
            const c = {id: e.id, nombre: e.nombre, apellido: e.apellido, rol: 'estudiante'};
            add ? addC(c) : removeC(e.id);
        });
    } else {
        g.estudiantes.flatMap(e => e.padres).forEach(p => {
            const c = {id: p.id, nombre: p.nombre, apellido: p.apellido, rol: 'padre'};
            add ? addC(c) : removeC(p.id);
        });
    }
}

// ── Vista: por rol ───────────────────────────────────────────────
function buildRolView() {
    if (!OTROS.length) {
        vRol.innerHTML = '<div class="no-results">Sin contactos en esta área</div>';
        return;
    }
    const roles = {};
    OTROS.forEach(o => { (roles[o.rol] = roles[o.rol] || []).push(o); });
    const labels = {docente:'Docentes',admin:'Administrativos',soporte:'Soporte técnico'};
    let html = '<div class="detail-list" style="padding:8px">';
    Object.entries(roles).forEach(([rol, items]) => {
        html += `<div style="margin-bottom:12px">
            <div class="otros-label" style="padding:6px 0">${esc(labels[rol]??rol)}</div>`;
        items.forEach(o => {
            html += `<div class="dl-contact"><label class="dl-person">
                <input type="checkbox" class="ck-contact" data-id="${o.id}"
                       onchange="toggleC(${JSON.stringify(o).replace(/"/g,'&quot;')})">
                <span class="dl-avatar dl-avatar--${esc(o.rol)}">${esc((o.apellido[0]??'').toUpperCase())}</span>
                <div><div class="dl-name">${esc(o.apellido+', '+o.nombre)}</div>
                     <div class="dl-rol">${esc(rolLabel(o.rol))}</div></div>
            </label></div>`;
        });
        html += '</div>';
    });
    html += '</div>';
    vRol.innerHTML = html;
    refreshCheckboxes();
}

// ── Vista restringida (padres / estudiantes) ─────────────────────
function buildRestrictedView(filter = '') {
    const q = filter.trim().toLowerCase();
    const docentes = OTROS.filter(o =>
        !q || (o.apellido + ' ' + o.nombre).toLowerCase().includes(q)
    );
    if (!docentes.length) {
        vGradeList.innerHTML = `<div class="no-results">
            <i class="mdi mdi-account-tie-outline" style="font-size:32px;display:block;opacity:.3;margin-bottom:8px"></i>
            ${q ? 'Sin docentes para "<strong>'+esc(q)+'</strong>"' : 'No tienes docentes asignados todavía'}
        </div>`;
        return;
    }
    let html = '<div class="detail-list" style="padding:8px">';
    html += `<div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;padding:0 4px">
        Docentes de tus materias
    </div>`;
    docentes.forEach(o => {
        const materias = (o.materias && o.materias.length) ? o.materias.join(' · ') : 'Docente';
        const oObj = {id:o.id, nombre:o.nombre, apellido:o.apellido, rol:'docente'};
        html += `<div class="dl-contact"><label class="dl-person">
            <input type="checkbox" class="ck-contact" data-id="${o.id}"
                   onchange="toggleC(${JSON.stringify(oObj).replace(/"/g,'&quot;')})">
            <span class="dl-avatar dl-avatar--docente">${esc((o.apellido[0]??'').toUpperCase())}</span>
            <div><div class="dl-name">${esc(o.apellido+', '+o.nombre)}</div>
                 <div class="dl-rol">${esc(materias)}</div></div>
        </label></div>`;
    });
    html += '</div>';
    vGradeList.innerHTML = html;
    refreshCheckboxes();
}

// ── Tabs ─────────────────────────────────────────────────────────
let activeTab = 'grado';
ctabs.forEach(tab => {
    tab.addEventListener('click', () => {
        ctabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        activeTab = tab.dataset.tab;
        if (!searchInput.value.trim()) {
            if (activeTab === 'grado') { showOnly(vGradeList); }
            else { buildRolView(); showOnly(vRol); }
        }
    });
});

// ── Búsqueda ─────────────────────────────────────────────────────
if (searchInput) searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    // Modo restringido: filtra la lista de docentes en su sitio
    if (RESTRINGIDO) {
        buildRestrictedView(searchInput.value);
        showOnly(vGradeList);
        return;
    }
    if (!q) {
        activeTab === 'grado' ? showOnly(vGradeList) : showOnly(vRol);
        return;
    }
    showOnly(vSearch);

    // Buscar en estudiantes, padres y otros
    const results = [];
    GRADES.forEach(g => {
        g.estudiantes.forEach(e => {
            if ((e.apellido+' '+e.nombre).toLowerCase().includes(q))
                results.push({id:e.id,nombre:e.nombre,apellido:e.apellido,rol:'estudiante',grado:g.grado});
            e.padres.forEach(p => {
                if ((p.apellido+' '+p.nombre).toLowerCase().includes(q))
                    results.push({id:p.id,nombre:p.nombre,apellido:p.apellido,rol:'padre',grado:g.grado});
            });
        });
        if (g.director && (g.director.apellido+' '+g.director.nombre).toLowerCase().includes(q))
            results.push({...g.director, rol:'docente', grado:g.grado});
    });
    OTROS.forEach(o => {
        if ((o.apellido+' '+o.nombre).toLowerCase().includes(q)) results.push(o);
    });

    if (!results.length) {
        vSearch.innerHTML = `<div class="no-results">Sin resultados para "<strong>${esc(q)}</strong>"</div>`;
        return;
    }
    let html = `<div class="detail-list" style="padding:8px">
        <div style="font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px">
            ${results.length} resultado${results.length!==1?'s':''}
        </div>`;
    results.forEach(c => {
        const sub = c.grado ? `${esc(rolLabel(c.rol))} · ${esc(c.grado)}` : esc(rolLabel(c.rol));
        html += `<div class="dl-contact"><label class="dl-person">
            <input type="checkbox" class="ck-contact" data-id="${c.id}"
                   onchange="toggleC(${JSON.stringify({id:c.id,nombre:c.nombre,apellido:c.apellido,rol:c.rol}).replace(/"/g,'&quot;')})">
            <span class="dl-avatar dl-avatar--${esc(c.rol)}">${esc((c.apellido[0]??'').toUpperCase())}</span>
            <div><div class="dl-name">${esc(c.apellido+', '+c.nombre)}</div>
                 <div class="dl-rol">${sub}</div></div>
        </label></div>`;
    });
    html += '</div>';
    vSearch.innerHTML = html;
    refreshCheckboxes();
});

// ── Panel de contactos (dropdown) ───────────────────────────────
let contactsPanelOpen = false;
function toggleContactsPanel(e) {
    if (e && e.target.closest('.chip')) return; // no cerrar al hacer clic en chip
    const panel   = document.getElementById('contacts-panel');
    const chevron = document.getElementById('para-chevron');
    const paraRow = document.getElementById('para-row-trigger');
    contactsPanelOpen = !contactsPanelOpen;
    panel.style.display   = contactsPanelOpen ? '' : 'none';
    if (chevron) chevron.style.transform = contactsPanelOpen ? 'rotate(180deg)' : '';
    if (paraRow) paraRow.classList.toggle('panel-open', contactsPanelOpen);
    if (contactsPanelOpen && !panelBuilt) {
        RESTRINGIDO ? buildRestrictedView() : buildGradeList();
        showOnly(vGradeList);
        panelBuilt = true;
    }
}
let panelBuilt = false;
document.addEventListener('click', function(e) {
    if (!contactsPanelOpen) return;
    const card = document.querySelector('.compose-card');
    if (card && !card.contains(e.target)) {
        contactsPanelOpen = false;
        const panel = document.getElementById('contacts-panel');
        const chevron = document.getElementById('para-chevron');
        const paraRow = document.getElementById('para-row-trigger');
        if (paraRow) paraRow.classList.remove('panel-open');
        if (panel) panel.style.display = 'none';
        if (chevron) chevron.style.transform = '';
    }
});

// ── Panel principal de adjuntos ──────────────────────────────────
function toggleAdjPanel() {
    const panel   = document.getElementById('adjinfo-panel');
    const chevron = document.getElementById('adjinfo-main-chevron');
    const open    = panel.style.display === 'none';
    panel.style.display   = open ? '' : 'none';
    chevron.style.transform = open ? 'rotate(180deg)' : '';
}

// ── Adjuntar información ─────────────────────────────────────────
function toggleAdj(id, btn) {
    const body = document.getElementById(id);
    const open = body.classList.toggle('open');
    btn.querySelector('.adjinfo-chevron').style.transform = open ? 'rotate(180deg)' : '';
}
function insertarVideo() {
    const raw = document.getElementById('video-url-input').value.trim();
    if (!raw) return;
    let url = raw;
    const yt = raw.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    if (yt) url = `https://www.youtube.com/embed/${yt[1]}?rel=0`;
    const vm = raw.match(/vimeo\.com\/(\d+)/);
    if (vm) url = `https://player.vimeo.com/video/${vm[1]}`;
    const range = quill.getSelection(true);
    quill.insertEmbed(range.index, 'video', url, 'user');
    quill.insertText(range.index + 1, '\n', 'user');
    quill.setSelection(range.index + 2);
    document.getElementById('video-url-input').value = '';
    quill.focus();
}
function insertarHtml() {
    const html = document.getElementById('html-input').value.trim();
    if (!html) return;
    const range = quill.getSelection(true);
    quill.clipboard.dangerouslyPasteHTML(range.index, html, 'user');
    document.getElementById('html-input').value = '';
    quill.focus();
}
function insertarDrive() {
    const url = document.getElementById('drive-url-input').value.trim();
    if (!url) return;
    const texto = 'Documento de Google Drive';
    const range = quill.getSelection(true);
    quill.insertText(range.index, texto, 'link', url);
    quill.insertText(range.index + texto.length, ' ');
    quill.setSelection(range.index + texto.length + 1);
    document.getElementById('drive-url-input').value = '';
    quill.focus();
}

// ── Adjuntos: preview ────────────────────────────────────────────
document.getElementById('adjunto-input').addEventListener('change', function () {
    const list = document.getElementById('adjunto-list');
    list.innerHTML = '';
    Array.from(this.files).forEach(f => {
        const li = document.createElement('li');
        li.className = 'adjunto-item';
        const kb = f.size < 1048576 ? (f.size/1024).toFixed(0)+' KB' : (f.size/1048576).toFixed(1)+' MB';
        li.innerHTML = `<i class="mdi mdi-file-outline"></i>${esc(f.name)} <span style="color:var(--text-muted)">(${kb})</span>`;
        list.appendChild(li);
    });
});

// ── Quill ────────────────────────────────────────────────────────
const quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Escribe tu mensaje aquí…',
    modules: {
        formula: true,
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ indent: '-1' }, { indent: '+1' }],
            ['blockquote', 'code-block'],
            ['link', 'image', 'formula'],
            ['clean']
        ]
    }
});

quillInit(quill, {
    uploadUrl:   '<?= APP_URL ?>/upload/imagen',
    menciones:   '<?= APP_URL ?>/api/menciones',
    wordCounter: '#quill-word-count',
});

// ── Reply pre-carga ──────────────────────────────────────────────
if (REPLY_LOCK && REPLY_DEST) {
    // Modo respuesta bloqueado: destinatario fijo desde el servidor
    addC({id:REPLY_DEST.id, nombre:REPLY_DEST.nombre, apellido:REPLY_DEST.apellido, rol:'docente'});
} else if (PRESEL.length > 0) {
    // Pre-selección desde lista de estudiantes (estudiante + padres)
    PRESEL.forEach(p => {
        addC({id: p.id, nombre: p.label, apellido: '', rol: 'estudiante'});
    });
} else if (REPLY_ID !== null) {
    GRADES.forEach(g => {
        g.estudiantes.forEach(e => {
            if (e.id === REPLY_ID) addC({id:e.id,nombre:e.nombre,apellido:e.apellido,rol:'estudiante'});
            e.padres.forEach(p => { if (p.id === REPLY_ID) addC({id:p.id,nombre:p.nombre,apellido:p.apellido,rol:'padre'}); });
        });
        if (g.director?.id === REPLY_ID) addC({...g.director, rol:'docente'});
    });
    OTROS.forEach(o => { if (o.id === REPLY_ID) addC(o); });
}

// ── Cita del historial ───────────────────────────────────────────
function toggleCita() {
    const h = document.getElementById('cita-historial');
    const t = document.getElementById('cita-toggle');
    const visible = h.style.display !== 'none';
    h.style.display = visible ? 'none' : 'block';
    t.innerHTML = visible
        ? '<i class="mdi mdi-dots-horizontal"></i> Mostrar historial citado'
        : '<i class="mdi mdi-chevron-up"></i> Ocultar historial citado';
}
if (REPLY_CITA) {
    document.getElementById('cita-wrap').style.display = 'block';
    document.getElementById('cita-historial').innerHTML = REPLY_CITA;
}

// ── Envío ────────────────────────────────────────────────────────
document.getElementById('compose-form').addEventListener('submit', function (ev) {
    let cuerpo = quill.root.innerHTML;
    // Adjuntar el historial citado al final del cuerpo
    if (REPLY_CITA) {
        cuerpo += '<div class="cita-historial">' + REPLY_CITA + '</div>';
    }
    document.getElementById('cuerpo-hidden').value = cuerpo;
    if (!selected.size) { ev.preventDefault(); alert('Selecciona al menos un destinatario.'); return; }
    if (quill.getText().trim().length < 2) { ev.preventDefault(); alert('Escribe tu respuesta antes de enviar.'); return; }
    document.getElementById('send-btn').disabled = true;
    document.getElementById('send-btn').innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Enviando…';
});

// ── Inicializar ──────────────────────────────────────────────────
// El panel de contactos se construye la primera vez que se abre (lazy)
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
