<?php
$pageTitle   = ($editando ? 'Editar' : 'Nuevo') . ' usuario — ' . APP_NAME;
$breadcrumbs = [
    ['label'=>'Inicio','url'=>APP_URL.'/dashboard'],
    ['label'=>'Usuarios','url'=>APP_URL.'/usuarios'],
    ['label'=>$editando?'Editar':'Nuevo usuario','url'=>''],
];
$noLeidos = 0;
$action   = $editando ? APP_URL.'/usuarios/'.$usuario['id'].'/actualizar' : APP_URL.'/usuarios/guardar';
$v  = fn($k,$def='') => htmlspecialchars(($usuario[$k]  ?? $def), ENT_QUOTES);
$vp = fn($k,$def='') => htmlspecialchars(($perfil[$k]   ?? $def), ENT_QUOTES);
$ve = fn($k,$def='') => htmlspecialchars(($estudiante[$k] ?? $def), ENT_QUOTES);
$rolActual = $usuario['rol'] ?? 'estudiante';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
        <?php if ($editando): ?>
        <p class="page-subtitle"><?= e($usuario['nombre'].' '.$usuario['apellido']) ?></p>
        <?php endif; ?>
    </div>
    <a href="<?= APP_URL ?>/usuarios" class="btn-secondary"><i class="mdi mdi-arrow-left"></i> Volver</a>
</div>

