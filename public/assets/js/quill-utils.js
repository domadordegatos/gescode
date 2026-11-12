/**
 * quill-utils.js  — utilidades compartidas para todos los editores Quill
 * Incluye: paste de imágenes, drag & drop, redimensionar imágenes,
 *          contador de palabras y menciones @usuario.
 *
 * Uso:
 *   quillInit(quill, {
 *     uploadUrl:    '/upload/imagen',
 *     menciones:    '/api/menciones',   // omitir para deshabilitar
 *     wordCounter:  '#word-counter',    // selector del elemento contador
 *   });
 */

// ── Resize overlay ────────────────────────────────────────────────
(function () {
    let overlay = null, activeImg = null;
    let startX, startW, startH, startAspect;

    const POSITIONS = ['nw','n','ne','e','se','s','sw','w'];
    const CURSORS   = { nw:'nw-resize',n:'n-resize',ne:'ne-resize',e:'e-resize',
                        se:'se-resize',s:'s-resize',sw:'sw-resize',w:'w-resize' };

    function build() {
        overlay = document.createElement('div');
        overlay.style.cssText =
            'position:absolute;pointer-events:none;box-sizing:border-box;' +
            'border:2px solid #0E0EB3;z-index:9999;display:none;';

        POSITIONS.forEach(pos => {
            const h = document.createElement('div');
            h.dataset.pos = pos;
            h.style.cssText =
                'position:absolute;width:10px;height:10px;background:#0E0EB3;' +
                'border:1px solid #fff;border-radius:2px;pointer-events:all;box-sizing:border-box;';
            placeHandle(h, pos);
            h.addEventListener('mousedown', startResize);
            overlay.appendChild(h);
        });
        document.body.appendChild(overlay);
    }

    function placeHandle(h, pos) {
        const half = '-5px', mid = 'calc(50% - 5px)';
        const map = {
            nw:{top:half,  left:half },  n:{top:half,   left:mid  },
            ne:{top:half,  right:half},  e:{top:mid,    right:half},
            se:{bottom:half,right:half}, s:{bottom:half,left:mid  },
            sw:{bottom:half,left:half},  w:{top:mid,    left:half },
        };
        Object.assign(h.style, map[pos]);
        h.style.cursor = CURSORS[pos];
    }

    function align() {
        if (!activeImg || !overlay) return;
        const r = activeImg.getBoundingClientRect();
        const sy = window.scrollY || document.documentElement.scrollTop;
        const sx = window.scrollX || document.documentElement.scrollLeft;
        Object.assign(overlay.style, {
            top: (r.top + sy) + 'px', left: (r.left + sx) + 'px',
            width: r.width + 'px',   height: r.height + 'px',
        });
    }

    function show(img) {
        activeImg = img;
        if (!overlay) build();
        align();
        overlay.style.display = 'block';
    }

    function hide() {
        if (overlay) overlay.style.display = 'none';
        activeImg = null;
    }

    function startResize(e) {
        e.preventDefault(); e.stopPropagation();
        const pos = e.target.dataset.pos;
        startX = e.clientX;
        startW = activeImg.offsetWidth;
        startH = activeImg.offsetHeight;
        startAspect = startW / startH;

        function move(ev) {
            const dx = ev.clientX - startX;
            const dy = ev.clientY - e.clientY;
            let newW = startW;
            if (pos.includes('e'))            newW = startW + dx;
            else if (pos.includes('w'))       newW = startW - dx;
            else if (pos === 's')             newW = startW + dy * startAspect;
            else if (pos === 'n')             newW = startW - dy * startAspect;
            newW = Math.max(40, Math.round(newW));
            const newH = Math.round(newW / startAspect);
            activeImg.style.width  = newW + 'px';
            activeImg.style.height = newH + 'px';
            activeImg.width  = newW;
            activeImg.height = newH;
            align();
        }
        function up() {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
        }
        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', up);
    }

    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG' && e.target.closest('.ql-editor')) show(e.target);
        else if (!e.target.closest('[data-pos]')) hide();
    });
    window.addEventListener('scroll', align, true);
    window.addEventListener('resize', align);
})();

