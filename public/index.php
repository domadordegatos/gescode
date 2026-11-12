<?php
// Servir archivos estáticos directamente con el servidor built-in de PHP
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) return false;
}

// ── Bootstrap ────────────────────────────────────────────────
define('ROOT', dirname(__DIR__));
ob_start(); // captura output accidental de includes

require ROOT . '/vendor/autoload.php';
require ROOT . '/config/config.php';
require ROOT . '/config/database.php';
require ROOT . '/app/Auth.php';
require ROOT . '/app/Backup.php';
require ROOT . '/app/Auditoria.php';
require ROOT . '/app/helpers.php';
require ROOT . '/app/Tenant.php';
require ROOT . '/app/Mailer.php';
require ROOT . '/app/Notificador.php';
require ROOT . '/app/Router.php';
require ROOT . '/app/models/Usuario.php';
require ROOT . '/app/controllers/AuthController.php';
require ROOT . '/app/controllers/DashboardController.php';
require ROOT . '/app/controllers/ComunicacionesController.php';
require ROOT . '/app/controllers/PeriodosController.php';
require ROOT . '/app/controllers/UsuariosController.php';
require ROOT . '/app/models/Grupo.php';
require ROOT . '/app/models/Evaluacion.php';
require ROOT . '/app/models/Asistencia.php';
require ROOT . '/app/models/Tarea.php';
require ROOT . '/app/controllers/DocenteController.php';
require ROOT . '/app/controllers/DesempeniosController.php';
require ROOT . '/app/controllers/ColegioController.php';
require ROOT . '/app/controllers/CalendarioController.php';
require ROOT . '/app/controllers/PerfilController.php';
require ROOT . '/app/controllers/ComunidadController.php';
require ROOT . '/app/controllers/UploadController.php';
require ROOT . '/app/controllers/MisHijosController.php';
require ROOT . '/app/controllers/EventosInstitucionalesController.php';
require ROOT . '/app/controllers/CargaNotasController.php';
require ROOT . '/app/controllers/SoporteFuncionalidadesController.php';
require ROOT . '/app/controllers/ObservadorController.php';
require ROOT . '/app/controllers/BoletinController.php';
require ROOT . '/app/controllers/ConstanciaController.php';
require ROOT . '/app/controllers/PlaneadorController.php';
require ROOT . '/app/controllers/CircularController.php';
require ROOT . '/app/controllers/PqrsController.php';
require ROOT . '/app/controllers/EncuestaController.php';
require ROOT . '/app/controllers/AutoevaluacionController.php';
require ROOT . '/app/controllers/ReportesController.php';
require ROOT . '/app/controllers/HistorialNotasController.php';
require ROOT . '/app/controllers/CitaController.php';
require ROOT . '/app/controllers/AutorizacionController.php';
require ROOT . '/app/controllers/RetiroController.php';
require ROOT . '/app/controllers/GradosController.php';
require ROOT . '/app/controllers/PapeleraController.php';
require ROOT . '/app/controllers/PapeleraGeneralController.php';
require ROOT . '/app/controllers/BackupController.php';
require ROOT . '/app/controllers/AccesosController.php';
require ROOT . '/app/controllers/MateriasController.php';
require ROOT . '/app/controllers/AsignacionesController.php';
require ROOT . '/app/controllers/AuditoriaController.php';
require ROOT . '/app/controllers/FichaEstudianteController.php';
require ROOT . '/app/controllers/SedesController.php';
require ROOT . '/app/controllers/HabilitacionesController.php';

// ── Tenant (subdominio) ──────────────────────────────────────
// Detecta el subdominio y carga el colegio — aborta si no existe o no hay subdominio
Tenant::detectar();
Tenant::colegio(); // fuerza la resolución ahora; muestra no_tenant/tenant_404 si aplica

// ── Sesión segura ────────────────────────────────────────────
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => SESSION_SECURE,
    'httponly' => SESSION_HTTPONLY,
    'samesite' => SESSION_SAMESITE,
]);
session_start();

// Si el usuario tiene sesión de otro colegio, forzar logout
if (Auth::check() && (int)(Auth::user()['colegio_id'] ?? 1) !== Tenant::id()) {
    Auth::logout();
    redirect('/');
}

// Respaldo automático diario (se dispara en segundo plano con la primera visita autenticada del día)
if (Auth::check()) {
    Backup::programarAutomaticoSiFalta();
}

