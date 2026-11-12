<?php
$pageTitle   = 'Cancelar notificación — ' . APP_NAME;
$breadcrumbs = [];
ob_start();
?>

<div style="min-height:60vh;display:flex;align-items:center;justify-content:center">
    <div class="card" style="max-width:480px;width:100%;text-align:center;padding:48px 40px">
        <?php if ($ok): ?>
            <i class="mdi mdi-bell-off-outline" style="font-size:3rem;color:var(--text-muted);display:block;margin-bottom:16px"></i>
            <h2 style="margin:0 0 12px">Notificación cancelada</h2>
            <p style="color:var(--text-muted);margin:0 0 28px">
                Ya no recibirás correos de tipo <strong><?= e($tipo_label) ?></strong>.
                Puedes volver a activarla en cualquier momento desde tu perfil.
            </p>
            <a href="<?= APP_URL ?>/perfil/notificaciones" class="btn-primary">
                <i class="mdi mdi-cog-outline"></i> Gestionar notificaciones
            </a>
        <?php else: ?>
            <i class="mdi mdi-alert-circle-outline" style="font-size:3rem;color:#DC2626;display:block;margin-bottom:16px"></i>
            <h2 style="margin:0 0 12px">Enlace inválido</h2>
            <p style="color:var(--text-muted);margin:0 0 28px">
                El enlace de cancelación no es válido o ya fue utilizado.
            </p>
            <a href="<?= APP_URL ?>/dashboard" class="btn-secondary">Ir al inicio</a>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