// ── Inicializador principal ───────────────────────────────────────
function quillInit(editor, opts) {
    opts = opts || {};
    const uploadUrl  = opts.uploadUrl  || '/upload/imagen';
    const mencUrl    = opts.menciones  || null;
    const counterSel = opts.wordCounter || null;

    // 0. Tooltips de la barra de herramientas
    const TOOLTIPS = {
        'ql-bold':        'Negrita (Ctrl+B)',
        'ql-italic':      'Cursiva (Ctrl+I)',
        'ql-underline':   'Subrayado (Ctrl+U)',
        'ql-strike':      'Tachado',
        'ql-color':       'Color de texto',
        'ql-background':  'Color de fondo',
        'ql-align':       'Alineación',
        'ql-list[value="ordered"]':  'Lista numerada',
        'ql-list[value="bullet"]':   'Lista con viñetas',
        'ql-indent[value="-1"]':     'Disminuir sangría',
        'ql-indent[value="+1"]':     'Aumentar sangría',
        'ql-blockquote':  'Cita',
        'ql-code-block':  'Bloque de código',
        'ql-link':        'Insertar enlace',
        'ql-image':       'Insertar imagen',
        'ql-formula':     'Insertar fórmula matemática (LaTeX)',
        'ql-clean':       'Limpiar formato',
        'ql-header':      'Estilo de texto',
    };

    const toolbar = editor.container.previousElementSibling;
    if (toolbar && toolbar.classList.contains('ql-toolbar')) {
        Object.entries(TOOLTIPS).forEach(([selector, tip]) => {
            const attrMatch = selector.match(/^([\w-]+)\[(.+)="(.+)"\]$/);
            let el;
            if (attrMatch) {
                el = toolbar.querySelector(`.${attrMatch[1]}[${attrMatch[2]}="${attrMatch[3]}"]`);
            } else {
                el = toolbar.querySelector(`.${selector}`);
            }
            if (el) el.setAttribute('title', tip);
        });
    }

    // 1. Paste de imágenes
    editor.root.addEventListener('paste', function(e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (const item of items) {
            if (!item.type.startsWith('image/')) continue;
            e.preventDefault();
            const file = item.getAsFile();
            if (file) { uploadImg(editor, uploadUrl, file); }
            break;
        }
    });

    // 2. Drag & drop de imágenes
    const root = editor.root;
    root.addEventListener('dragover', function(e) {
        if ([...e.dataTransfer.items].some(i => i.type.startsWith('image/'))) {
            e.preventDefault();
            root.style.outline = '2px dashed #0E0EB3';
        }
    });
    root.addEventListener('dragleave', function() {
        root.style.outline = '';
    });
    root.addEventListener('drop', function(e) {
        root.style.outline = '';
        const files = [...e.dataTransfer.files].filter(f => f.type.startsWith('image/'));
        if (!files.length) return;
        e.preventDefault();
        files.forEach(f => uploadImg(editor, uploadUrl, f));
    });

    // 3. Contador de palabras
    if (counterSel) {
        const el = document.querySelector(counterSel);
        if (el) {
            function updateCount() {
                const text = editor.getText().trim();
                const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
                const chars = text.length;
                el.textContent = words + ' palabra' + (words !== 1 ? 's' : '') +
                                 ' · ' + chars + ' carácter' + (chars !== 1 ? 'es' : '');
            }
            editor.on('text-change', updateCount);
            updateCount();
        }
    }

    // 4. Menciones @usuario
    if (mencUrl) {
        let mencBox = null, mencItems = [], mencIdx = 0, mencStart = -1;

        function closeMenc() {
            if (mencBox) { mencBox.remove(); mencBox = null; }
            mencItems = []; mencIdx = 0; mencStart = -1;
        }

        function renderMenc(list, range) {
            closeMenc();
            if (!list.length) return;
            mencItems = list;
            mencIdx   = 0;

            mencBox = document.createElement('div');
            mencBox.style.cssText =
                'position:fixed;background:#fff;border:1px solid #e5e7eb;border-radius:10px;' +
                'box-shadow:0 4px 20px rgba(0,0,0,.12);z-index:99999;min-width:200px;overflow:hidden;';

            list.forEach((u, i) => {
                const item = document.createElement('div');
                item.style.cssText =
                    'padding:9px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;' +
                    'font-family:Nunito,sans-serif;font-size:.88rem;';
                const rolColor = {docente:'#0E0EB3',admin:'#7c3aed',estudiante:'#059669',padre:'#d97706'};
                item.innerHTML =
                    `<span style="width:30px;height:30px;border-radius:50%;background:${rolColor[u.rol]||'#6b7280'};` +
                    `color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0">` +
                    `${u.value.split(' ').map(p=>p[0]).slice(0,2).join('')}</span>` +
                    `<span><strong>${u.value}</strong><br><span style="color:#9ca3af;font-size:.76rem">@${u.link}</span></span>`;
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    insertMenc(u);
                });
                item.addEventListener('mouseenter', function() {
                    setActive(i);
                });
                mencBox.appendChild(item);
            });

            // Posicionar cerca del cursor
            const bounds = editor.getBounds(range.index);
            const edRect = editor.root.getBoundingClientRect();
            mencBox.style.top  = (edRect.top  + bounds.bottom + 4) + 'px';
            mencBox.style.left = (edRect.left  + bounds.left)       + 'px';
            document.body.appendChild(mencBox);
            setActive(0);
        }

        function setActive(i) {
            mencIdx = i;
            [...mencBox.children].forEach((c, j) => {
                c.style.background = j === i ? '#f0f4ff' : '';
            });
        }

        function insertMenc(u) {
            const range = editor.getSelection();
            if (mencStart < 0) return;
            const len = (range ? range.index : mencStart) - mencStart;
            editor.deleteText(mencStart, len + 1);
            editor.insertText(mencStart, '@' + u.value + ' ', { color: '#0E0EB3', bold: true });
            editor.format('color', false);
            editor.format('bold', false);
            editor.setSelection(mencStart + u.value.length + 2);
            closeMenc();
        }

        editor.root.addEventListener('keydown', function(e) {
            if (!mencBox) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive((mencIdx+1) % mencItems.length); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive((mencIdx-1+mencItems.length) % mencItems.length); }
            else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); insertMenc(mencItems[mencIdx]); }
            else if (e.key === 'Escape') closeMenc();
        });

        editor.on('text-change', function() {
            const range = editor.getSelection();
            if (!range) return;
            const text  = editor.getText(0, range.index);
            const atIdx = text.lastIndexOf('@');
            if (atIdx < 0) { closeMenc(); return; }
            const query = text.slice(atIdx + 1);
            if (/\s/.test(query)) { closeMenc(); return; }
            mencStart = atIdx;
            if (query.length === 0) { closeMenc(); return; }
            fetch(mencUrl + '?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(list => renderMenc(list, range))
                .catch(closeMenc);
        });

        document.addEventListener('click', function(e) {
            if (mencBox && !mencBox.contains(e.target)) closeMenc();
        });
    }
}

// ── Subida de imagen (interna) ────────────────────────────────────
function uploadImg(editor, uploadUrl, file) {
    const formData = new FormData();
    formData.append('imagen', file);
    const range = editor.getSelection(true);
    const PLACEHOLDER = '⏳ Subiendo imagen…';
    editor.insertText(range.index, PLACEHOLDER, { color: '#9ca3af' });
    editor.setSelection(range.index + PLACEHOLDER.length);

    fetch(uploadUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            editor.deleteText(range.index, PLACEHOLDER.length);
            if (data.url) {
                editor.insertEmbed(range.index, 'image', data.url);
                editor.setSelection(range.index + 1);
            } else {
                alert('Error al subir imagen: ' + (data.error || 'desconocido'));
            }
        })
        .catch(() => {
            editor.deleteText(range.index, PLACEHOLDER.length);
            alert('No se pudo subir la imagen.');
        });
}

// Mantener compatibilidad con llamadas anteriores
function quillImagePaste(editor, uploadUrl) {
    quillInit(editor, { uploadUrl });
}