// ── Rutas ────────────────────────────────────────────────────
$router = new Router();

// Auth
$router->get ('/',                      [AuthController::class, 'showLogin']);
$router->post('/login',                 [AuthController::class, 'login']);
$router->get ('/logout',                [AuthController::class, 'logout']);
$router->get ('/recuperar-password',    [AuthController::class, 'showRecuperar']);
$router->post('/recuperar-password',    [AuthController::class, 'recuperar']);
$router->get ('/reset-password',        [AuthController::class, 'showReset']);
$router->post('/reset-password',        [AuthController::class, 'resetPassword']);
// Perfil de usuario
$router->get ('/perfil/avatar/{id}', [PerfilController::class, 'avatar']);
$router->get ('/perfil',             [PerfilController::class, 'ver']);
$router->get ('/perfil/editar',   [PerfilController::class, 'editarForm']);
$router->post('/perfil/guardar',  [PerfilController::class, 'guardar']);
$router->get ('/perfil/password',      [PerfilController::class, 'passwordForm']);
$router->post('/perfil/password',      [PerfilController::class, 'passwordGuardar']);
$router->post('/perfil/foto/eliminar', [PerfilController::class, 'eliminarFoto']);
$router->get ('/perfil/notificaciones',         [PerfilController::class, 'preferencias']);
$router->post('/perfil/notificaciones/guardar', [PerfilController::class, 'guardarPreferencias']);
$router->get ('/notificaciones/desuscribir',    [PerfilController::class, 'desuscribir']);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// Padre — Mis hijos
$router->get ('/mis-hijos',              [MisHijosController::class, 'index']);
$router->get ('/mis-hijos/{id}',         [MisHijosController::class, 'detalle']);
$router->get ('/mis-hijos/{id}/editar',  [MisHijosController::class, 'editar']);
$router->post('/mis-hijos/{id}/editar',  [MisHijosController::class, 'guardarEdicion']);
$router->get ('/mis-hijos/{id}/tarea/{tid}',          [MisHijosController::class, 'verTarea']);
$router->post('/mis-hijos/{id}/tarea/{tid}/marcar',  [MisHijosController::class, 'marcarUnaVista']);
$router->post('/mis-hijos/{id}/tareas/marcar-vistas',        [MisHijosController::class, 'marcarTareasVistas']);
$router->post('/mis-hijos/{id}/observaciones/marcar-vistas',   [MisHijosController::class, 'marcarObservacionesVistas']);
$router->post('/mis-hijos/{id}/observacion/{oid}/marcar',      [MisHijosController::class, 'marcarUnaObservacion']);
$router->post('/mis-hijos/{id}/nota/{mid}/marcar',             [MisHijosController::class, 'marcarNotaVista']);
$router->post('/mis-hijos/{id}/mensaje/{mid}/marcar',          [MisHijosController::class, 'marcarMensajeVisto']);
$router->post('/mis-hijos/{id}/mensaje/{mid}/responder',       [MisHijosController::class, 'responderMensajeHijo']);

// Comunicaciones
$router->get ('/comunicaciones',            [ComunicacionesController::class, 'bandeja']);
$router->get ('/comunicaciones/enviados',   [ComunicacionesController::class, 'enviados']);
$router->get ('/comunicaciones/redactar',   [ComunicacionesController::class, 'redactar']);
$router->post('/comunicaciones/enviar',     [ComunicacionesController::class, 'enviar']);
$router->post('/comunicaciones/accion-masiva', [ComunicacionesController::class, 'accionMasiva']);
$router->get ('/comunicaciones/adjunto/{id}', [ComunicacionesController::class, 'adjunto']);
$router->get ('/comunicaciones/{mensajeId}/destinatario/{usuarioId}', [ComunicacionesController::class, 'destinatarioInfo']);
$router->get ('/comunicaciones/{id}',         [ComunicacionesController::class, 'ver']);

// Admin — Configuración colegio
// Calendario / Agenda
$router->get('/calendario',       [CalendarioController::class, 'index']);
$router->get('/api/calendario',   [CalendarioController::class, 'eventos']);
$router->get ('/calendario/exportar',           [CalendarioController::class, 'exportar']);
$router->post('/calendario/regenerar-token',    [CalendarioController::class, 'regenerarToken']);
$router->get ('/calendario/suscripcion/{token}',[CalendarioController::class, 'suscripcion']);

