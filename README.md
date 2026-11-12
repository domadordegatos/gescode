# Gescode

Plataforma de gestión académica multi-tenant para instituciones educativas (tipo Phidias/SIGMA), construida en PHP plano sin framework.

## Módulos principales

- **Académico**: grados, grupos, asignaciones, indicadores de evaluación, calificaciones, boletines en PDF, histórico de notas, habilitación anual.
- **Asistencia**: control diario por grupo con estados (presente, ausente, tarde, excusa, incapacidad médica).
- **Observador del estudiante**: seguimiento académico/convivencial por estudiante.
- **Comunicación**: mensajería interna con hilos de conversación, circulares, PQRS, encuestas.
- **Autorizaciones y retiros**: firma digital de autorizaciones, control de retiros anticipados.
- **Citas**: agendamiento docente-acudiente.
- **Planeador de clases** y repositorio de recursos (Comunidad).
- **Administración**: usuarios y roles (admin, soporte, secretaría, docente, padre, estudiante), sedes, años lectivos, auditoría de acciones, reportes exportables (PDF/Excel), calendario sincronizable (ICS).

## Stack

- PHP 8.3 (sin framework, router propio)
- MySQL 8.4
- Composer: `dompdf/dompdf`, `phpoffice/phpspreadsheet`
- Multi-tenant por subdominio (un `colegio_id` por institución)

## Instalación local

Ver [INSTALACION.md](INSTALACION.md).
