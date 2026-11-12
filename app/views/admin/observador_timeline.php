<?php
$pageTitle   = e($estudiante['nombre'] . ' ' . $estudiante['apellido']) . ' — Observador — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Observador del estudiante', 'url' => APP_URL . '/observador'],
    ['label' => e($estudiante['nombre'] . ' ' . $estudiante['apellido']), 'url' => ''],
];
$noLeidos = 0;

$tipoCfg = [
    'academica'     => ['label' => 'Académica',     'color' => '#2563EB', 'bg' => '#DBEAFE', 'icon' => 'mdi-book-outline'],
    'convivencia'   => ['label' => 'Convivencia',    'color' => '#9333EA', 'bg' => '#F3E8FF', 'icon' => 'mdi-account-group-outline'],
    'logro'         => ['label' => 'Logro',          'color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => 'mdi-trophy-outline'],
    'disciplinaria' => ['label' => 'Disciplinaria',  'color' => '#B91C1C', 'bg' => '#FEE2E2', 'icon' => 'mdi-gavel'],
    'otro'          => ['label' => 'Otro',           'color' => '#6B7280', 'bg' => '#F3F4F6', 'icon' => 'mdi-note-text-outline'],
];

// Agrupar por año lectivo para la línea de tiempo
$porAno = [];
foreach ($timeline as $t) {
    $porAno[$t['ano']][] = $t;
}

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h1>
        <p class="page-subtitle">
            <?= e($estudiante['tipo_documento'] . ' ' . $estudiante['documento']) ?>
            — <?= count($timeline) ?> registro<?= count($timeline) != 1 ? 's' : '' ?> en el histórico
        </p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= APP_URL ?>/observador/<?= $estudiante['id'] ?>/pdf" target="_blank" class="btn-secondary">
            <i class="mdi mdi-file-pdf-box"></i> Exportar hoja de vida (PDF)
        </a>
        <a href="<?= APP_URL ?>/observador" class="btn-secondary">
            <i class="mdi mdi-arrow-left"></i> Buscar otro
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    <!-- Línea de tiempo -->
    <div>
        <?php if (empty($timeline)): ?>
            <div class="card empty-state" style="padding:40px">
                <i class="mdi mdi-timeline-text-outline"></i>
                <p>Sin observaciones ni anotaciones registradas todavía.</p>
            </div>
        <?php else: ?>
            <?php foreach ($porAno as $ano => $registros): ?>
            <div style="margin-bottom:8px;margin-top:18px;font-weight:800;font-size:.9rem;color:var(--text-muted)">
                <?= e($ano) ?>
            </div>
            <?php foreach ($registros as $r):
                $cfg = $tipoCfg[$r['tipo']] ?? $tipoCfg['otro'];
            ?>
            <div class="card" style="padding:14px 16px;margin-bottom:10px;border-left:4px solid <?= $cfg['color'] ?>">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:999px;background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
                        <i class="mdi <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
                    </span>
                    <span style="font-size:.78rem;color:var(--text-muted)"><?= date('d/m/Y', strtotime($r['fecha'])) ?></span>
                    <span style="font-size:.78rem;color:var(--text-muted)">· <?= e($r['grado'] . ' ' . $r['grupo']) ?></span>
                    <span style="font-size:.78rem;color:var(--text-muted)">· <?= e($r['doc_nombre'] . ' ' . $r['doc_apellido']) ?> (<?= ucfirst($r['doc_rol']) ?>)</span>
                </div>
                <p style="font-size:.85rem;margin:0;white-space:pre-wrap"><?= e($r['descripcion']) ?></p>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Registrar anotación disciplinaria -->
    <div class="card" style="padding:20px;position:sticky;top:80px">
        <div class="section-title" style="margin-bottom:12px">
            <i class="mdi mdi-gavel" style="color:#B91C1C"></i> Nueva anotación disciplinaria
        </div>
        <?php if (!$matriculaActual): ?>
            <p style="font-size:.82rem;color:var(--text-muted)">Este estudiante no tiene matrícula registrada.</p>
        <?php else: ?>
        <form method="POST" action="<?= APP_URL ?>/observador/<?= $estudiante['id'] ?>/guardar">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
            <input type="hidden" name="matricula_id" value="<?= (int)$matriculaActual ?>">
            <div class="form-group">
                <textarea name="descripcion" class="form-input" rows="5" required
                          placeholder="Ej: Llamado de atención por reiterados retardos. Se cita a acudiente."></textarea>
            </div>
            <small class="form-hint" style="display:block;margin-bottom:10px">
                Quedará visible para el acudiente igual que las observaciones de los docentes (con
                seguimiento de lectura), marcada como "Disciplinaria" para diferenciarla.
            </small>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center">
                <i class="mdi mdi-plus"></i> Registrar anotación
            </button>
        </form>
        <?php endif; ?>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
