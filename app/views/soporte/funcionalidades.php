<?php
$pageTitle   = 'Funcionalidades del colegio — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Funcionalidades', 'url' => ''],
];
$noLeidos = 0;

$toggles = [
    [
        'name'  => 'notificaciones_email',
        'id'    => 'noti',
        'color' => 'var(--primary)',
        'titulo'=> 'Notificaciones por correo electrónico',
        'desc'  => 'Permite que los usuarios configuren qué eventos les llegan al correo. Requiere plan activo con Resend.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_mensajes_ocultos',
        'id'    => 'msjocultos',
        'color' => '#0E7490',
        'titulo'=> 'Mensajes con destinatarios ocultos',
        'desc'  => 'Al redactar a varios destinatarios, permite elegir que nadie vea la lista completa, o que solo los administrativos puedan verla.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_cuentas_estudiantes',
        'id'    => 'est',
        'color' => '#7C3AED',
        'titulo'=> 'Habilitar cuentas de estudiante',
        'desc'  => 'Deja la preferencia lista para cuando los estudiantes puedan iniciar sesión por su cuenta. Aún no operativo: mientras tanto, los acudientes ven y responden en su nombre desde "Mensajes" en cada hijo.',
        'badge' => 'PRÓXIMAMENTE',
    ],
    [
        'name'  => 'permite_observador_consolidado',
        'id'    => 'observador',
        'color' => '#B91C1C',
        'titulo'=> 'Observador del estudiante (consolidado)',
        'desc'  => 'Línea de tiempo multi-año por estudiante: une las observaciones de los docentes con las anotaciones disciplinarias que registre coordinación/administración.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_planeador_clases',
        'id'    => 'planeador',
        'color' => '#0891B2',
        'titulo'=> 'Planeador de clases',
        'desc'  => 'Permite a los docentes registrar y consultar planeaciones de clase por materia, grupo y período.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_matricula_online',
        'id'    => 'matricula',
        'color' => '#15803D',
        'titulo'=> 'Matrícula en línea',
        'desc'  => 'Formulario público de inscripción para aspirantes, con revisión y aprobación desde administración.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_boletines_pdf',
        'id'    => 'boletines',
        'color' => '#C026D3',
        'titulo'=> 'Boletines de notas en PDF',
        'desc'  => 'Permite generar e imprimir el boletín de calificaciones de cada estudiante, por período o consolidado del año, con el logo del colegio.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_constancias',
        'id'    => 'constancias',
        'color' => '#EA580C',
        'titulo'=> 'Constancias y certificados',
        'desc'  => 'Permite a administración generar en PDF constancias de estudio y otros certificados por estudiante.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_circulares',
        'id'    => 'circulares',
        'color' => '#0369A1',
        'titulo'=> 'Circulares institucionales',
        'desc'  => 'Comunicados oficiales (no conversación) dirigidos a todos, a padres, a docentes o a un grado específico, con acuse de recibo: queda registrado quién lo leyó.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_pqrs',
        'id'    => 'pqrs',
        'color' => '#BE185D',
        'titulo'=> 'PQRS (Peticiones, quejas, reclamos y sugerencias)',
        'desc'  => 'Canal formal con número de radicado para que padres y docentes presenten solicitudes, con seguimiento de estado y respuesta de administración.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_encuestas',
        'id'    => 'encuestas',
        'color' => '#4D7C0F',
        'titulo'=> 'Encuestas',
        'desc'  => 'Permite a administración crear encuestas (evaluación docente, clima escolar, satisfacción) dirigidas a padres o docentes, con resultados agregados.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_autoevaluacion',
        'id'    => 'autoeval',
        'color' => '#7C2D12',
        'titulo'=> 'Autoevaluación / coevaluación',
        'desc'  => 'Cuando el docente marca un indicador como autoevaluación, el acudiente podrá diligenciar la nota en nombre del estudiante desde "Mis hijos".',
        'badge' => null,
    ],
    [
        'name'  => 'permite_citas',
        'id'    => 'citas',
        'color' => '#0D9488',
        'titulo'=> 'Citas padre-docente',
        'desc'  => 'Los docentes publican horarios disponibles y los padres reservan directamente el horario que les convenga, sin necesidad de coordinar por mensajes.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_autorizaciones',
        'id'    => 'autorizaciones',
        'color' => '#1D4ED8',
        'titulo'=> 'Autorizaciones y consentimientos digitales',
        'desc'  => 'Salidas pedagógicas, uso de imagen y otros consentimientos. El acudiente firma con su documento y una fotografía en vivo con reto aleatorio (anti-fraude), que queda almacenada de forma protegida.',
        'badge' => null,
    ],
    [
        'name'  => 'permite_retiro_anticipado',
        'id'    => 'retiro',
        'color' => '#9A3412',
        'titulo'=> 'Retiro anticipado de estudiantes',
        'desc'  => 'Portería/administración registra quién retira a un estudiante antes de la hora normal de salida, con fotografía en vivo y reto aleatorio como evidencia de identidad.',
        'badge' => null,
    ],
];

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Funcionalidades del colegio</h1>
        <p class="page-subtitle">Activa o desactiva funciones premium / en desarrollo para esta institución.</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/soporte/funcionalidades/guardar">
<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">

<?php foreach ($toggles as $t): $activo = !empty($colegio[$t['name']]); ?>
<div class="card" style="padding:20px 22px;margin-bottom:14px">
    <label style="display:flex;align-items:center;gap:14px;cursor:pointer">
        <div style="position:relative;width:44px;height:24px;flex-shrink:0">
            <input type="checkbox" name="<?= $t['name'] ?>" value="1"
                   <?= $activo ? 'checked' : '' ?>
                   style="display:none" id="toggle-<?= $t['id'] ?>">
            <div id="toggle-<?= $t['id'] ?>-track"
                 style="width:44px;height:24px;border-radius:12px;
                        background:<?= $activo ? $t['color'] : '#D1D5DB' ?>;
                        transition:background .2s;cursor:pointer"
                 onclick="toggleFlag('<?= $t['id'] ?>', '<?= $t['color'] ?>')">
                <div id="toggle-<?= $t['id'] ?>-thumb"
                     style="width:20px;height:20px;border-radius:50%;background:#fff;margin:2px;
                            transform:<?= $activo ? 'translateX(20px)' : 'translateX(0)' ?>;
                            transition:transform .2s"></div>
            </div>
        </div>
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-weight:700;font-size:.92rem"><?= e($t['titulo']) ?></span>
                <?php if ($t['badge']): ?>
                    <span style="font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:99px;background:<?= $t['color'] ?>;color:#fff"><?= $t['badge'] ?></span>
                <?php endif; ?>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px"><?= e($t['desc']) ?></div>
        </div>
    </label>
</div>
<?php endforeach; ?>

<div class="form-actions">
    <button type="submit" class="btn-primary">
        <i class="mdi mdi-content-save-outline"></i> Guardar
    </button>
</div>
</form>

<script>
function toggleFlag(id, colorOn) {
    var chk   = document.getElementById('toggle-' + id);
    var track = document.getElementById('toggle-' + id + '-track');
    var thumb = document.getElementById('toggle-' + id + '-thumb');
    chk.checked = !chk.checked;
    track.style.background = chk.checked ? colorOn : '#D1D5DB';
    thumb.style.transform  = chk.checked ? 'translateX(20px)' : 'translateX(0)';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