// Admin — Configuración colegio
$router->get ('/admin/colegio', [ColegioController::class, 'index']);
$router->post('/admin/colegio', [ColegioController::class, 'guardar']);

// Admin — Eventos institucionales (calendario, multi-día)
$router->get ('/admin/eventos',                [EventosInstitucionalesController::class, 'index']);
$router->post('/admin/eventos/guardar',        [EventosInstitucionalesController::class, 'guardar']);
$router->post('/admin/eventos/{id}/eliminar',  [EventosInstitucionalesController::class, 'eliminar']);

// Soporte / Admin — Desempeños
$router->get ('/soporte/desempenios',        [DesempeniosController::class, 'index']);
$router->post('/soporte/desempenios',        [DesempeniosController::class, 'guardar']);
$router->get ('/soporte/funcionalidades',        [SoporteFuncionalidadesController::class, 'index']);
$router->post('/soporte/funcionalidades/guardar',[SoporteFuncionalidadesController::class, 'guardar']);

// Observador del estudiante (consolidado multi-año)
$router->get ('/observador',                  [ObservadorController::class, 'index']);
$router->get ('/observador/buscar',            [ObservadorController::class, 'buscar']);
$router->get ('/observador/{id}',              [ObservadorController::class, 'estudiante']);
$router->post('/observador/{id}/guardar',      [ObservadorController::class, 'guardarAnotacion']);
$router->get ('/observador/{id}/pdf',          [ObservadorController::class, 'exportarPdf']);

// Boletines de notas en PDF
$router->get ('/boletines',             [BoletinController::class, 'index']);
$router->get ('/boletines/buscar',      [BoletinController::class, 'buscar']);
$router->get ('/boletines/grupos',          [BoletinController::class, 'grupos']);
$router->get ('/boletines/grupo/{id}',      [BoletinController::class, 'fichaGrupo']);
$router->get ('/boletines/grupo/{id}/pdf',  [BoletinController::class, 'generarGrupo']);
$router->get ('/boletines/{id}',        [BoletinController::class, 'ficha']);
$router->get ('/boletines/{id}/pdf',    [BoletinController::class, 'generar']);

// Constancias y certificados en PDF
$router->get ('/constancias',               [ConstanciaController::class, 'index']);
$router->get ('/constancias/buscar',        [ConstanciaController::class, 'buscar']);
$router->get ('/constancias/{id}',          [ConstanciaController::class, 'ficha']);
$router->post('/constancias/{id}/generar',  [ConstanciaController::class, 'generar']);

// Planeador de clases
$router->get ('/planeador',                          [PlaneadorController::class, 'index']);
$router->get ('/planeador/asignacion/{id}/nueva',    [PlaneadorController::class, 'nueva']);
$router->get ('/planeador/asignacion/{id}',          [PlaneadorController::class, 'porAsignacion']);
$router->post('/planeador/guardar',                  [PlaneadorController::class, 'guardar']);
$router->get ('/planeador/{id}/editar',              [PlaneadorController::class, 'editar']);
$router->post('/planeador/{id}/actualizar',          [PlaneadorController::class, 'actualizar']);
$router->post('/planeador/{id}/eliminar',            [PlaneadorController::class, 'eliminar']);

// Circulares institucionales
$router->get ('/circulares',                   [CircularController::class, 'index']);
$router->get ('/circulares/nueva',             [CircularController::class, 'nueva']);
$router->post('/circulares/publicar',          [CircularController::class, 'publicar']);
$router->post('/circulares/{id}/marcar-leida', [CircularController::class, 'marcarLeida']);
$router->get ('/circulares/{id}/seguimiento',  [CircularController::class, 'seguimiento']);

// PQRS
$router->get ('/pqrs',                     [PqrsController::class, 'index']);
$router->get ('/pqrs/nueva',               [PqrsController::class, 'nueva']);
$router->post('/pqrs/guardar',             [PqrsController::class, 'guardar']);
$router->get ('/pqrs/{id}',                [PqrsController::class, 'ver']);
$router->post('/pqrs/{id}/responder',      [PqrsController::class, 'responder']);
$router->post('/pqrs/{id}/estado',         [PqrsController::class, 'cambiarEstado']);

