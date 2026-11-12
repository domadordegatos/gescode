<?php
$pageTitle   = e($mensaje['asunto']) . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio',   'url' => APP_URL . '/dashboard'],
    ['label' => 'Mensajes', 'url' => APP_URL . '/comunicaciones'],
    ['label' => e($mensaje['asunto']), 'url' => ''],
];
$noLeidos = 0;
$totalHilo = count($mensajesHilo);
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($mensaje['asunto']) ?></h1>
        <?php if ($totalHilo > 1): ?>
        <p class="page-subtitle"><?= $totalHilo ?> mensajes en esta conversación</p>
        <?php endif; ?>
    </div>
    <a href="<?= APP_URL ?>/comunicaciones" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Volver
    </a>
</div>

<div style="max-width:820px">

    <?php foreach ($mensajesHilo as $i => $m): ?>
    <div class="card msg-hilo-item" style="padding:22px;margin-bottom:14px;<?= $m['es_propio'] ? 'border-left:3px solid var(--primary)' : '' ?>">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:12px">
                <div class="msg-avatar">
                    <img src="<?= APP_URL ?>/perfil/avatar/<?= (int)$m['remitente_id'] ?>" alt="<?= htmlspecialchars(substr($m['nombre'],0,1)) ?>">
                </div>
                <div>
                    <button type="button" class="link-destinatario" data-mensaje="<?= $m['id'] ?>" data-usuario="<?= $m['remitente_id'] ?>"
                            style="background:none;border:none;padding:0;font-weight:600;cursor:pointer;color:inherit;text-decoration:underline dotted">
                        <?= e($m['nombre'] . ' ' . $m['apellido']) ?>
                    </button>
                    <span style="font-size:.8rem;color:var(--text-muted);margin-left:6px"><?= ucfirst($m['rol']) ?></span>
                    <div style="font-size:.78rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></div>
                </div>
            </div>
            <?php if ($totalHilo > 1): ?>
            <span style="font-size:.74rem;color:var(--text-muted);background:var(--bg-soft,#F3F4F6);padding:2px 8px;border-radius:99px">
                <?= $i + 1 ?> / <?= $totalHilo ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Para: destinatarios -->
        <?php if (($m['total_destinatarios'] ?? 0) > 1): ?>
        <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
            <i class="mdi mdi-account-multiple-outline"></i>
            Para:
            <?php if ($m['puede_ver_todos'] && $m['destinatarios_vista']): ?>
                <?php foreach ($m['destinatarios_vista'] as $j => $d): ?>
                    <button type="button" class="link-destinatario" data-mensaje="<?= $m['id'] ?>" data-usuario="<?= $d['id'] ?>"
                            style="background:none;border:none;padding:0;color:var(--primary);cursor:pointer;text-decoration:underline dotted;font-size:inherit">
                        <?= e($d['nombre'] . ' ' . $d['apellido']) ?>
                    </button><?= $j < count($m['destinatarios_vista']) - 1 ? ', ' : '' ?>
                <?php endforeach; ?>
                <span style="color:var(--text-muted)">(<?= $m['total_destinatarios'] ?>)</span>
            <?php else: ?>
                tú y <?= $m['total_destinatarios'] - 1 ?> destinatario<?= $m['total_destinatarios'] - 1 != 1 ? 's' : '' ?> más
                <span style="font-style:italic">(lista no visible para este mensaje)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Cuerpo del mensaje -->
        <div class="msg-body-html">
            <?= safeHtml($m['cuerpo']) ?>
        </div>

        <!-- Adjuntos -->
        <?php if (!empty($m['adjuntos'])): ?>
            <ul class="adj-list">
                <?php foreach ($m['adjuntos'] as $adj): ?>
                    <li>
                        <a href="<?= APP_URL ?>/comunicaciones/adjunto/<?= (int)$adj['id'] ?>" class="adj-item" download>
                            <i class="mdi mdi-file-download-outline"></i>
                            <?= e($adj['nombre_orig']) ?>
                            <span style="font-size:.72rem;color:var(--text-muted);margin-left:4px">(<?= round($adj['tamano'] / 1024) ?> KB)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Lectura por destinatario (solo visible para el remitente de ESTE mensaje) -->
        <?php if ($m['es_propio'] && $m['destinatarios_vista']): ?>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border)">
            <div class="section-title" style="margin-bottom:8px;font-size:.82rem">
                <i class="mdi mdi-eye-check-outline" style="color:var(--primary)"></i>
                Lectura (<?= count(array_filter($m['destinatarios_vista'], fn($l) => $l['leido'])) ?>/<?= count($m['destinatarios_vista']) ?>)
            </div>
            <div class="obs-lecturas" style="border-top:none;padding-top:0">
                <?php foreach ($m['destinatarios_vista'] as $l): ?>
                    <?php if ($l['leido']): ?>
                        <span class="obs-leida-por">
                            <i class="mdi mdi-check-circle-outline" style="color:#16A34A"></i>
                            <?= e($l['nombre'] . ' ' . $l['apellido']) ?>
                            <span style="color:var(--text-muted)">(<?= ucfirst($l['rol']) ?>)</span>
                            · <em><?= date('d/m/Y H:i', strtotime($l['leido_at'])) ?></em>
                        </span>
                    <?php else: ?>
                        <span class="obs-leida-none">
                            <i class="mdi mdi-eye-off-outline"></i>
                            <?= e($l['nombre'] . ' ' . $l['apellido']) ?>
                            <span style="color:var(--text-muted)">(<?= ucfirst($l['rol']) ?>)</span>
                            — aún no lo ha leído
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <!-- Acciones -->
    <div style="margin-top:4px">
        <a href="<?= APP_URL ?>/comunicaciones/redactar?responder=<?= (int)$mensaje['id'] ?>" class="btn-primary">
            <i class="mdi mdi-reply"></i> Responder
        </a>
    </div>

