<?php
$pageTitle   = 'Rendimiento — ' . e($est['apellido'].', '.$est['nombre']) . ' — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo  = 'estudiantes';
$breadcrumbs = [
  ['label' => 'Inicio',     'url' => APP_URL.'/dashboard'],
  ['label' => 'Mis clases', 'url' => APP_URL.'/mis-grupos'],
  ['label' => $est['grado'].' '.$est['grupo'], 'url' => APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label' => e($est['apellido'].', '.$est['nombre']), 'url' => ''],
];

// Para que _grupo_tabs funcione necesita $grupo
$grupo = ['grado' => $est['grado'], 'grupo' => $est['grupo'],
          'grupo_id' => $gid, 'materia' => '', 'ano' => $est['ano']];

$periodosVis = periodosVisibles($periodos);
$grupo_periodo_select = '<select class="cp-select" style="font-size:.8rem;padding:4px 8px"
    onchange="location.href=location.pathname+\'?periodo=\'+this.value">';
foreach ($periodosVis as $p) {
    $sel = (int)$p['id'] === $periodo_id ? ' selected' : '';
    $dot = $p['activo'] ? ' ●' : '';
    $grupo_periodo_select .= '<option value="'.e($p['id']).'"'.$sel.'>'.e($p['nombre']).$dot.'</option>';
}
$grupo_periodo_select .= '</select>';

// Estadísticas globales
$totalMaterias   = count($materias);
$aprobadas       = 0;
$sumaPromedios   = 0;
$conNota         = 0;
foreach ($materias as $m) {
    if ($m['promedio'] !== null) {
        $conNota++;
        $sumaPromedios += $m['promedio'];
        if ($m['promedio'] >= 6) $aprobadas++;
    }
}
$promedioGlobal = $conNota > 0 ? $sumaPromedios / $conNota : null;

$pctGlobal  = $promedioGlobal !== null ? min(100, round($promedioGlobal / 10 * 100)) : 0;
$globalOk   = $promedioGlobal !== null && $promedioGlobal >= 6;
$iniciales  = strtoupper(mb_substr($est['nombre'], 0, 1) . mb_substr($est['apellido'], 0, 1));
$matId      = $est['matricula_id'];

ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<style>
.rend-layout { display:flex; gap:20px; margin-top:20px; align-items:flex-start; }
.rend-sidebar { width:260px; flex-shrink:0; position:sticky; top:80px; }
.rend-main { flex:1; min-width:0; }

.est-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.est-card-banner { height:56px; background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); }
.est-card-body { padding:0 16px 20px; }
.est-avatar-wrap { margin-top:-28px; margin-bottom:10px; }
.est-avatar { width:56px;height:56px;border-radius:50%;border:3px solid var(--surface);background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#fff;overflow:hidden; }
.est-avatar img { width:100%;height:100%;object-fit:cover; }
.est-card-name { font-weight:700;font-size:1rem;line-height:1.3;margin-bottom:2px; }
.est-card-meta { font-size:.8rem;color:var(--text-muted); }

.est-stats-row { margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.est-stat-box { background:var(--bg);border-radius:10px;padding:10px 12px;text-align:center; }
.est-stat-val { display:block;font-size:1.35rem;font-weight:800;line-height:1.1; }
.est-stat-val.ok { color:#16a34a; } .est-stat-val.warn { color:#dc2626; } .est-stat-val.neutral { color:var(--primary); }
.est-stat-lbl { display:block;font-size:.72rem;color:var(--text-muted);margin-top:2px; }

.est-gauge-wrap { margin-top:16px; }
.est-gauge-lbl { display:flex;justify-content:space-between;font-size:.78rem;color:var(--text-muted);margin-bottom:4px; }
.est-gauge-bar { height:8px;border-radius:4px;background:var(--border);overflow:hidden; }
.est-gauge-fill { height:100%;border-radius:4px;background:var(--primary);transition:width .4s ease; }
.est-gauge-fill.ok { background:#16a34a; } .est-gauge-fill.warn { background:#dc2626; }

.est-actions { margin-top:12px;display:flex;flex-direction:column;gap:6px; }


.mat-panel { background:var(--surface);border:1px solid var(--border);border-radius:12px;margin-bottom:14px;overflow:hidden; }
.mat-panel-head { display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer;user-select:none; }
.mat-panel-head:hover { background:var(--bg); }
.mat-toggle-icon { font-size:1.1rem;color:var(--text-muted);transition:transform .25s ease;flex-shrink:0; }
.mat-panel.open .mat-toggle-icon { transform:rotate(90deg); }
.mat-panel-name { flex:1;font-weight:700;font-size:.95rem; }
.mat-score-bar-wrap { flex-shrink:0;width:80px; }
.mat-score-bar { height:6px;border-radius:3px;background:var(--border);overflow:hidden;margin-bottom:3px; }
.mat-score-fill { height:100%;border-radius:3px;transition:width .4s ease; }
.mat-score-fill.ok { background:#16a34a; } .mat-score-fill.warn { background:#dc2626; } .mat-score-fill.neutral { background:var(--primary); }
.mat-score-num { font-size:.75rem;font-weight:700;text-align:right;color:var(--text-muted);transition:color .2s; }
.mat-score-num.ok { color:#16a34a; } .mat-score-num.warn { color:#dc2626; }

.mat-panel-body { display:none;border-top:1px solid var(--border); }
.mat-panel.open .mat-panel-body { display:block; }

.ind-block { padding:14px 16px;border-bottom:1px solid var(--border); }
.ind-block:last-child { border-bottom:none; }
.ind-block-head { display:flex;align-items:flex-start;gap:10px;margin-bottom:10px; }
.ind-code { display:inline-block;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:2px 7px;font-size:.72rem;font-weight:700;color:var(--text-muted);white-space:nowrap;flex-shrink:0; }
.ind-desc { font-size:.85rem;color:var(--text);line-height:1.4;flex:1; }
.ind-def-badge { flex-shrink:0;display:inline-flex;align-items:center;gap:4px;font-size:.78rem;font-weight:700;background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:2px 10px;transition:background .2s,color .2s; }
.ind-def-badge.ok { background:#dcfce7;border-color:#86efac;color:#15803d; }
.ind-def-badge.warn { background:#fee2e2;border-color:#fca5a5;color:#b91c1c; }
.ind-def-badge.sin { color:var(--text-muted); }

/* ── Chips editables ── */
.eval-chips { display:flex;flex-wrap:wrap;gap:8px; }
.eval-chip {
  display:flex;flex-direction:column;align-items:center;
  background:var(--bg);border:1px solid var(--border);border-radius:10px;
  padding:7px 12px;min-width:72px;text-align:center;
  cursor:pointer;transition:border-color .15s,box-shadow .15s;
  position:relative;
}
.eval-chip:hover { border-color:var(--primary); box-shadow:0 0 0 2px color-mix(in srgb,var(--primary) 15%,transparent); }
.eval-chip.editing { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 20%,transparent); }
.eval-chip.saving { opacity:.6; pointer-events:none; }
.eval-chip-name { font-size:.72rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;margin-bottom:4px; }
.eval-chip-date { font-size:.65rem;color:var(--text-muted);margin-bottom:4px; }
.eval-chip-nota { font-size:1.05rem;font-weight:800;line-height:1;transition:color .2s; }
.eval-chip-nota.ok { color:#16a34a; } .eval-chip-nota.warn { color:#dc2626; } .eval-chip-nota.sin { color:var(--text-muted);font-size:.85rem; }
.eval-chip-input {
  width:52px; font-size:1.05rem; font-weight:800; text-align:center;
  border:none; background:transparent; color:var(--primary);
  outline:none; padding:0;
  -moz-appearance:textfield;
}
.eval-chip-input::-webkit-inner-spin-button,
.eval-chip-input::-webkit-outer-spin-button { -webkit-appearance:none; }
.eval-chip-hint { font-size:.6rem;color:var(--text-muted);margin-top:2px; display:none; }
.eval-chip.editing .eval-chip-hint { display:block; }

/* Toast de guardado */
.nota-toast {
  position:fixed; bottom:24px; right:24px; z-index:9000;
  background:var(--surface); border:1px solid var(--border);
  border-radius:10px; padding:10px 16px;
  display:flex; align-items:center; gap:8px;
  font-size:.85rem; font-weight:600;
  box-shadow:0 4px 20px rgba(0,0,0,.12);
  transform:translateY(60px); opacity:0;
  transition:transform .25s ease, opacity .25s ease;
  pointer-events:none;
}
.nota-toast.show { transform:translateY(0); opacity:1; }
.nota-toast.ok { color:#15803d; border-color:#86efac; }
.nota-toast.err { color:#b91c1c; border-color:#fca5a5; }

@media(max-width:720px){
  .rend-layout { flex-direction:column; }
  .rend-sidebar { width:100%;position:static; }
}
</style>

<div class="rend-layout">

  <!-- ── Columna izquierda: perfil ── -->
  <aside class="rend-sidebar">
    <div class="est-card">
      <div class="est-card-banner"></div>
      <div class="est-card-body">
        <div class="est-avatar-wrap">
          <div class="est-avatar">
            <img src="<?= APP_URL ?>/perfil/avatar/<?= $est['usuario_id'] ?>"
                 alt="<?= $iniciales ?>"
                 onerror="this.style.display='none';this.parentNode.textContent='<?= $iniciales ?>'">
          </div>
        </div>
        <div class="est-card-name"><?= e($est['apellido'].', '.$est['nombre']) ?></div>
        <div class="est-card-meta"><?= e($est['grado'].' '.$est['grupo']) ?><br>Doc. <?= e($est['documento']) ?></div>

        <?php if ($conNota > 0): ?>
        <div class="est-stats-row">
          <div class="est-stat-box">
            <span class="est-stat-val neutral"><?= $totalMaterias ?></span>
            <span class="est-stat-lbl">Materias</span>
          </div>
          <div class="est-stat-box">
            <span class="est-stat-val <?= $aprobadas === $totalMaterias ? 'ok' : ($aprobadas === 0 ? 'warn' : 'neutral') ?>">
              <?= $aprobadas ?>/<?= $totalMaterias ?>
            </span>
            <span class="est-stat-lbl">Aprobadas</span>
          </div>
        </div>
        <div class="est-gauge-wrap">
          <div class="est-gauge-lbl">
            <span>Promedio global</span>
            <strong style="color:<?= $globalOk ? '#16a34a' : '#dc2626' ?>"><?= number_format($promedioGlobal, 1) ?></strong>
          </div>
          <div class="est-gauge-bar">
            <div class="est-gauge-fill <?= $globalOk ? 'ok' : 'warn' ?>" style="width:<?= $pctGlobal ?>%"></div>
          </div>
        </div>
        <?php endif; ?>

        <div class="est-actions">
          <a href="<?= APP_URL ?>/comunicaciones/redactar?est=<?= $est['estudiante_id'] ?>"
             class="btn-secondary btn-sm" style="justify-content:center">
            <i class="mdi mdi-email-outline"></i> Enviar mensaje
          </a>
          <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/estudiantes"
             class="btn-secondary btn-sm" style="justify-content:center">
            <i class="mdi mdi-arrow-left"></i> Volver al listado
          </a>
        </div>
      </div>
    </div>
  </aside>

  <!-- ── Columna derecha: notas ── -->
  <div class="rend-main">

    <?php if (empty($materias)): ?>
      <div class="empty-state">
        <i class="mdi mdi-clipboard-text-off-outline"></i>
        <p>No hay evaluaciones registradas para <?= e($periodo_sel['nombre'] ?? 'este periodo') ?>.</p>
      </div>
    <?php else: ?>

      <?php foreach ($materias as $midx => $mat): ?>
      <?php
        $prom   = $mat['promedio'];
        $promOk = $prom !== null && $prom >= 6;
        $pctBar = $prom !== null ? min(100, round($prom / 10 * 100)) : 0;
        $barCls = $prom === null ? 'neutral' : ($promOk ? 'ok' : 'warn');
        $numCls = $prom === null ? '' : ($promOk ? 'ok' : 'warn');
      ?>
      <div class="mat-panel <?= $midx === 0 ? 'open' : '' ?>" data-mat-id="<?= $mat['materia_id'] ?? $midx ?>">
        <div class="mat-panel-head" onclick="this.closest('.mat-panel').classList.toggle('open')">
          <i class="mdi mdi-chevron-right mat-toggle-icon"></i>
          <span class="mat-panel-name"><?= e($mat['nombre']) ?></span>
          <div class="mat-score-bar-wrap">
            <div class="mat-score-bar">
              <div class="mat-score-fill <?= $barCls ?>" style="width:<?= $pctBar ?>%"></div>
            </div>
            <div class="mat-score-num <?= $numCls ?>">
              <?= $prom !== null ? number_format($prom, 1) : '—' ?>
            </div>
          </div>
        </div>

        <div class="mat-panel-body">
          <?php if (empty($mat['indicadores'])): ?>
            <p class="text-muted" style="padding:14px 16px;font-size:.85rem">Sin indicadores en este periodo.</p>
          <?php else: ?>
            <?php foreach ($mat['indicadores'] as $ind): ?>
            <?php
              $indDef   = $ind['definitiva'];
              $indDefOk = $indDef !== null && $indDef >= 6;
            ?>
            <div class="ind-block" data-ind-id="<?= $ind['ind_id'] ?? '' ?>">
              <div class="ind-block-head">
                <?php if ($ind['codigo']): ?>
                  <span class="ind-code"><?= e($ind['codigo']) ?></span>
                <?php endif; ?>
                <span class="ind-desc"><?= e($ind['descripcion']) ?></span>
                <span class="ind-def-badge <?= $indDef !== null ? ($indDefOk ? 'ok' : 'warn') : 'sin' ?>">
                  <i class="mdi mdi-star-outline" style="font-size:.85rem"></i>
                  <span class="ind-def-val"><?= $indDef !== null ? number_format($indDef, 1) : '—' ?></span>
                </span>
              </div>
              <div class="eval-chips">
                <?php foreach ($ind['evaluaciones'] as $eval_id => $ev): ?>
                <div class="eval-chip"
                     data-eval-id="<?= $eval_id ?>"
                     data-mat-id="<?= $matId ?>"
                     title="Clic para editar nota">
                  <span class="eval-chip-name" title="<?= e($ev['nombre']) ?>"><?= e($ev['nombre']) ?></span>
                  <?php if ($ev['fecha']): ?>
                    <span class="eval-chip-date"><?= date('d/m', strtotime($ev['fecha'])) ?></span>
                  <?php endif; ?>
                  <span class="eval-chip-nota <?= $ev['nota'] !== null ? ($ev['nota'] >= 6 ? 'ok' : 'warn') : 'sin' ?>">
                    <?= $ev['nota'] !== null ? number_format($ev['nota'], 1) : '—' ?>
                  </span>
                  <span class="eval-chip-hint">↵ guardar · Esc cancelar</span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

    <?php endif; ?>
  </div>
</div>

<!-- Toast -->
<div class="nota-toast" id="nota-toast"></div>

<script>
const NOTA_API  = '<?= APP_URL ?>/api/nota';
const NOTA_MIN  = <?= notaMin() ?>;
const NOTA_MAX  = <?= notaMax() ?>;
let   csrfToken = '<?= csrfToken() ?>';

// ── Activar chips editables
document.querySelectorAll('.eval-chip').forEach(chip => {
  chip.addEventListener('click', () => startEdit(chip));
});

function startEdit(chip) {
  if (chip.classList.contains('editing')) return;

  const notaEl = chip.querySelector('.eval-chip-nota');
  const rawVal = notaEl.textContent.trim();
  const current = rawVal === '—' ? '' : rawVal;

  const input = document.createElement('input');
  input.type  = 'number';
  input.min   = NOTA_MIN;
  input.max   = NOTA_MAX;
  input.step  = '0.1';
  input.value = current;
  input.className = 'eval-chip-input';

  notaEl.replaceWith(input);
  chip.classList.add('editing');
  input.focus();
  input.select();

  let committed = false;

  async function commit() {
    if (committed) return;
    committed = true;
    await saveNota(chip, input, notaEl);
  }

  input.addEventListener('blur', commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') {
      committed = true;
      input.replaceWith(notaEl);
      chip.classList.remove('editing');
    }
  });
}

async function saveNota(chip, input, originalEl) {
  const val     = input.value.trim();
  const evalId  = chip.dataset.evalId;
  const matId   = chip.dataset.matId;

  // Si el valor no cambió (ambos vacíos o mismo número), no hacer nada
  const prevTxt = originalEl.textContent.trim();
  const prevNum = prevTxt === '—' ? '' : prevTxt;
  if (val === prevNum) {
    input.replaceWith(originalEl);
    chip.classList.remove('editing');
    return;
  }

  chip.classList.add('saving');

  const body = new URLSearchParams({ eval_id: evalId, mat_id: matId, valor: val, _csrf: csrfToken });

  try {
    const res  = await fetch(NOTA_API, { method: 'POST', body });
    const data = await res.json();

    if (data.ok) {
      csrfToken = data.csrf;

      // Actualizar chip
      const nota = data.nota;
      originalEl.textContent = nota !== null ? parseFloat(nota).toFixed(1) : '—';
      originalEl.className   = 'eval-chip-nota ' + notaCls(nota);
      input.replaceWith(originalEl);

      // Actualizar definitiva del indicador
      const indBlock = chip.closest('.ind-block');
      const defBadge = indBlock.querySelector('.ind-def-badge');
      const defVal   = indBlock.querySelector('.ind-def-val');
      const def      = data.definitiva;
      defVal.textContent   = def !== null ? parseFloat(def).toFixed(1) : '—';
      defBadge.className   = 'ind-def-badge ' + (def !== null ? (def >= 6 ? 'ok' : 'warn') : 'sin');

      // Actualizar barra y número de la materia
      const matPanel = chip.closest('.mat-panel');
      const barFill  = matPanel.querySelector('.mat-score-fill');
      const barNum   = matPanel.querySelector('.mat-score-num');
      const prom     = data.promedio;
      const pct      = prom !== null ? Math.min(100, Math.round(prom / 10 * 100)) : 0;
      barFill.style.width = pct + '%';
      barFill.className   = 'mat-score-fill ' + (prom !== null ? (prom >= 6 ? 'ok' : 'warn') : 'neutral');
      barNum.textContent  = prom !== null ? parseFloat(prom).toFixed(1) : '—';
      barNum.className    = 'mat-score-num ' + (prom !== null ? (prom >= 6 ? 'ok' : 'warn') : '');

      showToast('Nota guardada', 'ok');
    } else {
      input.replaceWith(originalEl);
      showToast(data.error || 'Error al guardar', 'err');
    }
  } catch (err) {
    input.replaceWith(originalEl);
    showToast('Error de conexión', 'err');
  }

  chip.classList.remove('editing', 'saving');
}

function notaCls(nota) {
  if (nota === null) return 'sin';
  return parseFloat(nota) >= 6 ? 'ok' : 'warn';
}

let toastTimer;
function showToast(msg, type) {
  const toast = document.getElementById('nota-toast');
  toast.textContent = msg;
  toast.className   = 'nota-toast ' + type + ' show';
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
}
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