// Encuestas
$router->get ('/encuestas',                        [EncuestaController::class, 'index']);
$router->get ('/encuestas/nueva',                  [EncuestaController::class, 'nueva']);
$router->post('/encuestas/guardar',                [EncuestaController::class, 'guardar']);
$router->get ('/encuestas/{id}/responder',         [EncuestaController::class, 'responder']);
$router->post('/encuestas/{id}/responder',         [EncuestaController::class, 'guardarRespuestas']);
$router->get ('/encuestas/{id}/resultados',        [EncuestaController::class, 'resultados']);
$router->post('/encuestas/{id}/cerrar',            [EncuestaController::class, 'cerrar']);

// Autoevaluación / coevaluación (padre en nombre del estudiante)
$router->get ('/autoevaluacion',                           [AutoevaluacionController::class, 'index']);
$router->post('/autoevaluacion/{id}/{eid}/guardar',        [AutoevaluacionController::class, 'guardar']);

// Reportes / estadísticas
$router->get ('/reportes', [ReportesController::class, 'index']);
$router->get ('/reportes/exportar', [ReportesController::class, 'exportarPdf']);
$router->get ('/reportes/exportar-excel', [ReportesController::class, 'exportarExcel']);

// Historial de cambios en notas
$router->get ('/historial-notas',          [HistorialNotasController::class, 'index']);
$router->get ('/historial-notas/buscar',   [HistorialNotasController::class, 'buscar']);
$router->get ('/historial-notas/{id}',     [HistorialNotasController::class, 'estudiante']);

// Citas padre-docente
$router->get ('/citas',                             [CitaController::class, 'index']);
$router->post('/citas/disponibilidad/crear',        [CitaController::class, 'crearDisponibilidad']);
$router->post('/citas/disponibilidad/{id}/eliminar',[CitaController::class, 'eliminarDisponibilidad']);
$router->get ('/citas/docente/{id}',                [CitaController::class, 'verDocente']);
$router->post('/citas/reservar',                    [CitaController::class, 'reservar']);
$router->post('/citas/reserva/{id}/cancelar',       [CitaController::class, 'cancelarReserva']);

// Autorizaciones y consentimientos digitales
$router->get ('/autorizaciones',                    [AutorizacionController::class, 'index']);
$router->get ('/autorizaciones/nueva',               [AutorizacionController::class, 'nueva']);
$router->post('/autorizaciones/guardar',             [AutorizacionController::class, 'guardar']);
$router->post('/autorizaciones/{id}/cerrar',         [AutorizacionController::class, 'cerrar']);
$router->get ('/autorizaciones/{id}/editar',         [AutorizacionController::class, 'editarForm']);
$router->post('/autorizaciones/{id}/actualizar',     [AutorizacionController::class, 'actualizar']);
$router->post('/autorizaciones/{id}/eliminar',       [AutorizacionController::class, 'eliminar']);
$router->get ('/autorizaciones/{id}/seguimiento',    [AutorizacionController::class, 'seguimiento']);
$router->get ('/autorizaciones/firma/{id}/foto',     [AutorizacionController::class, 'fotoFirma']);
$router->get ('/autorizaciones/firma/{id}/pdf',      [AutorizacionController::class, 'exportarFirmaPdf']);
$router->get ('/autorizaciones/firma/{id}',          [AutorizacionController::class, 'verFirma']);
$router->get ('/autorizaciones/firmar/{id}/{aid}',           [AutorizacionController::class, 'firmarForm']);
$router->post('/autorizaciones/firmar/{id}/{aid}/guardar',   [AutorizacionController::class, 'firmarGuardar']);

// Retiro anticipado de estudiantes
$router->get ('/retiro-anticipado',                      [RetiroController::class, 'index']);
$router->get ('/retiro-anticipado/nuevo',                [RetiroController::class, 'nuevo']);
$router->get ('/retiro-anticipado/buscar',               [RetiroController::class, 'buscar']);
$router->get ('/retiro-anticipado/registro/{id}/foto',   [RetiroController::class, 'foto']);
$router->get ('/retiro-anticipado/registro/{id}/pdf',    [RetiroController::class, 'exportarPdf']);
$router->get ('/retiro-anticipado/registro/{id}/anular', [RetiroController::class, 'anularForm']);
$router->post('/retiro-anticipado/registro/{id}/anular', [RetiroController::class, 'anular']);
$router->get ('/retiro-anticipado/registro/{id}',        [RetiroController::class, 'verRegistro']);
$router->get ('/retiro-anticipado/{id}',                 [RetiroController::class, 'ficha']);
$router->post('/retiro-anticipado/{id}/guardar',         [RetiroController::class, 'guardar']);

