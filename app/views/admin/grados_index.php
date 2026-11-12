<?php
$pageTitle   = 'Grados y Grupos — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Grados y Grupos', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="mdi mdi-school-outline"></i> Grados y Grupos</h1>
        <p class="page-subtitle"><?= $anoActivo ? 'Año lectivo activo: ' . e($anoActivo['nombre']) : 'No hay un año lectivo activo.' ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/materias" class="btn-secondary"><i class="mdi mdi-book-open-page-variant-outline"></i> Materias</a>
        <a href="<?= APP_URL ?>/grados/catalogo" class="btn-secondary"><i class="mdi mdi-stairs"></i> Grados</a>
        <a href="<?= APP_URL ?>/asignaciones" class="btn-secondary"><i class="mdi mdi-account-tie-outline"></i> Asignaciones</a>
        <a href="<?= APP_URL ?>/auditoria" class="btn-secondary"><i class="mdi mdi-clipboard-text-clock-outline"></i> Auditoría</a>
        <a href="<?= APP_URL ?>/ficha-estudiante" class="btn-secondary"><i class="mdi mdi-card-account-details-outline"></i> Ficha de estudiante</a>
        <a href="<?= APP_URL ?>/sedes" class="btn-secondary"><i class="mdi mdi-domain"></i> Sedes</a>
        <a href="<?= APP_URL ?>/habilitaciones" class="btn-secondary"><i class="mdi mdi-book-alert-outline"></i> Habilitación anual</a>
        <?php if ($anoActivo): ?>
        <a href="<?= APP_URL ?>/grados/nuevo-grupo" class="btn-secondary"><i class="mdi mdi-plus"></i> Nuevo grupo</a>
        <a href="<?= APP_URL ?>/grados/promocion" class="btn-primary">
            <i class="mdi mdi-arrow-up-bold-circle-outline"></i> Promover a nuevo año lectivo
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$grupos): ?>
    <div class="card" style="padding:30px;text-align:center;color:var(--text-muted);margin-bottom:24px">
        No hay grupos en el año lectivo activo.
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:28px">
<?php foreach ($grupos as $g): ?>
    <div class="card" style="padding:16px 18px;position:relative">
        <a href="<?= APP_URL ?>/grados/grupo/<?= $g['id'] ?>/editar" style="position:absolute;top:10px;right:10px;color:var(--text-muted)" title="Editar grupo">
            <i class="mdi mdi-pencil-outline"></i>
        </a>
        <a href="<?= APP_URL ?>/grados/ajustar/<?= $g['id'] ?>" style="text-decoration:none;color:inherit;display:block">
            <div style="font-weight:700"><?= e($g['grado'] . ' ' . $g['nombre']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:3px"><?= $g['total_est'] ?> estudiantes</div>
            <?php if ($g['director_nombre']): ?>
            <div style="font-size:.75rem;color:var(--text-muted)">Director: <?= e($g['director_nombre'] . ' ' . $g['director_apellido']) ?></div>
            <?php endif; ?>
        </a>
    </div>
<?php endforeach; ?>
</div>

<div style="font-weight:700;margin-bottom:10px">Años lectivos</div>
<div class="card" style="padding:0">
<?php foreach ($anosHistoricos as $a): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid var(--border)" id="ano-fila-<?= $a['id'] ?>">
        <div style="flex:1" id="ano-vista-<?= $a['id'] ?>">
            <span style="font-weight:600"><?= e($a['nombre']) ?></span>
            <?php if ($a['activo']): ?>
                <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:99px;background:#16A34A;color:#fff;margin-left:6px">ACTIVO</span>
            <?php else: ?>
                <span style="font-size:.7rem;color:var(--text-muted);text-transform:capitalize;margin-left:6px">(<?= e($a['estado']) ?>)</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px">
            <button type="button" class="btn-sm btn-outline" onclick="toggleEdicionAno(<?= $a['id'] ?>)" title="Renombrar"><i class="mdi mdi-pencil-outline"></i></button>
            <?php if (!$a['activo']): ?>
            <form method="POST" action="<?= APP_URL ?>/grados/ano/<?= $a['id'] ?>/reabrir" onsubmit="return confirm('¿Reabrir este año lectivo como el año activo? El año actualmente activo se cerrará.')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-outline" title="Reabrir como activo"><i class="mdi mdi-lock-open-variant-outline"></i></button>
            </form>
            <?php if ($a['estado'] !== 'archivado'): ?>
            <form method="POST" action="<?= APP_URL ?>/grados/ano/<?= $a['id'] ?>/archivar" onsubmit="return confirm('¿Archivar este año lectivo?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-outline" title="Archivar"><i class="mdi mdi-archive-outline"></i></button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/grados/ano/<?= $a['id'] ?>/renombrar" id="ano-edicion-<?= $a['id'] ?>" style="display:none;padding:10px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <div style="display:flex;gap:8px">
            <input type="text" name="nombre" class="form-input" value="<?= e($a['nombre']) ?>" required style="flex:1">
            <button type="submit" class="btn-sm btn-primary">Guardar</button>
        </div>
    </form>
<?php endforeach; ?>
</div>

<script>
function toggleEdicionAno(id) {
    const el = document.getElementById('ano-edicion-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
