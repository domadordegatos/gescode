-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: plataforma_academica
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acudientes`
--

DROP TABLE IF EXISTS `acudientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acudientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `estudiante_id` int unsigned NOT NULL,
  `parentesco` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  `familia_id` int unsigned DEFAULT NULL,
  `es_responsable_pago` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `estudiante_id` (`estudiante_id`),
  CONSTRAINT `acudientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acudientes_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acudientes`
--

LOCK TABLES `acudientes` WRITE;
/*!40000 ALTER TABLE `acudientes` DISABLE KEYS */;
INSERT INTO `acudientes` VALUES (1,3,1,'Padre',1,1,1),(2,3,2,'Padre',1,1,1),(3,4,1,'Madre',0,1,0),(4,4,2,'Madre',0,1,0);
/*!40000 ALTER TABLE `acudientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anos_lectivos`
--

DROP TABLE IF EXISTS `anos_lectivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anos_lectivos` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `colegio_id` smallint unsigned NOT NULL DEFAULT '1',
  `nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `estado` enum('activo','cerrado','archivado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anos_lectivos`
--

LOCK TABLES `anos_lectivos` WRITE;
/*!40000 ALTER TABLE `anos_lectivos` DISABLE KEYS */;
INSERT INTO `anos_lectivos` VALUES (1,1,'2026',1,'activo');
/*!40000 ALTER TABLE `anos_lectivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones`
--

DROP TABLE IF EXISTS `asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `grupo_id` smallint unsigned NOT NULL,
  `materia_id` smallint unsigned NOT NULL,
  `docente_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asignacion` (`grupo_id`,`materia_id`),
  KEY `materia_id` (`materia_id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`),
  CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`),
  CONSTRAINT `asignaciones_ibfk_3` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones`
--

LOCK TABLES `asignaciones` WRITE;
/*!40000 ALTER TABLE `asignaciones` DISABLE KEYS */;
INSERT INTO `asignaciones` VALUES (29,11,1,2),(30,1,1,2),(31,2,1,2),(32,3,1,2),(33,4,1,2),(34,1,8,2),(35,2,8,2),(36,3,8,2),(37,4,8,2),(38,5,8,2),(39,6,8,2),(40,7,8,2),(41,8,8,2);
/*!40000 ALTER TABLE `asignaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencia`
--

DROP TABLE IF EXISTS `asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencia` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `matricula_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','ausente','tarde','excusa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presente',
  `observacion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia` (`matricula_id`,`fecha`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencia_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencia`
--

LOCK TABLES `asistencia` WRITE;
/*!40000 ALTER TABLE `asistencia` DISABLE KEYS */;
INSERT INTO `asistencia` VALUES (1,1,'2026-05-26','presente',NULL,2,'2026-05-30 08:41:24'),(2,2,'2026-05-26','presente',NULL,2,'2026-05-30 08:41:24'),(3,1,'2026-05-27','presente',NULL,2,'2026-05-30 08:41:24'),(4,2,'2026-05-27','ausente',NULL,2,'2026-05-30 08:41:24'),(5,1,'2026-05-28','tarde',NULL,2,'2026-05-30 08:41:24'),(6,2,'2026-05-28','presente',NULL,2,'2026-05-30 08:41:24'),(7,1,'2026-05-29','presente',NULL,2,'2026-05-30 08:41:24'),(8,2,'2026-05-29','presente',NULL,2,'2026-05-30 08:41:24');
/*!40000 ALTER TABLE `asistencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calificaciones`
--

DROP TABLE IF EXISTS `calificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `evaluacion_id` int unsigned NOT NULL,
  `matricula_id` int unsigned NOT NULL,
  `valor` decimal(4,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cal` (`evaluacion_id`,`matricula_id`),
  KEY `matricula_id` (`matricula_id`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`evaluacion_id`) REFERENCES `evaluaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones`
--

LOCK TABLES `calificaciones` WRITE;
/*!40000 ALTER TABLE `calificaciones` DISABLE KEYS */;
INSERT INTO `calificaciones` VALUES (1,1,1,8.50,'2026-05-30 09:03:02','2026-05-31 10:36:53'),(2,1,2,7.80,'2026-05-30 09:03:02','2026-05-31 10:36:53'),(3,2,1,9.20,'2026-05-30 09:03:02','2026-05-31 10:36:53'),(4,2,2,8.10,'2026-05-30 09:03:02','2026-05-31 10:36:53'),(5,4,1,7.50,'2026-05-30 09:03:02','2026-05-30 09:03:02'),(6,4,2,6.90,'2026-05-30 09:03:02','2026-05-30 09:03:02'),(7,3,1,8.00,'2026-05-30 18:49:02','2026-05-31 10:36:53'),(8,3,2,8.50,'2026-05-30 18:49:02','2026-05-31 10:36:53'),(9,5,1,7.50,'2026-05-30 18:49:02','2026-05-30 18:49:02'),(10,5,2,9.00,'2026-05-30 18:49:02','2026-05-30 18:49:02'),(11,6,1,8.00,'2026-05-30 18:49:02','2026-05-30 18:49:02'),(12,6,2,8.50,'2026-05-30 18:49:02','2026-05-30 18:49:02'),(146,116,54,6.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(147,117,54,6.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(148,118,54,7.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(149,119,54,9.40,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(158,92,42,9.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(159,93,42,8.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(160,94,42,7.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(161,95,42,8.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(162,112,51,8.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(163,113,51,8.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(164,114,51,6.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(165,115,51,8.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(166,96,33,6.70,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(167,97,33,9.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(168,98,33,9.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(169,99,33,6.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(170,76,45,7.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(171,77,45,6.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(172,78,45,7.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(173,79,45,8.40,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(174,124,60,5.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(175,125,60,6.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(176,126,60,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(177,127,60,9.70,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(178,108,48,7.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(179,109,48,8.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(180,110,48,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(181,111,48,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(182,96,34,7.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(183,97,34,9.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(184,98,34,9.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(185,99,34,7.70,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(194,92,43,6.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(195,93,43,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(196,94,43,6.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(197,95,43,7.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(198,124,61,9.70,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(199,125,61,7.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(200,126,61,8.40,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(201,127,61,6.70,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(202,104,39,6.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(203,105,39,9.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(204,106,39,9.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(205,107,39,7.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(206,120,57,6.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(207,121,57,9.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(208,122,57,9.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(209,123,57,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(210,112,52,6.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(211,113,52,8.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(212,114,52,6.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(213,115,52,7.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(214,76,46,8.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(215,77,46,9.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(216,78,46,7.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(217,79,46,9.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(218,116,55,7.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(219,117,55,7.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(220,118,55,8.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(221,119,55,6.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(222,100,36,6.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(223,101,36,7.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(224,102,36,6.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(225,103,36,9.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(226,108,49,6.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(227,109,49,6.40,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(228,110,49,9.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(229,111,49,6.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(230,104,40,8.90,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(231,105,40,7.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(232,106,40,6.00,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(233,107,40,8.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(234,100,37,8.20,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(235,101,37,8.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(236,102,37,6.80,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(237,103,37,7.30,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(238,120,58,6.10,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(239,121,58,6.60,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(240,122,58,8.50,'2026-05-30 19:36:31','2026-05-30 19:36:31'),(241,123,58,8.80,'2026-05-30 19:36:31','2026-05-30 19:36:31');
/*!40000 ALTER TABLE `calificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colegios`
--

DROP TABLE IF EXISTS `colegios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colegios` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_corto` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdominio` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nit` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_primario` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0E0EB3',
  `color_secundario` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#1a1a6e',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `notificaciones_email` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nota_min` decimal(4,1) NOT NULL DEFAULT '1.0',
  `nota_max` decimal(4,1) NOT NULL DEFAULT '10.0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdominio` (`subdominio`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colegios`
--

LOCK TABLES `colegios` WRITE;
/*!40000 ALTER TABLE `colegios` DISABLE KEYS */;
INSERT INTO `colegios` VALUES (1,'Colegio San Mateo Apóstol','San Mateo','csma',NULL,'Ciudad',NULL,NULL,NULL,'#0E0EB3','#1a1a6e',1,0,'2026-05-30 09:44:38',1.0,10.0);
/*!40000 ALTER TABLE `colegios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comunidad_archivos`
--

DROP TABLE IF EXISTS `comunidad_archivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidad_archivos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int unsigned NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_orig` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano` int unsigned NOT NULL,
  `subido_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `subido_por` (`subido_por`),
  CONSTRAINT `comunidad_archivos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `comunidad_categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comunidad_archivos_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comunidad_archivos`
--

LOCK TABLES `comunidad_archivos` WRITE;
/*!40000 ALTER TABLE `comunidad_archivos` DISABLE KEYS */;
/*!40000 ALTER TABLE `comunidad_archivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comunidad_categorias`
--

DROP TABLE IF EXISTS `comunidad_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comunidad_categorias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `comunidad_categorias_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comunidad_categorias`
--

LOCK TABLES `comunidad_categorias` WRITE;
/*!40000 ALTER TABLE `comunidad_categorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `comunidad_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `desempenios`
--

DROP TABLE IF EXISTS `desempenios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `desempenios` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rango_min` decimal(4,1) NOT NULL,
  `rango_max` decimal(4,1) NOT NULL,
  `es_perdido` tinyint(1) NOT NULL DEFAULT '0',
  `orden` tinyint unsigned NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `desempenios`
--

LOCK TABLES `desempenios` WRITE;
/*!40000 ALTER TABLE `desempenios` DISABLE KEYS */;
INSERT INTO `desempenios` VALUES (1,'Desempeño Inferior','DI',0.0,6.9,1,1,1),(2,'Desempeño Básico','DB',7.0,7.9,0,2,1),(3,'Desempeño Alto','DA',8.0,8.9,0,3,1),(4,'Desempeño Superior','DS',9.0,10.0,0,4,1);
/*!40000 ALTER TABLE `desempenios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` enum('TI','CC','CE','PP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TI',
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F','O') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lugar_nacimiento` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estrato` tinyint unsigned DEFAULT NULL,
  `eps` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barrio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_emergencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel_emergencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estudiantes`
--

LOCK TABLES `estudiantes` WRITE;
/*!40000 ALTER TABLE `estudiantes` DISABLE KEYS */;
INSERT INTO `estudiantes` VALUES (1,5,'1098765432','TI','2012-03-15','F','2026-05-30 08:41:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,6,'1098765433','TI','2013-07-22','M','2026-05-30 08:41:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,27,'1010001001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,28,'1010001002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,29,'1010002001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(26,30,'1010002002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,31,'1010003001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,32,'1010003002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,33,'1010004001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(30,34,'1010004002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(31,35,'1010005001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,36,'1010005002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,37,'1010006001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(34,38,'1010006002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(35,39,'1010007001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(36,40,'1010007002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(37,41,'1010008001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(38,42,'1010008002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(39,43,'1010009001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(40,44,'1010009002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(41,45,'1010010001','TI',NULL,'M','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(42,46,'1010010002','TI',NULL,'F','2026-05-30 19:36:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `estudiantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluaciones`
--

DROP TABLE IF EXISTS `evaluaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `indicador_id` int unsigned NOT NULL,
  `nombre` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT '100.00',
  `privado` tinyint(1) NOT NULL DEFAULT '0',
  `cantidad` tinyint unsigned NOT NULL DEFAULT '1',
  `recuperacion_de` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `indicador_id` (`indicador_id`),
  KEY `fk_eval_recuperacion` (`recuperacion_de`),
  CONSTRAINT `evaluaciones_ibfk_1` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores_eval` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eval_recuperacion` FOREIGN KEY (`recuperacion_de`) REFERENCES `indicadores_eval` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluaciones`
--

LOCK TABLES `evaluaciones` WRITE;
/*!40000 ALTER TABLE `evaluaciones` DISABLE KEYS */;
INSERT INTO `evaluaciones` VALUES (1,1,'Taller semana 2: uso de estructuras secuenciales para obtener porcentajes de diferentes tipos, nota en cuaderno','2025-04-27',33.33,0,1,NULL,'2026-05-30 09:03:02'),(2,1,'Taller semana 4: desarrollo de ejercicios de condicionales en el cuaderno, explicación y retroalimentación en cuaderno','2025-05-11',33.33,0,1,NULL,'2026-05-30 09:03:02'),(3,1,'Taller semana 6: Desarrollo de 2 ejercicios con estructuras condicionales anidadas o uso de operadores lógicos, nota y retroalimentaciones en cuaderno','2025-05-25',33.34,0,1,NULL,'2026-05-30 09:03:02'),(4,2,'Quiz: tipos de datos primitivos y su uso en Python','2025-04-30',50.00,0,1,NULL,'2026-05-30 09:03:02'),(5,2,'Taller práctico: declaración y uso de variables en algoritmos','2025-05-14',50.00,0,1,NULL,'2026-05-30 09:03:02'),(6,6,'Entrega documento final','2025-03-28',40.00,0,1,NULL,'2026-05-30 18:49:02'),(8,2,'Evaluaci??n L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:35:01'),(9,3,'Evaluaci??n L3','2025-07-27',100.00,0,1,NULL,'2026-05-30 19:35:01'),(10,4,'Evaluaci??n L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:35:01'),(11,5,'Evaluaci??n L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:35:01'),(12,6,'Evaluaci??n L3','2025-05-28',100.00,0,1,NULL,'2026-05-30 19:35:01'),(71,2,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(72,3,'Evaluación L3','2025-07-27',100.00,0,1,NULL,'2026-05-30 19:36:31'),(73,4,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(74,5,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(75,6,'Evaluación L3','2025-05-28',100.00,0,1,NULL,'2026-05-30 19:36:31'),(76,71,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(77,72,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(78,73,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(79,74,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(84,63,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(85,64,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(86,65,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(87,66,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(92,67,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(93,68,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(94,69,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(95,70,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(96,51,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(97,52,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(98,53,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(99,54,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(100,55,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(101,56,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(102,57,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(103,58,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(104,59,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(105,60,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(106,61,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(107,62,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(108,75,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(109,76,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(110,77,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(111,78,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(112,79,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(113,80,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(114,81,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(115,82,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(116,83,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(117,84,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(118,85,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(119,86,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(120,87,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(121,88,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(122,89,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(123,90,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31'),(124,91,'Evaluación L1','2025-04-30',100.00,0,1,NULL,'2026-05-30 19:36:31'),(125,92,'Evaluación L2','2025-05-14',100.00,0,1,NULL,'2026-05-30 19:36:31'),(126,93,'Evaluación L1','2025-06-29',100.00,0,1,NULL,'2026-05-30 19:36:31'),(127,94,'Evaluación L2','2025-07-13',100.00,0,1,NULL,'2026-05-30 19:36:31');
/*!40000 ALTER TABLE `evaluaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `familias`
--

DROP TABLE IF EXISTS `familias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `familias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `familias`
--

LOCK TABLES `familias` WRITE;
/*!40000 ALTER TABLE `familias` DISABLE KEYS */;
INSERT INTO `familias` VALUES (1,'García Martínez','Familia piloto de prueba','2026-05-30 08:41:24');
/*!40000 ALTER TABLE `familias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fechas_importantes`
--

DROP TABLE IF EXISTS `fechas_importantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fechas_importantes` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `periodo_id` smallint unsigned NOT NULL,
  `tipo` enum('notas','observaciones','planeaciones','recuperaciones','habilitaciones') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fi` (`periodo_id`,`tipo`),
  CONSTRAINT `fechas_importantes_ibfk_1` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fechas_importantes`
--

LOCK TABLES `fechas_importantes` WRITE;
/*!40000 ALTER TABLE `fechas_importantes` DISABLE KEYS */;
INSERT INTO `fechas_importantes` VALUES (1,2,'notas','2026-04-13','2026-07-28'),(2,2,'observaciones','2026-04-09','2026-11-28'),(3,2,'planeaciones','2026-04-09','2026-11-28'),(4,2,'habilitaciones','2026-04-09','2026-11-28');
/*!40000 ALTER TABLE `fechas_importantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grados`
--

DROP TABLE IF EXISTS `grados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grados` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` tinyint unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grados`
--

LOCK TABLES `grados` WRITE;
/*!40000 ALTER TABLE `grados` DISABLE KEYS */;
INSERT INTO `grados` VALUES (1,'Primero',1,1),(2,'Segundo',2,1),(3,'Tercero',3,1),(4,'Cuarto',4,1),(5,'Quinto',5,1),(6,'Sexto',6,1),(7,'Séptimo',7,1),(8,'Octavo',8,1),(9,'Noveno',9,1),(10,'Décimo',10,1),(11,'Undécimo',11,1);
/*!40000 ALTER TABLE `grados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos`
--

DROP TABLE IF EXISTS `grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `grado_id` smallint unsigned NOT NULL,
  `ano_lectivo_id` smallint unsigned NOT NULL,
  `nombre` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `director_id` int unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `observacion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grado_id` (`grado_id`),
  KEY `ano_lectivo_id` (`ano_lectivo_id`),
  KEY `director_id` (`director_id`),
  CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`),
  CONSTRAINT `grupos_ibfk_2` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `anos_lectivos` (`id`),
  CONSTRAINT `grupos_ibfk_3` FOREIGN KEY (`director_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos`
--

LOCK TABLES `grupos` WRITE;
/*!40000 ALTER TABLE `grupos` DISABLE KEYS */;
INSERT INTO `grupos` VALUES (1,6,1,'A',NULL,1,NULL),(2,6,1,'B',NULL,1,NULL),(3,7,1,'A',NULL,1,NULL),(4,7,1,'B',NULL,1,NULL),(5,8,1,'A',NULL,1,NULL),(6,9,1,'A',NULL,1,NULL),(7,10,1,'A',NULL,1,NULL),(8,11,1,'A',NULL,1,NULL),(9,3,1,'A',NULL,1,NULL),(10,4,1,'A',NULL,1,NULL),(11,5,1,'A',NULL,1,NULL);
/*!40000 ALTER TABLE `grupos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indicadores`
--

DROP TABLE IF EXISTS `indicadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indicadores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo_eval_id` smallint unsigned NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT '100.00',
  PRIMARY KEY (`id`),
  KEY `tipo_eval_id` (`tipo_eval_id`),
  CONSTRAINT `indicadores_ibfk_1` FOREIGN KEY (`tipo_eval_id`) REFERENCES `tipos_evaluacion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indicadores`
--

LOCK TABLES `indicadores` WRITE;
/*!40000 ALTER TABLE `indicadores` DISABLE KEYS */;
INSERT INTO `indicadores` VALUES (1,1,'Demuestra respeto y colaboración con sus compañeros en el aula',50.00),(2,1,'Participa activamente en las actividades propuestas por el docente',50.00),(3,2,'Identifica los componentes básicos de un computador y su función',50.00),(4,2,'Comprende el concepto de algoritmo y lo aplica en la solución de problemas',50.00),(5,3,'Crea documentos de texto con formato básico usando procesador de texto',50.00),(6,3,'Elabora presentaciones digitales con información clara y organizada',50.00);
/*!40000 ALTER TABLE `indicadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indicadores_eval`
--

DROP TABLE IF EXISTS `indicadores_eval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indicadores_eval` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `asignacion_id` int unsigned NOT NULL,
  `periodo_id` smallint unsigned NOT NULL,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L1',
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_2` text COLLATE utf8mb4_unicode_ci,
  `peso` decimal(5,2) NOT NULL DEFAULT '1.00',
  `privado` tinyint(1) NOT NULL DEFAULT '0',
  `autoevaluacion` tinyint(1) NOT NULL DEFAULT '0',
  `redaccion_tipo` enum('prefijo','personalizado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prefijo',
  `desc_superior` text COLLATE utf8mb4_unicode_ci,
  `desc_alto` text COLLATE utf8mb4_unicode_ci,
  `desc_basico` text COLLATE utf8mb4_unicode_ci,
  `desc_inferior` text COLLATE utf8mb4_unicode_ci,
  `orden` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `asignacion_id` (`asignacion_id`),
  KEY `periodo_id` (`periodo_id`),
  CONSTRAINT `indicadores_eval_ibfk_1` FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `indicadores_eval_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indicadores_eval`
--

LOCK TABLES `indicadores_eval` WRITE;
/*!40000 ALTER TABLE `indicadores_eval` DISABLE KEYS */;
INSERT INTO `indicadores_eval` VALUES (1,30,2,'L1','Comprende y aplica el uso de estructuras secuenciales y repetitivas para la creación de programas útiles.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,1),(2,30,2,'L2','Identifica y utiliza correctamente los tipos de datos y variables en la construcción de algoritmos.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,2),(3,30,2,'L3','Desarrolla habilidades de pensamiento lógico mediante la resolución de problemas con pseudocódigo.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,3),(4,30,1,'L1','Identifica los componentes básicos de un computador y describe sus funciones principales.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,1),(5,30,1,'L2','Reconoce y aplica conceptos básicos de sistemas operativos en tareas cotidianas.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,2),(6,30,1,'L3','Crea documentos de texto con formato básico usando procesadores de texto.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,3),(67,31,1,'L1','Identifica componentes de hardware y software en un equipo de cómputo.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,1),(68,31,1,'L2','Aplica herramientas ofimáticas básicas para la creación de documentos digitales.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,2),(69,31,2,'L1','Navega de forma segura en internet reconociendo riesgos y buenas prácticas.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,1),(70,31,2,'L2','Crea presentaciones digitales con estructura lógica y diseño apropiado.',NULL,1.00,0,0,'prefijo',NULL,NULL,NULL,NULL,2);
/*!40000 ALTER TABLE `indicadores_eval` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materias`
--

DROP TABLE IF EXISTS `materias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materias` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materias`
--

LOCK TABLES `materias` WRITE;
/*!40000 ALTER TABLE `materias` DISABLE KEYS */;
INSERT INTO `materias` VALUES (1,'Informática','INF-6'),(2,'Matemáticas','MAT-6'),(3,'Español y Literatura','ESP-6'),(4,'Ciencias Naturales','CN-6'),(5,'Ciencias Sociales','CS-6'),(6,'Educación Física','EF-6'),(7,'Inglés','ING-6'),(8,'Tecnología',NULL);
/*!40000 ALTER TABLE `materias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriculas`
--

DROP TABLE IF EXISTS `matriculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriculas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `estudiante_id` int unsigned NOT NULL,
  `grupo_id` smallint unsigned NOT NULL,
  `estado` enum('activo','retirado','reprobado','promovido','pendiente','suspendido','egresado','inscrito') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_retiro` date DEFAULT NULL,
  `observacion` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matricula` (`estudiante_id`,`grupo_id`),
  KEY `grupo_id` (`grupo_id`),
  CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matriculas`
--

LOCK TABLES `matriculas` WRITE;
/*!40000 ALTER TABLE `matriculas` DISABLE KEYS */;
INSERT INTO `matriculas` VALUES (1,1,1,'activo',NULL,NULL),(2,2,1,'activo',NULL,NULL),(33,23,9,'activo',NULL,NULL),(34,24,9,'activo',NULL,NULL),(36,25,10,'activo',NULL,NULL),(37,26,10,'activo',NULL,NULL),(39,28,11,'activo',NULL,NULL),(40,27,11,'activo',NULL,NULL),(42,29,2,'activo',NULL,NULL),(43,30,2,'activo',NULL,NULL),(45,32,3,'activo',NULL,NULL),(46,31,3,'activo',NULL,NULL),(48,33,4,'activo',NULL,NULL),(49,34,4,'activo',NULL,NULL),(51,35,5,'activo',NULL,NULL),(52,36,5,'activo',NULL,NULL),(54,37,6,'activo',NULL,NULL),(55,38,6,'activo',NULL,NULL),(57,39,7,'activo',NULL,NULL),(58,40,7,'activo',NULL,NULL),(60,41,8,'activo',NULL,NULL),(61,42,8,'activo',NULL,NULL);
/*!40000 ALTER TABLE `matriculas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `remitente_id` int unsigned NOT NULL,
  `asunto` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuerpo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `remitente_id` (`remitente_id`),
  CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes`
--

LOCK TABLES `mensajes` WRITE;
/*!40000 ALTER TABLE `mensajes` DISABLE KEYS */;
INSERT INTO `mensajes` VALUES (1,2,'Nueva tarea: tarea 1pruebas (Informática)','<p>Se ha publicado una nueva tarea de <strong>Informática</strong>: <strong>tarea 1pruebas</strong>.</p><p>zscvnalvdinoadivoid</p><p><img src=\"data:image/jpeg;base64,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\"></p>','2026-06-09 08:32:45'),(2,2,'Nueva tarea: tarea 1pruebas (Informática)','<p>Se ha publicado una nueva tarea de <strong>Informática</strong>: <strong>tarea 1pruebas</strong>.</p><p>zscvnalvdinoadivoid</p><p><img src=\"data:image/jpeg;base64,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\"></p>','2026-06-09 08:32:45'),(3,3,'Re: Nueva tarea: tarea 1pruebas (Informática)','<p>idoaihdoihcsadidvij</p><p>dsvfsd</p><p>fds</p><p>v</p><p>s</p><div class=\"cita-historial\"><blockquote class=\"msg-quote\"><p class=\"msg-quote-head\"><em>El 09/06/2026 08:32, Juan Carlos Rodríguez escribió:</em></p><p>Se ha publicado una nueva tarea de <strong>Informática</strong>: <strong>tarea 1pruebas</strong>.</p><p>zscvnalvdinoadivoid</p><p><img src=\"data:image/jpeg;base64,UklGRmxJAABXRUJQVlA4IGBJAAAQEAOdASqABzgEPlEokUakoqGhIZmYOJAKCWlu+/v6n8Av5c+7LtKYV/r2fNx//7xEeGf/bHVtZI+XP+4///vbIMpod1gfZB6/cJ853+a9mGYj6a/qr1Lfb8zv4P/r/rn3YfKX/f/tl/mfgf/Gv6H/3P7F+///47mn+m/br34fxf/r/lp8DP4p/mP3W9/T/QfsB7yf/z0/3qofwD/R+wB50H/o9pT+w/+L2V9PjeM/ff/35z69u7P/X4G/vg728Vfyf9d4iJhf/88Yz/t67lcmX5N1/kn/49akCI0XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXi9kE8y8Xsgnt1PV2Dg9CWJN5VEVmbE8VnAT8Vt1LQOpuJxBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZHF4vYYOMJZ+o2fAtLg7+XU9QD2IsP7gOE+QPXevZD7fGxUkKiVf239QzMvaerrMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5fypZVXoFpc51H7Jc2XIN/1P25RjWq2AcYcV7/KKqf3Epf+DDB959F7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5l4xr5l12mYH/JMqrn0dsvF7CukTL2n7co4PhWADlP0b4P01al0UTpr/I1LgvhyZ5xmZe09XWZeL2QTzLxeyCeZeL2QTzL7OKzKuHLd9gso3l1+xeyCeZ14KoeZeMjNwHqqhQgYuiycQwLKWT+9NnWH1IKA7EqFdZl4vZBPMvF7IJ5l4vZBPMvF7IJ5l12pQWROML5+NOqp6gHX0GDmhdXW96ts1sQly2InLI03/u6Gj5pg6NzZQSo3hWv9uUYz1dZl4vZBPMvF7IJ5l4vZBR6qer19egyFXzcm9K6rYz1AUJ7dlpDyCe3U/fQuHCEopY0viTmQUogsOTMJBjj+IxrxeyCeZVvisy8XsgnmXi9kE8y8YbBBPMxdxYfD06FXITXs+7NQdfBGfmATzN7wMzTZZNP2zWDHDOaae1+XynBT8IJt6V/yJpclEAoC3t5l4vZBPMvF7IJ5l4vZBPMvF7DBxhIlvJDo0TUIN5bSDb28y8Xsg3UgfEM3SObsugDK14iRmzAN29gDBugalVdvTSAZ3FAKMZ6usy8XsgnmXi9kE8y8Xsgd8632fT5yULeJpXW20mUoLNBlEAoC3QbMSHhOEmOFjgvP7XtV6i3JW8Wn5ekoMIdkAIOwatb2oO9yzMy9qrcy9p6usy8XsgnmXkoVmbBEj8MSLmkOKdsvFwQ4/FfDE6MZ4HQoSHn/3uZyLS0J+oz8pLsVaXfKp/jX4XUDUzl00edCCeZeL2QTzLxAtobEBBLeZeL2QTzL0vBngdMMbWyHmXWt0iZe09XWX3iXDAe6NZ6iXZ6kfIzN9+DwB/Be7f1dOTV7XMaY8gnmXi9kE8y8XDmwmnQq5w2Q0UoJKb+mL7D6t7j1mcPzdJ22Zh1ro57T1dZl5Ewu6eiYK9uhc3fXGYPgFpuNr5BpcgJOpTq0nhAg9XWZeL2QTzKuV4qRNAhCDFP3fJOMKysstDtXUCa0WoNaOe2cwqRCyheL2FdCR+KzLxewroPxnBE2eI7vo2F49K4egzQxreDREmlBi/QNQkv/wcQ+mGNYIBRjPVspNtii4yKztD8cMk3V5dfiIA2R43nbO/Sxsn9tOaeSr+LDwiXhd/QkE8NiEtEdgE8y8PQPZCr/4gwW74uGUdRLZTc8bdUQ6QNF70Bu6oSMnZmi/mziOUNPG4zGa6XfTnj931Rmq3lNvUOCAgRWtRoDjcQm04gcfpuTjmyxn160Rewlp/UjqWuW8QTA+9HLPiCULodxEmEtrBBZENDdt9ltvgaU7yhyxInyTzEyZtOX2cVmXi32xdQb7XqHNAZphhgDMqcQ03JsONfeX3u4DCofoEDqs2GG0rst3vA38kuEVNFvUMXo4ZAbRMKUSoeSSDhtFXhowytuanaYu/DdxXMaisA2C/ucx/ATd1rVstImzOTftcbeefl0Y/ed6efAz08+m7eSErgNNIb2a3UJ+bYGwGO/xQ9Z3b6rxSFNeBocpsM+ElVh8QzyauCvAID6RWBWHGjbTcI33r8AeVHiATxq6n++MTvYej63ixdupRVqRLx7Hr5MN3Mq3oFHthFa3VJHeHFM7OCZzbZIom5KU0WHZHZxi0aug6jpP5wcLnxausy61tRMNkPM2J4rcEQ1Bxc4mqtFp8K9opMF86uB9HcP2GIF/YTHf0J/kh9YXrNz1mUxV27YPm278HG/koOIcz5j07TU/fMCR+KzLxeyCj1U9XWZeMDM/y1cuotsd05mhrmzS0BR/agbg+nmOe0gkhuRz4595pyOq9tZ76IE9jP7kDIijBRCvlIzxUCE0f+4ogFGM9XWZeMNc4ZAd+fh2WFj7BUjTPKJ7qTBteWCZtjGvFH/F9ybXkzd8HJRhUcPmR0NZpBInHwv5QAKXcYVZ50aIVm0uypGq207QPeL2QTzLx0t0qbjfwVe3vVdHxyanWx5KjJnsXiLMv6lffsdmdSJoESSXp823guSOvkHWy4hcOS8uN58IrP8qDqHMOFzFuIseb/S3Vuutx8L5vJ1t9qB9K51raSQ5B8IrPoE66bC+zlyVc4e/ykmbqPeyJ4u5+ZZLHZQgFybphkdWgEjT+2O/Gnf0MKo1HhKI1Va1SMQzCj+zv2W0XH4kTMglAD+u8ws/sAlpic1P+cQEC7afpiN21FRuxequdROimusUnhWZsT9TJ+QIX1PWG25ueQnS3qkSxEXRnr4S10sc09DdaWN6vVryvEDCy5UjfA2bMvS9RTqRQCifsMZTB2LAKOoJgX2ZgmKpZvwecDayrA6Zu2kj7xPuVIjnEjhn2th4TRJ1xGn82wOEvuI/fNtF2NegKMZ6usv5WFRvCZe09XWbK36wvdybP1IzuRqOBzUV01si1GzMyyF2TANg6wlbYGPUZ/hMbfC5/tAUXjKK7ZwvACJGaBcMCdSd+OYNXq3blblHNn04HFEeTXsusy+zisv34eUAoxnq6zLxeyOLxejANAMNLjWyAMVhaOpkUWfpstV3n6IPga4gqEryDtWNnZPTJrAfSCNlc+RYBlMeVnP5fJjdyScYeXhJZmxPFMozh8bjMy9p6usy8lCsy61vaOkRrsUZjbso0c7M0atOOEzN2zNRtryhVX3kVdI4tBYyuEnkADMtMGDrTVrNLrH1pYlCx8dBA6kE8v5X3FHRAKMZ6usy8Xsgnh7F7dtEHm9a6UW0vp8J2cf+8ju/Jd+GB7E2hh30Bc9w+y0oRnMYM4p4ZjxgREagf9BtU16IXus2xWIWL4ogmHl4vZBPMzk6n7fGV7oJ2HYXdBRWiaJLdsQlEPtHTDI6tlwR5vRVd05kj4IAd9FUwF+SIK6dPNLUAzaITxLFa+019tTw7ime7q1/JwJYADDy8XsgnmZydT1dYbQ7TpbpnxCX7QzroYWvEL/B7EpafBHaLj+K26sVnAGqbiF5A/kAk4wmhKLN4Ve/buGzZX2U10nntGJR+pG0bPXPU0yJ0Z5w8OIw/VX63H9Fh3Ovx19pA5vxWZeL2QTzgJ+F/tJlINwmxCX7O/2m1D6io/f6etADO19QVXElEhYvZoMr7oZy6Ci2oBBlDdWAu25bN4ZdI4Hy3mfowJZ0JgNdpdovdqSQE6c/bFMgYVK0g7umnMy5/N11ZuHQyv6V1mXi9kE8NodiEmDboJ2Hky+JDEpIxU8w/6kE7MSyR+k95YOoN4rTcCPkDu5tXFNgSkmzBC/DAG/3s99Gi9kE8y8X+1p/xQgs9bSwECulIwk+Mq1PC6aUY6YamsFlbzRiiAUYz1dYbEykG3QPBxl8nWETFKT5KSyxxZhK9AOr05vkQqD+UiSa2Y5oG50N6oKWvY5sFOazFgFHaGNtSMMnePxggnJl5bW/D0vEhe/hHKmVVaMzL2nq60Ecwv/oXW1vS03v8mUqqvzoCmHDnpZBxBp0Vjeh40qGfrJRdYbQ7Q7EG3QUVoxaon8SheP2HmCXlMewKAFGPUW4jFlk9Rc6qu3wJeDvZWHd9XTzxrKTFRBnQgSI0M26I4Hom7UJt+ejLAxBXvMvF7IJ5l4d8+OrWQ0xO/jRWAtQAZ42Xf00YpLPOGfFwykG3RrR4c9DqZA8rS75NTpYy+p2OvqeW3icygqVXG3fDE1Valbmf11VSVCfiiuky4TdTlQXzFS1nS0wcgAAvrlG1WZeL2QTzLxeyDHE0tgPkLb5gPkLbbPC7P+XARWiLIqRL0pjfjCfA7O/Axntsuhog/SbCf1FKpw+BtikUc1YZ8JElxxC2zYgHHTsH2ZihjyEXkmULEke42McJggcp/mob96aOB25KrQ6IBRjPV1mXi9kE81CEgD29u2KZlaerMJe7hjBnOS+JnrEa1Yynnn/1IMevXuwLe0xmoBd9Imv5OqTin7hiAZ5tJAq3A6hKgA+Y92i8vQIpYoIHLWCMJAApXfWB2AvPASH+FJzUFGj0y9p6usy8XsgnmXi9hDtMG1z2FL8eU8NvoC9HZf6BSLH5B7eYvZEF8wbQKNbmZV6FvO/dkre5DatKInmoFzd/1oDMrljo9h5KzVedIITh9T67/8j6HzMwfoiCcQ498hBuN5YSAf+9QJxGx5eL2QTzLxeyCeZeL2QTzb+Uy3JiOcrbMIenE2QdgiiiavY8L4F5oiIOccvj+9wXLhvYpJeNchw/Ls95x63boWBSnpo5OGO1pQ28WVYJGy8YFJqKeN+mYaEa0soj5EWmQAZT3opf/dIFth2G1c2PqlbGerrMvF7IJ5l4vZBPMvF7CWnkNrErANN9qKWXsVT27lY8w1aEoQsy2KhMqqByjE4EmqXuLCWZX+av2burp540tPhUwJawMluiP4pD9Y7SuFykQ3wrbZyYph5KO2Y/EGvWQRvQQU/Xnot8zNKRmdGwRoTEQfSpfn0cLgieZeL2QTzLxeyCeZeL2QTzLw6NTXC3fYwX8Lxf7L7IMqPecBusb8v/w8E40OxpCgDcgtBCA+5ZgL/BnhPhr+OQW95FXbnqc/WCW09AH5YjFNUIDqrB8qgzLkvAsp+b3R2QoqSAaRig8Og9BjPV1mXi9kE8y8XsgnmXi9kE9GF/zQlaObYbOUXsgnoP6FPMmD84uEeIs6S7BFvJLwjpAA99BJmVPdq6+ygIOMhLQiUmGjQrnY9B+YY6SWE+iYL/hAvG0fsJja50yDXizehvEglDM/FZl4vZBPMvF7IJ5l4vZBPMvF7IJ5l44XwXzOWDqiFc4+Xx08y8XsgnnBMMmFb4svu3JiztDTirSIClZ4fBHTnMIRVZuIRygbkLs/UL/q16aXNAkj2Fr7BY1n1sTLwDSOoC+wfuSGpdIWGfc4gzx9MEfEPo5iUJ4vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBQMJoOm8f7UBtPpfMidrhc4zMyaD6wdqvQym34TTaN3El5L+9R+NOhNN6bxd8c8kpZ9vdqiJgKGfCc2k3xa1AmMMEKX0+fsdgNVn1AmDRUgk3KqIuJE4xMvTAOShXmfOjGerrMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvGwvmTo3HGjHqPCg3J2Kvvht0HtsQlogFGM9XWZsag/apiKzMXfs7fisy8gOStXWZeL2QTzLxeyCeZeL2QTzLxeyCeZi70Xz6TUk4iEgraBd+i+mUE0WBiiX8jVq1vcTvfp5l4t9WINveYvBqOg9tiDboHXSJl7T1dZl4vZBPMvF7IJ5l5AZxHRdaOHXiaXZzTOVrx/ym6XfEa+O2TF0c+u+Hth2NrGerrMvF7IJ5qNBWbiW4hEpGtEiR3flUFui8jhQd/pKgFGKU/xwcZfteoEv7GwoPbYmUhLQcChfWIU8Hi3HtrUCfJhI2+JeCVlW3O+akSMOPfp4fHqAeDVs4Sc7EBh5WldfRMgMnqerrMvF7IJ5l5KFbdfFsFRkdRySkeIKLI6tuOVK0CX9EOlRlRDKxP5hCCmzNzDxCmhel9OuEg5C17m3O+WLK9pAEE9o8KCoyPvhNd7if5grMvF7IJ6xe22ISaf6gyneXIpYiOF5qrMvF7I4vF7IZoiyDBo7dCWZTG9G81n1KCGvgBf/QoQIFXaJJLTddKmX7XtF5hUjWfI532nq6zLxcFAKMfXtiDfllJugW8yXQ0UXhfk9Qc9p6g57T1eZaQ1R1NglBJntFmifQA+Rp9W2tfT68hmSSDZjHtfz/72l1PAxEdlid0FTIkpa43G/X0MawXyd7bEG3QO/XK0u/a9ovMKkazk8XsgnmXi9kE8y8Xsn1gK7273868m/GEra36U9vlSbb/p6HA6gXB0UBQgUsADINugDZrCxEbblEu/a9ovMKkLTk8XsgnmXi30UDEZHVtxv3D3c0QHzlDHbYhJg26ChPL+NXmaTUVz5RXEc50XdniJA6k19baU8GlEhDrCImp8R8qfRgtRJJPXmOugjhUgNFSA3B8jq2X7VdF5hUjWfICziHJAMExwsT9AZfte0XmFSNZ8gMqFzY7vehBPD49aHUL2RYFQM7yabNb8TEjC+zs4n6NWSJuIAZt/xMrUwQKbOEVVv6jSsZ9UGOmlXRjE31LRAKMZ64MSewz84ZKXVsvRvaLzB5YBEeF0gKQGiY4WJ/ol36js9BAFmnnkEImh18y9p6umTT1dfCPq2cfEGf2tq7eWiP+pZNipAbOwkrmk8jSgzW+YA+d3xnt+OrZeje0Xl6AsQbetYiOFiI0WN/RLv2vaMYqN+5n0mFMUXAHl6twCZs7lm5f/2FguL3j1Bz2nqDntPd0y0g95WyjFL77yZYUpcjrhIM63E8ZEDY1rAVWR0qzTytU2rF78ML+XDsZ9/L9wtYCf6Jd+17NGY46SyR4WJjhYmOFijA2FzOYVI0RyZyXmEzdLvoeHn//vm6f4ROW0Cc34pk09XTJp6usPj4Kv/6naY/S4zQWBVWnD/tTTl+9zFaRheKibGt05bCDnGPvD+FLQRnEQmK0utZ8jq2X7XtAUS1Lbv2vaTVRu+m3QVGOFiY4WpJ139L4ish4h2+rdD+MKlC/6noH/8VqbhhbOKzLxeyB3+p6g57T1bAMXUfjAshxoNClSipwjkZll42Gvs16Lu02++7DUw8cz0sG0ja+rJNJlvJNMhZLqtQD/AZ0EGRwKX1iI4WIjRYR2iY4WJ/ol37XtF5hUi9zW4aKvJMPAtoUiuDt87vpMz37YzRMZ6wCF9T1a/NaHe/TZNPV0yaerrNCnQuULkzCLp3LHKiUueelrbidN7vyR57uO9W1naIL4lIitcBORnVifbvWknK5yIfth54oZ9MV8UlOfhf85rLCPzZ2e1q/nJMvhwvm/XW4+F836wyoAD5uILoH7H8AoG3z8mDWI0sgwsl98Egiu/RTp66TD2P04NxM7AJ5sXe09XWZeL2QTzLxeyCeX8ausy8XspIOO/guqTSsqdQX2R92K1IRxarveTxOvXusFVjqgEaOk/Zy7VmVQvBVrHi72pmXvX/Dy8Xse3MlogFGKU/zBWZeL2QT0H0LcXYY2BTt0xXNbuBUelE3ez2ctQpSCdosAGgCfSCQiYXntPXrrMvF7Inqy/jV1mXi4KAUYzwgoxnhBRjPV1mXjVjposmPI0ztQtMDE7kQl0DKe3eX250GEWiFadJ0EPEkBJNDTesNmvy8XsgnmXi9kE8y8XsgnmXi9kE8y8XsgnmXh9rL8ieGwlhXYnxiXagtPj61qkSOexh+Ulhaseo8rJbvl6liAUWnP6kE8zOWq09XWZeLgne/TzLrsVmXi9kE8v41Bzyw/MImXwSF48T/KNwnzVca3u4hVsHKRV68P6AIvUJfup7tu493Wsa96vZBPMvF7IJ5l4vZBPMvF7IJ5l4vZBPMvF7IJ5mG7kxAmGefkqNNW0vwH+na/V1STUvatj019LFLabxpvRvpeF71cE736eZeL2QTzLxeyCeZeL2QTzLxcFAKMZ6usy88XChwtZpQpEYIhA0y1V2W+xJUW1tYVQQa08xf6J5UM+dxzZEzSg3d/LxeyCeZeL2QTzLxejEw7eHx6usy8XoxMslogEFvMvF/ccRSl5bIREYov9tPqOhmrbv1HYLONM65Z+DGh+1mXeLgoBRjPV1mXi9kE8v41dZl4vYYHe0u8KBWZeL2QTzLrsVl9t0DCiGI1LEwdORW3S0p7lHD4jNLuPTb8ETvp3hMvaerrMvF7IJ5l4vZBPMvFvvmEBvp5l4vZBPMvF7IJ5l59A5DvOcLAPG5Z3uxCNxkUz5kOx08yJ3r2VF0XzAkfhboKDv7SDflfmtA46A7yk2lxQImGjXbZFkQCjGeoOeUgbU1FYApAfl2VKpmDiZRnunytGQLd26UO42oAD++NSf6R9P/pCYP/wIP5Of0Y2gAAAAAAAAAAAAAAGQay26yweKEi900iWKSDFi9uEdnus8mp5U/IqZr9cZwFuB5kQS+h/pwGBCXX8AAAAAAAAAAAAAdq67sGJ/zjsfqSxZsreVCGuteoDt2OsIooyL0HaLL8UFEX+wtQ1lHOHrRgANRkZLQxJCvFXRquPu6EfWikYhBpmxidhgtzx1TEnA2Y06URH5DaDrl7vBnFuasWPzcfNGANjsiCX0VDt90VRldKUqB0w4i+Xk2wSc0JGYfuVAAAAAAAAAAAAR/6PjBpSwSoljRm7GdAXwYrzaIhVxECDEs8PS9tDypOjeAcMmBfbZrf8GIYXQZAxhoIOxIW9InVbxDc3aDobbxcx2RBL6Kh3IfWkk9L3PjZUFS61Im7HWfX3uMkXIMZB1os5I8cldCaVAmxibpZofejuZkLF3zC12X6Q3CAFcAAAAAAAAABA6b4A+Em+l/VkN3Wea4El1+3m0S00Y7VcCxXxpES0fzJRJS+bN+aoAl8YcM3vFKEIvgMJRAPm7ELLrAVqhUkk+cSiwTw1ytZye20qPHFHxLB/lSioK3wtpHvk+vP6mtdrdD2ml7G+PR/VZoSqxVTApTcSuhQoGWw50nYTQN80htVWGsAAAAAAAABwDYZkQ5WF+GJYaM3YzoC5wJnNZ+MLgicywwr0mc/DRrXZyWxW/G27/+6TSUZHkFkcNxt7vWP7xqmGpY1tST49LetGMzkHOTn+oJROODekopWVU/2UTZHEKi+ufCWH4z7N3LIGCY0lsuRBL6KoB74tbWtcuc1oQoTGFdZ+N/ga8MQj0cP2RSdP+KHcUJz8AAAAAAAADxJQDylrNX8wlTniAiZXQVrT/ykRRECwoSqSQpzlONmpnOY0wgBmKbrEJ7Tz7l6/cODvuLEoz7nCF31Sp03Ino+CbtBy9kQS+W9mmWbMHe1x2Dlt7+VX1f3STdMmJX8YOyIg+N2zVRQoolN6y0xW5MxEz0B42iCsoMEagidoOPxxD+76ldl+CYSSqWT4M34AAAAAAOCFM4nbZX8OCQbsG53iYyMxXUJnShDAEAgDArhqLGowZGQb9nY513zgh0veWhLJARGA45soWy/B+Ud1hDJ3VXIg8CTsHk1Yf6vUdFE9m10pAycbyzbLIctilKFKrJs3mzcxYTnVm4K8wl2QjnG9VimAjwXVNY7grdzUhXF6zN4IXIceyyrbf94AQOufiVBLfN5ukmSYQAAAAAXSLQa5EwZBvOCQbsG/jmE+SUukIhlS8UMwBOZJt2Pmn9mkb43H3vXUONQGzzkvBOsL6aKMKNLQxCKEbGKBPUVj6fQoLw4x1sPAoAeww+fwDacRpgKi1E1v4oJeMvnfH5bw+udvQZvYWzT/rovvOxh68Aju3nh6pOAQfSM1I9Ii/wqVp40OBfardlEYEAALSbGfsZwjQkarAAAAApWBiDuSSTMhPrO7r8L9JKKRr6nevt2PiT3HikGggFYItbBMBNjx1z4YmMiUlKFxEH/BtPC4oiOoYsGMh2MSKJejSRTC+2yIJfP+sbJU9kUL4UtqofNfa47By2/kVSWRBL6Sg4iWI/XRN2c6q0kQaHb1f3HoE9YvqzRBpetBHUNo8yUzaP6lQFZytPFKVqkbtPD7o30RzFt7VwHALtYIE4AADKqPdD96U9OTJtyM9QUyIpPu34HWX8As1Q2fOHx7bz/jknOkyL4HOf0+g7cn2PlD5HL/Ov8/UN9bE9cmTaAmSRTiiidwpk0SUdiFyB2vruwtf9qZwG3i6HZn1nUtYl0Bs9Jv0WpIFTv7cdrOK1A21+Jgg3pbefXVATZ3e0NSqHg+IGE7O3AyiYfAaPXVKNoQZmwi2FKwn8Rn3M25fQgshxYk/AAACB03wDXHIQV5iCIlg8OXC5hmgsM0BBVUjSn4XvxfC5pkZiua0S4OjVjP9NVbFKxlw9e/Y9VK4zgs9IsjCT95pnUALp4ZuHy/idBGA9SuQNQy7ovvi9rvbf8UNl46zbJbI/oNyhTAc7+qKj06lgsur/E8uH70VWO1nGpYZeMrqtThdzl4kzUw4aFyu8de40eO9ZHbdDP8Ck2c/rL6gSyZP/wBaywd6E3EqyhIGH2Rj7qWhsrrWrIGSg/3l38xqK0VVTIp5oKtCmPdfG/nlHMHUejrpwE2Zb7lUIoh3PXQZ9qAIdamo9ZfEpc6SDKXXDJGvzUpNrs25uHHBU9uxCg7iu9sJd9R0kETBMiCW+1DAUMTpWKdL3L0XziY9w16NET6Lkp2ApFXTYQTadBk8EfeHyLp9vpLGDD85MnYPRD6GDNzUxMR1bcAzBhJaRAUV1kEAFNRaGHBAFvwg7JvrAypmRMuyzW/rGmeeyZKFUpKVri4SpIobWJ+ejjyBHNYhmmdl4EG/X7T7UKq97ZNANNcpMpochCTiKtD1K2UHJolK7SiL2cv1xpoC20IgZpzylqpYTeYfifUvKkOn3mnHYn25Bm1xF+W0e2j/yH54IHN3VsgKMOJ1FlYfulVb6pnfK0EC4XqgUc0Q3jmVim4aRKj4ay74C/8iPTMF7Er8i2aR/NQkQwGvSfrx2QN7JY6HdlhJetWW+7TX+w5T9QsAAQQQPCMOBv56Bv1XfR15fgF7ZDCsiwhgGLIcpWTKWUTSikRXFwD7NtIyB7VKg05YKxPQ5mORohXuRef40UY2EelaxwQCrCFUobGk16CcZWmYBnjmI6rf27lDyjfqbYQ/EqfLjJcpvv7k/6H3odUcp1T3FBweoT9MAFGpNseleR+1JQOQQwfHSDh3IwNRSCEHPwxFAxyLBbrlchpklggTbOWvnR5MWkS+DuJG7mqdA12fYpv2/VjtE7II/xekeiDqAy6Kvxil4tN74QGwkbqVsSHWJtH8sz14Ei7p0f1cYcxKvaLSlrJ06UhwhkBXP0cPkk4S4mtSofHrKFpGKowr579u/UovNxbSW6UIXr+j8gnqFyNYnRMcp6owxdDLwTk1BBHcx30ekOQEJkAt+hqel5AYveyi0VckXQ9R2Vol6wQUbhaXmKHODjaMQO4kT+mYjAT4TbfXBGSBdnK/w8VMQyNdnVnRndck4sKjsFvtvphQ1QIDgIrOFmfiYRarSW8b3VVK/UltmNb8sP92RGIQZqpMqp/NFRsYeQPKsilKEXBbFYxvka4mknnGCTlwRiVQqLn3FkX3ozGeIm9mV5ea+J8oOE0jlm8MLYxShImglWga6AuypiSqoirUvHPHvyp9fhg+KOzNstCBrsBOG7L7GMJtVwwxy5GBdRkKZ2iRn/rDLdIO5pjub+xjnPCaqrdN0bJzy/qTBwMZS4J2m2MKbE+3ALRylqUEF7Ka1U2JBCYHhFvAy/8T55gIATL9FqbKDgkX6DgQBiILz2NdhupClyVFL5LmXHDR3Kcd70xT0OlkJCaMpxy3tpLfICv6U20B196b0ZCwDD7Oa9SDnfM5hafBvjQi8ngWup82ZqK/a2tFmQGQ9tCvw0Jc0kcd5lZF5zNdQUwaCYwCNtqQwGp5eBRpBNaSHKlsMFFvcV5HonOoIWM3SbZieT9rd1omSD0Rnrh3xRQIHscJCwGemtGxbh9XybcLb0f/1l7T28hQtXMsKdwiDiPbPfK6BMCkIudamVX4orEWYCtnMMy39uSWEvnoQmP6785NiBiVHP8bGFievinLcO75E7TPfAs5hfxKImD1qtIuaa8hmc422RmnDMaVyevdmSHnZ/BmAAA3f17AALSkPPT3VVGHyGcILpaSEw9HuTO6c/brPhLIZ+TJhd8H7db+1ImnD8LguDvrlbv79MKVYg3NWlTS3ldR47w0AXo/3nR8Av4YHHRjrOzZydHe52t+pX1mCM+ZY9DgQODrz3vbLWxRA6yNw9dXPRbLQOfmlpDifoK3nMlOUujPgnK1saMYt8iWrPSmBQLgCVKmahfumwqoCv0xgvzF8lq76nGwDfUEE6liigBRi3I4ox9VabmkyHi+/lrSqOAGFuY/rjwB8dcNL5KAQQgZ+eeQDGHV8PFDL54YjWvJhZsXIjTKgP3u3cEcajL0lNSiriBEPlVupKrB34LoICTgrNkkZDMZmwhsuTLcCoug8cEmv2cV+gZqmbnhtQAAFK1vCpD3IvGbYEBsTShWKzPGOd+koPwp+trbLbZs4r+XmFsX0GP6NTUD8ew8v5TwxZjctugmWTZm4OwzRUgjp5awweWnugeMbtaKK/3ZQYQwR5cm+ZhxZLX09pnu57cZlemlxQIYrdWd0XyfMx8vcKX8nSYSDzjLCMBJeh62XpRAAHi+jjOX8LFIW5cl6CEZNlqG6tssvgeCl426doD5nTUYzSzCEA0LJ5bIUNFCJGaaLnJU2oXgoKAAl/xsT2iJBDhlmT757i6nsh2FotwgvgZo50UXAfvov8TEGgiKdzo86jq5B/ys14Srp8669phhtyiSATE/fjIreyjNQ5twxv0EGAfvE2UAHz8OCcVjq9k8NyDHgUozZFMmGjM5Fp1HMadmx9fNIXHpr9MeNQcf0OMZxqdMx1dcVFKIQss+W8T+Ebifc3ZTfIe9aaKLAj/S1jte5wQWZCFIkHThaaphDZijzjpsKV9OHARN4VU1h30hwsiHUF4Es09M1eU50fr0YEX+QgLUYH/3v3hkcRYTRDgeyYGDE/BtFOVuY2r5jFtXs+Lc1ncdvnIdieq8JCPhcVOR2RuzehKyUMqJHFjoWjFc0ji2fzSdRpGYAnR91KXjU2YLkkUpYkYGy2bpWq2759if55vXqTTJSjeH3TtcyNvNOkI0m+a95+TWSiKQwGeAq1guRLX/l7LuCtZ9lUl9uB6/zAKRW79K9sEs7lr0s7MF7D9gGKSvFxtaQwYJGv2CKAK00SlPrSiGY5Gi6UJCH3GjT72OBhMcm7DXv6OPwQ+/8qiNvjvGR/iZBTuEuXO53VONCpf1vhPeCjLntAkyVu9M4pehhMMsp8LP4dMOtXUE30S/6sUGztTaCZgxgd2khZsjB+nPvRsco7Rk/75dhv9Osyr97P/+D6zpPPrgn6PvsAXiFlX1xw1GcugwbZbVqW2Qc3YRoPKLX0+ibeMliap4aDAxuXhDLJo2YYNGO1AyrEcVRIUJ3gGIVFlKJu9tS39MF4wPWq9Bd4Zy2VYSxubpk0VKq1Z+9448ROzr5NrY6r8b36TzDfHRBVjBO21+f6pFbXobyhC4L3PTulIp1shj50HEVXABRg8/b0ZiI9MGIcSPyesVMmuXay9WKsTUVE/lX+/kFqxo9l1R6vTd+NM1WhzHgsnoDf81GIMFFoBD5qaE1RoO+UX7HajqnpVNkzzGahICH0qQzrAl9Ilyu69mKf+jvkpP84h87D1ULlafHD9ed/GKDGhoDP2bD2mvDLAeKvak0695CLG7DSV9mz2ReH6HImEYShRhQtvx2hq7S5f6QfElocgH8iu6thGw1FbL5r7ERQ6JsXEt93wzjAVg9a2jgbTh5Fl2EQPk9uHOjNO2rCRb09FpBJyhaoNlSqYosyW1sYrEXML936wEuxWkG8splCbNLb7zpFGgI7/04y0EKxI8G2uKcf19cx2fjj9ZijPFHaTQJnLWUh44btJfy5riuYfnA7kri04Q1gmFaqdhA5uEvvqWJc1K6e9BT20gVZCsiZajCk8B5pyuLfklTKSTA4A+RNA4hbTMfmq1Dc+A4zypuxBhcm+TSCY79pq9mY9hMTXCm96hGjybReskBICuZDer1WNrP5M0buoAbr0qoeiZpPdZrUmXTzEpEBgUE9xghsHaZxV+Zc6OVCeJuOl54Yu70ouhEEMjpp87j0ab2/IeIMcgDo78Sir/VEw4YV/MZf/zPh9ANytxC1UPq5GZI0jJO8x6sp5NQHEpOrX3ytLvhvrzCPV+2ufNldVWiL3Rs9f1lhuJOG2rIicUzt+HGHQBYPdo1CruHety1k0P2CvkBeipB4GwzpTrPFg91T+gr63NMoLJsMxz9oazKEprqn24qmX2t3l4hlFMEFueZ0zM4TLb+lNAdqqST40xMQKLd6UiQQyEHvFGbehdKTZZVaV+N1Jpc6zMpU8Y4TLTm4SJifc5WN8ybjPceiDIY0U/nUFmMOLHLdRlxYaMHBcr7cpjE4Xe0bZyYG8Jh0BM1KZOITmKbRUIWhj1HVToMBHp1ugxqZ9k3Dw2XjWdBCeOIA80xubNK/SrdSJgFFSI7rxBpIkWORdXkz1up/Wab2c52HEFu4cKFG/wnoh7ttlOMuqsl3PLnnC1+zIdS3P0xIpJGF2QSg8rm9H/I4uiMcaWcosOIw/ClEaPG6/ihN6GHCRDKIWDiZFzosFNAimUihiIum4QB5hL27AgIV2mHfcmxLbjvdN80SYT3+beD9IO3Scdn34mdg8DI93Zy5v7V5J2ey7O7kLKUnEerAUzdcIKSw6dtaZJzp2GP0BNfdqaDh988t+KMPVcubc0M01OBhbThphLb40aO/s+4/cdNvc7blYK8jPc6bWhPI3S3zHMU8tuRz36l+LHdKEJ2DujdkbJXkUz4ZSibA6FLWhigRT3uY5D5vFadgIJSxzcaHDIcJe3TVM7Swx/MAlIdWUgyXEuUwlTnYhr43L4ICSgTUHiEMHYhEsA3BC562Ep2HSfFSVGsXDCtDVm2O27xGLgOQM5eXH/jXSSLVbi7FIyKNfom5n0Qz83QMA5fxgh6xvZIlhNK7/4uSd6O3DUuw6GiAmDco7rBn5OGblTH8yv4EUJZWlifCriOoezW2TC47P4T1QwPLrEKDeoOJEfESiQdEoh9l+BNuHt0PQwszLUyXE/t+WVFF7NmMzW1KQAwHEOA1j3kNWosIFshH7MW2TqRvLGfOpUseTrCIuAHrBd4p0x0Yp9zmGYj6RI16AWa3PVPIfamJfS9zRotePJcNmDkiI+TjBOfDjVNx+PL4Wz4FWVZCjDk+lQLoUT1KNuKQJ+xHeRHOg6JYSn8Z0yC8FbOwJDHtcD5Ovwg6ur4yuAx1FKPgQ62MouamalNzufCUDskRq3/XsI5YLo1XPcsgL4Dy9mQPd3459/qXk/XKqLhkk8+UGFXJpz5KQbeUfrb3nM261fTP6gip8pm+2eQ/E1pexNwsATbkxciqgnH9kzP3xq3x3+P6JqSTgd5kXv2jctYH6Q+0gQzeuQq1K+VG8UN4xyL35wpvT7pT6lW4t7uEibRQLUECkUojD9q6N+02rMLr/uPzs8csz6TIXd8iIOaPrpqQpk76JpCV8phDOJZVPfwaPqZR+CsFvBOITnUS6nexSCgr46KSu+28BvB+rWJ7Uf9w6EOXnt0znzMtTCtF5s1loZqVm9aE3dan29Sb9tmwFunlbp6dHEkvQJJ2xiWkmRV7D0uLMEJsSXhI/w/YR+BIiyhMK/ZlkHSoXi4ki6kodlLCoN68PRexRF/b2Qiewl7LJvlDCqwBlLQbrrOD/4/s+u8aOtRODgTKyf7AMwwTR25D8NrxJDpLdQmRdCdPFWunocYcVAO1yxKTAuOPEvOeYBUJzFvXqADv3ATNe/wD+xuXZUCoqyGY5vNChSipiLYdM6Fr1IZfPGGqIEOn9xDuUru76Bc5c4IdXeaGzsdm9OAwfH55DTSKUX0JTT1zJmmahNcPgzbpDztIuUGMDCDsdFT+kIP/loszjPcOnsshIvsIjNO7R9fJKQKD0UGp4gSl62nCLA6pf/k69T4B7vzAmE/ZE+ZFuduxPZvPqkNCWeeb5fq4tvuTxSI79IEHeOxC5EIsm2UucFPA+s4Hwh5DWXm5aY+0HYXpoS+JAb0ED79Y4aa4O+96Wc1HpWEBdprOu1x9Fki4cXAAG3j2xP67vKHScfBOk9o6wnf3hoy5nka1ZdlVqs2hY3dRolMwuZ5k2aoUKOkP4meMyoMWVvBXpNROL8q+j4OSUCNnyQCAyBnKua4k1XT3qvITJWTqEF24Bxhc3BoNkMZpjkTsoHETwFyk1HrIUBsxLMQhE4/n/NxS6HAmPxiXYtjBnLBOaHlQ4gdQvG/QV7FSiAHD/QS043y0kXko1cNpsrTMgry0jzn8OX4w/1Wpyk8rkQO6yj/uRAHJBZODxU2i+EBy5B48U8HNHBt4gOpTX92Hx8hdVOIjA+26CIWkkqOmvR3/x6V86gOrRcc/1gt7GTun/e0BLThVlcZOlwG74S2/KMSVlN795/MceiKYYSe8awcMkAtcuTvl+rIkW8uehqgzJlgZ4vZN9CZNP2VBWIpVnn6PEahjsKnn4K6lOewKMaX1b04UugCPeY65DZjxz/m6lUVnIhWCuKCFkV0lCpQLwtU9UqqmL5sAg9ovz7HjsQuf+6NUPuJm0GBl7Q2pbZvM38L1Us9NEqKE6SXdmp3tKk/hTtKSgHBN46rr5RGhbR8YbuZcB0lbIy4ps6aG+t69/RvVIAAAFRFScn+W+FJ9sKxRUk7wwQ7LtRbHi3mxQ7AbL/STDpGJxsIDGD2nbizBM3zB6OXBOsYhhRXox8X9lDATyJTRk3KhgO+Jy6/3EOCU0YmgUWyRnID+aj3CTT60gsiCEjPuJLVqQLiEOCMDI172gq29QqB/kD94hH2ZsScJTfhFqMSQ4uFVHTb3gb3W2qnv9qDQkLNknxyeI6q0+c/IGgMqPGfV6GmSvERkJif0KUcg1HBgt93f32BY+2L9aJrUE58IG3KY+O64ItN+Sy68Bk8yj1ZwXx960rwoogcOsaSU7qbz6LPTUYrbHLon+68GdKyNICjoIi2jQnTGT5wjZb3dG/INE2nUeI3nKmK6BELwPq0A1i7klxmZArx3GKW53N2s2NMyhazAxHd5GeUo2GtLkifk/1mAAAAAmEKXf4M+kjgsdy8Wh62vTYcFw7hiY8aijZRScQHX8qPhdbje9Ev0L1dvk7IQhA59Fbg7etzjFmkcbs623EJF6M6J6T/UK3ZBH+S/peNKygKk4daclUb7XbgmEMj4mk6zpIWJAHgBJM+3yeYT7rZCXBO3qizGlsaAzRPR6upHYKGOlmuRV715TJIhz0DjPeLTk6CGPYeUmlpJIFkc5BN8ZxO6F38ahTi7ELdoFz1CSFHOH3JcRD3SV94BRBJ466/BXJNXqMxrOa8VW+bNAF5KOb5hqkWSDyppPSeNCbSUlY+faNJFwrXGENG8lyVUFF+G29APu11cVtnQ9m8zf+P7YfldYAAABpCFcPYHLtxR6ZEU2qBVfdsh/cbvQwwxTB0LlSPm7eLNuAp/t/na397HvGUB6sUZJUdDvIQDo1F9YczNRPtVDfQX86AvXU7eMyO2XgGPwUc2Fm5+I1oMfgm9ktvKdPnrP3AHvMRGXrIaY2bKngQFym5/zkPfqoINyqoipybu9wn9YMSp8Q9jXJ4jaeeQlbQY5O4u83acRXNlzScQ4/XPOO/tpi17oauPLrhymjua9xIwHHqc0FPIWhyCX1WRZJGKMz+AjM7GJj4H+zX+P0n+Jb1zxgMJPp+fRHeJ60zUb2ExbyY0pQDOI69kp0+iRkn9LLw4aeSuQMVeUQdhaDtjW3GXKzI5MVjWcMlFiH1ewBfHposNrXO/ZAAAAAAA1HdlAYhm+DnW8oS9xtcimaGtGZQYPF4GSB8teJCE9nuHvw606vOvWojgIECYtx/Yb6vDPMOGXI+5/S0t2XHaKmBf4D7d2PEHEqM7FOn0gl5Zf6PfgmZalARpBPea+W5GuY419etVviuP2zulpNqT9VD+KGmWzwOCf/0U6Jr8FdyyhuhbnEuzDzuACKX9saVue+EfNTUH14Ul9fwKyM/cYughXwy2RN+CzCG2ysoDuQGYITz0m9HhOR+MaCH9+JffT06qOyZ1/UuFUGC9yao4GXU9eqU29qi2a+CrXb93XFDcqpOpTuPqjV747qpvzebeDHlH1BzCxiom7SSTwF2j/9R07kF0gbnkA++ZEEwmmBBo56stp8jPVy/VwBs7HuOoOxLtJHqHviHxXhguZf6XKacBBHFF+GG4FVRSNMoys2QgSOt4dgAAAAAOrqxj7tMwZmTByJz/IhNNjU3/CvdNKX5x9HgxdbxNmaHlyVP6D16mkYDumZT6Y/ra8C5jMEzFyT2fOz0X1HfXahM3cvBnjJjo4CscUD1qYzHyEhB9VWERcWkJMgnPHI+6dymVqJrNthqmfWtzYt922oDYK0B8ECZfIoX95u/FjQhyj1M/UOkXGkA0ICirnQ129M/c1VC5thMg3isLFp9kbcuYqbs3Hu016NN5YZWyk1KyYM4nZIa5CMLXBGUn619eVdrAbSQQAWmSZ6Vqo3qnSllWEuxxe0wQ6ia8FY8k9abGZpDwVkCQ1cBAMZU/PojvSlE1QNkclchi17oT8X57wJkQTCaYJ0gAMSilRy9iEm43CM3SitOJ8p7eiZDk21aMcocWx8n82AvuJW3d34fmrLgas05sVEMStHQCK+San8wAAAAAAAAAFOb+0fgK3D0amyi9Gdxz64/SNCvO6ynKl59EkPi6Vi98sjFRNgqJ6HdmrksghG1gJ+X+l9hpMuFNte3Q1DPD6nsBP4ASC8naX2Lzbmvqz8pW894C7ty/7UvHxySrAvWtd8raS9PaL2Vh/o3BdpdOdw2LI8h+sXTEcWP8NoS1KJG5/MFjz8iDNiHBtXJgQVdanG7maxAlkd2CoLf097QdDfCAfJD6YFhzzHQqY6Uap+x03jhzHM2pO5dpfIyLRkXn/Y/oCvNU7wdAHz9YEPqBnWgrXyv5srCAJJ7oYmdfH6qApPNUcvy83o0Vj92KOKCDyGqoz2EiA7l/TbZlH/YMoacc35/UZFvkAwqgKK/IIdPw7YtLD6lE0apN65gSDSnNpI9SVBXR7c1LmQZGz6LTZcDSso36dX3r5GCDu3d/kh+7Cl1qzTBzD7IbRTKGMwVlx88217OjDTtZHEK88CG+UvpAGl3PyALTQQsY8GXo3tDzVk9GRzAFyoAAAAAAADeHiHqBKTayh+P31QV170dZYWEXQ37vkAC33FkvxlGDZZJqWYnnhjY847bKkvMDKEIaqqB7sbJQPdWXr1LHtd3EOBWKv5IuAESRvW4XVEDM3/eGwdQJIz/KqifYbHM7bWnUmwMK48Suh1kX0YJ+dy5BiEtcjTaEAFvryYe7E3ClHbYczjBXrffyHKk1XRoczhJCroacop3bkwkcNzE/w7CN6T9xLiYop6qBReS6I8Sz4Fdwdv2IbaQa4hzVpdOwt7lp9ALpqMRdwVfZtLPhspVZvLpE15oGgv6bNrDR1baIrzIxbq6cLW77+RKaHiGnBavFlp5nAdHsJ15XGbtPZYv89m8/jVXZ0L/EigavmpYZdal9N/sR6E/0/rYBAJc++0l7tNPk1yNpwe7uB/3MKLgDCRbWHoknjE8zpUh8QVUwy/TNCYpw1F9X8u7L+ZbJGpcrOV4oeoa3j+8kbAAAAAAAAAKnP1oB/mu5Px3Bs1+zB6pcCpgNX4vS6gf0UAAAvOTVMzqCNlF2XhRTJJBeZbeqj2UEiu193FHn2La4fl/Cd889oco3yEK79EDXcZwCgz3Wy57rCZtfrsOIn+1uY1T4nN91MZj4wrlrEx6zPTMsT81mMYet2RhXMrlCQGPWWg0RMzYroL5j+e4KeG507jhwYKQwIAfNPrDW1OgBrl9Li9ZQDow+OaQktT2V1v6CbG6TWNtFX9INEcBSkSBgSIk3NidbagWUtLoRVFToK9yMBEPMNSU4YqBvPkQTBZbtdDP3zyZxnuFWnFuwVp5BFz5ZFFCjOS+nTprmgjCU8D6sUbecDPCBjkMYnmdMKctsYRK+sJSV8UqciEaIhPLJNFOk+bNM5d9gRmb55bgjgAAAAAAAARpDUkkD0NiOToA41iXeyW+X9ldvNggpbwUqVo9InVH6MCIAAAHNWK3TQilCrWO7xy7Q6jg79iSZw/oUD56TMcPRU4W2E7UsnmNFxF8r1W5HTvyYW/pjPbv63U5lGMCHC/FQh+GoZ4fRyoL5qbjIhiP/3ZGot4/7TbD637jjUvB41O2XjKa5WEwR2do3sXLyWU6lIbdGKNc+jeS0hfXLp2S9O6Ads0qGiurED4UCL6P2sSc114BiveQBn4Y9HlBRBa5b/3raAx47qHx/hV0bLBa0YoGU/mHYPRwMT9fIku1ciU/qQU9tGtZ2LCP3PwAJ2NRZV2HkSxq8Z0TIG2Kb4x/qhjbHKXNCCgTVohxuCmcrptbSHT2aIQwmSQi+EmVX9GnePKS5USNEQIzBJ1qt7zUXAf3DjFV8AAAAAAAAAADWXGYJTUBew6MlLzODxL1aI7pArOgE1SX1ihXqB9gAAAADoyJug0DOnGMoPtwR4XoObaBT4CglbcPNz1FjMSPoZoMwRGE8UuFtshXaUKDWnWsa8F9uDrM9APfMY/jRcGqpwVfdo+YGO4AdqruSeAjkUaOr8z0MOoFMlTQychF7fTca3KuS7k7fP42ZYxPFHhtZyyuRzqSeOdcqGFTa7JJvBFqSfUUzBfJXM+pg1q8NENXYtJFfVT8VHUtqSjEWUukM2AO3bFBRpJ17RBM2PG+PqSg398oxFxb2hPnnDxErQTrXW5YemAVkzLyWawB3dp+/xbqTpRtkcjuaERy4kRE2ZNmKF9ReZ9+uRcuv7nxT9YEhpOONzn0ecilPUZKsXf0Q9yN68zwhSwpqXXLqt4MXx+WKmjQMEhpabkrVXxFVa3ldOVo9mpVsAAAAAAAAAAABCt3p+CsD8uucCCkZnfpjFHPSStdFzy5DfiQoBrv6nsWFM0bsxyyeveO/AJvgEj4H3T/vchgAeYkcoJKEo3aOFZbLhZHIgwC4TAznCXbei52fZQg1rD56xNLKTrkTMzkdvwb1rH4C4DvYy2Ad8rcEgWSrxZwNepz4CUYD4B2gpOP/PsidIB0x17nIHs1IsrGqkV6XjHsD9BcwFc62+W9l6f5/k4jKjXKDs0MIUYP/lAPLdFrBzQzmUBcAutIRJO2MsSEB8SihalhWCbcIoEAY9Xh+Zx3GvnRE8VYxOip+6ugJ3hwCTZCjE2G2sgE1PcDXtj7OrxrJ83B906c6Yj8vtrzUrRzeQAAAAAAAAAAAGNc5UOgkEHFLzjt+GyfoUiujftPqZphyp8+/5l5IvQ29p8Y0WTdN9iSt9JQAAAAAAAAACSAGShGhb6XGxO/bWWAAAAAAAACpXkns+3biF3FA/refe/eGs4g/B/vsMsP2OyN7Byzc//YN4t0ps1EIk25cjV4qZU2gAAAAa/1pa1+AAAAAAAotTKArJ6Y/zd5u83kIpHtMttqPwktgK0yn+wgEoimqeRhpRlg0nRW7Me1wvnSDd6w8SmMtw7nrmv+Ae4Vb4+cX1FJtEime2OS3C8tizbT1BqS2dLDgwwn9fVfQB6w6tmltuhYFPgCvN4VWrvQAAAAB3pAAAAAJkxzrnmrSsCri0l1GX+1Zzvdh7l7pqkq3qKz3g0Zniw03kef14CCz1aZbfOMh442Vr0v2gFvA8QVzgAK4Igm1QlkWmH6yZlRrz3Sq4IptahCk402O3jo2GMZu+H3e5hA2wG9kJvE3XRfOpbun5jWbQh8Ki9wgwK02NmUvBihyVlIOxlamblTHszavn0HqaDeMDlqHd5trQg2B59s1/23DAxj6nuB9QWU3n1733kYxN7Cw+n696a5UOG3e1vtUs6UAAAACh/XeaVACiw8HtcpgKCctcMzoTX1oCTZ61TngE7F+e4Aqmyt/sdFrhq/6eljhhuSPqWnJgbIZDxRcj3rlVLmMUbXKwMQif/TStE3Mp1SY/7MSh1QvKyTXxrdImFZvDd1gAAAAAAAASpO6aN4bZqxZn0fhBB77R0YVYcwAAAAAKoLMnBRl0ui8ZJVxF7HrFjpJ6S7D8S9NSrZOXrGEM6tF4pM0/PKbQGtXwNL7bhgbLI53hsJ3QuY77Z0KfwSRJvIpQGytMX5NTW4fpRBP+ZOlAAPsQAAAAAAAaaDH5dhcCP4FgnPEdCC0V9AqMyqNloDzuaetAhCRe1xRP6nxZwIHh9DCRe3pPTjBqxZOJ1pczp3q1/g+Ae4Vu/naMovJrvSCQaZfzZZTfRIB+ddKkgAAABnSP9xxOPLLeRDslLky5Seisr1afjMELcAAAFEwW2ThMYtFl/RSZAEVyusgbP5UNFMdLian7s+b1ZWcrd4VSZgL7FDbFA2BnGIgBc9bQLqmzYCtRM1/22wu9Q9gk7LxViLokG9qFSZC+qAAAAHLgAAAAHydjwCyxNJAAAAA5NjzK4Lp1eq6bdAAANaMPkshElIG/pHE/kFbWqmIgto1CPGIbhyih4F9Pwq+RhRJpuhyj0z1w9EAhj/AUQZXThZuZZm37khXdtaDAvuu8pB0S4liU47mLAhEchUBqGPoT4SXTvayr0AA4DFjpHbW3RfkJNIAAArX7nEQJNQgOAiPi4Mrjj7y8OjDexB9zdXRoWNA1dqcu3ydbC83TazT4nr7LUBuAAAAF0YurUnxf3FFAyp1rIdASW/PWrxPKogd1QOt6IIZFzGU+LJkSn0OG3zPekZpgb3hAbE8j5P5TROj6r8mfKyurUQ6jYZS0X9pGaUk1Pxkm2eCB2U5yOybPiGAAAELcWVMRcnUU8lCJBoqMzpBzw3DV2mGdKz6o+dDo8pxIXjWLJi5vPFfxckUpcn3C2rTV6Dqx5ritjlXFoi/tkjuV77YsYPkB7MgWhcqWiHcG7VL9ZQAAAAAVNaiIFtWMimKnJsmHz6B+VRAAkpvpXtxrngRUfIAgk4p5cwi+idO68hIR50mmi39AzzdYGOD5TycOLFE/L6TGS+gQUD6oHuLPxEmECr6jU7YTtile/ENqA10qmgAAAABKSe2RAAAASAetRXr1d7yBvBWm9Pkq1PZRTz0dHiBAu7y+8/0xtrEVPApWcadpoRonJmE3FYC8CPAAAAAA5PbKANtXyrJmrcy471F+/PwLxxNlqilGp1RADgYwNQj7T0X2rNhpWVG199YFwHO5SV/UXggDRJHc+JpD0hRJ4nHM8wmrkyxpUyPD64lTcua7evqC2kguxC7GGY2kPmMbH2DaNoAGFjOS7cW3NKh8hLcEVipP/Uf+KVoiKhoEHP9sfYtmS9kEpgEZs2oNHyWkSj5+22+cQfjNcg6JzYmrruBb+1dwozz0GqYYXeyxYz6XWJfJIycerRssAAAAAAEOyIsija1OVWCkr/nFv5RVGXBcbUJyIzvf+5TRAHJSayM8urBJntlRA7gcB8b+4fkZ3LQbIzTm8SQhWOUvpFQaJt/FQFWWkXn87ycuR9Yg1/+J0Iva1K33//FV+0VgJTSU1AjKBZAsyhwtk5BuJx8KRL//UXwKTAABRUAAC8WDjg+POOZvAmK+JipDxM+RMN6ULISu1pJfMY1GnpkMzhp4dIN9/JbniTIQntJPAcdhsWjXND5Rt1//5Ghbxm4FUmMxQ+6di8Oh9+SZljWTAAAAAAAAmUih0zZubTjv313LA3CN1D1EsMv/oePASPTwzN/GvWVervi7naljy1RT7kVRD/028iXP91oJHq/cVTFnQTAuXwyP7qWVx+SL/2OcDWiWeHSUZO13k53u5lqJzbdN22kYYyYLhkgM6G/1k6VgRIzPs5UVO3jhW2hV81iyyf7kgrxwp7Y8s2ObuR/iX8alseKzZxkwdK632GEG+jwWzzHKLVQFlUiFBsT+yTUceBp9HkC5WYdbdwNGVIYmpgEhfbJKrZweoYVi2ljGHfUOd+W5d1DpjTHdZ2IF92IS60oMu5utG0CfTE1OFmIupitT9ArCA7KRJSuDZjiCogXvEIwj8on3P3AAAAAAAACk8J6AdEhrW1c9cYOSsmODBwUODZmKvY63EyyApEAJ7OxiGdvWIfusHFgDFLscUp9Q5Jyi7H+1kx3RNw+Ma40N3ZtMl1EqrZUHxReAAAAAAAAAFPQc9+2LXm533qkbumIRsrvf8I/Bxo9yAC2/uQ/0w9Lu7kyUKW0G4DsCRLiP2mOq1m7ZjYYgKE4h0QPcHkCIyncAAAAAAAAAAAI2MN0k/52aHmMnvTaZNR7Tmyu5ERSE+sjIU4Z9xg4mi+h8kHLMr284SuszmX3lWHMBv7tePY+rig2BS2C0uAAAAAAAAAABYB16Zc7K0gO2VWYm75mP3dS6O76KqRG4oAxjoU3tDRr1zb1u+jEGpd/iQfR8IImP65/EoPy0v8/g3sWHQAAAAAAAAAAAA//1fzkER/1POTWtr65MRcr4fLnRDRYS5VGxgxNDeVTBWVG6/s47acw8Z/gm1qmHJiVUGdztetNcQAAAAAAAAAAAAJGGv1ufXuplCDtv0sgKIknHgX4e31zchclE9EbxGn2VpBw4+gBmnpsDKcEitBjUWODgug2BwAAAAAAAAAAAAAEPYei0hegfAxsgJQ+omT5/P1QSWKDlyElbyXclVQQTh88e0bGEvs29gSBECzQPyg7+PGAAAAAAAAAAAAAAACzCJLYFaa23WRBakgcDfn8U7PDvJnYg0NRV2zb4C89/wDjx9hGyEmAqB4AAAAAAAAAAAAAAAFUgKqN1DR7ycj8fd00T1C6Hz0xEfnErEUGKMg5HF3wwnLU0y6SlIAjI2HAAAAAAAAAAAAAAAADlCwqaBwDHba4mOzoYS/8TYGRX2svb/lzgTuL0AxCjC6AjzAAAAAAAAAAAAAAAAAAAANeMr5cBC6b3pyerOkjwlgAAA\"></p></blockquote></div>','2026-06-09 08:33:20'),(4,2,'Nueva tarea: tarea 1pruebas alidaioshdiohaihdfihadifa (Informática)','<p>Se ha publicado una nueva tarea de <strong>Informática</strong>: <strong>tarea 1pruebas alidaioshdiohaihdfihadifa</strong>.</p><p>alscjaliscillasjsa</p>','2026-06-09 15:41:56'),(5,2,'Nueva tarea: tarea 1pruebas alidaioshdiohaihdfihadifa (Informática)','<p>Se ha publicado una nueva tarea de <strong>Informática</strong>: <strong>tarea 1pruebas alidaioshdiohaihdfihadifa</strong>.</p><p>alscjaliscillasjsa</p>','2026-06-09 15:41:56'),(6,3,'prueba de conteo','<p>adjaiosjdjasidjaoijdas</p><p><img src=\"http://localhost:8080/uploads/imagenes/22581eebc1c993d88c7c440f.jpg\" width=\"229\" height=\"230\" style=\"width: 229px; height: 230px;\"></p>','2026-06-11 07:59:48');
/*!40000 ALTER TABLE `mensajes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes_adjuntos`
--

DROP TABLE IF EXISTS `mensajes_adjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajes_adjuntos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `mensaje_id` int unsigned NOT NULL,
  `nombre_orig` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mensaje_id` (`mensaje_id`),
  CONSTRAINT `mensajes_adjuntos_ibfk_1` FOREIGN KEY (`mensaje_id`) REFERENCES `mensajes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes_adjuntos`
--

LOCK TABLES `mensajes_adjuntos` WRITE;
/*!40000 ALTER TABLE `mensajes_adjuntos` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajes_adjuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes_destinatarios`
--

DROP TABLE IF EXISTS `mensajes_destinatarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajes_destinatarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `mensaje_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT '0',
  `leido_at` datetime DEFAULT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dest` (`mensaje_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `mensajes_destinatarios_ibfk_1` FOREIGN KEY (`mensaje_id`) REFERENCES `mensajes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mensajes_destinatarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes_destinatarios`
--

LOCK TABLES `mensajes_destinatarios` WRITE;
/*!40000 ALTER TABLE `mensajes_destinatarios` DISABLE KEYS */;
INSERT INTO `mensajes_destinatarios` VALUES (1,1,5,0,NULL,0),(2,1,6,0,NULL,0),(3,1,3,1,'2026-06-09 08:32:55',0),(4,1,4,0,NULL,0),(5,2,33,0,NULL,0),(6,2,34,0,NULL,0),(7,3,2,1,'2026-06-09 08:33:45',0),(8,4,5,0,NULL,0),(9,4,6,0,NULL,0),(10,4,3,0,NULL,0),(11,4,4,0,NULL,0),(12,5,33,0,NULL,0),(13,5,34,0,NULL,0),(14,6,2,0,NULL,0);
/*!40000 ALTER TABLE `mensajes_destinatarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notas`
--

DROP TABLE IF EXISTS `notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `indicador_id` int unsigned NOT NULL,
  `matricula_id` int unsigned NOT NULL,
  `valor` decimal(4,1) NOT NULL,
  `fecha` date NOT NULL,
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nota` (`indicador_id`,`matricula_id`),
  KEY `matricula_id` (`matricula_id`),
  CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`indicador_id`) REFERENCES `indicadores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notas`
--

LOCK TABLES `notas` WRITE;
/*!40000 ALTER TABLE `notas` DISABLE KEYS */;
/*!40000 ALTER TABLE `notas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `observaciones`
--

DROP TABLE IF EXISTS `observaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `matricula_id` int unsigned NOT NULL,
  `docente_id` int unsigned NOT NULL,
  `tipo` enum('academica','convivencia','logro','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'academica',
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `matricula_id` (`matricula_id`),
  KEY `docente_id` (`docente_id`),
  CONSTRAINT `observaciones_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`),
  CONSTRAINT `observaciones_ibfk_2` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `observaciones`
--

LOCK TABLES `observaciones` WRITE;
/*!40000 ALTER TABLE `observaciones` DISABLE KEYS */;
INSERT INTO `observaciones` VALUES (1,2,2,'convivencia','durante la clase el estudiante tiene multiples llamados de atencion por charla con sus compañeros','2026-06-11','2026-06-11 11:04:00'),(2,2,2,'logro','la estudiante merece el merito por recuperar sus notas de esta forma','2026-06-11','2026-06-11 11:06:39');
/*!40000 ALTER TABLE `observaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,2,'e0514a5e1d90f796f11b66bb76118328cdeefb2c9d460a3a0f99df6b45dc74e4','2026-06-10 16:58:46',1,'2026-06-10 15:58:46'),(2,2,'d3e635366851895ca550aa13c3d10770ae5d244c3f2016ea5e5c0f3aa23eea52','2026-06-10 17:10:40',1,'2026-06-10 16:10:40'),(3,2,'8233515de620c7f406b46e9565e8611e8a6612e3fdbefcab025b4e80cdc966b5','2026-06-10 17:14:19',1,'2026-06-10 16:14:19'),(4,2,'acdef443afa5ee0b02ac71333018df6f89ba52cd3228dfeb596c921be30d82d1','2026-06-10 17:16:56',0,'2026-06-10 16:16:56');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfiles`
--

DROP TABLE IF EXISTS `perfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfiles` (
  `usuario_id` int unsigned NOT NULL,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` enum('CC','TI','CE','PP','NIT') COLLATE utf8mb4_unicode_ci DEFAULT 'CC',
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_nacimiento` date DEFAULT NULL,
  `pais` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barrio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nacionalidad` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_personal` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular_indicativo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '+57',
  `genero` enum('M','F','O') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estrato` tinyint unsigned DEFAULT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `perfiles_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfiles`
--

LOCK TABLES `perfiles` WRITE;
/*!40000 ALTER TABLE `perfiles` DISABLE KEYS */;
INSERT INTO `perfiles` VALUES (2,'','CC','6084321234','3142567890','CLL 13 20 25','Yopal','','','2026-06-10 15:54:19',NULL,NULL,NULL,'',NULL,NULL,'+57',NULL,NULL);
/*!40000 ALTER TABLE `perfiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodos`
--

DROP TABLE IF EXISTS `periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodos` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `ano_lectivo_id` smallint unsigned NOT NULL,
  `nombre` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` tinyint unsigned NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `permite_notas` tinyint(1) NOT NULL DEFAULT '1',
  `permite_observaciones` tinyint(1) NOT NULL DEFAULT '1',
  `permite_planeaciones` tinyint(1) NOT NULL DEFAULT '1',
  `permite_habilitaciones` tinyint(1) NOT NULL DEFAULT '1',
  `porcentaje_ano` decimal(5,2) NOT NULL DEFAULT '25.00',
  `cierre_notas` date DEFAULT NULL,
  `max_notas` date DEFAULT NULL,
  `fecha_evaluaciones` date DEFAULT NULL,
  `peso` decimal(4,2) NOT NULL DEFAULT '1.00',
  PRIMARY KEY (`id`),
  KEY `ano_lectivo_id` (`ano_lectivo_id`),
  CONSTRAINT `periodos_ibfk_1` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `anos_lectivos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodos`
--

LOCK TABLES `periodos` WRITE;
/*!40000 ALTER TABLE `periodos` DISABLE KEYS */;
INSERT INTO `periodos` VALUES (1,1,'I PERIODO',1,'2026-02-03','2026-04-25',0,0,1,1,1,20.00,NULL,NULL,NULL,1.00),(2,1,'II PERIODO',2,'2026-04-28','2026-06-27',1,1,1,1,1,30.00,NULL,NULL,NULL,1.00),(3,1,'III PERIODO',3,'2026-07-14','2026-09-26',0,0,1,1,1,20.00,NULL,NULL,NULL,1.00),(4,1,'IV PERIODO',4,'2026-09-29','2026-11-28',0,0,1,1,1,30.00,NULL,NULL,NULL,1.00);
/*!40000 ALTER TABLE `periodos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preferencias_notificaciones`
--

DROP TABLE IF EXISTS `preferencias_notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `preferencias_notificaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `tipo` enum('mensaje','observacion','asistencia','tarea','nota','tarea_asignada') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `token_unsub` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pref` (`usuario_id`,`tipo`),
  CONSTRAINT `preferencias_notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preferencias_notificaciones`
--

LOCK TABLES `preferencias_notificaciones` WRITE;
/*!40000 ALTER TABLE `preferencias_notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `preferencias_notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promociones`
--

DROP TABLE IF EXISTS `promociones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promociones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `matricula_id` int unsigned NOT NULL,
  `nueva_matricula` int unsigned DEFAULT NULL,
  `accion` enum('promovido','reprobado','retirado','traslado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_origen_id` smallint unsigned NOT NULL,
  `ano_destino_id` smallint unsigned DEFAULT NULL,
  `realizado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `matricula_id` (`matricula_id`),
  KEY `realizado_por` (`realizado_por`),
  CONSTRAINT `promociones_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`),
  CONSTRAINT `promociones_ibfk_2` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promociones`
--

LOCK TABLES `promociones` WRITE;
/*!40000 ALTER TABLE `promociones` DISABLE KEYS */;
/*!40000 ALTER TABLE `promociones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesiones` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `asignacion_id` int unsigned NOT NULL,
  `periodo_id` smallint unsigned NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` mediumtext,
  `tipo_entrega` enum('ninguna','clase','linea') NOT NULL DEFAULT 'ninguna',
  `fecha_publicacion` datetime NOT NULL,
  `fecha_limite` datetime DEFAULT NULL,
  `evaluacion_id` int unsigned DEFAULT NULL,
  `foro_habilitado` tinyint(1) NOT NULL DEFAULT '0',
  `mensaje_id` int unsigned DEFAULT NULL,
  `creado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `periodo_id` (`periodo_id`),
  KEY `evaluacion_id` (`evaluacion_id`),
  KEY `creado_por` (`creado_por`),
  KEY `idx_asig_periodo` (`asignacion_id`,`periodo_id`),
  CONSTRAINT `tareas_ibfk_1` FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  CONSTRAINT `tareas_ibfk_3` FOREIGN KEY (`evaluacion_id`) REFERENCES `evaluaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tareas_ibfk_4` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
INSERT INTO `tareas` VALUES (1,1,2,'tarea 1pruebas','<p>zscvnalvdinoadivoid</p><p><img src=\"data:image/jpeg;base64,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\"></p>','ninguna','2026-06-09 08:32:00',NULL,NULL,0,1,2,'2026-06-09 08:32:45','2026-06-09 08:32:45'),(2,4,2,'tarea 1pruebas','<p>zscvnalvdinoadivoid</p><p><img src=\"data:image/jpeg;base64,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\"></p>','ninguna','2026-06-09 08:32:00',NULL,NULL,0,2,2,'2026-06-09 08:32:45','2026-06-09 08:32:45'),(3,30,2,'tarea 1pruebas alidaioshdiohaihdfihadifa','<p>alscjaliscillasjsa</p>','ninguna','2026-06-09 15:41:00',NULL,NULL,0,4,2,'2026-06-09 15:41:56','2026-06-09 15:41:56'),(4,31,2,'tarea 1pruebas alidaioshdiohaihdfihadifa','<p>alscjaliscillasjsa</p>','ninguna','2026-06-09 15:41:00',NULL,NULL,0,5,2,'2026-06-09 15:41:56','2026-06-09 15:41:56');
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas_adjuntos`
--

DROP TABLE IF EXISTS `tareas_adjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas_adjuntos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tarea_id` int unsigned NOT NULL,
  `nombre_orig` varchar(255) NOT NULL,
  `nombre_disk` varchar(255) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `tamano` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tarea_id` (`tarea_id`),
  CONSTRAINT `tareas_adjuntos_ibfk_1` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas_adjuntos`
--

LOCK TABLES `tareas_adjuntos` WRITE;
/*!40000 ALTER TABLE `tareas_adjuntos` DISABLE KEYS */;
/*!40000 ALTER TABLE `tareas_adjuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_evaluacion`
--

DROP TABLE IF EXISTS `tipos_evaluacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_evaluacion` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `asignacion_id` int unsigned NOT NULL,
  `periodo_id` smallint unsigned NOT NULL,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `asignacion_id` (`asignacion_id`),
  KEY `periodo_id` (`periodo_id`),
  CONSTRAINT `tipos_evaluacion_ibfk_1` FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tipos_evaluacion_ibfk_2` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_evaluacion`
--

LOCK TABLES `tipos_evaluacion` WRITE;
/*!40000 ALTER TABLE `tipos_evaluacion` DISABLE KEYS */;
INSERT INTO `tipos_evaluacion` VALUES (1,30,2,'Ser',30.00),(2,30,2,'Saber',40.00),(3,30,2,'Hacer',30.00);
/*!40000 ALTER TABLE `tipos_evaluacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traslados`
--

DROP TABLE IF EXISTS `traslados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `traslados` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `estudiante_id` int unsigned NOT NULL,
  `grupo_origen_id` smallint unsigned NOT NULL,
  `grupo_destino_id` smallint unsigned NOT NULL,
  `motivo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realizado_por` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `grupo_origen_id` (`grupo_origen_id`),
  KEY `grupo_destino_id` (`grupo_destino_id`),
  KEY `realizado_por` (`realizado_por`),
  CONSTRAINT `traslados_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`),
  CONSTRAINT `traslados_ibfk_2` FOREIGN KEY (`grupo_origen_id`) REFERENCES `grupos` (`id`),
  CONSTRAINT `traslados_ibfk_3` FOREIGN KEY (`grupo_destino_id`) REFERENCES `grupos` (`id`),
  CONSTRAINT `traslados_ibfk_4` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traslados`
--

LOCK TABLES `traslados` WRITE;
/*!40000 ALTER TABLE `traslados` DISABLE KEYS */;
/*!40000 ALTER TABLE `traslados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_roles`
--

DROP TABLE IF EXISTS `usuario_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_roles` (
  `usuario_id` int unsigned NOT NULL,
  `rol` enum('admin','docente','estudiante','padre','soporte') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`usuario_id`,`rol`),
  CONSTRAINT `usuario_roles_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_roles`
--

LOCK TABLES `usuario_roles` WRITE;
/*!40000 ALTER TABLE `usuario_roles` DISABLE KEYS */;
INSERT INTO `usuario_roles` VALUES (1,'admin'),(2,'docente'),(3,'padre'),(4,'padre'),(5,'estudiante'),(6,'estudiante');
/*!40000 ALTER TABLE `usuario_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `colegio_id` smallint unsigned NOT NULL DEFAULT '1',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('admin','docente','estudiante','padre','soporte') COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tipo_documento` enum('CC','TI','CE','PP','NIT','OTRO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuarios_colegio` (`colegio_id`),
  CONSTRAINT `fk_usuarios_colegio` FOREIGN KEY (`colegio_id`) REFERENCES `colegios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,'admin','admin@colegio.edu.co','$2y$12$hj7oojRAq6Y2OA13BvueY.qncAQ7ekG/Y.MaIAv.eViYnMWjrNq1y','Administrador','Sistema',NULL,'admin',1,NULL,1,'2026-06-09 08:20:22','2026-05-30 08:30:33','2026-06-11 14:53:39',NULL,NULL),(2,1,'docente1','neyderflashh@gmail.com','$2y$12$s08Q6cuBOARTT3FUXSXmHu7f96.1NCLYZNZT.zODBJ7QbzmrDBwUa','Juan Carlos','Rodríguez','2.jpg','docente',0,NULL,1,'2026-06-11 16:30:22','2026-05-30 08:41:23','2026-06-11 16:30:22',NULL,NULL),(3,1,'padre.garcia','carlos.garcia@gmail.com','$2y$12$hj7oojRAq6Y2OA13BvueY.qncAQ7ekG/Y.MaIAv.eViYnMWjrNq1y','Carlos Alberto','García',NULL,'padre',0,NULL,1,'2026-06-11 15:35:01','2026-05-30 08:41:24','2026-06-11 15:35:01',NULL,NULL),(4,1,'madre.garcia','lucia.martinez@gmail.com','$2y$12$hj7oojRAq6Y2OA13BvueY.qncAQ7ekG/Y.MaIAv.eViYnMWjrNq1y','Lucía','Martínez',NULL,'padre',0,NULL,1,NULL,'2026-05-30 08:41:24','2026-05-30 08:41:24',NULL,NULL),(5,1,'sofia.garcia','sofia.garcia@colegio.edu.co','$2y$12$hj7oojRAq6Y2OA13BvueY.qncAQ7ekG/Y.MaIAv.eViYnMWjrNq1y','Sofía','García Martínez',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 08:41:24','2026-05-30 08:41:24',NULL,NULL),(6,1,'miguel.garcia','miguel.garcia@colegio.edu.co','$2y$12$hj7oojRAq6Y2OA13BvueY.qncAQ7ekG/Y.MaIAv.eViYnMWjrNq1y','Miguel Ángel','García Martínez',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 08:41:24','2026-05-30 08:41:24',NULL,NULL),(27,1,'carlos.morales.3a','carlos.morales.3a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Carlos','Morales Ríos',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(28,1,'isabella.ruiz.3a','isabella.ruiz.3a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Isabella','Ruiz Cárdenas',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(29,1,'santiago.vargas.4a','santiago.vargas.4a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Santiago','Vargas Peña',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(30,1,'valentina.castro.4a','valentina.castro.4a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Valentina','Castro Herrera',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(31,1,'sebastian.lopez.5a','sebastian.lopez.5a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Sebastián','López Díaz',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(32,1,'mariana.torres.5a','mariana.torres.5a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Mariana','Torres Salcedo',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(33,1,'andres.bermudez.6b','andres.bermudez.6b@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Andrés','Bermúdez Pinto',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(34,1,'juliana.pinto.6b','juliana.pinto.6b@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Juliana','Pinto Rojas',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(35,1,'nicolas.herrera.7a','nicolas.herrera.7a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Nicolás','Herrera Gómez',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(36,1,'daniela.sanchez.7a','daniela.sanchez.7a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Daniela','Sánchez Lozano',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(37,1,'felipe.cardona.7b','felipe.cardona.7b@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Felipe','Cardona Ospina',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(38,1,'sara.jimenez.7b','sara.jimenez.7b@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Sara','Jiménez Arango',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(39,1,'camilo.reyes.8a','camilo.reyes.8a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Camilo','Reyes Montoya',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(40,1,'natalia.gomez.8a','natalia.gomez.8a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Natalia','Gómez Suárez',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(41,1,'alejandro.diaz.9a','alejandro.diaz.9a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Alejandro','Díaz Rincón',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(42,1,'paula.ramirez.9a','paula.ramirez.9a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Paula','Ramírez Castaño',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(43,1,'mateo.silva.10a','mateo.silva.10a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Mateo','Silva Pineda',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(44,1,'valeria.mendoza.10a','valeria.mendoza.10a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Valeria','Mendoza Quiroga',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(45,1,'esteban.cortes.11a','esteban.cortes.11a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Esteban','Cortés Navarro',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL),(46,1,'luisa.pena.11a','luisa.pena.11a@test.com','$2y$10$7xrv7Z9x4PaIwvH/g0mdqOo4pF/f7yb9dRRy4MgPHp5u2/pEpnI7C','Luisa','Peña Villamizar',NULL,'estudiante',0,NULL,1,NULL,'2026-05-30 19:36:31','2026-05-30 19:36:31',NULL,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'plataforma_academica'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-11 16:31:15
