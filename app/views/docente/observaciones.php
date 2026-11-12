<?php
$gid        = $grupo['grupo_id'];
$pageTitle  = 'Observaciones - '.$grupo['grado'].' '.$grupo['grupo'].' - '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo = 'observaciones';
$breadcrumbs = [
    ['label'=>'Inicio',       'url'=>APP_URL.'/dashboard'],
    ['label'=>'Mis Grupos',   'url'=>APP_URL.'/mis-grupos'],
    ['label'=>$grupo['grado'].' '.$grupo['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
    ['label'=>'Observaciones','url'=>''],
];

$tipos_obs = ['academica'=>'Academica','convivencia'=>'Convivencia','logro'=>'Logro','otro'=>'Otro','disciplinaria'=>'Disciplinaria'];
$colores   = ['academica'=>'#4F46E5','convivencia'=>'#DC2626','logro'=>'#059669','otro'=>'#D97706','disciplinaria'=>'#B91C1C'];
$iconos    = ['academica'=>'mdi-book-open-outline','convivencia'=>'mdi-alert-circle-outline','logro'=>'mdi-trophy-outline','otro'=>'mdi-note-outline','disciplinaria'=>'mdi-gavel'];
// Nota: 'disciplinaria' solo se muestra (registrada por coordinación vía el Observador
// consolidado); el docente no puede crearla ni reasignarla — queda fuera del <select> de edición.

// Agrupar por estudiante
$porEst = [];
foreach ($observaciones as $ob) {
    $mid = $ob['matricula_id'];
    if (!isset($porEst[$mid])) {
        $porEst[$mid] = [
            'nombre'   => $ob['nombre'],
            'apellido' => $ob['apellido'],
            'items'    => [],
            'conteo'   => ['academica'=>0,'convivencia'=>0,'logro'=>0,'otro'=>0],
        ];
    }
    $porEst[$mid]['items'][] = $ob;
    $porEst[$mid]['conteo'][$ob['tipo']] = ($porEst[$mid]['conteo'][$ob['tipo']] ?? 0) + 1;
}
uasort($porEst, fn($a,$b) => strcmp($a['apellido'], $b['apellido']));

// Conteo global
$totales = ['academica'=>0,'convivencia'=>0,'logro'=>0,'otro'=>0];
foreach ($observaciones as $ob) $totales[$ob['tipo']]++;

ob_start();
?>
<style>
/* ── Tabla estudiantes ── */
.obs-wrap { display: flex; gap: 0; position: relative; }

.obs-table-panel { flex: 1; min-width: 0; }

.obs-stat-chip {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 16px;
}
.obs-stat-icon { width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: .95rem; }
.obs-stat-val  { font-size: 1.15rem; font-weight: 800; color: #1f2937; line-height: 1; }
.obs-stat-lab  { font-size: .72rem; color: var(--text-muted); margin-top: 1px; }

.obs-table-wrap { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.obs-table { width: 100%; border-collapse: collapse; }
.obs-table thead th {
    padding: 10px 14px; text-align: left; font-size: .78rem;
    font-weight: 700; color: var(--text-muted); background: #f9fafb;
    border-bottom: 1px solid var(--border);
}
.obs-table thead th.center { text-align: center; }
.obs-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer; transition: background .12s;
}
.obs-table tbody tr:last-child { border-bottom: none; }
.obs-table tbody tr:hover { background: #f5f7ff; }
.obs-table tbody tr.active-row { background: var(--primary-light); }
.obs-table td { padding: 11px 14px; vertical-align: middle; }
.obs-table td.center { text-align: center; }

.obs-est-cell { display: flex; align-items: center; gap: 10px; }
.obs-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--primary-light); color: var(--primary);
    font-weight: 800; font-size: .8rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.obs-avatar.conv { background: #fee2e2; color: #dc2626; }
.obs-avatar.logro{ background: #d1fae5; color: #059669; }
.obs-est-name { font-size: .88rem; font-weight: 700; color: #1f2937; }
.obs-est-sub  { font-size: .74rem; color: var(--text-muted); margin-top: 1px; }

.obs-count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 24px; height: 24px; border-radius: 20px;
    font-size: .75rem; font-weight: 800; padding: 0 7px;
    cursor: pointer; transition: transform .1s, filter .1s;
}
.obs-count-badge:hover { transform: scale(1.12); filter: brightness(.9); }
.obs-count-badge.zero { background: #f3f4f6; color: #9ca3af; font-weight: 600; pointer-events: none; }

.obs-total-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 24px; border-radius: 20px;
    background: #e0e7ff; color: var(--primary);
    font-size: .78rem; font-weight: 800;
}
.obs-total-badge.high { background: #fee2e2; color: #dc2626; }

/* ── Drawer ── */
.obs-drawer {
    flex-direction: column;
    background: #fff; border: 1px solid var(--border);
    border-radius: var(--radius);
    position: sticky; top: 20px; max-height: calc(100vh - 100px);
    flex: 0; min-width: 0; overflow: hidden;
    opacity: 0; transform: translateX(24px);
    transition: flex .28s ease, opacity .25s ease, transform .28s ease;
    pointer-events: none; display: flex;
}
.obs-drawer.open {
    flex: 1; opacity: 1; transform: translateX(0);
    pointer-events: auto;
}

/* Tabla colapsada: oculta columnas de conteo */
.obs-table-panel.collapsed { width: 220px; flex-shrink: 0; }
.obs-table-panel.collapsed .obs-col-hide { display: none; }
.obs-table-panel.collapsed .obs-est-sub  { display: none; }
.obs-table-panel.collapsed tbody tr { opacity: .35; transition: opacity .15s; }
.obs-table-panel.collapsed tbody tr.active-row { opacity: 1; background: var(--primary-light); }
.obs-table-panel.collapsed tbody tr:hover { opacity: .6; }

.obs-drawer-head {
    padding: 16px 18px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    background: #f9fafb;
}
.obs-drawer-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--primary-light); color: var(--primary);
    font-weight: 800; font-size: .88rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.obs-drawer-name  { font-size: .92rem; font-weight: 800; color: #1f2937; }
.obs-drawer-count { font-size: .76rem; color: var(--text-muted); margin-top: 1px; }
.obs-drawer-close {
    margin-left: auto; background: none; border: none;
    cursor: pointer; font-size: 1.2rem; color: #9ca3af;
    line-height: 1; padding: 4px;
}
.obs-drawer-close:hover { color: #374151; }

.obs-drawer-body { overflow-y: auto; flex: 1; padding: 16px 18px; }

/* Timeline en drawer */
.obs-tl { position: relative; padding-left: 22px; }
.obs-tl::before { content: ''; position: absolute; left: 7px; top: 6px; bottom: 6px; width: 2px; background: #e5e7eb; border-radius: 2px; }
.obs-tl-item { position: relative; margin-bottom: 14px; }
.obs-tl-dot  { position: absolute; left: -17px; top: 14px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 2px currentColor; }

.obs-card {
    background: #fff; border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
}
.obs-card-head {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px; border-bottom: 1px solid #f3f4f6; background: #fafafa;
}
.obs-tipo-pill {
    display: flex; align-items: center; gap: 4px;
    font-size: .72rem; font-weight: 800; padding: 2px 9px;
    border-radius: 20px; color: #fff;
}
.obs-card-fecha { font-size: .75rem; color: var(--text-muted); margin-left: auto; }
.obs-card-actions { display: flex; gap: 4px; }
.obs-card-btn {
    background: none; border: none; cursor: pointer;
    font-size: .88rem; padding: 3px 5px; border-radius: 5px;
    color: #9ca3af; transition: background .12s, color .12s;
}
.obs-card-btn:hover { background: #f3f4f6; color: #374151; }
.obs-card-btn.del:hover { background: #fee2e2; color: #dc2626; }

.obs-card-body { padding: 10px 12px; }
.obs-card-texto { font-size: .84rem; color: #374151; line-height: 1.6; white-space: pre-wrap; }
.obs-card-doc   { font-size: .72rem; color: #9ca3af; margin-top: 6px; display: flex; align-items: center; gap: 3px; }

/* Formulario edicion inline */
.obs-edit-form {
    background: #f9fafb; border: 1px solid var(--border);
    border-radius: var(--radius); padding: 12px; margin-top: 8px; display: none;
}
.obs-edit-form.open { display: block; }
.obs-edit-form select,
.obs-edit-form input,
.obs-edit-form textarea {
    width: 100%; border: 1.5px solid #e5e7eb; border-radius: 6px;
    padding: 7px 10px; font-family: inherit; font-size: .83rem;
    outline: none; background: #fff; margin-bottom: 8px;
}
.obs-edit-form select:focus,
.obs-edit-form input:focus,
.obs-edit-form textarea:focus { border-color: var(--primary); }
.obs-edit-row { display: flex; gap: 8px; }
.obs-edit-row > * { flex: 1; }
.obs-edit-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }

.obs-drawer-footer {
    padding: 12px 18px; border-top: 1px solid var(--border); flex-shrink: 0;
}
.obs-drawer-footer button {
    width: 100%; padding: 10px;
    background: var(--primary); color: #fff; border: none;
    border-radius: 7px; font-family: inherit; font-size: .88rem;
    font-weight: 700; cursor: pointer; transition: background .15s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.obs-drawer-footer button:hover { background: var(--primary-dark); }


.obs-empty-row td { text-align: center; padding: 40px; color: var(--text-muted); font-size: .88rem; }
</style>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<div class="page-header" style="margin-top:20px;flex-wrap:wrap;gap:12px">
    <!-- Titulo + chips en la misma linea -->
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;flex:1;min-width:0">
        <?php foreach ($tipos_obs as $k => $v): ?>
        <div class="obs-stat-chip">
            <div class="obs-stat-icon" style="background:<?= $colores[$k] ?>22">
                <i class="mdi <?= $iconos[$k] ?>" style="color:<?= $colores[$k] ?>"></i>
            </div>
            <div>
                <div class="obs-stat-val"><?= $totales[$k] ?></div>
                <div class="obs-stat-lab"><?= $v ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Buscador + boton -->
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
        <div style="position:relative">
            <i class="mdi mdi-magnify" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#aaa;font-size:1rem"></i>
            <input type="text" id="obs-search" placeholder="Buscar estudiante..."
                   oninput="filtrarTabla(this.value)"
                   style="border:1.5px solid var(--border);border-radius:7px;padding:8px 12px 8px 30px;font-family:inherit;font-size:.84rem;outline:none;width:200px">
        </div>
        <button class="btn-primary" onclick="abrirModalNueva()">
            <i class="mdi mdi-plus"></i> Nueva observacion
        </button>
    </div>
</div>

<!-- Layout tabla + drawer -->
<div class="obs-wrap">
    <!-- Tabla -->
    <div class="obs-table-panel">
        <div class="obs-table-wrap">
            <table class="obs-table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th class="center obs-col-hide" style="color:<?= $colores['academica'] ?>">Acad.</th>
                        <th class="center obs-col-hide" style="color:<?= $colores['convivencia'] ?>">Conv.</th>
                        <th class="center obs-col-hide" style="color:<?= $colores['logro'] ?>">Logro</th>
                        <th class="center obs-col-hide" style="color:<?= $colores['otro'] ?>">Otro</th>
                        <th class="center obs-col-hide">Total</th>
                        <th class="obs-col-hide"></th>
                    </tr>
                </thead>
                <tbody id="obs-tbody">
                <?php if (empty($estudiantes)): ?>
                    <tr class="obs-empty-row"><td colspan="7"><i class="mdi mdi-account-off-outline" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3"></i>No hay estudiantes en este grupo</td></tr>
                <?php else: ?>
                    <?php foreach ($estudiantes as $est):
                        $mid    = $est['matricula_id'];
                        $datos  = $porEst[$mid] ?? ['conteo'=>['academica'=>0,'convivencia'=>0,'logro'=>0,'otro'=>0],'items'=>[]];
                        $cnt    = $datos['conteo'];
                        $total  = array_sum($cnt);
                        $inics  = strtoupper(substr($est['nombre'],0,1).substr($est['apellido'],0,1));
                        $avCls  = $cnt['convivencia'] > 0 ? 'conv' : ($cnt['logro'] > 0 ? 'logro' : '');
                    ?>
                    <tr data-mid="<?= $mid ?>"
                        data-nombre="<?= strtolower(e($est['apellido'].' '.$est['nombre'])) ?>"
                        onclick="seleccionarEst(<?= $mid ?>, this)">
                        <td>
                            <div class="obs-est-cell">
                                <div class="obs-avatar <?= $avCls ?>"><?= $inics ?></div>
                                <div>
                                    <div class="obs-est-name"><?= e($est['apellido'].', '.$est['nombre']) ?></div>
                                    <div class="obs-est-sub"><?= $total ?> observacion<?= $total!=1?'es':'' ?></div>
                                </div>
                            </div>
                        </td>
                        <?php foreach (['academica','convivencia','logro','otro'] as $t): ?>
                        <td class="center obs-col-hide">
                            <?php if ($cnt[$t] > 0): ?>
                                <span class="obs-count-badge"
                                      style="background:<?= $colores[$t] ?>22;color:<?= $colores[$t] ?>"
                                      onclick="event.stopPropagation();seleccionarEstFiltro(<?= $mid ?>, '<?= $t ?>', this.closest('tr'))">
                                    <?= $cnt[$t] ?>
                                </span>
                            <?php else: ?>
                                <span class="obs-count-badge zero">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="center obs-col-hide">
                            <span class="obs-total-badge <?= $total >= 3 ? 'high' : '' ?>"><?= $total ?: '0' ?></span>
                        </td>
                        <td class="center obs-col-hide">
                            <i class="mdi mdi-chevron-right" style="font-size:1.1rem;color:#9ca3af"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Drawer -->
    <div class="obs-drawer" id="obs-drawer">
        <div class="obs-drawer-head">
            <div class="obs-drawer-avatar" id="drawer-avatar">--</div>
            <div>
                <div class="obs-drawer-name" id="drawer-name">Estudiante</div>
                <div class="obs-drawer-count" id="drawer-count">0 observaciones</div>
            </div>
            <button class="obs-drawer-close" onclick="cerrarDrawer()" title="Cerrar">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        <div class="obs-drawer-body" id="drawer-body"></div>
        <div class="obs-drawer-footer">
            <button onclick="abrirModalNueva(true)">
                <i class="mdi mdi-plus"></i> Nueva observacion para este estudiante
            </button>
        </div>
    </div>
</div>

<!-- Modal nueva observacion -->
<div id="modal-obs" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:500px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border)">
            <h3 style="font-size:.97rem;font-weight:800">Nueva observacion</h3>
            <button onclick="cerrarModal()" style="background:none;border:none;cursor:pointer;font-size:1.3rem;color:#9ca3af">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/observaciones/guardar" style="padding:20px">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label>Estudiante <span style="color:#dc2626">*</span></label>
                <select name="matricula_id" class="form-select" required id="modal-est">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($estudiantes as $est): ?>
                        <option value="<?= $est['matricula_id'] ?>"><?= e($est['apellido'].', '.$est['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="form-select">
                    <?php foreach ($tipos_obs as $k=>$v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Descripcion <span style="color:#dc2626">*</span></label>
                <textarea name="descripcion" class="form-textarea" rows="3" placeholder="Describe la observacion..." required></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="mdi mdi-check"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
// Preparar datos JSON para el drawer
$drawerData = [];
$appUrl  = APP_URL;
$csrfName = CSRF_TOKEN_NAME;

foreach ($estudiantes as $est) {
    $mid   = $est['matricula_id'];
    $datos = $porEst[$mid] ?? ['items'=>[],'conteo'=>['academica'=>0,'convivencia'=>0,'logro'=>0,'otro'=>0]];
    $inics = strtoupper(substr($est['nombre'],0,1).substr($est['apellido'],0,1));

    $html = '';
    if (empty($datos['items'])) {
        $html = '<div style="text-align:center;padding:40px 0;color:#9ca3af">'
              . '<i class="mdi mdi-note-off-outline" style="font-size:2.2rem;display:block;margin-bottom:10px;opacity:.35"></i>'
              . '<p style="font-size:.86rem">Sin observaciones registradas</p></div>';
    } else {
        $html .= '<div class="obs-tl">';
        foreach ($datos['items'] as $ob) {
            $c    = $colores[$ob['tipo']] ?? '#666';
            $tl   = $tipos_obs[$ob['tipo']] ?? ucfirst($ob['tipo']);
            $ic   = $iconos[$ob['tipo']]  ?? 'mdi-note-outline';
            $f    = date('d/m/Y', strtotime($ob['fecha']));
            $desc = htmlspecialchars($ob['descripcion']);
            $doc  = htmlspecialchars($ob['doc_nombre'].' '.$ob['doc_apellido']);
            $csrf = csrfToken();
            $oid  = (int)$ob['id'];
            $fecha_val = $ob['fecha'];
            // opciones select
            $opts = '';
            foreach (['academica'=>'Academica','convivencia'=>'Convivencia','logro'=>'Logro','otro'=>'Otro'] as $tv=>$tl2) {
                $sel  = $tv === $ob['tipo'] ? ' selected' : '';
                $opts .= '<option value="'.$tv.'"'.$sel.'>'.$tl2.'</option>';
            }
            // Info de lecturas para esta observación
            $lects = $lecturas[$oid] ?? [];
            $lectHtml = '';
            if (empty($lects)) {
                $lectHtml = '<span class="obs-leida-none"><i class="mdi mdi-eye-off-outline"></i> Sin leer por ningún acudiente</span>';
            } else {
                foreach ($lects as $lr) {
                    $fn = date('d/m/Y H:i', strtotime($lr['visto_at']));
                    $lectHtml .= '<span class="obs-leida-por"><i class="mdi mdi-check-circle-outline" style="color:#16A34A"></i> '
                               . htmlspecialchars($lr['nombre']) . ' · <em>' . $fn . '</em></span>';
                }
            }

            $html .= '<div class="obs-tl-item" id="obs-item-'.$oid.'">'
                   . '<div class="obs-tl-dot" style="color:'.$c.'"></div>'
                   . '<div class="obs-card">'
                   .   '<div class="obs-card-head">'
                   .     '<span class="obs-tipo-pill" style="background:'.$c.'"><i class="mdi '.$ic.'"></i> '.$tl.'</span>'
                   .     '<span class="obs-card-fecha"><i class="mdi mdi-calendar-outline"></i> '.$f.'</span>'
                   .     '<div class="obs-card-actions">'
                   .       '<button class="obs-card-btn" title="Editar" onclick="toggleEdit('.$oid.')"><i class="mdi mdi-pencil-outline"></i></button>'
                   .       '<button class="obs-card-btn del" title="Eliminar" onclick="confirmarBorrar('.$oid.','.$gid.')"><i class="mdi mdi-trash-can-outline"></i></button>'
                   .     '</div>'
                   .   '</div>'
                   .   '<div class="obs-card-body">'
                   .     '<p class="obs-card-texto">'.$desc.'</p>'
                   .     '<p class="obs-card-doc"><i class="mdi mdi-account-outline"></i> '.$doc.'</p>'
                   .     '<div class="obs-lecturas">'.$lectHtml.'</div>'
                   .   '</div>'
                   .   '<div class="obs-edit-form" id="obs-edit-'.$oid.'">'
                   .     '<form method="POST" action="'.$appUrl.'/grupos/'.$gid.'/observaciones/'.$oid.'/editar">'
                   .       '<input type="hidden" name="'.$csrfName.'" value="'.$csrf.'">'
                   .       '<div class="obs-edit-row">'
                   .         '<select name="tipo">'.$opts.'</select>'
                   .       '</div>'
                   .       '<textarea name="descripcion" rows="3">'.$desc.'</textarea>'
                   .       '<div class="obs-edit-actions">'
                   .         '<button type="button" class="btn-secondary" style="font-size:.8rem;padding:6px 12px" onclick="toggleEdit('.$oid.')">Cancelar</button>'
                   .         '<button type="submit" class="btn-primary" style="font-size:.8rem;padding:6px 12px"><i class="mdi mdi-check"></i> Guardar</button>'
                   .       '</div>'
                   .     '</form>'
                   .   '</div>'
                   . '</div>'
                   . '</div>';
        }
        $html .= '</div>';
    }

    $total = array_sum($datos['conteo']);
    $drawerData[$mid] = [
        'nombre' => $est['apellido'].', '.$est['nombre'],
        'inics'  => $inics,
        'total'  => $total,
        'html'   => $html,
    ];
}

?>
<script>
const drawerData = <?= json_encode($drawerData) ?>;
const gid = <?= $gid ?>;
let midActivo = null;

function seleccionarEst(mid, tr) {
    abrirDrawer(mid, tr, null);
}

function seleccionarEstFiltro(mid, tipo, tr) {
    abrirDrawer(mid, tr, tipo);
}

function abrirDrawer(mid, tr, filtroTipo) {
    const d = drawerData[mid];
    if (!d) return;

    document.querySelectorAll('#obs-tbody tr').forEach(r => r.classList.remove('active-row'));
    tr.classList.add('active-row');
    midActivo = mid;

    document.getElementById('drawer-avatar').textContent = d.inics;
    document.getElementById('drawer-name').textContent   = d.nombre;
    document.getElementById('drawer-count').textContent  = d.total + ' observacion' + (d.total !== 1 ? 'es' : '');
    document.getElementById('drawer-body').innerHTML     = d.html;

    document.querySelector('.obs-table-panel').classList.add('collapsed');
    document.getElementById('obs-drawer').classList.add('open');

    if (filtroTipo) {
        setTimeout(() => {
            const items = document.querySelectorAll('.obs-tl-item');
            for (const item of items) {
                if (item.querySelector('.obs-tipo-pill') && item.innerHTML.includes(filtroTipo)) {
                    item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    break;
                }
            }
        }, 100);
    }
}

function cerrarDrawer() {
    document.getElementById('obs-drawer').classList.remove('open');
    document.querySelector('.obs-table-panel').classList.remove('collapsed');
    document.querySelectorAll('#obs-tbody tr').forEach(r => r.classList.remove('active-row'));
    midActivo = null;
}

function toggleEdit(oid) {
    const form = document.getElementById('obs-edit-' + oid);
    form.classList.toggle('open');
}

function confirmarBorrar(oid, gid) {
    if (!confirm('Eliminar esta observacion?')) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = '<?= APP_URL ?>/grupos/' + gid + '/observaciones/' + oid + '/borrar';
    f.innerHTML = '<input name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">';
    document.body.appendChild(f);
    f.submit();
}

function filtrarTabla(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#obs-tbody tr[data-nombre]').forEach(tr => {
        tr.style.display = tr.dataset.nombre.includes(q) ? '' : 'none';
    });
}

function abrirModalNueva(preselect) {
    if (preselect && midActivo) {
        document.getElementById('modal-est').value = midActivo;
    }
    document.getElementById('modal-obs').style.display = 'flex';
}
function cerrarModal() {
    document.getElementById('modal-obs').style.display = 'none';
}
document.getElementById('modal-obs').addEventListener('click', e => {
    if (e.target === e.currentTarget) cerrarModal();
});
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>