// Grados y Grupos / Promoción de año lectivo
$router->get ('/grados',                           [GradosController::class, 'index']);
$router->get ('/grados/promocion',                  [GradosController::class, 'promocionForm']);
$router->post('/grados/promocion/ejecutar',         [GradosController::class, 'promocionEjecutar']);
$router->get ('/grados/ajustar/{id}',               [GradosController::class, 'ajustarForm']);
$router->post('/grados/matricula/{id}/retirar',     [GradosController::class, 'retirarMatricula']);
$router->post('/grados/matricula/{id}/mover',       [GradosController::class, 'moverGrupo']);

// Catálogo de grados
$router->get ('/grados/catalogo',                   [GradosController::class, 'catalogoGrados']);
$router->post('/grados/catalogo/guardar',           [GradosController::class, 'guardarGrado']);
$router->post('/grados/catalogo/{id}/actualizar',   [GradosController::class, 'actualizarGrado']);
$router->post('/grados/catalogo/{id}/eliminar',     [GradosController::class, 'eliminarGrado']);

// Grupos nuevos / edición
$router->get ('/grados/nuevo-grupo',                [GradosController::class, 'nuevoGrupoForm']);
$router->post('/grados/grupo/crear',                [GradosController::class, 'crearGrupo']);
$router->get ('/grados/grupo/{id}/editar',          [GradosController::class, 'editarGrupoForm']);
$router->post('/grados/grupo/{id}/actualizar',      [GradosController::class, 'actualizarGrupo']);
$router->post('/grados/grupo/{id}/eliminar',        [GradosController::class, 'eliminarGrupo']);

// Materias
$router->get ('/materias',                     [MateriasController::class, 'index']);
$router->post('/materias/guardar',             [MateriasController::class, 'guardar']);
$router->post('/materias/{id}/actualizar',     [MateriasController::class, 'actualizar']);
$router->post('/materias/{id}/eliminar',       [MateriasController::class, 'eliminar']);

// Asignaciones docente-materia-grupo
$router->get ('/asignaciones',                 [AsignacionesController::class, 'index']);
$router->post('/asignaciones/guardar',         [AsignacionesController::class, 'guardar']);
$router->post('/asignaciones/{id}/actualizar', [AsignacionesController::class, 'actualizar']);
$router->post('/asignaciones/{id}/eliminar',   [AsignacionesController::class, 'eliminar']);

// Auditoría general
$router->get ('/auditoria', [AuditoriaController::class, 'index']);

// Ficha única del estudiante
$router->get ('/ficha-estudiante',             [FichaEstudianteController::class, 'index']);
$router->get ('/ficha-estudiante/buscar',      [FichaEstudianteController::class, 'buscar']);
$router->get ('/ficha-estudiante/{id}',        [FichaEstudianteController::class, 'ver']);

// Sedes (multi-sede)
$router->get ('/sedes',                [SedesController::class, 'index']);
$router->post('/sedes/guardar',        [SedesController::class, 'guardar']);
$router->post('/sedes/{id}/actualizar',[SedesController::class, 'actualizar']);
$router->post('/sedes/{id}/eliminar',  [SedesController::class, 'eliminar']);

// Habilitación anual
$router->get ('/habilitaciones',                [HabilitacionesController::class, 'index']);
$router->get ('/habilitaciones/{id}',            [HabilitacionesController::class, 'ver']);
$router->post('/habilitaciones/{id}/guardar',    [HabilitacionesController::class, 'guardar']);

// Años lectivos históricos
$router->post('/grados/ano/{id}/renombrar', [GradosController::class, 'renombrarAno']);
$router->post('/grados/ano/{id}/reabrir',   [GradosController::class, 'reabrirAno']);
$router->post('/grados/ano/{id}/archivar', [GradosController::class, 'archivarAno']);

// Estudiantes sin acudiente
$router->get ('/usuarios/sin-acudiente', [UsuariosController::class, 'sinAcudiente']);
$router->post('/usuarios/{id}/cerrar-sesiones', [UsuariosController::class, 'cerrarSesiones']);

