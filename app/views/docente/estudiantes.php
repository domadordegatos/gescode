<?php
$gid         = $grupo['grupo_id'];
$pageTitle   = 'Estudiantes — '.$grupo['grado'].' '.$grupo['grupo'].' — '.APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo  = 'estudiantes';
$breadcrumbs = [
  ['label'=>'Inicio',    'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos','url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>''],
];
ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<div class="page-header" style="margin-top:20px">
  <h1 class="page-title">Estudiantes</h1>
  <div class="d-flex gap-8 align-center">
    <span class="badge-count"><?= count($estudiantes) ?> matriculados</span>
    <?php if ($periodo): ?>
      <span class="period-badge">
        <i class="mdi mdi-calendar-range"></i> <?= e($periodo['nombre']) ?>
      </span>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/boletines/grupo/<?= $gid ?>" class="btn-sm btn-outline">
      <i class="mdi mdi-file-pdf-box"></i> Boletines del grupo
    </a>
  </div>
</div>

<?php if (empty($estudiantes)): ?>
  <div class="empty-state">
    <i class="mdi mdi-account-school-outline"></i>
    <p>No hay estudiantes matriculados en este grupo.</p>
  </div>
<?php else: ?>
<div class="card" style="overflow:auto">
  <table class="data-table est-select-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Estudiante</th>
        <th>Documento</th>
        <th>Género</th>
        <th>Estado</th>
        <th style="width:80px;text-align:center">Acciones</th>
        <th style="width:36px;text-align:center">
          <label class="est-cb-wrap" title="Seleccionar todos">
            <input type="checkbox" id="cb-all" onchange="toggleAll(this)">
            <span class="est-cb"></span>
          </label>
        </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($estudiantes as $i => $est): ?>
      <tr class="est-row" data-uid="<?= $est['usuario_id'] ?>"
          data-estid="<?= $est['estudiante_id'] ?>">
        <td class="text-muted"><?= $i+1 ?></td>
        <td>
          <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/estudiante/<?= $est['usuario_id'] ?>/notas"
             class="est-name-link"><?= e($est['apellido'].', '.$est['nombre']) ?></a>
        </td>
        <td><?= e($est['documento']) ?></td>
        <td><?= $est['genero'] === 'F' ? 'Femenino' : ($est['genero'] === 'M' ? 'Masculino' : '—') ?></td>
        <td>
          <span class="estado-badge estado-<?= $est['estado'] ?>">
            <?= ucfirst($est['estado']) ?>
          </span>
        </td>
        <td style="text-align:center;white-space:nowrap">
          <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/estudiante/<?= $est['usuario_id'] ?>/notas"
             class="action-link" title="Ver notas">
            <i class="mdi mdi-clipboard-list-outline"></i>
          </a>
          <a href="<?= APP_URL ?>/comunicaciones/redactar?est=<?= $est['estudiante_id'] ?>"
             class="action-link" title="Enviar mensaje">
            <i class="mdi mdi-email-outline"></i>
          </a>
        </td>
        <td style="text-align:center">
          <label class="est-cb-wrap">
            <input type="checkbox" class="est-cb-input" onchange="updateSelection()">
            <span class="est-cb"></span>
          </label>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Barra flotante de acciones masivas -->
<div class="bulk-bar" id="bulk-bar">
  <div class="bulk-bar-inner">
    <span class="bulk-count">
      <i class="mdi mdi-account-check-outline"></i>
      <strong id="bulk-count-num">0</strong> seleccionados
    </span>
    <div class="bulk-actions">
      <button type="button" class="bulk-btn bulk-btn-notes" onclick="bulkNotas()" title="Ver notas comparadas">
        <i class="mdi mdi-clipboard-list-outline"></i> Ver notas
      </button>
      <button type="button" class="bulk-btn bulk-btn-msg" onclick="bulkMensaje()" title="Enviar mensaje a seleccionados">
        <i class="mdi mdi-email-outline"></i> Enviar mensaje
      </button>
      <button type="button" class="bulk-btn bulk-btn-cancel" onclick="clearSelection()" title="Cancelar selección">
        <i class="mdi mdi-close"></i>
      </button>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
const _GID  = <?= $gid ?>;
const _BASE = '<?= APP_URL ?>';

function getSelected() {
  return [...document.querySelectorAll('.est-cb-input:checked')]
    .map(cb => cb.closest('tr'));
}

function updateSelection() {
  const rows    = [...document.querySelectorAll('.est-cb-input')];
  const checked = rows.filter(r => r.checked);
  const bar     = document.getElementById('bulk-bar');
  const countEl = document.getElementById('bulk-count-num');
  const cbAll   = document.getElementById('cb-all');

  cbAll.indeterminate = checked.length > 0 && checked.length < rows.length;
  cbAll.checked       = checked.length > 0 && checked.length === rows.length;

  rows.forEach(cb => cb.closest('tr').classList.toggle('est-row--selected', cb.checked));

  countEl.textContent = checked.length;
  bar.classList.toggle('visible', checked.length > 0);
}

function toggleAll(cbAll) {
  document.querySelectorAll('.est-cb-input').forEach(cb => cb.checked = cbAll.checked);
  updateSelection();
}

function clearSelection() {
  document.querySelectorAll('.est-cb-input').forEach(cb => cb.checked = false);
  const cbAll = document.getElementById('cb-all');
  cbAll.checked = false;
  cbAll.indeterminate = false;
  updateSelection();
}

function bulkMensaje() {
  const params = getSelected().map(tr => 'est[]=' + tr.dataset.estid).join('&');
  window.location.href = _BASE + '/comunicaciones/redactar?' + params;
}

function bulkNotas() {
  const params = getSelected().map(tr => 'uid[]=' + tr.dataset.uid).join('&');
  window.location.href = _BASE + '/grupos/' + _GID + '/notas-grupo?' + params;
}

/* Resetear barra al volver con el botón Atrás (bfcache) */
window.addEventListener('pageshow', function(e) {
  if (e.persisted) clearSelection();
});
</script>
<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
