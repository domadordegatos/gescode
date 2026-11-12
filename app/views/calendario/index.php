<?php
$pageTitle   = 'Agenda — ' . APP_NAME;
$noLeidos    = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Pizarra', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Agenda',  'url' => ''],
];
ob_start();
?>

<style>
/* ── Layout ─────────────────────────────────────────── */
.agenda-wrap {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 800px) {
    .agenda-wrap { grid-template-columns: 1fr; }
}

/* ── Mini calendario ─────────────────────────────────── */
.mini-cal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    position: sticky;
    top: 80px;
}
.mini-cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 12px;
    border-bottom: 1px solid var(--border);
}
.mini-cal-header h3 {
    font-size: .92rem;
    font-weight: 800;
    color: var(--text);
    margin: 0;
    text-transform: capitalize;
}
.mini-cal-nav {
    display: flex;
    gap: 4px;
}
.mini-cal-nav button {
    width: 28px; height: 28px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: transparent;
    cursor: pointer;
    color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    transition: background .15s, color .15s;
}
.mini-cal-nav button:hover { background: var(--primary-light); color: var(--primary); }

.mini-cal-grid {
    padding: 12px 16px 16px;
}
.mini-cal-dow {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    margin-bottom: 4px;
}
.mini-cal-dow span {
    text-align: center;
    font-size: .65rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    padding: 4px 0;
}
.mini-cal-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.mini-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .15s;
    min-height: 34px;
    position: relative;
}
.mini-day:hover { background: var(--surface-2); }
.mini-day.other-month { opacity: .3; pointer-events: none; }
.mini-day.today .day-num { background: var(--primary); color: #fff; border-radius: 50%; }
.mini-day.selected { background: var(--primary-light); }
.mini-day.selected .day-num { color: var(--primary); font-weight: 800; }
.day-num {
    font-size: .78rem;
    font-weight: 600;
    color: var(--text);
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
}
.day-dots {
    display: flex;
    gap: 2px;
    height: 5px;
    align-items: center;
}
.dot {
    width: 4px; height: 4px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-tarea          { background: #F59E0B; }
.dot-evaluacion     { background: #3B82F6; }
.dot-fecha_importante { background: #8B5CF6; }

/* Leyenda */
.mini-cal-legend {
    border-top: 1px solid var(--border);
    padding: 12px 20px;
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.legend-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .75rem;
    color: var(--text-muted);
}
.legend-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ── Cronología ──────────────────────────────────────── */
.cronologia {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    min-height: 400px;
}
.crono-header {
    padding: 18px 24px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.crono-header h2 {
    font-size: .95rem;
    font-weight: 800;
    color: var(--text);
    margin: 0;
}
.crono-header-sub {
    font-size: .78rem;
    color: var(--text-muted);
    margin-top: 2px;
}
.crono-filters {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.filter-chip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: .73rem;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid;
    transition: opacity .15s;
    user-select: none;
}
.filter-chip.off { opacity: .35; }
.chip-tarea      { border-color: #F59E0B; color: #92400E; background: #FEF3C7; }
.chip-evaluacion { border-color: #3B82F6; color: #1E40AF; background: #EFF6FF; }
.chip-fi         { border-color: #8B5CF6; color: #4C1D95; background: #F5F3FF; }

.crono-body { padding: 8px 24px 16px; }

.crono-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 60px 24px;
    color: var(--text-muted);
}
.crono-empty i { font-size: 2.5rem; opacity: .4; }
.crono-empty p { font-size: .85rem; text-align: center; }

/* Fecha dentro de la tarjeta — día único */
.card-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    flex-shrink: 0;
    border-right: 1px solid var(--border);
    padding-right: 14px;
    margin-right: 2px;
}
.card-date-n {
    font-size: 1.25rem;
    font-weight: 900;
    color: var(--text);
    line-height: 1;
}
.card-date-w {
    font-size: .6rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: .05em;
    margin-top: 2px;
}
.card-date-today .card-date-n { color: var(--primary); }
.card-date-today .card-date-w { color: var(--primary); }

/* Fecha dentro de la tarjeta — rango multi-día */
.card-date-range {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    border-right: 1px solid var(--border);
    padding-right: 14px;
    margin-right: 2px;
}
.card-date-range .range-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 28px;
}
.card-date-range .range-n {
    font-size: 1.25rem;
    font-weight: 900;
    color: var(--text);
    line-height: 1;
}
.card-date-range .range-w {
    font-size: .58rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: .04em;
    margin-top: 2px;
}
.card-date-range .range-sep {
    font-size: .9rem;
    color: var(--text-muted);
    font-weight: 300;
    padding: 0 2px;
    align-self: flex-start;
    margin-top: 4px;
}

/* Tarjeta de evento */
.evento-card {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 12px 14px 12px 16px;
    border-radius: 12px;
    border: 1px solid var(--border);
    margin-bottom: 8px;
    cursor: pointer;
    transition: box-shadow .15s, border-color .15s, transform .1s;
    background: var(--surface);
    text-decoration: none;
}
.evento-card:hover {
    border-color: var(--primary);
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transform: translateY(-1px);
}
.evento-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin: 0 14px;
}
.icon-tarea           { background: #FEF3C7; color: #D97706; }
.icon-evaluacion      { background: #EFF6FF; color: #2563EB; }
.icon-fecha_importante{ background: #F5F3FF; color: #7C3AED; }

.evento-body { flex: 1; min-width: 0; }
.evento-titulo {
    font-size: .87rem;
    font-weight: 700;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}
.evento-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.evento-tag {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: .7rem;
    color: var(--text-muted);
    font-weight: 600;
}
.evento-tag i { font-size: .8rem; }
.evento-badge {
    font-size: .65rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 99px;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.badge-tarea           { background: #FEF3C7; color: #92400E; }
.badge-evaluacion      { background: #EFF6FF; color: #1E40AF; }
.badge-fecha_importante{ background: #F5F3FF; color: #4C1D95; }

/* ── Modal ───────────────────────────────────────────── */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9000;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity .2s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
    background: var(--surface);
    border-radius: 20px;
    max-width: 480px; width: 100%;
    overflow: hidden;
    transform: translateY(12px) scale(.97);
    transition: transform .2s;
    box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
.modal-banner {
    height: 6px;
}
.modal-banner.tarea           { background: #F59E0B; }
.modal-banner.evaluacion      { background: #3B82F6; }
.modal-banner.fecha_importante{ background: #8B5CF6; }
.modal-header {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--border);
}
.modal-header-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.modal-header-text { flex: 1; min-width: 0; }
.modal-title {
    font-size: 1rem; font-weight: 800; color: var(--text);
    margin: 0 0 4px;
}
.modal-subtitle { font-size: .78rem; color: var(--text-muted); }
.modal-close {
    border: none; background: none; cursor: pointer;
    color: var(--text-muted); font-size: 1.2rem;
    padding: 4px; line-height: 1;
}
.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; }
.modal-row {
    display: flex; align-items: center; gap: 10px;
    font-size: .84rem; color: var(--text-muted);
}
.modal-row i { font-size: 1rem; width: 20px; flex-shrink: 0; }
.modal-row strong { color: var(--text); margin-left: 4px; }
.modal-footer { padding: 0 24px 20px; display: flex; gap: 10px; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Agenda académica</h1>
        <p class="page-subtitle">Tareas, evaluaciones y fechas institucionales.</p>
    </div>
    <a href="<?= APP_URL ?>/calendario/exportar" class="btn-secondary">
        <i class="mdi mdi-calendar-sync-outline"></i> Exportar a mi calendario
    </a>
</div>

<div class="agenda-wrap">

    <!-- ── Mini calendario ─────────────────────────────── -->
    <div>
        <div class="mini-cal">
            <div class="mini-cal-header">
                <h3 id="mc-titulo">cargando...</h3>
                <div class="mini-cal-nav">
                    <button id="mc-prev" title="Mes anterior"><i class="mdi mdi-chevron-left"></i></button>
                    <button id="mc-hoy"  title="Hoy" style="font-size:.7rem;width:auto;padding:0 8px;font-weight:700">HOY</button>
                    <button id="mc-next" title="Mes siguiente"><i class="mdi mdi-chevron-right"></i></button>
                </div>
            </div>
            <div class="mini-cal-grid">
                <div class="mini-cal-dow">
                    <span>L</span><span>M</span><span>X</span>
                    <span>J</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="mini-cal-days" id="mc-days"></div>
            </div>
            <div class="mini-cal-legend">
                <div class="legend-row"><div class="legend-dot dot-tarea"></div> Tarea / entrega</div>
                <div class="legend-row"><div class="legend-dot dot-evaluacion"></div> Evaluación</div>
                <div class="legend-row"><div class="legend-dot dot-fecha_importante"></div> Fecha institucional</div>
            </div>
        </div>
    </div>

    <!-- ── Cronología ──────────────────────────────────── -->
    <div class="cronologia">
        <div class="crono-header">
            <div>
                <h2 id="crono-titulo">Próximas fechas</h2>
                <div class="crono-header-sub" id="crono-sub">Cargando eventos...</div>
            </div>
            <div class="crono-filters">
                <div class="filter-chip chip-tarea"      data-tipo="tarea"            onclick="toggleFiltro(this)">
                    <i class="mdi mdi-clipboard-text-outline"></i> Tareas
                </div>
                <div class="filter-chip chip-evaluacion" data-tipo="evaluacion"       onclick="toggleFiltro(this)">
                    <i class="mdi mdi-pencil-box-outline"></i> Evaluaciones
                </div>
                <div class="filter-chip chip-fi"         data-tipo="fecha_importante" onclick="toggleFiltro(this)">
                    <i class="mdi mdi-calendar-star"></i> Institucional
                </div>
            </div>
        </div>
        <div class="crono-body" id="crono-body">
            <div class="crono-empty"><i class="mdi mdi-calendar-clock"></i><p>Cargando...</p></div>
        </div>
    </div>

</div>

<!-- ── Modal de evento ─────────────────────────────────── -->
<div class="modal-overlay" id="evento-modal" onclick="cerrarModal(event)">
    <div class="modal-box">
        <div class="modal-banner" id="modal-banner"></div>
        <div class="modal-header">
            <div class="modal-header-icon" id="modal-icon"></div>
            <div class="modal-header-text">
                <h3 class="modal-title" id="modal-titulo"></h3>
                <div class="modal-subtitle" id="modal-subtitulo"></div>
            </div>
            <button class="modal-close" onclick="document.getElementById('evento-modal').classList.remove('open')">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        <div class="modal-body" id="modal-body"></div>
        <div class="modal-footer" id="modal-footer"></div>
    </div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';

// ── Estado ─────────────────────────────────────────────
let currentYear  = new Date().getFullYear();
let currentMonth = new Date().getMonth(); // 0-based
let eventosMes   = [];
let diaSeleccionado = null;
let filtros = { tarea: true, evaluacion: true, fecha_importante: true };

const DIAS_ES   = ['lun','mar','mié','jue','vie','sáb','dom'];
const MESES_ES  = ['enero','febrero','marzo','abril','mayo','junio',
                   'julio','agosto','septiembre','octubre','noviembre','diciembre'];
const DIAS_FULL = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
const TIPOS_LABEL = { tarea: 'Tarea', evaluacion: 'Evaluación', fecha_importante: 'Fecha institucional' };
const TIPOS_ICON  = { tarea: 'mdi-clipboard-text-outline', evaluacion: 'mdi-pencil-box-outline', fecha_importante: 'mdi-calendar-star' };
const TIPOS_URL = {
    tarea:            e => e.id      ? APP_URL + '/tarea/' + e.id : null,
    evaluacion:       e => e.grupo_id ? APP_URL + '/grupos/' + e.grupo_id + '/evaluaciones' : null,
    fecha_importante: e => null,
};
const TIPOS_BTN = {
    tarea:      'Ver tarea',
    evaluacion: 'Ver evaluaciones del grupo',
    fecha_importante: null,
};

// ── Inicializar ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    cargarMes(currentYear, currentMonth);
    document.getElementById('mc-prev').addEventListener('click', () => navMes(-1));
    document.getElementById('mc-next').addEventListener('click', () => navMes(1));
    document.getElementById('mc-hoy').addEventListener('click', () => {
        currentYear  = new Date().getFullYear();
        currentMonth = new Date().getMonth();
        diaSeleccionado = null;
        cargarMes(currentYear, currentMonth);
    });
});

function navMes(delta) {
    currentMonth += delta;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
    diaSeleccionado = null;
    cargarMes(currentYear, currentMonth);
}

// ── Carga de eventos del mes ───────────────────────────
async function cargarMes(y, m) {
    const inicio = y + '-' + String(m+1).padStart(2,'0') + '-01';
    const ultimo = new Date(y, m+1, 0).getDate();
    const fin    = y + '-' + String(m+1).padStart(2,'0') + '-' + String(ultimo).padStart(2,'0');

    document.getElementById('mc-titulo').textContent = MESES_ES[m] + ' ' + y;
    document.getElementById('crono-body').innerHTML =
        '<div class="crono-empty"><i class="mdi mdi-loading mdi-spin"></i><p>Cargando...</p></div>';

    try {
        const r = await fetch(APP_URL + '/api/calendario?inicio=' + inicio + '&fin=' + fin);
        const data = await r.json();
        if (!r.ok || !Array.isArray(data)) {
            console.error('[Calendario] API error:', data);
            eventosMes = [];
        } else {
            eventosMes = data;
        }
    } catch(e) {
        console.error('[Calendario] fetch error:', e);
        eventosMes = [];
    }

    renderMiniCal(y, m);
    renderCronologia();
}

// ── Mini calendario ────────────────────────────────────
function renderMiniCal(y, m) {
    const container = document.getElementById('mc-days');
    container.innerHTML = '';

    // Indexar eventos por fecha.
    // Rangos cortos (≤31 días): marcar cada día del rango.
    // Rangos largos (>31 días): marcar solo el día de inicio para no saturar el mini-cal.
    const porFecha = {};
    eventosMes.forEach(e => {
        const inicio = e.fecha.substring(0,10);
        const fin    = e.fecha_fin ? e.fecha_fin.substring(0,10) : inicio;
        const dInicio = new Date(inicio + 'T12:00:00');
        const dFin    = new Date(fin    + 'T12:00:00');
        const dias    = Math.round((dFin - dInicio) / 86400000);

        if (dias <= 31) {
            let cur = new Date(dInicio);
            while (cur <= dFin) {
                const f = cur.toISOString().substring(0,10);
                if (!porFecha[f]) porFecha[f] = new Set();
                porFecha[f].add(e.tipo);
                cur.setDate(cur.getDate() + 1);
            }
        } else {
            // Rango muy largo: solo marcar el inicio
            if (!porFecha[inicio]) porFecha[inicio] = new Set();
            porFecha[inicio].add(e.tipo);
        }
    });

    const hoy     = new Date();
    const today   = hoy.getFullYear() + '-' + String(hoy.getMonth()+1).padStart(2,'0') + '-' + String(hoy.getDate()).padStart(2,'0');

    // Primer día del mes (ajustar: lunes=0)
    let firstDow = new Date(y, m, 1).getDay(); // 0=dom
    firstDow = (firstDow === 0) ? 6 : firstDow - 1; // lunes=0

    const lastDay = new Date(y, m+1, 0).getDate();
    const prevLast = new Date(y, m, 0).getDate();

    // Días del mes anterior
    for (let i = firstDow - 1; i >= 0; i--) {
        container.appendChild(makeDay(prevLast - i, true, false, null));
    }
    // Días del mes actual
    for (let d = 1; d <= lastDay; d++) {
        const fStr = y + '-' + String(m+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const isToday    = fStr === today;
        const isSelected = fStr === diaSeleccionado;
        container.appendChild(makeDay(d, false, isToday, porFecha[fStr] || null, fStr, isSelected));
    }
    // Días del mes siguiente
    const total = firstDow + lastDay;
    const remaining = (7 - (total % 7)) % 7;
    for (let i = 1; i <= remaining; i++) {
        container.appendChild(makeDay(i, true, false, null));
    }
}

function makeDay(num, otherMonth, isToday, tipos, fStr, isSelected) {
    const div = document.createElement('div');
    div.className = 'mini-day' +
        (otherMonth  ? ' other-month' : '') +
        (isToday     ? ' today' : '') +
        (isSelected  ? ' selected' : '');

    const numEl = document.createElement('div');
    numEl.className = 'day-num';
    numEl.textContent = num;
    div.appendChild(numEl);

    if (tipos && tipos.size > 0) {
        const dots = document.createElement('div');
        dots.className = 'day-dots';
        const shown = [...tipos].slice(0, 3);
        shown.forEach(t => {
            const d = document.createElement('div');
            d.className = 'dot dot-' + t;
            dots.appendChild(d);
        });
        div.appendChild(dots);
    }

    if (!otherMonth && fStr) {
        div.addEventListener('click', () => seleccionarDia(fStr));
    }
    return div;
}

function seleccionarDia(fStr) {
    diaSeleccionado = (diaSeleccionado === fStr) ? null : fStr;
    renderMiniCal(currentYear, currentMonth);
    renderCronologia();
}

// ── Cronología ─────────────────────────────────────────
function renderCronologia() {
    const body  = document.getElementById('crono-body');
    const titulo = document.getElementById('crono-titulo');
    const sub    = document.getElementById('crono-sub');

    // Filtrar por filtros activos
    let eventos = eventosMes.filter(e => filtros[e.tipo]);

    // Filtrar por día seleccionado o mostrar desde hoy
    let hoyStr = new Date().toISOString().substring(0,10);
    if (diaSeleccionado) {
        // Un evento aparece en un día si el día cae dentro de [fecha, fecha_fin]
        // (no solo si coincide exactamente con el día de inicio), para que los
        // eventos multi-día (recesos, jornadas) se vean en cada día que abarcan.
        eventos = eventos.filter(e => {
            const ini = e.fecha.substring(0,10);
            const fin = e.fecha_fin ? e.fecha_fin.substring(0,10) : ini;
            return diaSeleccionado >= ini && diaSeleccionado <= fin;
        });
        const [y,mo,d] = diaSeleccionado.split('-');
        const fd = new Date(diaSeleccionado + 'T12:00:00');
        const dowName = DIAS_FULL[fd.getDay() === 0 ? 6 : fd.getDay()-1];
        titulo.textContent = dowName.charAt(0).toUpperCase() + dowName.slice(1) + ' ' + parseInt(d) + ' de ' + MESES_ES[parseInt(mo)-1];
        sub.textContent = eventos.length + ' evento' + (eventos.length !== 1 ? 's' : '');
    } else {
        // Próximos 60 días desde hoy
        eventos = eventos.filter(e => e.fecha.substring(0,10) >= hoyStr);
        titulo.textContent = 'Próximas fechas';
        sub.textContent = 'Mes de ' + MESES_ES[currentMonth] + ' ' + currentYear;
    }

    if (eventos.length === 0) {
        body.innerHTML = '<div class="crono-empty"><i class="mdi mdi-calendar-check-outline"></i><p>Sin eventos' +
            (diaSeleccionado ? ' para este día.' : ' próximos en este mes.') + '</p></div>';
        return;
    }

    // Agrupar por fecha
    const porFecha = {};
    eventos.forEach(e => {
        const f = e.fecha.substring(0,10);
        if (!porFecha[f]) porFecha[f] = [];
        porFecha[f].push(e);
    });

    let html = '';
    const hoy = new Date().toISOString().substring(0,10);

    Object.keys(porFecha).sort().forEach(fecha => {
        const fd = new Date(fecha + 'T12:00:00');
        const dow = fd.getDay() === 0 ? 6 : fd.getDay() - 1;
        const isToday = fecha === hoy;
        const dayLabel = { num: fd.getDate(), dow: isToday ? 'HOY' : DIAS_ES[dow], isToday };
        porFecha[fecha].forEach(e => {
            html += renderEventoCard(e, dayLabel);
        });
    });

    body.innerHTML = html;
}

function renderEventoCard(e, day) {
    const tipo  = e.tipo;
    const icon  = TIPOS_ICON[tipo] || 'mdi-calendar';
    const label = TIPOS_LABEL[tipo] || tipo;
    const url   = TIPOS_URL[tipo] ? TIPOS_URL[tipo](e) : null;
    const data  = encodeURIComponent(JSON.stringify(e));
    const onclick = url ? `window.location='${url}'` : `abrirModal(${data})`;

    let meta = '';
    if (e.materia) meta += `<span class="evento-tag"><i class="mdi mdi-book-outline"></i>${escHtml(e.materia)}</span>`;
    if (e.grupo)   meta += `<span class="evento-tag"><i class="mdi mdi-account-group-outline"></i>${escHtml(e.grupo)}</span>`;
    if (e.periodo) meta += `<span class="evento-tag"><i class="mdi mdi-calendar-range-outline"></i>${escHtml(e.periodo)}</span>`;

    const todayClass = day.isToday ? 'card-date-today' : '';

    // Bloque de fecha: rango si tiene fecha_fin distinto
    let dateBlock;
    const fechaFin = e.fecha_fin ? e.fecha_fin.substring(0,10) : null;
    const isRange  = fechaFin && fechaFin !== e.fecha.substring(0,10);

    if (isRange) {
        const fd2  = new Date(fechaFin + 'T12:00:00');
        const dow2 = fd2.getDay() === 0 ? 6 : fd2.getDay() - 1;
        dateBlock = `<div class="card-date-range">
            <div class="range-day">
                <span class="range-n">${day.num}</span>
                <span class="range-w">${day.dow}</span>
            </div>
            <span class="range-sep">—</span>
            <div class="range-day">
                <span class="range-n">${fd2.getDate()}</span>
                <span class="range-w">${DIAS_ES[dow2]}</span>
            </div>
        </div>`;
    } else {
        dateBlock = `<div class="card-date ${todayClass}">
            <span class="card-date-n">${day.num}</span>
            <span class="card-date-w">${day.dow}</span>
        </div>`;
    }

    return `<div class="evento-card" onclick="${onclick}">
        ${dateBlock}
        <div class="evento-icon icon-${tipo}">
            <i class="mdi ${icon}"></i>
        </div>
        <div class="evento-body">
            <div class="evento-titulo">${escHtml(e.nombre)}</div>
            <div class="evento-meta">
                <span class="evento-badge badge-${tipo}">${label}</span>
                ${meta}
            </div>
        </div>
        <i class="mdi mdi-chevron-right" style="color:var(--text-muted);font-size:1.1rem;align-self:center"></i>
    </div>`;
}

// ── Filtros ────────────────────────────────────────────
function toggleFiltro(chip) {
    const tipo = chip.dataset.tipo;
    filtros[tipo] = !filtros[tipo];
    chip.classList.toggle('off', !filtros[tipo]);
    renderCronologia();
}

// ── Modal ──────────────────────────────────────────────
function abrirModal(data) {
    const e    = (typeof data === 'string') ? JSON.parse(data) : data;
    const tipo = e.tipo;
    const icon = TIPOS_ICON[tipo] || 'mdi-calendar';
    const url  = TIPOS_URL[tipo] ? TIPOS_URL[tipo](e) : null;

    document.getElementById('modal-banner').className  = 'modal-banner ' + tipo;
    document.getElementById('modal-icon').className    = 'modal-header-icon icon-' + tipo;
    document.getElementById('modal-icon').innerHTML    = `<i class="mdi ${icon}"></i>`;
    document.getElementById('modal-titulo').textContent = e.nombre;
    document.getElementById('modal-subtitulo').textContent = TIPOS_LABEL[tipo];

    // Fecha
    const fd  = new Date(e.fecha + 'T12:00:00');
    const dow = fd.getDay() === 0 ? 6 : fd.getDay() - 1;
    const fechaStr = DIAS_FULL[dow] + ' ' + fd.getDate() + ' de ' + MESES_ES[fd.getMonth()] + ' ' + fd.getFullYear();

    let bodyHtml = `<div class="modal-row"><i class="mdi mdi-calendar-outline"></i><span><strong>${fechaStr}</strong></span></div>`;
    if (e.fecha_fin && e.fecha_fin !== e.fecha) {
        const fd2 = new Date(e.fecha_fin + 'T12:00:00');
        const dow2 = fd2.getDay() === 0 ? 6 : fd2.getDay() - 1;
        const fechaFin = DIAS_FULL[dow2] + ' ' + fd2.getDate() + ' de ' + MESES_ES[fd2.getMonth()];
        bodyHtml += `<div class="modal-row"><i class="mdi mdi-calendar-end-outline"></i><span>Hasta <strong>${fechaFin}</strong></span></div>`;
    }
    if (e.materia)  bodyHtml += `<div class="modal-row"><i class="mdi mdi-book-outline"></i><span>Materia: <strong>${escHtml(e.materia)}</strong></span></div>`;
    if (e.grupo)    bodyHtml += `<div class="modal-row"><i class="mdi mdi-account-group-outline"></i><span>Grupo: <strong>${escHtml(e.grupo)}</strong></span></div>`;
    if (e.periodo)  bodyHtml += `<div class="modal-row"><i class="mdi mdi-calendar-range-outline"></i><span>Período: <strong>${escHtml(e.periodo)}</strong></span></div>`;
    if (e.docente_nombre) bodyHtml += `<div class="modal-row"><i class="mdi mdi-account-tie-outline"></i><span>Docente: <strong>${escHtml(e.docente_nombre + ' ' + e.docente_apellido)}</strong></span></div>`;

    document.getElementById('modal-body').innerHTML = bodyHtml;

    let footer = `<button class="btn-secondary" onclick="document.getElementById('evento-modal').classList.remove('open')">Cerrar</button>`;
    const btnLabel = TIPOS_BTN[tipo];
    if (url && btnLabel) footer += `<a href="${url}" class="btn-primary"><i class="mdi mdi-arrow-right"></i> ${btnLabel}</a>`;
    document.getElementById('modal-footer').innerHTML = footer;

    document.getElementById('evento-modal').classList.add('open');
}

function cerrarModal(e) {
    if (e.target === document.getElementById('evento-modal'))
        document.getElementById('evento-modal').classList.remove('open');
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
