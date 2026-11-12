<?php
$pageTitle   = 'Calendario académico — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Calendario académico', 'url' => ''],
];
$noLeidos = 0;
$tipoLabel = EventosInstitucionalesController::TIPOS_LABEL;
$tipoColor = ['evento' => '#1D4ED8', 'receso' => '#D97706', 'otro' => '#6B7280'];
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Calendario académico</h1>
        <p class="page-subtitle">Publica fechas que todos verán en su Agenda (eventos, recesos, jornadas) — pueden abarcar varios días.</p>
    </div>
</div>

<?php if ($periodos): ?>
<div class="card" style="padding:18px 22px;margin-bottom:20px">
    <div class="section-title" style="margin-bottom:10px"><i class="mdi mdi-calendar-range-outline" style="color:var(--primary)"></i> Períodos del año lectivo activo</div>
    <table class="data-table">
        <thead><tr><th>Período</th><th>Fecha inicio</th><th>Fecha fin</th><th>Cierre de notas</th></tr></thead>
        <tbody>
        <?php foreach ($periodos as $p): ?>
            <tr>
                <td><?= e($p['nombre']) ?></td>
                <td><?= $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '—' ?></td>
                <td><?= $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'])) : '—' ?></td>
                <td><?= $p['cierre_notas'] ? date('d/m/Y', strtotime($p['cierre_notas'])) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="font-size:.78rem;color:var(--text-muted);margin-top:8px">Estas fechas se editan desde <a href="<?= APP_URL ?>/periodos">Períodos</a>.</p>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start">

    <!-- Formulario crear/editar -->
    <div class="card" style="padding:22px">
        <div class="section-title" style="margin-bottom:14px" id="form-titulo">
            <i class="mdi mdi-calendar-plus-outline" style="color:var(--primary)"></i> Nueva fecha institucional
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/eventos/guardar" id="form-evento">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <input type="hidden" name="id" id="ev-id" value="">

            <div class="form-group">
                <label>Tipo <span class="required">*</span></label>
                <select name="tipo" id="ev-tipo" class="form-input">
                    <?php foreach ($tipoLabel as $key => $label): ?>
                        <option value="<?= $key ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Título <span class="required">*</span></label>
                <input type="text" name="titulo" id="ev-titulo" class="form-input" maxlength="150"
                       placeholder="Ej: Semana de receso escolar" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="ev-descripcion" class="form-input" rows="3"
                          placeholder="Detalle opcional que verán los usuarios"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Fecha inicio <span class="required">*</span></label>
                    <input type="date" name="fecha_inicio" id="ev-fecha-inicio" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" id="ev-fecha-fin" class="form-input">
                    <small class="form-hint">Vacío = mismo día que el inicio</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="btn-guardar-evento">
                    <i class="mdi mdi-check"></i> Publicar evento
                </button>
                <button type="button" class="btn-secondary" id="btn-cancelar-edicion" style="display:none" onclick="cancelarEdicion()">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

    <!-- Listado -->
    <div class="card" style="padding:0;overflow:hidden">
        <?php if (empty($eventos)): ?>
            <div class="empty-state" style="padding:40px">
                <i class="mdi mdi-calendar-blank-outline"></i>
                <p>No hay eventos institucionales publicados todavía.</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Evento</th>
                    <th>Fechas</th>
                    <th>Publicado por</th>
                    <th style="text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev):
                    $esRango = $ev['fecha_fin'] !== $ev['fecha_inicio'];
                ?>
                <tr>
                    <td style="padding:10px 12px">
                        <span style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:99px;background:<?= $tipoColor[$ev['tipo']] ?? '#6B7280' ?>;color:#fff"><?= e($tipoLabel[$ev['tipo']] ?? $ev['tipo']) ?></span>
                    </td>
                    <td style="padding:10px 12px">
                        <strong><?= e($ev['titulo']) ?></strong>
                        <?php if (!empty($ev['descripcion'])): ?>
                            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                                <?= e(mb_substr($ev['descripcion'], 0, 90)) ?><?= mb_strlen($ev['descripcion']) > 90 ? '…' : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 12px;white-space:nowrap;font-size:.84rem">
                        <?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?>
                        <?php if ($esRango): ?>
                            → <?= date('d/m/Y', strtotime($ev['fecha_fin'])) ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 12px;font-size:.82rem;color:var(--text-muted)">
                        <?= $ev['creador_nombre'] ? e($ev['creador_nombre'].' '.$ev['creador_apellido']) : '—' ?>
                    </td>
                    <td style="padding:10px 12px;text-align:right;white-space:nowrap">
                        <button type="button" class="btn-icon" title="Editar"
                                onclick='editarEvento(<?= json_encode($ev, JSON_UNESCAPED_UNICODE) ?>)'>
                            <i class="mdi mdi-pencil-outline"></i>
                        </button>
                        <form method="POST" action="<?= APP_URL ?>/admin/eventos/<?= (int)$ev['id'] ?>/eliminar"
                              style="display:inline" onsubmit="return confirm('¿Eliminar este evento institucional?');">
                            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                            <button type="submit" class="btn-icon" title="Eliminar" style="color:var(--danger)">
                                <i class="mdi mdi-trash-can-outline"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
function editarEvento(ev) {
    document.getElementById('form-titulo').innerHTML = '<i class="mdi mdi-calendar-edit-outline" style="color:var(--primary)"></i> Editar fecha institucional';
    document.getElementById('ev-id').value = ev.id;
    document.getElementById('ev-tipo').value = ev.tipo;
    document.getElementById('ev-titulo').value = ev.titulo;
    document.getElementById('ev-descripcion').value = ev.descripcion || '';
    document.getElementById('ev-fecha-inicio').value = ev.fecha_inicio;
    document.getElementById('ev-fecha-fin').value = ev.fecha_fin;
    document.getElementById('btn-guardar-evento').innerHTML = '<i class="mdi mdi-check"></i> Guardar cambios';
    document.getElementById('btn-cancelar-edicion').style.display = '';
    document.getElementById('form-evento').scrollIntoView({behavior:'smooth', block:'start'});
}
function cancelarEdicion() {
    document.getElementById('form-titulo').innerHTML = '<i class="mdi mdi-calendar-plus-outline" style="color:var(--primary)"></i> Nueva fecha institucional';
    document.getElementById('form-evento').reset();
    document.getElementById('ev-id').value = '';
    document.getElementById('btn-guardar-evento').innerHTML = '<i class="mdi mdi-check"></i> Publicar evento';
    document.getElementById('btn-cancelar-edicion').style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
