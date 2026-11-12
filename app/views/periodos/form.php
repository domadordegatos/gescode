<?php
$pageTitle   = ($editando ? 'Editar' : 'Nuevo') . ' período — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio',   'url' => APP_URL . '/dashboard'],
    ['label' => 'Períodos', 'url' => APP_URL . '/periodos'],
    ['label' => $editando ? 'Editar' : 'Nuevo período', 'url' => ''],
];
$noLeidos = 0;

$action = $editando
    ? APP_URL . '/periodos/' . $periodo['id'] . '/actualizar'
    : APP_URL . '/periodos/guardar';

$v = fn(string $k, $def = '') => htmlspecialchars($periodo[$k] ?? $def);
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $editando ? 'Editar período' : 'Nuevo período' ?></h1>
        <p class="page-subtitle">Año lectivo: <strong><?= e($ano['nombre']) ?></strong></p>
    </div>
    <a href="<?= APP_URL ?>/periodos" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Volver
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- ── Formulario principal ─────────────────────────────────── -->
    <div class="card" style="padding:24px">
        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

            <!-- Nombre y número -->
            <div style="display:grid;grid-template-columns:1fr 130px;gap:14px">
                <div class="form-group">
                    <label for="nombre">Nombre del período <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="form-input"
                           placeholder="Ej: Primer Período, Período 1…"
                           value="<?= $v('nombre') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="numero">Número</label>
                    <input type="number" id="numero" name="numero" class="form-input"
                           min="1" max="20"
                           value="<?= $editando ? (int)$periodo['numero'] : (int)$siguiente ?>">
                </div>
            </div>

            <!-- Porcentaje -->
            <div class="form-group">
                <label for="porcentaje">
                    Porcentaje del período <span class="required">*</span>
                </label>
                <div style="display:flex;align-items:center;gap:12px">
                    <div style="position:relative;width:160px">
                        <input type="number" id="porcentaje" name="porcentaje"
                               class="form-input" step="0.01" min="0.01" max="100"
                               value="<?= $editando ? number_format((float)$periodo['porcentaje_ano'],2,'.','') : number_format($pct_libre,2,'.','') ?>"
                               oninput="actualizarPct()" required
                               style="padding-right:30px">
                        <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:700">%</span>
                    </div>
                    <div id="pct-indicator" style="font-size:.85rem;font-weight:700"></div>
                </div>
                <div style="margin-top:8px;background:var(--border);border-radius:20px;height:8px;overflow:hidden;max-width:400px">
                    <div id="pct-bar" style="height:100%;border-radius:20px;transition:width .2s,background .2s"></div>
                </div>
                <small class="form-hint" id="pct-hint">
                    Otros períodos ocupan <strong><?= number_format($suma,2) ?>%</strong>.
                    Disponible: <strong id="pct-disponible"><?= number_format($pct_libre,2) ?>%</strong>
                </small>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:20px 0">
            <p class="section-title" style="margin-bottom:16px">
                <i class="mdi mdi-calendar-range-outline" style="color:var(--primary)"></i>
                Fechas del período
                <span style="font-size:.78rem;font-weight:400;color:var(--text-muted);margin-left:6px">(todas opcionales, solo informativas)</span>
            </p>

            <!-- Fechas en grid 2 col -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label for="fecha_inicio">
                        <i class="mdi mdi-calendar-start"></i> Inicio del período
                    </label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-input"
                           value="<?= $v('fecha_inicio') ?>">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">
                        <i class="mdi mdi-calendar-end"></i> Fin del período
                    </label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-input"
                           value="<?= $v('fecha_fin') ?>">
                </div>
                <div class="form-group">
                    <label for="cierre_notas">
                        <i class="mdi mdi-calendar-lock-outline"></i> Cierre de notas
                    </label>
                    <input type="date" id="cierre_notas" name="cierre_notas" class="form-input"
                           value="<?= $v('cierre_notas') ?>">
                    <small class="form-hint">Fecha límite para registrar notas</small>
                </div>
                <div class="form-group">
                    <label for="max_notas">
                        <i class="mdi mdi-calendar-clock-outline"></i> Máx. subida de notas
                    </label>
                    <input type="date" id="max_notas" name="max_notas" class="form-input"
                           value="<?= $v('max_notas') ?>">
                    <small class="form-hint">Fecha máxima absoluta</small>
                </div>
                <div class="form-group">
                    <label for="fecha_evaluaciones">
                        <i class="mdi mdi-calendar-check-outline"></i> Subida de evaluaciones
                    </label>
                    <input type="date" id="fecha_evaluaciones" name="fecha_evaluaciones" class="form-input"
                           value="<?= $v('fecha_evaluaciones') ?>">
                    <small class="form-hint">Fecha para publicar evaluaciones</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="mdi mdi-content-save-outline"></i>
                    <?= $editando ? 'Guardar cambios' : 'Crear período' ?>
                </button>
                <a href="<?= APP_URL ?>/periodos" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- ── Panel lateral: resumen ────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:12px">

        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">
                <i class="mdi mdi-chart-pie" style="color:var(--primary)"></i> Distribución actual
            </div>
            <?php if ($suma > 0): ?>
                <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:10px">
                    Porcentaje ocupado por otros períodos:
                </div>
                <div style="background:var(--border);border-radius:20px;height:14px;overflow:hidden;margin-bottom:8px">
                    <div style="height:100%;width:<?= min(100,$suma) ?>%;background:var(--primary);border-radius:20px"></div>
                </div>
                <div style="font-weight:800;font-size:1.1rem;color:var(--primary)"><?= number_format($suma,2) ?>% ocupado</div>
                <div style="font-size:.82rem;color:var(--text-muted)">Disponible: <strong><?= number_format($pct_libre,2) ?>%</strong></div>
            <?php else: ?>
                <div class="empty-state" style="padding:20px 0">
                    <i class="mdi mdi-chart-pie" style="font-size:32px"></i>
                    <p>Este es el primer período</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">
                <i class="mdi mdi-information-outline" style="color:var(--primary)"></i> Recomendaciones
            </div>
            <ul style="font-size:.82rem;color:var(--text-muted);padding-left:1.2em;line-height:1.8">
                <li>La suma de todos los períodos debe ser exactamente <strong>100%</strong>.</li>
                <li>Los porcentajes típicos para <strong>4 períodos</strong> son 25% cada uno.</li>
                <li>Para <strong>3 períodos</strong>: 33.33% / 33.33% / 33.34%.</li>
                <li>Las fechas son solo informativas; el bloqueo de notas se controla manualmente.</li>
            </ul>
        </div>

    </div>

</div>

<script>
const SUMA_OTROS = <?= json_encode($suma) ?>;
const bar  = document.getElementById('pct-bar');
const ind  = document.getElementById('pct-indicator');
const disp = document.getElementById('pct-disponible');

function actualizarPct() {
    const val  = parseFloat(document.getElementById('porcentaje').value) || 0;
    const total = SUMA_OTROS + val;
    const pct   = Math.min(100, total);
    const ok    = Math.abs(total - 100) < 0.01;
    const over  = total > 100.01;
    const color = ok ? 'var(--success)' : over ? 'var(--danger)' : 'var(--warning)';

    bar.style.width      = pct + '%';
    bar.style.background = color;
    ind.style.color      = color;
    ind.textContent      = ok ? '✔ Suma perfecta (100%)' : over
        ? `⚠ Total: ${total.toFixed(2)}% (+${(total-100).toFixed(2)}%)`
        : `Total: ${total.toFixed(2)}% — Faltan ${(100-total).toFixed(2)}%`;
    disp.textContent     = Math.max(0, 100 - SUMA_OTROS - val).toFixed(2) + '%';
}
actualizarPct();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