</div>

<!-- Panel lateral de información del participante -->
<div id="panel-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:999" onclick="cerrarPanelDestinatario()"></div>
<div id="panel-destinatario" style="display:none;position:fixed;top:0;right:0;height:100vh;width:320px;background:var(--surface,#fff);box-shadow:-4px 0 20px rgba(0,0,0,.15);z-index:1000;overflow-y:auto;padding:22px">
    <button type="button" onclick="cerrarPanelDestinatario()" style="position:absolute;top:14px;right:14px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text-muted)">
        <i class="mdi mdi-close"></i>
    </button>
    <div id="panel-destinatario-contenido">
        <div style="text-align:center;padding:40px 0;color:var(--text-muted)"><i class="mdi mdi-loading mdi-spin"></i> Cargando...</div>
    </div>
</div>

<script>
function abrirPanelDestinatario(mensajeId, usuarioId) {
    const overlay = document.getElementById('panel-overlay');
    const panel   = document.getElementById('panel-destinatario');
    const cont    = document.getElementById('panel-destinatario-contenido');
    overlay.style.display = 'block';
    panel.style.display   = 'block';
    cont.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--text-muted)"><i class="mdi mdi-loading mdi-spin"></i> Cargando...</div>';

    fetch('<?= APP_URL ?>/comunicaciones/' + mensajeId + '/destinatario/' + usuarioId)
        .then(r => r.json())
        .then(d => {
            if (d.error) { cont.innerHTML = '<p style="color:var(--danger)">' + d.error + '</p>'; return; }

            const rolLabel = {admin:'Administrador', docente:'Docente', estudiante:'Estudiante', padre:'Padre/Familiar', soporte:'Soporte', secretaria:'Secretaría'}[d.rol] || d.rol;
            let html = `
                <div style="text-align:center;margin-bottom:18px">
                    <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;margin:0 auto 10px">
                        ${d.nombre.charAt(0).toUpperCase()}
                    </div>
                    <div style="font-weight:700;font-size:1.05rem">${d.nombre} ${d.apellido}</div>
                    <div style="font-size:.82rem;color:var(--text-muted)">${rolLabel}${d.cargo ? ' · ' + d.cargo : ''}</div>
                </div>
            `;

            if (d.rol === 'padre') {
                html += '<div class="section-title" style="font-size:.85rem;margin-bottom:8px"><i class="mdi mdi-account-child-outline"></i> Hijos a cargo</div>';
                if (!d.hijos || !d.hijos.length) {
                    html += '<p style="font-size:.82rem;color:var(--text-muted)">Sin hijos vinculados.</p>';
                } else {
                    d.hijos.forEach(h => {
                        html += `<div style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px;font-size:.85rem">
                            <strong>${h.nombre} ${h.apellido}</strong><br>
                            <span style="color:var(--text-muted);font-size:.78rem">${h.parentesco} · ${h.grado ? h.grado + ' ' + h.grupo : 'Sin matrícula activa'}</span>
                        </div>`;
                    });
                }
            } else if (d.rol === 'estudiante') {
                html += '<div class="section-title" style="font-size:.85rem;margin-bottom:8px"><i class="mdi mdi-school-outline"></i> Grado actual</div>';
                html += d.grado ? `<p style="font-size:.85rem">${d.grado.grado} ${d.grado.grupo}</p>` : '<p style="font-size:.82rem;color:var(--text-muted)">Sin matrícula activa.</p>';
            } else if (d.rol === 'docente') {
                html += '<div class="section-title" style="font-size:.85rem;margin-bottom:8px"><i class="mdi mdi-google-classroom"></i> Grupos que dirige</div>';
                if (!d.grupos_dirige || !d.grupos_dirige.length) {
                    html += '<p style="font-size:.82rem;color:var(--text-muted)">No es director de grupo.</p>';
                } else {
                    d.grupos_dirige.forEach(g => { html += `<p style="font-size:.85rem">${g.grado} ${g.grupo}</p>`; });
                }
            }

            cont.innerHTML = html;
        })
        .catch(() => { cont.innerHTML = '<p style="color:var(--danger)">No se pudo cargar la información.</p>'; });
}
function cerrarPanelDestinatario() {
    document.getElementById('panel-overlay').style.display = 'none';
    document.getElementById('panel-destinatario').style.display = 'none';
}
document.querySelectorAll('.link-destinatario').forEach(btn => {
    btn.addEventListener('click', () => abrirPanelDestinatario(btn.dataset.mensaje, btn.dataset.usuario));
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
