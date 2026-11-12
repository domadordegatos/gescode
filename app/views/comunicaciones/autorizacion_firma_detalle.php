<?php
$pageTitle   = 'Evidencia de firma — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => APP_URL . '/autorizaciones'],
    ['label' => 'Evidencia', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Evidencia de firma digital</h1>
        <p class="page-subtitle"><?= e($firma['titulo']) ?></p>
    </div>
</div>

<div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;font-size:.78rem;color:#92400E;margin-bottom:18px;max-width:760px">
    <i class="mdi mdi-eye-off-outline"></i> Esta fotografía contiene datos biométricos (dato sensible). Consúltala solo cuando sea
    estrictamente necesario para verificar o resolver una disputa sobre esta autorización.
</div>

<div style="display:flex;gap:20px;flex-wrap:wrap;max-width:900px">
    <div class="card" style="padding:16px;flex-shrink:0">
        <img src="<?= APP_URL ?>/autorizaciones/firma/<?= $firma['id'] ?>/foto" style="width:280px;border-radius:10px;display:block">
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:8px;text-align:center">
            Reto solicitado: <strong><?= e($firma['instruccion_reto']) ?></strong>
        </div>
        <a href="<?= APP_URL ?>/autorizaciones/firma/<?= $firma['id'] ?>/pdf" target="_blank" class="btn-sm btn-secondary" style="margin-top:10px;width:100%;text-align:center;display:block">
            <i class="mdi mdi-file-pdf-box"></i> Exportar como PDF
        </a>
    </div>

    <div class="card" style="padding:20px 22px;flex:1;min-width:320px">
        <table style="width:100%;font-size:.85rem;border-collapse:collapse">
            <tr><td style="padding:5px 0;color:var(--text-muted);width:140px">Acudiente</td><td><strong><?= e($firma['padre_nombre'] . ' ' . $firma['padre_apellido']) ?></strong></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Estudiante</td><td><?= e($firma['est_nombre'] . ' ' . $firma['est_apellido']) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Documento ingresado</td><td style="font-family:monospace"><?= e($firma['documento_confirmado']) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Fecha y hora</td><td><?= date('d/m/Y H:i:s', strtotime($firma['created_at'])) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted)">Dirección IP</td><td style="font-family:monospace"><?= e($firma['ip']) ?></td></tr>
            <tr><td style="padding:5px 0;color:var(--text-muted);vertical-align:top">Navegador/dispositivo</td><td style="font-size:.78rem;word-break:break-all"><?= e($firma['user_agent']) ?></td></tr>
        </table>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
            <div style="font-weight:700;font-size:.82rem;margin-bottom:6px">Texto exacto aceptado</div>
            <div style="font-size:.84rem;white-space:pre-wrap;background:var(--surface-2);padding:12px;border-radius:8px"><?= nl2br(e($firma['texto_aceptado'])) ?></div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