// Papelera de notas (recuperación tras eliminación)
$router->get ('/papelera',                   [PapeleraController::class, 'index']);
$router->get ('/papelera/{id}',               [PapeleraController::class, 'ver']);
$router->post('/papelera/{id}/restaurar',     [PapeleraController::class, 'restaurar']);

// Papelera general (usuarios, planeaciones, citas, archivos)
$router->get ('/papelera-general',                [PapeleraGeneralController::class, 'index']);
$router->get ('/papelera-general/{id}',           [PapeleraGeneralController::class, 'ver']);
$router->post('/papelera-general/{id}/restaurar', [PapeleraGeneralController::class, 'restaurar']);

// Respaldos de base de datos (solo soporte)
$router->get ('/soporte/backups',              [BackupController::class, 'index']);
$router->post('/soporte/backups/generar',      [BackupController::class, 'generar']);
$router->get ('/soporte/backups/{nombre}',     [BackupController::class, 'descargar']);

// Registro de accesos
$router->get ('/accesos', [AccesosController::class, 'index']);

// Docente — Grupos
$router->get ('/mis-grupos',  [DocenteController::class, 'misGrupos']);
$router->get ('/grupos/{id}', [DocenteController::class, 'grupo']);

// Docente — Tabs de grupo
$router->get ('/grupos/{id}/estudiantes',           [DocenteController::class, 'estudiantes']);
$router->get ('/grupos/{id}/estudiante/{uid}/notas',[DocenteController::class, 'notasEstudiante']);
$router->post('/api/nota',                         [DocenteController::class, 'guardarNotaApi']);
$router->get ('/grupos/{id}/notas-grupo',            [DocenteController::class, 'notasGrupo']);
$router->get ('/grupos/{id}/evaluaciones',          [DocenteController::class, 'evaluaciones']);

// Docente — Carga de notas por Excel
$router->get ('/grupos/{id}/carga-notas',             [CargaNotasController::class, 'formulario']);
$router->post('/grupos/{id}/carga-notas/subir',       [CargaNotasController::class, 'subir']);
$router->post('/grupos/{id}/carga-notas/emparejar',   [CargaNotasController::class, 'emparejar']);
$router->post('/grupos/{id}/carga-notas/confirmar',   [CargaNotasController::class, 'confirmar']);
$router->get ('/grupos/{id}/carga-notas/plantilla',   [CargaNotasController::class, 'plantilla']);
$router->get ('/grupos/{id}/asistencia',            [DocenteController::class, 'asistencia']);
$router->post('/grupos/{id}/asistencia/guardar',    [DocenteController::class, 'guardarAsistencia']);
$router->get ('/grupos/{id}/observaciones',              [DocenteController::class, 'observaciones']);
$router->post('/grupos/{id}/observaciones/guardar',      [DocenteController::class, 'guardarObservacion']);
$router->post('/grupos/{id}/observaciones/{oid}/editar', [DocenteController::class, 'editarObservacion']);
$router->post('/grupos/{id}/observaciones/{oid}/borrar', [DocenteController::class, 'borrarObservacion']);
$router->get ('/grupos/{id}/consolidado-indicadores', [DocenteController::class, 'consolidadoIndicadores']);
$router->get ('/grupos/{id}/consolidado-periodo',    [DocenteController::class, 'consolidadoPeriodo']);

// Docente — Tareas
$router->get ('/grupos/{id}/tareas',         [DocenteController::class, 'tareas']);
$router->get ('/grupos/{id}/tareas/nueva',   [DocenteController::class, 'nuevaTareaForm']);
$router->post('/grupos/{id}/tareas/guardar', [DocenteController::class, 'guardarTarea']);
$router->get ('/tarea/{id}',               [DocenteController::class, 'verTarea']);
$router->get ('/tarea/{id}/editar',          [DocenteController::class, 'editarTareaForm']);
$router->post('/tarea/{id}/editar',          [DocenteController::class, 'editarTareaGuardar']);
$router->post('/tarea/{id}/eliminar',        [DocenteController::class, 'eliminarTarea']);
$router->get ('/tarea/adjunto/{id}',         [DocenteController::class, 'tareaAdjunto']);

// Docente — Tipos de evaluación
$router->post('/tipo-eval/crear',              [DocenteController::class, 'crearTipoEval']);
$router->post('/tipo-eval/{id}/eliminar',      [DocenteController::class, 'eliminarTipoEval']);

