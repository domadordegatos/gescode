<?php
$titulo = 'Ficha de ' . $estudiante['nombre'] . ' ' . $estudiante['apellido'];
$tipoObsLabel = ['academica'=>'Académica','convivencia'=>'Convivencia','logro'=>'Logro','disciplinaria'=>'Disciplinaria','otro'=>'Otro'];
$pqrsEstadoLabel = ['radicado'=>'Radicado','en_tramite'=>'En trámite','resuelto'=>'Resuelto','cerrado'=>'Cerrado'];
$totalAsist = array_sum($asistencia);
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div>
        <a href="<?= APP_URL ?>/ficha-estudiante" style="color:var(--text-muted);font-size:.85rem"><i class="mdi mdi-arrow-left"></i> Volver a buscar</a>
        <h2 style="margin:4px 0 0"><i class="mdi mdi-card-account-details-outline"></i> <?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h2>
        <p style="color:var(--text-muted);margin:2px 0 0">
            Doc: <?= e($estudiante['documento'] ?? 'N/A') ?>
            <?php if ($matriculaActual): ?> · <?= e($matriculaActual['grado'] . ' ' . $matriculaActual['grupo']) ?> (<?= e($matriculaActual['ano']) ?>)<?php else: ?> · Sin matrícula activa<?php endif; ?>
        </p>
    </div>
    <a href="<?= APP_URL ?>/usuarios/<?= $estudiante['usuario_id'] ?>/editar" class="btn-secondary"><i class="mdi mdi-pencil-outline"></i> Editar usuario</a>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="display:flex;flex-wrap:wrap;border-bottom:1px solid var(--border)">
        <?php
        $tabs = [
            'datos' => ['Datos personales', 'mdi-account-outline'],
            'notas' => ['Notas (período activo)', 'mdi-notebook-outline'],
            'asistencia' => ['Asistencia', 'mdi-calendar-check-outline'],
            'observaciones' => ['Observaciones', 'mdi-eye-outline'],
            'autorizaciones' => ['Autorizaciones', 'mdi-file-sign'],
            'retiros' => ['Retiros anticipados', 'mdi-exit-run'],
            'pqrs' => ['PQRS familia', 'mdi-message-alert-outline'],
        ];
        foreach ($tabs as $key => [$label, $icon]):
        ?>
        <button type="button" class="tab-ficha-btn" data-tab="<?= $key ?>" onclick="verTabFicha('<?= $key ?>')" style="padding:12px 16px;border:none;background:none;cursor:pointer;font-size:.85rem;color:var(--text-muted);border-bottom:3px solid transparent">
            <i class="mdi <?= $icon ?>"></i> <?= $label ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div style="padding:20px">

        <div class="tab-ficha-panel" id="tab-datos">
            <table style="width:100%;font-size:.9rem">
                <tr><td style="padding:6px 0;color:var(--text-muted);width:180px">Nombre completo</td><td><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted)">Documento</td><td><?= e($estudiante['documento'] ?? 'N/A') ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted)">Correo</td><td><?= e($estudiante['email'] ?? 'N/A') ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted)">Fecha de nacimiento</td><td><?= !empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : 'N/A' ?></td></tr>
                <tr><td style="padding:6px 0;color:var(--text-muted)">Matrícula actual</td><td><?= $matriculaActual ? e($matriculaActual['grado'] . ' ' . $matriculaActual['grupo'] . ' — ' . $matriculaActual['ano'] . ' (' . $matriculaActual['estado'] . ')') : 'Sin matrícula activa' ?></td></tr>
            </table>
            <h4 style="margin:18px 0 8px">Acudientes vinculados</h4>
            <?php if (!$acudientes): ?>
                <p style="color:#B91C1C"><i class="mdi mdi-alert-outline"></i> Este estudiante no tiene acudiente vinculado.</p>
            <?php else: ?>
                <?php foreach ($acudientes as $ac): ?>
                <div style="display:flex;justify-content:space-between;padding:10px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px">
                    <span><strong><?= e($ac['nombre'] . ' ' . $ac['apellido']) ?></strong> <span style="color:var(--text-muted);font-size:.82rem">(<?= e($ac['parentesco']) ?>)</span></span>
                    <a href="<?= APP_URL ?>/usuarios/<?= $ac['usuario_id'] ?>/editar" style="font-size:.82rem">Ver usuario <i class="mdi mdi-chevron-right"></i></a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-notas" style="display:none">
            <?php if (!$notas): ?>
                <p style="color:var(--text-muted)">Sin notas registradas en el período activo.</p>
            <?php else: ?>
                <table style="width:100%;font-size:.9rem">
                    <thead><tr style="text-align:left;color:var(--text-muted)"><th style="padding:6px 0">Materia</th><th>Promedio</th></tr></thead>
                    <tbody>
                    <?php foreach ($notas as $n): ?>
                        <tr><td style="padding:6px 0"><?= e($n['materia']) ?></td><td><strong><?= $n['promedio'] ?></strong></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-asistencia" style="display:none">
            <?php $labAsist = ['presente'=>'Presente','tarde'=>'Tarde','excusa'=>'Excusa','ausente'=>'Ausente','incapacidad'=>'Incapacidad']; ?>
            <?php if (!$totalAsist): ?>
                <p style="color:var(--text-muted)">Sin registros de asistencia.</p>
            <?php else: ?>
                <?php foreach ($asistencia as $estado => $n): ?>
                <div style="margin-bottom:10px">
                    <div style="display:flex;justify-content:space-between;font-size:.88rem"><span><?= $labAsist[$estado] ?></span><strong><?= $n ?> (<?= round($n / $totalAsist * 100) ?>%)</strong></div>
                    <div style="background:var(--border);border-radius:4px;height:8px;overflow:hidden;margin-top:3px">
                        <div style="background:var(--primary);height:100%;width:<?= round($n / $totalAsist * 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-observaciones" style="display:none">
            <?php if (!$observaciones): ?>
                <p style="color:var(--text-muted)">Sin observaciones registradas.</p>
            <?php else: ?>
                <?php foreach ($observaciones as $ob): ?>
                <div style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px">
                    <div style="display:flex;justify-content:space-between;font-size:.82rem;color:var(--text-muted)">
                        <span><?= e($tipoObsLabel[$ob['tipo']] ?? $ob['tipo']) ?> · <?= date('d/m/Y', strtotime($ob['fecha'])) ?></span>
                        <span>Por <?= e($ob['doc_nombre'] . ' ' . $ob['doc_apellido']) ?></span>
                    </div>
                    <p style="margin:6px 0 0"><?= e($ob['descripcion']) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-autorizaciones" style="display:none">
            <?php if (!$autorizaciones): ?>
                <p style="color:var(--text-muted)">Sin autorizaciones registradas.</p>
            <?php else: ?>
                <?php foreach ($autorizaciones as $a): ?>
                <div style="display:flex;justify-content:space-between;padding:10px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px">
                    <span><?= e($a['titulo']) ?> <span style="color:var(--text-muted);font-size:.82rem">(<?= e($a['tipo']) ?>)</span></span>
                    <?php if ($a['firmado']): ?>
                        <span style="color:#16A34A"><i class="mdi mdi-check-circle-outline"></i> Firmada <?= $a['firmado_at'] ? date('d/m/Y', strtotime($a['firmado_at'])) : '' ?></span>
                    <?php else: ?>
                        <span style="color:#D97706"><i class="mdi mdi-clock-outline"></i> Pendiente</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-retiros" style="display:none">
            <?php if (!$retiros): ?>
                <p style="color:var(--text-muted)">Sin retiros anticipados registrados.</p>
            <?php else: ?>
                <?php foreach ($retiros as $r): ?>
                <div style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px">
                    <div style="display:flex;justify-content:space-between;font-size:.82rem;color:var(--text-muted)">
                        <span><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                        <?= $r['anulado'] ? '<span style="color:#B91C1C">Anulado</span>' : '<span style="color:#16A34A">Válido</span>' ?>
                    </div>
                    <p style="margin:6px 0 0">Retirado por <strong><?= e($r['nombre_persona']) ?></strong> (<?= e($r['parentesco']) ?>) — <?= e($r['motivo']) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-ficha-panel" id="tab-pqrs" style="display:none">
            <?php if (!$pqrs): ?>
                <p style="color:var(--text-muted)">Sin PQRS radicados por la familia de este estudiante.</p>
            <?php else: ?>
                <?php foreach ($pqrs as $p): ?>
                <div style="display:flex;justify-content:space-between;padding:10px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px">
                    <span><?= e($p['radicado']) ?> — <?= e($p['asunto']) ?></span>
                    <span style="color:var(--text-muted);font-size:.82rem"><?= e($pqrsEstadoLabel[$p['estado']] ?? $p['estado']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function verTabFicha(key) {
    document.querySelectorAll('.tab-ficha-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-ficha-btn').forEach(b => { b.style.borderBottomColor = 'transparent'; b.style.color = 'var(--text-muted)'; });
    document.getElementById('tab-' + key).style.display = 'block';
    document.querySelector('.tab-ficha-btn[data-tab="' + key + '"]').style.borderBottomColor = 'var(--primary)';
    document.querySelector('.tab-ficha-btn[data-tab="' + key + '"]').style.color = 'var(--primary)';
}
verTabFicha('datos');
</script>
