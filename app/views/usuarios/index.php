<?php
$pageTitle   = 'Usuarios — ' . APP_NAME;
$breadcrumbs = [['label'=>'Inicio','url'=>APP_URL.'/dashboard'],['label'=>'Usuarios','url'=>'']];
$noLeidos    = 0;
$rolLabels   = ['admin'=>'Admin','docente'=>'Docente','estudiante'=>'Estudiante','padre'=>'Familiar','soporte'=>'Soporte'];
$rolColors   = ['admin'=>'#DC2626','docente'=>'#2563EB','estudiante'=>'#7C3AED','padre'=>'#059669','soporte'=>'#D97706'];
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Usuarios</h1>
        <p class="page-subtitle">Gestión de todos los usuarios del sistema</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= APP_URL ?>/usuarios/carga-masiva" class="btn-secondary">
            <i class="mdi mdi-upload"></i> Carga masiva
        </a>
        <a href="<?= APP_URL ?>/usuarios/nuevo" class="btn-primary">
            <i class="mdi mdi-account-plus-outline"></i> Nuevo usuario
        </a>
    </div>
</div>

<!-- Tabs por rol -->
<div class="msg-tabs" style="margin-bottom:16px">
    <a href="<?= APP_URL ?>/usuarios" class="msg-tab <?= !$rol ? 'active' : '' ?>">
        Todos <span class="badge" style="background:var(--primary)"><?= array_sum($conteos) ?></span>
    </a>
    <?php foreach ($rolLabels as $r => $label): ?>
    <a href="<?= APP_URL ?>/usuarios?rol=<?= $r ?><?= $q ? '&q='.urlencode($q) : '' ?>"
       class="msg-tab <?= $rol===$r ? 'active' : '' ?>">
        <?= $label ?>
        <?php if (!empty($conteos[$r])): ?>
            <span class="badge" style="background:<?= $rolColors[$r] ?>"><?= $conteos[$r] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Buscador -->
<form method="GET" action="<?= APP_URL ?>/usuarios" style="margin-bottom:16px;display:flex;gap:8px">
    <?php if ($rol): ?><input type="hidden" name="rol" value="<?= e($rol) ?>"><?php endif; ?>
    <div style="position:relative;flex:1;max-width:400px">
        <i class="mdi mdi-magnify" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
        <input type="text" name="q" value="<?= e($q) ?>"
               class="form-input" style="padding-left:32px"
               placeholder="Buscar por nombre, username o email…">
    </div>
    <select name="grupo_id" class="form-input" style="max-width:220px">
        <option value="">Todos los grupos</option>
        <?php foreach ($grupos as $g): ?>
        <option value="<?= $g['id'] ?>" <?= $grupoId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['grado'] . ' ' . $g['grupo']) ?></option>
        <?php endforeach; ?>
    </select>
    <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;white-space:nowrap">
        <input type="checkbox" name="sin_acudiente" value="1" <?= $sinAcudiente ? 'checked' : '' ?>> Sin acudiente
    </label>
    <button type="submit" class="btn-primary"><i class="mdi mdi-magnify"></i> Buscar</button>
    <?php if ($q || $grupoId || $sinAcudiente): ?><a href="<?= APP_URL ?>/usuarios<?= $rol ? '?rol='.$rol : '' ?>" class="btn-secondary">Limpiar</a><?php endif; ?>
</form>

<?php if (empty($usuarios)): ?>
<div class="card"><div class="empty-state">
    <i class="mdi mdi-account-off-outline"></i>
    <p>No se encontraron usuarios<?= $q ? " para «{$q}»" : '' ?>.</p>
</div></div>
<?php else: ?>

<div class="card" style="overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Username</th>
                <th>Rol / Cargo</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:<?= $rolColors[$u['rol']] ?? '#888' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;flex-shrink:0">
                        <?= strtoupper(substr($u['apellido'],0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;display:flex;align-items:center;gap:6px">
                            <?= e($u['apellido'].', '.$u['nombre']) ?>
                            <?php if (!empty($u['es_principal']) && $u['rol'] === 'admin'): ?>
                            <span title="Admin principal" style="display:inline-flex;align-items:center;gap:3px;font-size:.68rem;font-weight:700;padding:1px 7px;border-radius:99px;background:#FEF9C3;color:#92400E;border:1px solid #FDE68A">
                                <i class="mdi mdi-star" style="font-size:.75rem"></i> Principal
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted)"><?= e($u['cargo'] ?? '') ?></div>
                    </div>
                </div>
            </td>
            <td><code style="font-size:.82rem"><?= e($u['username']) ?></code></td>
            <td>
                <span class="badge-pill" style="background:<?= $rolColors[$u['rol']] ?>22;color:<?= $rolColors[$u['rol']] ?>;border:1px solid <?= $rolColors[$u['rol']] ?>44">
                    <?= $rolLabels[$u['rol']] ?? $u['rol'] ?>
                </span>
            </td>
            <td style="font-size:.82rem"><?= e($u['email']) ?></td>
            <td>
                <?php if ($u['activo']): ?>
                    <span class="badge-pill badge-pill--success">Activo</span>
                <?php else: ?>
                    <span class="badge-pill badge-pill--muted">Inactivo</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/editar" class="btn-icon" title="Editar">
                        <i class="mdi mdi-pencil-outline"></i>
                    </a>
                    <?php if ($u['rol'] === 'admin' && empty($u['es_principal']) && Auth::isPrincipal()): ?>
                    <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/toggle-principal" style="display:inline"
                          onsubmit="return confirm('¿Marcar a <?= e($u['nombre'].' '.$u['apellido']) ?> como admin principal?\nEl admin principal actual perderá ese estado.')">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn-icon" title="Marcar como principal" style="color:#92400E">
                            <i class="mdi mdi-star-outline"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/toggle-activo" style="display:inline">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn-icon <?= $u['activo'] ? '' : 'btn-icon--success' ?>"
                                title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                            <i class="mdi mdi-<?= $u['activo'] ? 'account-off-outline' : 'account-check-outline' ?>"></i>
                        </button>
                    </form>
                    <?php if ($u['id'] !== Auth::id()): ?>
                    <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/eliminar" style="display:inline"
                          onsubmit="return confirm('¿Eliminar a <?= e($u['nombre'].' '.$u['apellido']) ?>?\nEsta acción no se puede deshacer.')">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Eliminar">
                            <i class="mdi mdi-trash-can-outline"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php if ($total > $limit): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:16px;flex-wrap:wrap">
    <?php
    $pages = ceil($total / $limit);
    $base  = APP_URL.'/usuarios?'.http_build_query(array_filter(['rol'=>$rol,'q'=>$q]));
    for ($p = 1; $p <= $pages; $p++):
    ?>
    <a href="<?= $base ?>&pagina=<?= $p ?>"
       style="padding:6px 12px;border-radius:6px;font-weight:700;font-size:.82rem;border:1.5px solid var(--border);background:<?= $p===$pagina ? 'var(--primary)' : '#fff' ?>;color:<?= $p===$pagina ? '#fff' : 'var(--text)' ?>;text-decoration:none">
        <?= $p ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
