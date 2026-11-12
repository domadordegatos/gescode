/* Plataforma Académica — app.js */
(function () {
  'use strict';

  // ── Sidebar toggle ──────────────────────────────────────────
  const container = document.getElementById('app-container');
  const toggler   = document.getElementById('sidebar-toggler');

  if (toggler && container) {
    // Restaurar estado guardado
    if (localStorage.getItem('sidebar-collapsed') === '1') {
      container.classList.add('collapsed');
    }

    toggler.addEventListener('click', () => {
      container.classList.toggle('collapsed');
      localStorage.setItem(
        'sidebar-collapsed',
        container.classList.contains('collapsed') ? '1' : '0'
      );
    });
  }

  // ── Mobile sidebar overlay ──────────────────────────────────
  if (window.innerWidth <= 768 && toggler && container) {
    toggler.addEventListener('click', (e) => {
      e.stopPropagation();
      container.classList.toggle('mobile-open');
      container.classList.remove('collapsed');
    });
    // Cerrar al hacer click fuera
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#sidebar') && container.classList.contains('mobile-open')) {
        container.classList.remove('mobile-open');
      }
    });
  }

  // ── Nav groups (submenús colapsables) ──────────────────────
  document.querySelectorAll('.nav-group-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.nav-group').classList.toggle('open');
    });
  });

  // ── Auto-cerrar alerts ──────────────────────────────────────
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  // ── Confirm links ───────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Custom select (reemplaza todos los .cp-select) ──────────
  // El panel de opciones se monta en <body> con position:fixed para
  // escapar de cualquier contenedor con overflow:auto/hidden (modales, etc.)
  const CUS_PANEL = (function () {
    const panel = document.createElement('div');
    panel.className = 'cus-list';
    panel.style.cssText = 'display:none;position:fixed;z-index:9999';
    document.body.appendChild(panel);
    return panel;
  })();

  let _activeWrap = null;

  function cusClose() {
    if (_activeWrap) _activeWrap.classList.remove('open');
    CUS_PANEL.style.display = 'none';
    CUS_PANEL._onSelect = null;
    _activeWrap = null;
  }

  function cusPosition(triggerEl) {
    const r = triggerEl.getBoundingClientRect();
    const spaceBelow = window.innerHeight - r.bottom;
    const panelH = Math.min(220, CUS_PANEL.scrollHeight || 220);
    if (spaceBelow < panelH && r.top > panelH) {
      // open upward
      CUS_PANEL.style.top  = (r.top - panelH - 5) + 'px';
    } else {
      CUS_PANEL.style.top  = (r.bottom + 5) + 'px';
    }
    CUS_PANEL.style.left  = r.left + 'px';
    CUS_PANEL.style.width = r.width + 'px';
  }

  document.addEventListener('click', e => {
    if (_activeWrap && !_activeWrap.contains(e.target)) cusClose();
  });
  document.addEventListener('scroll', () => { if (_activeWrap) cusClose(); }, true);

  function initCustomSelects(root) {
    (root || document).querySelectorAll('.cp-select:not(.cus-initialized)').forEach(sel => {
      sel.classList.add('cus-initialized');

      const wrap = document.createElement('div');
      wrap.className = 'cus-wrap';
      sel.parentNode.insertBefore(wrap, sel);
      wrap.appendChild(sel);

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'cus-trigger';
      trigger.innerHTML = '<span class="cus-trigger-text"></span>'
                        + '<i class="mdi mdi-chevron-down cus-trigger-icon"></i>';
      wrap.appendChild(trigger);

      const triggerText = trigger.querySelector('.cus-trigger-text');

      function syncTrigger() {
        const opt = sel.options[sel.selectedIndex];
        triggerText.textContent = opt ? opt.text : '';
        if (_activeWrap === wrap) {
          CUS_PANEL.querySelectorAll('.cus-opt').forEach(item => {
            item.classList.toggle('selected', item.dataset.value === String(sel.value));
          });
        }
      }

      function open() {
        if (_activeWrap && _activeWrap !== wrap) cusClose();

        // Build options into shared panel
        CUS_PANEL.innerHTML = '';
        Array.from(sel.options).forEach(opt => {
          const item = document.createElement('div');
          item.className = 'cus-opt' + (opt.selected ? ' selected' : '');
          item.textContent = opt.text;
          item.dataset.value = opt.value;
          item.addEventListener('mousedown', e => {
            e.preventDefault();
            sel.value = opt.value;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            syncTrigger();
            cusClose();
          });
          CUS_PANEL.appendChild(item);
        });

        CUS_PANEL.style.display = 'block';
        cusPosition(trigger);
        _activeWrap = wrap;
        wrap.classList.add('open');
      }

      trigger.addEventListener('click', e => {
        e.stopPropagation();
        wrap.classList.contains('open') ? cusClose() : open();
      });

      sel.addEventListener('change', syncTrigger);
      syncTrigger();
    });
  }

  initCustomSelects();

  // Expose globally so views with dynamic selects can call it
  window.initCustomSelects = initCustomSelects;

})();
