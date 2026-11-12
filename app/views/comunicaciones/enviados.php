<?php
$pageTitle   = 'Enviados — ' . APP_NAME;
$breadcrumbs = [
  ['label' => 'Inicio',   'url' => APP_URL . '/dashboard'],
  ['label' => 'Mensajes', 'url' => APP_URL . '/comunicaciones'],
  ['label' => 'Enviados', 'url' => ''],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
  <h1 class="page-title">Mensajes enviados</h1>
  <a href="<?= APP_URL ?>/comunicaciones/redactar" class="btn-primary">
    <i class="mdi mdi-pencil-outline"></i> Redactar
  </a>
</div>

<div class="msg-tabs">
  <a href="<?= APP_URL ?>/comunicaciones" class="msg-tab">
    <i class="mdi mdi-inbox-outline"></i> Bandeja
  </a>
  <a href="<?= APP_URL ?>/comunicaciones/enviados" class="msg-tab active">
    <i class="mdi mdi-send-outline"></i> Enviados
  </a>
</div>

<?php
$f = $filtros ?? [];
$hayFiltroAvanzado = !empty($f['asunto']) || !empty($f['fecha_desde']) || !empty($f['fecha_hasta']);
$hayFiltro = array_filter($f);
?>
<div class="msg-search-wrap">
  <form method="GET" action="<?= APP_URL ?>/comunicaciones/enviados" id="search-form">
    <div class="msg-search-bar">
      <i class="mdi mdi-magnify msg-search-icon"></i>
      <input type="text" name="q" class="msg-search-input"
             placeholder="Buscar en enviados…"
             value="<?= htmlspecialchars($f['q'] ?? '') ?>"
             autocomplete="off" spellcheck="false">
      <button type="button" class="msg-filter-btn <?= $hayFiltroAvanzado ? 'active' : '' ?>"
              onclick="toggleMsgFilter()" title="Filtros avanzados">
        <i class="mdi mdi-tune-variant"></i>
      </button>
      <?php if ($hayFiltro): ?>
      <a href="<?= APP_URL ?>/comunicaciones/enviados" class="msg-clear-btn" title="Limpiar filtros">
        <i class="mdi mdi-close-circle"></i>
      </a>
      <?php endif; ?>
    </div>
    <div class="msg-filter-panel" id="msg-filter-panel" <?= $hayFiltroAvanzado ? '' : 'style="display:none"' ?>>
      <div class="msg-filter-grid">
        <div class="msg-filter-field">
          <label>Asunto</label>
          <input type="text" name="asunto" placeholder="Palabras en el asunto"
                 value="<?= htmlspecialchars($f['asunto'] ?? '') ?>">
        </div>
        <div class="msg-filter-field">
          <label>Fecha desde</label>
          <input type="date" name="fecha_desde" value="<?= htmlspecialchars($f['fecha_desde'] ?? '') ?>">
        </div>
        <div class="msg-filter-field">
          <label>Fecha hasta</label>
          <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($f['fecha_hasta'] ?? '') ?>">
        </div>
      </div>
      <div class="msg-filter-actions">
        <button type="submit" class="btn-primary btn-sm"><i class="mdi mdi-magnify"></i> Buscar</button>
        <a href="<?= APP_URL ?>/comunicaciones/enviados" class="btn-secondary btn-sm">Limpiar</a>
      </div>
    </div>
  </form>
</div>

<div class="card" id="msg-list">
  <?php include __DIR__ . '/_enviados_lista.php'; ?>
</div>

<?php
$extraFoot = '<script>
const _BASE = "' . APP_URL . '/comunicaciones/enviados";

function toggleMsgFilter() {
  const panel = document.getElementById("msg-filter-panel");
  const btn   = document.querySelector(".msg-filter-btn");
  const open  = panel.style.display === "none";
  panel.style.display = open ? "" : "none";
  if (!open) btn.classList.remove("active");
}

function buscarAjax() {
  const list = document.getElementById("msg-list");
  const q    = document.querySelector(".msg-search-input").value;
  const params = new URLSearchParams({ ajax: 1, q });
  document.querySelectorAll("#search-form input[name]:not(.msg-search-input)").forEach(el => {
    if (el.value) params.set(el.name, el.value);
  });
  list.style.opacity = "0.5";
  fetch(_BASE + "?" + params.toString())
    .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
    .then(html => { list.innerHTML = html; list.style.opacity = "1"; })
    .catch(() => { list.style.opacity = "1"; });
}

let _t;
document.querySelector(".msg-search-input").addEventListener("input", function() {
  clearTimeout(_t);
  _t = setTimeout(buscarAjax, 350);
});

document.getElementById("search-form").addEventListener("submit", function(e) {
  e.preventDefault();
  buscarAjax();
});
</script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