<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

    <!-- ── Columna principal ─────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- 1. Datos de acceso -->
        <div class="card" style="padding:22px">
            <div class="section-title"><i class="mdi mdi-account-key-outline" style="color:var(--primary)"></i> Datos de acceso</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" class="form-input" required
                           value="<?= $v('nombre') ?>" placeholder="Nombre(s)"
                           oninput="sugerirUsername()">
                </div>
                <div class="form-group">
                    <label>Apellido <span class="required">*</span></label>
                    <input type="text" name="apellido" class="form-input" required
                           value="<?= $v('apellido') ?>" placeholder="Apellido(s)"
                           oninput="sugerirUsername()">
                </div>
            </div>

            <!-- Rol principal -->
            <div class="form-group">
                <label>Rol principal <span class="required">*</span>
                    <small style="font-weight:400;color:var(--text-muted)"> — determina el dashboard y permisos</small>
                </label>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <?php foreach ($roles as $r => $label): ?>
                    <?php $checked = $rolActual===$r; ?>
                    <label style="display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid <?= $checked?'var(--primary)':'var(--border)' ?>;border-radius:20px;cursor:pointer;font-size:.85rem;font-weight:<?= $checked?'700':'500' ?>;background:<?= $checked?'var(--primary-light)':'#fff' ?>;color:<?= $checked?'var(--primary)':'var(--text)' ?>;transition:all .15s"
                          id="lbl-rol-<?= $r ?>">
                        <input type="radio" name="rol" value="<?= $r ?>" <?= $checked?'checked':'' ?>
                               onchange="cambiarRol(this.value)" style="display:none">
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Roles adicionales -->
            <div class="form-group">
                <label>Roles adicionales
                    <small style="font-weight:400;color:var(--text-muted)"> — un docente puede ser también padre, etc.</small>
                </label>
                <div style="display:flex;flex-wrap:wrap;gap:6px" id="roles-extra-wrap">
                    <?php foreach ($roles as $r => $label):
                        $tieneExtra = in_array($r, $usuario['roles'] ?? []) && $r !== $rolActual;
                    ?>
                    <label class="rol-extra-lbl" data-rol="<?= $r ?>"
                           style="display:flex;align-items:center;gap:6px;padding:5px 12px;border:1.5px solid <?= $tieneExtra?'var(--primary)':'var(--border)' ?>;border-radius:20px;cursor:pointer;font-size:.8rem;background:<?= $tieneExtra?'var(--primary-light)':'#fff' ?>;color:<?= $tieneExtra?'var(--primary)':'var(--text-muted)' ?>;transition:all .15s"
                           id="lbl-extra-<?= $r ?>">
                        <input type="checkbox" name="roles_extra[]" value="<?= $r ?>"
                               <?= $tieneExtra?'checked':'' ?> style="display:none"
                               onchange="toggleExtraLabel(this)">
                        <i class="mdi mdi-plus-circle-outline" id="ico-extra-<?= $r ?>"></i>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-hint">Los roles adicionales permiten acceso a sus funciones sin cambiar el dashboard principal.</small>
            </div>

            <div class="form-group" id="grupo-wrap" style="<?= !in_array('estudiante',$usuario['roles']??[$rolActual])?'display:none':'' ?>">
                    <label>Grupo/Grado</label>
                    <select name="grupo_id" class="form-select">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($estudiante['grupo_id']??'')==$g['id']?'selected':'' ?>>
                            <?= e($g['grado'].' '.$g['grupo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" id="inp-username" class="form-input" required
                           value="<?= $v('username') ?>" placeholder="usuario.apellido">
                    <small class="form-hint">Se genera automáticamente, puedes modificarlo.</small>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" required
                           value="<?= $v('email') ?>" placeholder="correo@dominio.com">
                </div>
            </div>

            <div class="form-group">
                <label><?= $editando ? 'Nueva contraseña' : 'Contraseña' ?> <?= $editando?'':'<span class="required">*</span>' ?></label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="password" id="inp-password" class="form-input"
                           placeholder="<?= $editando?'Dejar vacío para no cambiar':'Contraseña segura' ?>"
                           <?= $editando?'':'required' ?>>
                    <button type="button" class="btn-secondary" onclick="generarPassword()" title="Generar contraseña">
                        <i class="mdi mdi-refresh"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Documento (estudiantes) -->
        <div class="card" style="padding:22px" id="sec-documento" <?= $rolActual!=='estudiante'?'style="display:none"':'' ?>>
            <div class="section-title"><i class="mdi mdi-card-account-details-outline" style="color:var(--primary)"></i> Documento de identidad</div>
            <div style="display:grid;grid-template-columns:160px 1fr;gap:14px">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo_documento" class="form-select">
                        <?php foreach (['TI'=>'Tarjeta Identidad','CC'=>'Cédula de Ciudadanía','CE'=>'Cédula Extranjería','PP'=>'Pasaporte'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= $ve('tipo_documento')===$k?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de documento</label>
                    <input type="text" name="documento" class="form-input"
                           value="<?= $ve('documento') ?>" placeholder="1098765432">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 160px 120px;gap:14px">
                <div class="form-group">
                    <label>Lugar de nacimiento</label>
                    <input type="text" name="lugar_nacimiento" class="form-input"
                           value="<?= $ve('lugar_nacimiento') ?>" placeholder="Ciudad">
                </div>
                <div class="form-group">
                    <label>Género</label>
                    <select name="genero" class="form-select">
                        <option value="">—</option>
                        <option value="M" <?= $ve('genero')==='M'?'selected':'' ?>>Masculino</option>
                        <option value="F" <?= $ve('genero')==='F'?'selected':'' ?>>Femenino</option>
                        <option value="O" <?= $ve('genero')==='O'?'selected':'' ?>>Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estrato</label>
                    <select name="estrato" class="form-select">
                        <option value="">—</option>
                        <?php for($i=1;$i<=6;$i++): ?>
                        <option value="<?= $i ?>" <?= $ve('estrato')==$i?'selected':'' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- 3. Información personal -->
        <div class="card" style="padding:22px">
            <div class="section-title"><i class="mdi mdi-account-circle-outline" style="color:var(--primary)"></i> Información personal</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-input"
                           value="<?= $vp('fecha_nacimiento') ?>">
                </div>
                <div class="form-group">
                    <label>Nacionalidad</label>
                    <input type="text" name="nacionalidad" class="form-input"
                           value="<?= $vp('nacionalidad','Colombiana') ?>" placeholder="Colombiana">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>País</label>
                    <input type="text" name="pais" class="form-input"
                           value="<?= $vp('pais','Colombia') ?>" placeholder="Colombia">
                </div>
                <div class="form-group">
                    <label>Departamento</label>
                    <input type="text" name="departamento" class="form-input"
                           value="<?= $vp('departamento') ?>" placeholder="Casanare">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" class="form-input"
                           value="<?= $vp('ciudad') ?>" placeholder="Yopal">
                </div>
            </div>
        </div>

        <!-- 4. Información de contacto -->
        <div class="card" style="padding:22px">
            <div class="section-title"><i class="mdi mdi-map-marker-outline" style="color:var(--primary)"></i> Información de contacto</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-input"
                           value="<?= $vp('direccion') ?>" placeholder="Calle / Carrera / Av...">
                </div>
                <div class="form-group">
                    <label>Barrio</label>
                    <input type="text" name="barrio" class="form-input"
                           value="<?= $vp('barrio') ?>" placeholder="Nombre del barrio">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-input"
                           value="<?= $vp('telefono') ?>" placeholder="6084321234">
                </div>
                <div class="form-group">
                    <label>Celular</label>
                    <div style="display:flex;gap:6px">
                        <select name="cel_indicativo" class="form-select" style="width:90px;flex-shrink:0">
                            <option value="+57" <?= $vp('celular_indicativo','+57')==='+57'?'selected':'' ?>>+57</option>
                            <option value="+1"  <?= $vp('celular_indicativo')==='+1' ?'selected':'' ?>>+1</option>
                            <option value="+34" <?= $vp('celular_indicativo')==='+34'?'selected':'' ?>>+34</option>
                        </select>
                        <input type="text" name="celular" class="form-input"
                               value="<?= $vp('celular') ?>" placeholder="3101234567">
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Datos de colaborador (docentes/admin/soporte) -->
        <div class="card" style="padding:22px" id="sec-colaborador" <?= $rolActual==='estudiante'||$rolActual==='padre'?'style="display:none"':'' ?>>
            <div class="section-title"><i class="mdi mdi-briefcase-outline" style="color:var(--primary)"></i> Datos de colaborador</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Email personal</label>
                    <input type="email" name="email_personal" class="form-input"
                           value="<?= $vp('email_personal') ?>" placeholder="correo@personal.com">
                    <small class="form-hint">Email personal (diferente al institucional)</small>
                </div>
                <div class="form-group">
                    <label>Cargo</label>
                    <select name="cargo" class="form-select">
                        <option value="">— Seleccionar cargo —</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($v('cargo')===$c||$vp('cargo')===$c)?'selected':'' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div style="display:flex;gap:10px;justify-content:flex-end;padding:4px 0">
            <a href="<?= APP_URL ?>/usuarios" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">
                <i class="mdi mdi-content-save-outline"></i>
                <?= $editando ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
        </div>

    </div><!-- /columna principal -->

    <!-- ── Panel lateral ─────────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px"><i class="mdi mdi-information-outline" style="color:var(--primary)"></i> Ayuda</div>
            <ul style="font-size:.82rem;color:var(--text-muted);padding-left:1.2em;line-height:2">
                <li>Los campos con <span style="color:var(--danger)">*</span> son obligatorios.</li>
                <li>El username se genera automáticamente desde nombre y apellido.</li>
                <li>Si el estudiante necesita ser asignado a un grupo, selecciónalo en el campo Grupo/Grado.</li>
                <li>Para carga de múltiples usuarios usa <a href="<?= APP_URL ?>/usuarios/carga-masiva" style="color:var(--primary)">Carga masiva</a>.</li>
            </ul>
        </div>
        <?php if ($editando): ?>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px;color:var(--danger)"><i class="mdi mdi-alert-outline"></i> Zona peligrosa</div>
            <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $usuario['id'] ?>/eliminar"
                  onsubmit="return confirm('¿Eliminar permanentemente este usuario?\nEsta acción no se puede deshacer.')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-secondary" style="width:100%;color:var(--danger);border-color:var(--danger)">
                    <i class="mdi mdi-trash-can-outline"></i> Eliminar usuario
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    </div><!-- /grid -->
</form>

<?php if ($editando): ?>
<div class="card" style="padding:22px;max-width:620px;margin-top:20px">
    <div class="section-title" style="margin-bottom:12px"><i class="mdi mdi-shield-key-outline" style="color:var(--primary)"></i> Seguridad de la cuenta</div>
    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:12px">
        Última conexión: <strong><?= !empty($usuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca ha iniciado sesión' ?></strong>
    </p>
    <?php if (!empty($usuario['session_token']) && Auth::id() !== (int)$usuario['id']): ?>
    <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $usuario['id'] ?>/cerrar-sesiones" onsubmit="return confirm('¿Cerrar la sesión activa de este usuario en todos los dispositivos? Deberá volver a iniciar sesión.');">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <button type="submit" class="btn-secondary"><i class="mdi mdi-logout-variant"></i> Cerrar sesión en todos los dispositivos</button>
    </form>
    <?php else: ?>
        <span style="font-size:.82rem;color:var(--text-muted)"><i class="mdi mdi-information-outline"></i> Este usuario no tiene una sesión activa en este momento.</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($editando && $usuario['rol'] === 'padre'): ?>
<div class="card" style="padding:22px;max-width:620px;margin-top:20px">
    <div class="section-title"><i class="mdi mdi-account-child-outline" style="color:var(--primary)"></i> Hijos vinculados (acudientes)</div>

    <?php if (!$hijosVinculados): ?>
    <div style="color:var(--text-muted);font-size:.85rem;margin-bottom:14px">Este padre aún no tiene hijos vinculados.</div>
    <?php else: ?>
    <div style="margin-bottom:16px">
        <?php foreach ($hijosVinculados as $h): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px">
            <div>
                <div style="font-weight:600;font-size:.88rem"><?= e($h['nombre'] . ' ' . $h['apellido']) ?></div>
                <div style="font-size:.76rem;color:var(--text-muted)"><?= e($h['parentesco'] ?: 'Acudiente') ?><?= $h['grupo'] ? ' · ' . e($h['grado'] . ' ' . $h['grupo']) : '' ?></div>
            </div>
            <form method="POST" action="<?= APP_URL ?>/usuarios/<?= $usuario['id'] ?>/hijos/<?= $h['acudiente_id'] ?>/quitar" onsubmit="return confirm('¿Quitar este vínculo?')">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-sm btn-danger-outline"><i class="mdi mdi-link-off"></i></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="border-top:1px solid var(--border);padding-top:14px">
        <label style="font-weight:600;font-size:.85rem;display:block;margin-bottom:8px">Vincular un hijo</label>
        <input type="text" id="buscar-hijo" class="form-input" placeholder="Nombre, apellido o documento del estudiante..." autocomplete="off" style="margin-bottom:8px">
        <div id="resultados-hijo"></div>
    </div>
</div>

<form id="form-vincular-hijo" method="POST" action="<?= APP_URL ?>/usuarios/<?= $usuario['id'] ?>/hijos/agregar" style="display:none">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
    <input type="hidden" name="estudiante_id" id="input-hijo-estudiante-id">
    <input type="hidden" name="parentesco" id="input-hijo-parentesco">
</form>

<script>
(function() {
    const input = document.getElementById('buscar-hijo');
    const cont  = document.getElementById('resultados-hijo');
    let t = null;
    input.addEventListener('input', () => {
        clearTimeout(t);
        const q = input.value.trim();
        if (q.length < 2) { cont.innerHTML = ''; return; }
        t = setTimeout(() => {
            fetch('<?= APP_URL ?>/usuarios/<?= $usuario['id'] ?>/hijos/buscar?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(datos => {
                    if (!datos.length) { cont.innerHTML = '<div style="padding:8px;color:var(--text-muted);font-size:.82rem">Sin resultados.</div>'; return; }
                    cont.innerHTML = datos.map(d => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-bottom:1px solid var(--border)">
                            <div style="font-size:.85rem">${d.nombre} ${d.apellido} <span style="color:var(--text-muted);font-size:.76rem">(${d.documento || 'sin doc.'})</span></div>
                            <div style="display:flex;gap:6px">
                                <button type="button" class="btn-sm btn-outline" onclick="vincularHijo(${d.estudiante_id}, 'Padre')">Padre</button>
                                <button type="button" class="btn-sm btn-outline" onclick="vincularHijo(${d.estudiante_id}, 'Madre')">Madre</button>
                                <button type="button" class="btn-sm btn-outline" onclick="vincularHijo(${d.estudiante_id}, 'Acudiente')">Otro</button>
                            </div>
                        </div>
                    `).join('');
                });
        }, 300);
    });
})();
function vincularHijo(estudianteId, parentesco) {
    document.getElementById('input-hijo-estudiante-id').value = estudianteId;
    document.getElementById('input-hijo-parentesco').value = parentesco;
    document.getElementById('form-vincular-hijo').submit();
}
</script>
<?php endif; ?>

<script>
const ROLES_STAFF = ['docente','admin','soporte'];

function getActiveRoles() {
    const principal = document.querySelector('input[name=rol]:checked')?.value ?? '';
    const extras = [...document.querySelectorAll('input[name="roles_extra[]"]:checked')].map(c => c.value);
    return [principal, ...extras].filter(Boolean);
}

function cambiarRol(rolNuevo) {
    // Estilo de los botones de rol principal
    document.querySelectorAll('input[name=rol]').forEach(r => {
        const lbl = document.getElementById('lbl-rol-' + r.value);
        const active = r.value === rolNuevo;
        lbl.style.borderColor  = active ? 'var(--primary)' : 'var(--border)';
        lbl.style.background   = active ? 'var(--primary-light)' : '#fff';
        lbl.style.color        = active ? 'var(--primary)' : 'var(--text)';
        lbl.style.fontWeight   = active ? '700' : '500';
    });
    // Ocultar el mismo rol en los extras
    document.querySelectorAll('.rol-extra-lbl').forEach(lbl => {
        lbl.style.display = lbl.dataset.rol === rolNuevo ? 'none' : '';
    });
    actualizarSecciones();
}

function toggleExtraLabel(cb) {
    const lbl = cb.closest('label');
    lbl.style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
    lbl.style.background  = cb.checked ? 'var(--primary-light)' : '#fff';
    lbl.style.color       = cb.checked ? 'var(--primary)' : 'var(--text-muted)';
    document.getElementById('ico-extra-' + cb.value).className =
        cb.checked ? 'mdi mdi-check-circle' : 'mdi mdi-plus-circle-outline';
    actualizarSecciones();
}

function actualizarSecciones() {
    const all = getActiveRoles();
    const tieneEst    = all.includes('estudiante');
    const tieneStaff  = all.some(r => ROLES_STAFF.includes(r));

    document.getElementById('grupo-wrap').style.display     = tieneEst   ? '' : 'none';
    document.getElementById('sec-documento').style.display  = tieneEst   ? '' : 'none';
    document.getElementById('sec-colaborador').style.display = tieneStaff ? '' : 'none';
}

// Inicializar
cambiarRol(document.querySelector('input[name=rol]:checked')?.value ?? 'estudiante');

function sugerirUsername() {
    const nombre   = document.querySelector('[name=nombre]').value.trim();
    const apellido = document.querySelector('[name=apellido]').value.trim();
    if (!nombre || !apellido) return;
    const n = nombre.split(' ')[0].toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]/g,'');
    const a = apellido.split(' ')[0].toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]/g,'');
    const inp = document.getElementById('inp-username');
    if (!inp.dataset.modified) inp.value = n + '.' + a;
}
document.getElementById('inp-username').addEventListener('input', function() {
    this.dataset.modified = 'true';
});

function generarPassword() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
    let pwd = '';
    for (let i = 0; i < 10; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('inp-password').value = pwd;
    document.getElementById('inp-password').type = 'text';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
