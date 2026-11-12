-- ============================================================
-- MIGRACIÓN: Módulo Comunidad — categorías y archivos
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS comunidad_categorias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  orden       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  creado_por  INT UNSIGNED NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comunidad_archivos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  titulo       VARCHAR(200) NOT NULL,
  descripcion  VARCHAR(255) DEFAULT NULL,
  archivo_disk VARCHAR(255) NOT NULL,   -- nombre en disco (UUID)
  archivo_orig VARCHAR(255) NOT NULL,   -- nombre original del archivo
  mime         VARCHAR(100) NOT NULL,
  tamano       INT UNSIGNED NOT NULL,   -- bytes
  subido_por   INT UNSIGNED NOT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES comunidad_categorias(id) ON DELETE CASCADE,
  FOREIGN KEY (subido_por)   REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migración comunidad completada' AS resultado;
