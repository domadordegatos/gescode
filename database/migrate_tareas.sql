-- ============================================================
-- MIGRACIÓN: Módulo Tareas
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tareas (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asignacion_id     INT UNSIGNED NOT NULL,
  periodo_id        SMALLINT UNSIGNED NOT NULL,
  nombre            VARCHAR(200) NOT NULL,
  descripcion       MEDIUMTEXT   DEFAULT NULL,          -- HTML del editor
  tipo_entrega      ENUM('ninguna','clase','linea') NOT NULL DEFAULT 'ninguna',
  fecha_publicacion DATETIME     NOT NULL,
  fecha_limite      DATETIME     DEFAULT NULL,          -- solo si tipo != ninguna
  evaluacion_id     INT UNSIGNED DEFAULT NULL,          -- evaluación asociada (opcional)
  foro_habilitado   TINYINT(1)   NOT NULL DEFAULT 0,
  mensaje_id        INT UNSIGNED DEFAULT NULL,          -- notificación enviada
  creado_por        INT UNSIGNED NOT NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (asignacion_id) REFERENCES asignaciones(id) ON DELETE CASCADE,
  FOREIGN KEY (periodo_id)    REFERENCES periodos(id),
  FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones(id) ON DELETE SET NULL,
  FOREIGN KEY (creado_por)    REFERENCES usuarios(id),
  INDEX idx_asig_periodo (asignacion_id, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tareas_adjuntos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tarea_id    INT UNSIGNED NOT NULL,
  nombre_orig VARCHAR(255) NOT NULL,
  nombre_disk VARCHAR(255) NOT NULL,
  mime        VARCHAR(100) NOT NULL,
  tamano      INT UNSIGNED NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migración tareas completada' AS resultado;
