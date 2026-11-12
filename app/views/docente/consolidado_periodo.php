<?php
$pageTitle  = 'Consolidado por Período — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$gid        = $grupo['grupo_id'];
$tab_activo = 'evaluaciones';
$breadcrumbs = [
    ['label'=>'Inicio',         'url'=>APP_URL.'/dashboard'],
    ['label'=>'Mis Grupos',     'url'=>APP_URL.'/mis-grupos'],
    ['label'=>$grupo['grado'].' '.$grupo['grupo'], 'url'=>APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id],
    ['label'=>'Consolidado por Período', 'url'=>''],
];

// desempenioDeNota() y notaMinAprobacion() viven en app/helpers.php (compartidas
// con la vista equivalente del padre).

$estados = ['activo'=>'Activo','pendiente'=>'Pendiente','retirado'=>'Retirado',
            'suspendido'=>'Suspendido','egresado'=>'Egresado','inscrito'=>'Inscrito','reprobado'=>'Reprobado'];
$modos   = ['valor'=>'Valor numérico','abrev'=>'Abreviatura (DA, DB…)','texto'=>'Texto completo'];

ob_start();
?>

<?php
// Inyectamos el título y el botón Volver en la barra del grupo
$grupo_extra_title = ' <span class="sep">·</span> <span style="font-size:.82rem;font-weight:600;opacity:.85">Consolidado por Período</span>';
$grupo_extra_action = '<a href="'.APP_URL.'/grupos/'.$gid.'/evaluaciones?asig='.$asig_id.'"
   style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:6px;
          background:rgba(255,255,255,.15);color:#fff;font-size:.78rem;font-weight:700;
          transition:background .15s" onmouseover="this.style.background=\'rgba(255,255,255,.28)\'"
   onmouseout="this.style.background=\'rgba(255,255,255,.15)\'">
   <i class="mdi mdi-arrow-left"></i> Volver
</a>';
?>
<?php include __DIR__.'/_grupo_tabs.php'; ?>

<!-- Filtros compactos -->
<form method="GET" style="margin-bottom:16px">
    <input type="hidden" name="asig" value="<?= $asig_id ?>">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <select name="periodo_fil" class="native-select" style="width:auto" onchange="this.form.submit()">
            <option value="0" <?= $periodo_fil==0?'selected':'' ?>>Todos los períodos</option>
            <?php foreach (periodosVisibles($todos_periodos) as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $periodo_fil==(int)$p['id']?'selected':'' ?>>
                    <?= e($p['nombre']) ?><?= $p['activo'] ? ' ●' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="estado" class="native-select" style="width:auto" onchange="this.form.submit()">
            <?php foreach ($estados as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $estado_fil===$val?'selected':'' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
        <select name="modo_nota" class="native-select" style="width:auto" onchange="this.form.submit()">
            <?php foreach ($modos as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $modo_nota===$val?'selected':'' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (empty($estudiantes)): ?>
    <div class="empty-state">
        <i class="mdi mdi-account-school-outline"></i>
        <p>No hay estudiantes con estado «<?= $estados[$estado_fil] ?? $estado_fil ?>» en este grupo.</p>
    </div>
<?php else: ?>

<div class="card" style="overflow-x:auto">
    <table class="data-table consol-table">
        <thead>
            <tr>
                <th style="min-width:180px">Estudiante</th>
                <?php foreach ($periodos as $p): ?>
                    <th style="text-align:center;min-width:110px">
                        <?= e($p['nombre']) ?>
                        <?php if ($p['activo']): ?>
                            <span class="dot-active" style="display:inline-block;margin-left:4px"></span>
                        <?php endif; ?>
                        <div style="font-size:.7rem;font-weight:600;color:var(--primary);margin-top:2px">
                            <?= number_format($p['porcentaje_ano'],0) ?>%
                        </div>
                    </th>
                <?php endforeach; ?>
                <th style="text-align:center;min-width:90px;background:#F0F4FF">Promedio</th>
                <th style="text-align:center;min-width:90px;background:#EEF2FF">Definitiva</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $est): ?>
            <tr>
                <td style="padding:6px 12px">
                    <?= e($est['apellido'].', '.$est['nombre']) ?>
                    <?php if ($est['estado'] !== 'activo'): ?>
                        <span class="text-xs text-muted">(<?= ucfirst($est['estado']) ?>)</span>
                    <?php endif; ?>
                </td>
                <?php foreach ($periodos as $p): ?>
                    <?php $nota = $est['notas_periodo'][$p['id']] ?? null; ?>
                    <td style="text-align:center;padding:6px 8px">
                        <?= desempenioDeNota($nota, $desempenios, $modo_nota) ?>
                    </td>
                <?php endforeach; ?>
                <td style="text-align:center;background:#F5F7FF;padding:6px 8px">
                    <?= desempenioDeNota($est['promedio'], $desempenios, $modo_nota) ?>
                </td>
                <td style="text-align:center;background:#EEF2FF;padding:6px 8px">
                    <?= desempenioDeNota($est['definitiva'], $desempenios, $modo_nota) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <!-- Promedios del grupo -->
        <tfoot>
            <tr>
                <td><strong class="text-sm">Promedio grupo</strong></td>
                <?php foreach ($periodos as $p):
                    $vals = array_filter(
                        array_map(fn($e) => $e['notas_periodo'][$p['id']] ?? null, $estudiantes),
                        fn($v) => $v !== null
                    );
                    $prom_g = count($vals) ? round(array_sum($vals)/count($vals),1) : null;
                ?>
                    <td style="text-align:center">
                        <?php if ($prom_g !== null): ?>
                            <strong class="<?= $prom_g >= notaMinAprobacion($desempenios) ? 'nota-ok' : 'nota-fail' ?>">
                                <?= number_format($prom_g,1) ?>
                            </strong>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <?php
                    $proms = array_filter(array_column($estudiantes,'promedio'), fn($v)=>$v!==null);
                    $prom_tot = count($proms) ? round(array_sum($proms)/count($proms),1) : null;
                ?>
                <td style="text-align:center;background:#F5F7FF">
                    <?php if ($prom_tot!==null): ?>
                        <strong class="<?= $prom_tot>=notaMinAprobacion($desempenios)?'nota-ok':'nota-fail' ?>"><?= number_format($prom_tot,1) ?></strong>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="text-align:center;background:#EEF2FF">
                    <?php if ($prom_tot!==null): ?>
                        <strong class="<?= $prom_tot>=notaMinAprobacion($desempenios)?'nota-ok':'nota-fail' ?>"><?= number_format($prom_tot,1) ?></strong>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Leyenda de desempeños -->
<?php if ($modo_nota !== 'valor' && !empty($desempenios)): ?>
<div class="consol-leyenda" style="margin-top:12px">
    <strong class="text-sm" style="display:block;margin-bottom:6px">Escala de desempeños:</strong>
    <?php foreach ($desempenios as $d): ?>
    <div class="leyenda-item">
        <strong style="color:<?= $d['es_perdido']?'var(--danger)':'var(--success)' ?>"><?= e($d['abreviatura']) ?></strong>
        — <?= e($d['nombre']) ?>
        (<?= number_format($d['rango_min'],1) ?> – <?= number_format($d['rango_max'],1) ?>)
        <?= $d['es_perdido'] ? ' <span style="color:var(--danger);font-size:.75rem">● reprobatorio</span>' : '' ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>

