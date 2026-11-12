<?php
class SoporteFuncionalidadesController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function index(): void {
        Auth::requireRole('soporte');
        $colegio = $this->db->query('SELECT * FROM colegios WHERE id = ' . Tenant::id())->fetch();
        render('soporte/funcionalidades', compact('colegio'));
    }

    public function guardar(): void {
        Auth::requireRole('soporte');
        csrfVerify();

        $notiEmail   = isset($_POST['notificaciones_email'])          ? 1 : 0;
        $cuentasEst  = isset($_POST['permite_cuentas_estudiantes'])   ? 1 : 0;
        $msjOcultos  = isset($_POST['permite_mensajes_ocultos'])      ? 1 : 0;
        $observador  = isset($_POST['permite_observador_consolidado'])? 1 : 0;
        $planeador   = isset($_POST['permite_planeador_clases'])      ? 1 : 0;
        $matricula   = isset($_POST['permite_matricula_online'])      ? 1 : 0;
        $boletines   = isset($_POST['permite_boletines_pdf'])         ? 1 : 0;
        $constancias = isset($_POST['permite_constancias'])           ? 1 : 0;
        $circulares  = isset($_POST['permite_circulares'])            ? 1 : 0;
        $pqrs        = isset($_POST['permite_pqrs'])                  ? 1 : 0;
        $encuestas   = isset($_POST['permite_encuestas'])             ? 1 : 0;
        $autoeval    = isset($_POST['permite_autoevaluacion'])        ? 1 : 0;
        $citas       = isset($_POST['permite_citas'])                 ? 1 : 0;
        $autorizac   = isset($_POST['permite_autorizaciones'])        ? 1 : 0;
        $retiro      = isset($_POST['permite_retiro_anticipado'])     ? 1 : 0;

        $this->db->prepare(
            'UPDATE colegios SET notificaciones_email=?, permite_cuentas_estudiantes=?, permite_mensajes_ocultos=?,
                                  permite_observador_consolidado=?, permite_planeador_clases=?, permite_matricula_online=?,
                                  permite_boletines_pdf=?, permite_constancias=?, permite_circulares=?, permite_pqrs=?, permite_encuestas=?,
                                  permite_autoevaluacion=?, permite_citas=?, permite_autorizaciones=?, permite_retiro_anticipado=?
             WHERE id=?'
        )->execute([$notiEmail, $cuentasEst, $msjOcultos, $observador, $planeador, $matricula, $boletines, $constancias, $circulares, $pqrs, $encuestas, $autoeval, $citas, $autorizac, $retiro, Tenant::id()]);

        Auditoria::registrar('funcionalidades.actualizar', 'Actualizó los flags de funcionalidades habilitadas para el colegio.');
        flashSuccess('Funcionalidades actualizadas correctamente.');
        redirect('/soporte/funcionalidades');
    }
}