// Docente — Indicadores
$router->post('/grupos/{id}/indicador/crear',       [DocenteController::class, 'crearIndicador']);
$router->get ('/indicador/{id}/editar',             [DocenteController::class, 'editarIndicadorForm']);
$router->post('/indicador/{id}/editar',             [DocenteController::class, 'editarIndicadorGuardar']);
$router->post('/indicador/{id}/eliminar',           [DocenteController::class, 'eliminarIndicador']);
$router->get ('/indicador/{id}/calificar',          [DocenteController::class, 'calificarIndicadorForm']);
$router->post('/indicador/{id}/calificar',          [DocenteController::class, 'calificarIndicadorGuardar']);

// Docente — Evaluaciones
$router->post('/evaluacion/crear',              [DocenteController::class, 'crearEvaluacion']);
$router->get ('/evaluacion/{id}/calificar',     [DocenteController::class, 'calificarForm']);
$router->post('/evaluacion/{id}/calificar',     [DocenteController::class, 'calificarGuardar']);
$router->post('/evaluacion/{id}/eliminar',      [DocenteController::class, 'eliminarEvaluacion']);

// Usuarios
$router->get ('/usuarios',                       [UsuariosController::class, 'index']);
$router->get ('/usuarios/nuevo',                 [UsuariosController::class, 'nuevo']);
$router->post('/usuarios/guardar',               [UsuariosController::class, 'guardar']);
$router->get ('/usuarios/plantilla',             [UsuariosController::class, 'plantilla']);
$router->get ('/usuarios/carga-masiva',          [UsuariosController::class, 'cargaMasiva']);
$router->post('/usuarios/procesar-carga',        [UsuariosController::class, 'procesarCarga']);
$router->get ('/usuarios/{id}/editar',           [UsuariosController::class, 'editar']);
$router->post('/usuarios/{id}/actualizar',       [UsuariosController::class, 'actualizar']);
$router->post('/usuarios/{id}/toggle-activo',    [UsuariosController::class, 'toggleActivo']);
$router->post('/usuarios/{id}/toggle-principal', [UsuariosController::class, 'togglePrincipal']);
$router->post('/usuarios/{id}/eliminar',         [UsuariosController::class, 'eliminar']);
$router->get ('/usuarios/{id}/hijos/buscar',          [UsuariosController::class, 'buscarHijo']);
$router->post('/usuarios/{id}/hijos/agregar',         [UsuariosController::class, 'agregarHijo']);
$router->post('/usuarios/{id}/hijos/{aid}/quitar',    [UsuariosController::class, 'quitarHijo']);

// Comunidad
$router->get ('/comunidad',                                [ComunidadController::class, 'index']);
$router->get ('/comunidad/admin',                          [ComunidadController::class, 'admin']);
$router->post('/comunidad/categoria/crear',                [ComunidadController::class, 'crearCategoria']);
$router->post('/comunidad/categoria/{id}/editar',          [ComunidadController::class, 'editarCategoria']);
$router->post('/comunidad/categoria/{id}/eliminar',        [ComunidadController::class, 'eliminarCategoria']);
$router->post('/comunidad/archivo/subir',                  [ComunidadController::class, 'subirArchivo']);
$router->post('/comunidad/archivo/{id}/eliminar',          [ComunidadController::class, 'eliminarArchivo']);
$router->get ('/comunidad/archivo/{id}',                   [ComunidadController::class, 'descargar']);

// Uploads
$router->post('/upload/imagen',      [UploadController::class, 'imagen']);
$router->get ('/api/menciones',      [UploadController::class, 'menciones']);

// Períodos
$router->get ('/periodos',                        [PeriodosController::class, 'index']);
$router->get ('/periodos/nuevo',                  [PeriodosController::class, 'nuevo']);
$router->post('/periodos/guardar',                [PeriodosController::class, 'guardar']);
$router->get ('/periodos/{id}/editar',            [PeriodosController::class, 'editar']);
$router->post('/periodos/{id}/actualizar',        [PeriodosController::class, 'actualizar']);
$router->post('/periodos/{id}/eliminar',          [PeriodosController::class, 'eliminar']);
$router->post('/periodos/{id}/toggle-notas',      [PeriodosController::class, 'toggleNotas']);
$router->post('/periodos/{id}/toggle-activo',     [PeriodosController::class, 'toggleActivo']);

$router->dispatch();
