<?php
$pageTitle   = 'Mensajes — ' . APP_NAME;
$breadcrumbs = [
  ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
  ['label' => 'Mensajes', 'url' => APP_URL . '/comunicaciones'],
];
$noLeidos = 0;
ob_start();
?>

<div class="page-header">
  <h1 class="page-title">Mensajes</h1>
  <a href="<?= APP_URL ?>/comunicaciones/redactar" class="btn-primary">
    <i class="mdi mdi-pencil-outline"></i> Redactar
  </a>
</div>

<div class="msg-tabs">
  <a href="<?= APP_URL ?>/comunicaciones"  class="msg-tab active">
    <i class="mdi mdi-inbox-outline"></i> Bandeja de entrada
  </a>
  <a href="<?= APP_URL ?>/comunicaciones/enviados" class="msg-tab">
    <i class="mdi mdi-send-outline"></i> Enviados
  </a>
</div>

<?php
$f = $filtros ?? [];
$hayFiltroAvanzado = !empty($f['de']) || !empty($f['asunto']) || !empty($f['fecha_desde']) || !empty($f['fecha_hasta']);
$hayFiltro = array_filter($f);
?>
<div class="msg-search-wrap">
  <form method="GET" action="<?= APP_URL ?>/comunicaciones" id="search-form">
    <div class="msg-search-bar">
      <i class="mdi mdi-magnify msg-search-icon"></i>
      <input type="text" name="q" class="msg-search-input"
             placeholder="Buscar en bandeja de entrada…"
             value="<?= htmlspecialchars($f['q'] ?? '') ?>"
             autocomplete="off" spellcheck="false">
      <button type="button" class="msg-filter-btn <?= $hayFiltroAvanzado ? 'active' : '' ?>"
              onclick="toggleMsgFilter()" title="Filtros avanzados">
        <i class="mdi mdi-tune-variant"></i>
      </button>
      <?php if ($hayFiltro): ?>
      <a href="<?= APP_URL ?>/comunicaciones" class="msg-clear-btn" title="Limpiar filtros">
        <i class="mdi mdi-close-circle"></i>
      </a>
      <?php endif; ?>
    </div>
    <input type="hidden" name="estado" id="estado-hidden" value="<?= htmlspecialchars($f['estado'] ?? '') ?>">
    <div class="msg-filter-panel" id="msg-filter-panel" <?= $hayFiltroAvanzado ? '' : 'style="display:none"' ?>>
      <div class="msg-filter-grid">
        <div class="msg-filter-field">
          <label>De</label>
          <input type="text" name="de" placeholder="Nombre del remitente"
                 value="<?= htmlspecialchars($f['de'] ?? '') ?>">
        </div>
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
        <a href="<?= APP_URL ?>/comunicaciones" class="btn-secondary btn-sm">Limpiar</a>
      </div>
    </div>
  </form>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
  <div style="display:flex;align-items:center;gap:10px">
    <label style="display:flex;align-items:center;gap:6px;font-size:.85rem">
      <input type="checkbox" id="ck-todos" onclick="toggleTodosMsg(this)"> Seleccionar todo
    </label>
    <span id="msg-sel-count" style="font-size:.8rem;color:var(--text-muted)"></span>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <div id="msg-bulk-actions" style="display:none;gap:6px">
      <button type="button" class="btn-secondary btn-sm" onclick="accionMasivaMsg('marcar_leido')"><i class="mdi mdi-email-open-outline"></i> Marcar leído</button>
      <button type="button" class="btn-secondary btn-sm" onclick="accionMasivaMsg('marcar_no_leido')"><i class="mdi mdi-email-outline"></i> Marcar no leído</button>
      <button type="button" class="btn-secondary btn-sm" onclick="accionMasivaMsg('archivar')"><i class="mdi mdi-archive-outline"></i> Archivar</button>
      <button type="button" class="btn-secondary btn-sm" onclick="accionMasivaMsg('eliminar')" style="color:var(--danger)"><i class="mdi mdi-trash-can-outline"></i> Eliminar</button>
    </div>
    <select id="filtro-estado" class="form-input" style="max-width:170px" onchange="cambiarFiltroEstado(this.value)">
      <option value="" <?= empty($f['estado']) ? 'selected' : '' ?>>Leídos y no leídos</option>
      <option value="no_leidos" <?= ($f['estado'] ?? '') === 'no_leidos' ? 'selected' : '' ?>>Solo no leídos</option>
      <option value="leidos" <?= ($f['estado'] ?? '') === 'leidos' ? 'selected' : '' ?>>Solo leídos</option>
    </select>
  </div>
</div>

<div class="card" id="msg-list">
  <?php include __DIR__ . '/_bandeja_lista.php'; ?>
</div>

<?php
$extraFoot = '<script>
const _BASE = "' . APP_URL . '/comunicaciones";
let _csrfBulk = "' . csrfToken() . '";

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
  // copiar filtros avanzados si existen
  document.querySelectorAll("#search-form input[name]:not(.msg-search-input)").forEach(el => {
    if (el.value) params.set(el.name, el.value);
  });
  list.style.opacity = "0.5";
  fetch(_BASE + "?" + params.toString())
    .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
    .then(html => { list.innerHTML = html; list.style.opacity = "1"; actualizarSeleccionMsg(); })
    .catch(() => { list.style.opacity = "1"; }); // en error, mantiene lista actual
}

function cambiarFiltroEstado(valor) {
  document.getElementById("estado-hidden").value = valor;
  buscarAjax();
}

function actualizarSeleccionMsg() {
  const n = document.querySelectorAll(".msg-ck:checked").length;
  document.getElementById("msg-sel-count").textContent = n ? n + " seleccionado" + (n !== 1 ? "s" : "") : "";
  document.getElementById("msg-bulk-actions").style.display = n ? "flex" : "none";
  const total = document.querySelectorAll(".msg-ck").length;
  document.getElementById("ck-todos").checked = n > 0 && n === total;
}
function toggleMsgSeleccion() { actualizarSeleccionMsg(); }
function toggleTodosMsg(cb) {
  document.querySelectorAll(".msg-ck").forEach(el => el.checked = cb.checked);
  actualizarSeleccionMsg();
}

function accionMasivaMsg(accion) {
  const ids = Array.from(document.querySelectorAll(".msg-ck:checked")).map(el => el.value);
  if (!ids.length) return;
  const labels = {archivar:"archivar", eliminar:"eliminar", marcar_leido:"marcar como leídos", marcar_no_leido:"marcar como no leídos"};
  if ((accion === "eliminar" || accion === "archivar") && !confirm("¿Deseas " + labels[accion] + " " + ids.length + " conversación(es)?")) return;

  const body = new URLSearchParams();
  body.set("' . CSRF_TOKEN_NAME . '", _csrfBulk);
  body.set("accion", accion);
  ids.forEach(id => body.append("ids[]", id));

  fetch(_BASE + "/accion-masiva", { method: "POST", body })
    .then(r => r.json())
    .then(d => {
      if (d.csrf) _csrfBulk = d.csrf;
      if (d.ok) { buscarAjax(); }
      else { alert(d.error || "No se pudo completar la acción."); }
    })
    .catch(() => alert("No se pudo completar la acción."));
}

// Búsqueda en tiempo real
let _t;
document.querySelector(".msg-search-input").addEventListener("input", function() {
  clearTimeout(_t);
  _t = setTimeout(buscarAjax, 350);
});

// Botón buscar y filtros avanzados
document.getElementById("search-form").addEventListener("submit", function(e) {
  e.preventDefault();
  buscarAjax();
});
</script>';
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
