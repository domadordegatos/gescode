/**
 * quill-image-paste.js
 * - Pegar imágenes con Ctrl+V (sube al servidor)
 * - Clic en imagen muestra manijas para redimensionar
 * Uso: quillImagePaste(quill, '/upload/imagen');
 */

// ── Resize overlay ────────────────────────────────────────────────
(function () {
    let overlay = null, handles = [], activeImg = null;
    let startX, startW, startH;

    const HANDLE_POSITIONS = ['nw','n','ne','e','se','s','sw','w'];
    const CURSORS = { nw:'nw-resize', n:'n-resize', ne:'ne-resize', e:'e-resize',
                      se:'se-resize', s:'s-resize', sw:'sw-resize', w:'w-resize' };

    function createOverlay() {
        overlay = document.createElement('div');
        overlay.style.cssText = 'position:absolute;pointer-events:none;box-sizing:border-box;' +
            'border:2px solid #0E0EB3;z-index:9999;';

        HANDLE_POSITIONS.forEach(pos => {
            const h = document.createElement('div');
            h.dataset.pos = pos;
            h.style.cssText = 'position:absolute;width:10px;height:10px;background:#0E0EB3;' +
                'border:1px solid #fff;border-radius:2px;pointer-events:all;box-sizing:border-box;';
            positionHandle(h, pos);
            h.addEventListener('mousedown', onHandleMousedown);
            overlay.appendChild(h);
            handles.push(h);
        });

        document.body.appendChild(overlay);
    }

    function positionHandle(h, pos) {
        const half = '-5px';
        const mid  = 'calc(50% - 5px)';
        const map = {
            nw: { top: half,  left: half  },
            n:  { top: half,  left: mid   },
            ne: { top: half,  right: half },
            e:  { top: mid,   right: half },
            se: { bottom: half, right: half },
            s:  { bottom: half, left: mid },
            sw: { bottom: half, left: half },
            w:  { top: mid,   left: half  },
        };
        Object.assign(h.style, map[pos]);
        h.style.cursor = CURSORS[pos];
    }

    function showOverlay(img) {
        activeImg = img;
        if (!overlay) createOverlay();
        alignOverlay();
        overlay.style.display = 'block';
    }

    function hideOverlay() {
        if (overlay) overlay.style.display = 'none';
        activeImg = null;
    }

    function alignOverlay() {
        if (!activeImg || !overlay) return;
        const rect = activeImg.getBoundingClientRect();
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollX = window.scrollX || document.documentElement.scrollLeft;
        overlay.style.top    = (rect.top  + scrollY) + 'px';
        overlay.style.left   = (rect.left + scrollX) + 'px';
        overlay.style.width  = rect.width  + 'px';
        overlay.style.height = rect.height + 'px';
    }

    function onHandleMousedown(e) {
        e.preventDefault();
        e.stopPropagation();
        const pos = e.target.dataset.pos;
        startX = e.clientX;
        startW = activeImg.offsetWidth;
        startH = activeImg.offsetHeight;
        const aspect = startW / startH;

        function onMove(ev) {
            const dx = ev.clientX - startX;
            let newW = startW;

            if (pos.includes('e'))  newW = startW + dx;
            if (pos.includes('w'))  newW = startW - dx;
            if (pos === 'n' || pos === 's') {
                // solo vertical → mantener proporción
                const dy = ev.clientY - e.clientY;
                newW = pos === 's' ? startW + dy * aspect : startW - dy * aspect;
            }

            newW = Math.max(40, Math.round(newW));
            const newH = Math.round(newW / aspect);
            activeImg.style.width  = newW + 'px';
            activeImg.style.height = newH + 'px';
            activeImg.width  = newW;
            activeImg.height = newH;
            alignOverlay();
        }

        function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            alignOverlay();
        }

        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    }

    // Escuchar clics en imágenes dentro de cualquier editor Quill
    document.addEventListener('click', function (e) {
        if (e.target.tagName === 'IMG' && e.target.closest('.ql-editor')) {
            showOverlay(e.target);
        } else if (!e.target.closest('[data-pos]')) {
            hideOverlay();
        }
    });

    // Reposicionar al hacer scroll o resize
    window.addEventListener('scroll', alignOverlay, true);
    window.addEventListener('resize', alignOverlay);
})();

// ── Paste de imágenes ─────────────────────────────────────────────
function quillImagePaste(editor, uploadUrl) {
    editor.root.addEventListener('paste', function (e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (const item of items) {
            if (!item.type.startsWith('image/')) continue;
            e.preventDefault();
            const file = item.getAsFile();
            if (!file) continue;
            uploadQuillImage(editor, uploadUrl, file);
            break;
        }
    });
}

function uploadQuillImage(editor, uploadUrl, file) {
    const formData = new FormData();
    formData.append('imagen', file);

    const range = editor.getSelection(true);
    editor.insertText(range.index, '⏳ Subiendo imagen…', { color: '#9ca3af' });
    editor.setSelection(range.index + 18);

    fetch(uploadUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            editor.deleteText(range.index, 18);
            if (data.url) {
                editor.insertEmbed(range.index, 'image', data.url);
                editor.setSelection(range.index + 1);
            } else {
                alert('Error al subir la imagen: ' + (data.error || 'desconocido'));
            }
        })
        .catch(() => {
            editor.deleteText(range.index, 18);
            alert('No se pudo subir la imagen. Verifica tu conexión.');
        });
}
