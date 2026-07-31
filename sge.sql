-- MySQL dump 10.13  Distrib 8.4.10, for Linux (x86_64)
--
-- Host: localhost    Database: sge_sdoca
-- ------------------------------------------------------
-- Server version	8.4.10-0ubuntu0.26.04.1

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
-- Table structure for table `agent_conversation_messages`
--

DROP TABLE IF EXISTS `agent_conversation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_conversation_messages` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversation_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_calls` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool_results` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `usage` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_index` (`conversation_id`,`user_id`,`updated_at`),
  KEY `agent_conversation_messages_user_id_index` (`user_id`),
  KEY `agent_conversation_messages_conversation_id_index` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_conversation_messages`
--

LOCK TABLES `agent_conversation_messages` WRITE;
/*!40000 ALTER TABLE `agent_conversation_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_conversation_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_conversations`
--

DROP TABLE IF EXISTS `agent_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_conversations` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_conversations_user_id_updated_at_index` (`user_id`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_conversations`
--

LOCK TABLES `agent_conversations` WRITE;
/*!40000 ALTER TABLE `agent_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alunos`
--

DROP TABLE IF EXISTS `alunos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alunos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matricula` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao` enum('activo','finalista','concluido','reprovado','desistente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alunos_matricula_unique` (`matricula`),
  KEY `alunos_inscricao_id_foreign` (`inscricao_id`),
  KEY `alunos_user_id_foreign` (`user_id`),
  CONSTRAINT `alunos_inscricao_id_foreign` FOREIGN KEY (`inscricao_id`) REFERENCES `inscricoes` (`id`),
  CONSTRAINT `alunos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alunos`
--

LOCK TABLES `alunos` WRITE;
/*!40000 ALTER TABLE `alunos` DISABLE KEYS */;
INSERT INTO `alunos` VALUES ('019fb2b4-b07f-708c-a158-2173fe4ec20a','019fb2b4-98b6-727e-ae5e-ce5b5681fdef','019fb2b4-b07b-718b-851c-7df72454f6c9','MAT-2026-0001','activo','2026-07-30 11:06:53','2026-07-30 11:06:53');
/*!40000 ALTER TABLE `alunos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ano_lectivos`
--

DROP TABLE IF EXISTS `ano_lectivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ano_lectivos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ano_lectivos`
--

LOCK TABLES `ano_lectivos` WRITE;
/*!40000 ALTER TABLE `ano_lectivos` DISABLE KEYS */;
INSERT INTO `ano_lectivos` VALUES ('019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2025/2026','2025-09-01','2026-07-31',1,1,NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `ano_lectivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avisos`
--

DROP TABLE IF EXISTS `avisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avisos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('aviso','evento','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aviso',
  `data` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinatario` enum('todos','alunos','professores') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'todos',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avisos_instituicao_id_foreign` (`instituicao_id`),
  CONSTRAINT `avisos_instituicao_id_foreign` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avisos`
--

LOCK TABLES `avisos` WRITE;
/*!40000 ALTER TABLE `avisos` DISABLE KEYS */;
/*!40000 ALTER TABLE `avisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banca_juri_pap`
--

DROP TABLE IF EXISTS `banca_juri_pap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banca_juri_pap` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo_pap_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `funcao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banca_juri_pap_professor_id_foreign` (`professor_id`),
  KEY `banca_juri_pap_grupo_pap_id_foreign` (`grupo_pap_id`),
  CONSTRAINT `banca_juri_pap_grupo_pap_id_foreign` FOREIGN KEY (`grupo_pap_id`) REFERENCES `grupo_pap` (`id`),
  CONSTRAINT `banca_juri_pap_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banca_juri_pap`
--

LOCK TABLES `banca_juri_pap` WRITE;
/*!40000 ALTER TABLE `banca_juri_pap` DISABLE KEYS */;
INSERT INTO `banca_juri_pap` VALUES ('019fb2fd-65b5-70ae-a9b7-8c11247e3538','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2fb-046d-7374-9342-1ba8fbd62b6b','Vogal 1','2026-07-30 12:26:18','2026-07-30 12:26:18'),('019fb2fd-8361-7219-a26f-81ce402e13c6','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2fb-046d-7374-9342-1ba8fbd62b6b','Presidente','2026-07-30 12:26:26','2026-07-30 12:26:26'),('019fb2fd-a06e-7319-a69f-e54d97994d05','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2fb-046d-7374-9342-1ba8fbd62b6b','Vogal 2','2026-07-30 12:26:33','2026-07-30 12:26:33');
/*!40000 ALTER TABLE `banca_juri_pap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('sge-cache-322309801bf1df365d70e65d50e602c0','i:1;',1785408180),('sge-cache-322309801bf1df365d70e65d50e602c0:timer','i:1785408180;',1785408180),('sge-cache-6bbf64272b7149532d9f21e9be49a456','i:1;',1785413422),('sge-cache-6bbf64272b7149532d9f21e9be49a456:timer','i:1785413422;',1785413422),('sge-cache-822faf12b0c18a55133d1cda506ae4fb','i:1;',1785413432),('sge-cache-822faf12b0c18a55133d1cda506ae4fb:timer','i:1785413432;',1785413432),('sge-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:108:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:17:\"instituicoes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:19:\"instituicoes.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:5;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:14:\"alunos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"alunos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:13:\"alunos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"alunos.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:19:\"cursoclasse.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:16:\"cursoclasse.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:18:\"cursoclasse.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:18:\"cursoclasse.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:18:\"cursoclasse.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:24:\"cursoclasseturno.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:21:\"cursoclasseturno.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:23:\"cursoclasseturno.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:23:\"cursoclasseturno.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:23:\"cursoclasseturno.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:14:\"turnos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:11:\"turnos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:18;a:3:{s:1:\"a\";i:19;s:1:\"b\";s:13:\"turnos.create\";s:1:\"c\";s:3:\"web\";}i:19;a:3:{s:1:\"a\";i:20;s:1:\"b\";s:13:\"turnos.update\";s:1:\"c\";s:3:\"web\";}i:20;a:3:{s:1:\"a\";i:21;s:1:\"b\";s:13:\"turnos.delete\";s:1:\"c\";s:3:\"web\";}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"turmas.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:11:\"turmas.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:13:\"turmas.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:13:\"turmas.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:13:\"turmas.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:29:\"classeturnodisciplina.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:26:\"classeturnodisciplina.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:28:\"classeturnodisciplina.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:28:\"classeturnodisciplina.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:28:\"classeturnodisciplina.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:14:\"pautas.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:11:\"pautas.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:12:\"notas.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:5;i:3;i:6;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:12:\"notas.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:5;i:3;i:6;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:13:\"notas.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:5;i:1;i:7;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:14:\"grelha.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:7;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:19:\"professores.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:16:\"professores.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:18:\"professores.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:18:\"professores.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:18:\"professores.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:14:\"avisos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:11:\"avisos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:13:\"avisos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:13:\"avisos.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:13:\"avisos.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:18:\"inscricoes.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:15:\"inscricoes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:17:\"inscricoes.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:17:\"inscricoes.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:51;a:3:{s:1:\"a\";i:52;s:1:\"b\";s:17:\"inscricoes.delete\";s:1:\"c\";s:3:\"web\";}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:16:\"grupopap.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:13:\"grupopap.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:15:\"grupopap.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:15:\"grupopap.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:15:\"grupopap.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:20:\"grupopap.definirData\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:20:\"bancajuripap.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:17:\"bancajuripap.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:19:\"bancajuripap.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:19:\"bancajuripap.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:5;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:19:\"bancajuripap.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:24:\"elementogrupopap.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:21:\"elementogrupopap.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:23:\"elementogrupopap.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:23:\"elementogrupopap.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:23:\"elementogrupopap.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:30:\"elementogrupopap.atualizarNota\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:5;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:14:\"cursos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:11:\"cursos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:13:\"cursos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:13:\"cursos.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:13:\"cursos.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:15:\"classes.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:12:\"classes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:14:\"classes.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:14:\"classes.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:14:\"classes.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:22:\"curso-tutelado.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:19:\"curso-tutelado.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:21:\"curso-tutelado.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:21:\"curso-tutelado.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:5;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:21:\"curso-tutelado.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:12:\"notas.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;i:4;i:6;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:18:\"utilizadores.gerir\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:15:\"acessos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:14:\"acessos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:15:\"relatorios.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:18:\"pagamentos.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:15:\"pagamentos.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:17:\"pagamentos.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:17:\"pagamentos.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:17:\"pagamentos.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:21:\"itemspagaveis.viewAny\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:18:\"itemspagaveis.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:20:\"itemspagaveis.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:20:\"itemspagaveis.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:20:\"itemspagaveis.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:99;a:3:{s:1:\"a\";i:100;s:1:\"b\";s:22:\"coordenador.view-curso\";s:1:\"c\";s:3:\"web\";}i:100;a:3:{s:1:\"a\";i:101;s:1:\"b\";s:24:\"coordenador.update-curso\";s:1:\"c\";s:3:\"web\";}i:101;a:3:{s:1:\"a\";i:102;s:1:\"b\";s:30:\"coordenador.manage-professores\";s:1:\"c\";s:3:\"web\";}i:102;a:3:{s:1:\"a\";i:103;s:1:\"b\";s:25:\"coordenador.manage-turmas\";s:1:\"c\";s:3:\"web\";}i:103;a:3:{s:1:\"a\";i:104;s:1:\"b\";s:23:\"coordenador.view-pautas\";s:1:\"c\";s:3:\"web\";}i:104;a:3:{s:1:\"a\";i:105;s:1:\"b\";s:25:\"coordenador.update-pautas\";s:1:\"c\";s:3:\"web\";}i:105;a:3:{s:1:\"a\";i:106;s:1:\"b\";s:24:\"coordenador.create-notas\";s:1:\"c\";s:3:\"web\";}i:106;a:3:{s:1:\"a\";i:107;s:1:\"b\";s:24:\"coordenador.update-notas\";s:1:\"c\";s:3:\"web\";}i:107;a:3:{s:1:\"a\";i:108;s:1:\"b\";s:27:\"coordenador.view-relatorios\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:6:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:8:\"Director\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:11:\"Subdirector\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:10:\"Secretaria\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:11:\"Coordenador\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:9:\"Professor\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:5:\"Aluno\";s:1:\"c\";s:3:\"web\";}}}',1785496166);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidatos`
--

DROP TABLE IF EXISTS `candidatos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidatos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_estudante` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `morada` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `candidatos_bi_unique` (`bi`),
  UNIQUE KEY `candidatos_numero_estudante_unique` (`numero_estudante`),
  UNIQUE KEY `candidatos_email_unique` (`email`),
  KEY `candidatos_user_id_foreign` (`user_id`),
  CONSTRAINT `candidatos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidatos`
--

LOCK TABLES `candidatos` WRITE;
/*!40000 ALTER TABLE `candidatos` DISABLE KEYS */;
INSERT INTO `candidatos` VALUES ('019fb2b4-98b2-7006-9da4-3dbf3b91cdae',NULL,'Paulina Capitão','LA454I5409553','202600','+244935001358','capitaopaulinafernando@gmail.com',NULL,'2026-07-30 11:06:47','2026-07-30 11:06:47');
/*!40000 ALTER TABLE `candidatos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classe_turno_disciplina`
--

DROP TABLE IF EXISTS `classe_turno_disciplina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_turno_disciplina` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_classe_turno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disciplina_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carga_horaria` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ano_lectivo_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tem_professor` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cct_disc_al_unique` (`curso_classe_turno_id`,`disciplina_id`,`ano_lectivo_id`),
  KEY `classe_turno_disciplina_disciplina_id_foreign` (`disciplina_id`),
  KEY `classe_turno_disciplina_ano_lectivo_id_foreign` (`ano_lectivo_id`),
  CONSTRAINT `classe_turno_disciplina_ano_lectivo_id_foreign` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `ano_lectivos` (`id`),
  CONSTRAINT `classe_turno_disciplina_curso_classe_turno_id_foreign` FOREIGN KEY (`curso_classe_turno_id`) REFERENCES `curso_classe_turno` (`id`),
  CONSTRAINT `classe_turno_disciplina_disciplina_id_foreign` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classe_turno_disciplina`
--

LOCK TABLES `classe_turno_disciplina` WRITE;
/*!40000 ALTER TABLE `classe_turno_disciplina` DISABLE KEYS */;
INSERT INTO `classe_turno_disciplina` VALUES ('019fb2b8-da29-7106-bd55-c107df6638a7','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-8718-7095-ae38-7b5cbeb84a65',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:29:36'),('019fb2b8-da32-7371-9b04-452665052b91','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-8700-7153-a4bc-2991438969e1',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:11:36'),('019fb2b8-da37-7230-bc7b-5bb1071f95a7','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-8705-7051-92ed-b3fe186d7726',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:11:43'),('019fb2b8-da3b-7230-9aee-e65d36fb86d0','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-870e-715c-a81a-e66f63b28042',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:11:59'),('019fb2b8-da3f-73f6-8819-7a1bfffc80e2','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-870a-70e4-af40-c879b6228e11',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:11:52'),('019fb2b8-da42-72d0-a699-e1e3459c81f1','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-871b-7110-af3d-dcbeb3495725',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:12:18'),('019fb2b8-da46-7223-a43d-3cdae153c7ef','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-8711-734a-8eeb-4ca22b348a19',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:12:07'),('019fb2b8-da49-73c6-a746-fd7fc2451fdc','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-871e-71ef-a6a3-aeaaacd9ec30',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:12:28'),('019fb2b8-da4d-72a2-9f32-27f7c96768ca','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-872d-723e-930f-ea54be5bc9d3',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:11:26','2026-07-30 11:12:39'),('019fb2c8-1fe6-70ee-acea-0ea6d0fc17ac','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-8718-7095-ae38-7b5cbeb84a65',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:35'),('019fb2c8-1fef-7153-8724-af9fce13bc38','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-8700-7153-a4bc-2991438969e1',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:08'),('019fb2c8-1ff3-73f2-841d-7da9cdeff44c','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-8705-7051-92ed-b3fe186d7726',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:15'),('019fb2c8-1ff7-7180-aeb0-3da16a0a1155','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-870e-715c-a81a-e66f63b28042',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:27'),('019fb2c8-1ffb-71bc-a098-ddabbe4dd2f3','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-870a-70e4-af40-c879b6228e11',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:21'),('019fb2c8-1ffe-7296-b42d-2a63ce6fcfe7','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-872a-7365-bf49-1a0e05857e22',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:41:35'),('019fb2c8-2002-7294-8ad0-5f6bfe076a9b','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-871b-7110-af3d-dcbeb3495725',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:47'),('019fb2c8-2008-70c7-a12f-8abaec914c9f','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-8725-71a6-89a5-baac51cb3b41',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:41:23'),('019fb2c8-200d-7061-b93f-b872fb85db1f','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-8722-7159-a980-119652d2bdb6',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:41:08'),('019fb2c8-2010-70b8-aab7-eb9a3814522a','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-871e-71ef-a6a3-aeaaacd9ec30',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:40:57'),('019fb2c8-2013-70ce-ba7d-c3659fa74ee7','019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-872d-723e-930f-ea54be5bc9d3',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 11:28:07','2026-07-30 11:46:01'),('019fb2f2-ce82-73e4-b486-c1802fe83cc0','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-8705-7051-92ed-b3fe186d7726',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:14:51'),('019fb2f2-ce8b-72d3-b08b-158620c556da','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-8731-712d-87fa-5950cf357a2a',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:26'),('019fb2f2-ce8f-705f-957a-155f8018395c','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-871b-7110-af3d-dcbeb3495725',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:08'),('019fb2f2-ce94-7338-b338-e67b7fa983cf','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-870a-70e4-af40-c879b6228e11',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:14:56'),('019fb2f2-ce98-7211-b22a-20cb535b3c95','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-872a-7365-bf49-1a0e05857e22',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:15'),('019fb2f2-ce9b-7055-9ddf-50c6d82e2cce','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-8734-726d-85dc-42ba5e82dcad',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:36'),('019fb2f2-ce9e-71af-b836-151bf8c46231','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-870e-715c-a81a-e66f63b28042',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:02'),('019fb2f2-cea3-70d4-b591-ebf11ac88c68','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-873a-7370-981d-8bf3878de3d1',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:58'),('019fb2f2-cea7-7308-b0f0-16c42a36bee3','019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-8737-70d8-aa49-af78d171f434',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:14:44','2026-07-30 12:15:44'),('019fb2fa-6acc-7248-ad8a-97476a69cde4','019fb2b2-ea81-718f-b855-8527e9f80395','019fb29c-8741-71bf-bbd0-090c7df523f1',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:23:03','2026-07-30 12:23:20'),('019fb2fa-6ad5-7256-b971-f204147b4123','019fb2b2-ea81-718f-b855-8527e9f80395','019fb29c-8737-70d8-aa49-af78d171f434',NULL,'019fb29c-86ac-70d7-a7fc-92cdd6fc3c49',1,'2026-07-30 12:23:03','2026-07-30 12:23:13');
/*!40000 ALTER TABLE `classe_turno_disciplina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classe_turno_disciplina_horarios`
--

DROP TABLE IF EXISTS `classe_turno_disciplina_horarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classe_turno_disciplina_horarios` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_turno_disciplina_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dia_semana` tinyint NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ctdh_ctd_dia` (`classe_turno_disciplina_id`,`dia_semana`),
  CONSTRAINT `fk_ctdh_ctd` FOREIGN KEY (`classe_turno_disciplina_id`) REFERENCES `classe_turno_disciplina` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classe_turno_disciplina_horarios`
--

LOCK TABLES `classe_turno_disciplina_horarios` WRITE;
/*!40000 ALTER TABLE `classe_turno_disciplina_horarios` DISABLE KEYS */;
INSERT INTO `classe_turno_disciplina_horarios` VALUES ('019fb2d0-5ed1-7316-8b94-c52674c969f7','019fb2b8-da29-7106-bd55-c107df6638a7',1,'08:00:00','09:30:00','2026-07-30 11:37:08','2026-07-30 11:37:08'),('019fb2d0-5ed2-7016-b2ad-26638837a56b','019fb2b8-da29-7106-bd55-c107df6638a7',2,'08:00:00','09:30:00','2026-07-30 11:37:08','2026-07-30 11:37:08'),('019fb2d0-5ed2-7016-b2ad-266388416655','019fb2b8-da29-7106-bd55-c107df6638a7',3,'08:00:00','09:30:00','2026-07-30 11:37:08','2026-07-30 11:37:08'),('019fb2d0-5ed2-7016-b2ad-266388ddaec7','019fb2b8-da29-7106-bd55-c107df6638a7',4,'08:00:00','09:30:00','2026-07-30 11:37:08','2026-07-30 11:37:08'),('019fb2d0-5ed2-7016-b2ad-266389213660','019fb2b8-da29-7106-bd55-c107df6638a7',5,'08:00:00','09:30:00','2026-07-30 11:37:08','2026-07-30 11:37:08'),('019fb2d0-c6c1-70f4-94b1-0282d52ce4c4','019fb2b8-da37-7230-bc7b-5bb1071f95a7',1,'08:00:00','09:30:00','2026-07-30 11:37:34','2026-07-30 11:37:34'),('019fb2d0-c6c2-732f-bd3d-b80a85b7f30d','019fb2b8-da37-7230-bc7b-5bb1071f95a7',2,'08:00:00','09:30:00','2026-07-30 11:37:34','2026-07-30 11:37:34'),('019fb2d0-c6c2-732f-bd3d-b80a868bb9e6','019fb2b8-da37-7230-bc7b-5bb1071f95a7',3,'08:00:00','09:30:00','2026-07-30 11:37:34','2026-07-30 11:37:34'),('019fb2d0-c6c2-732f-bd3d-b80a87010071','019fb2b8-da37-7230-bc7b-5bb1071f95a7',4,'08:00:00','09:30:00','2026-07-30 11:37:34','2026-07-30 11:37:34'),('019fb2d0-c6c2-732f-bd3d-b80a87e10ef3','019fb2b8-da37-7230-bc7b-5bb1071f95a7',5,'08:00:00','09:30:00','2026-07-30 11:37:34','2026-07-30 11:37:34');
/*!40000 ALTER TABLE `classe_turno_disciplina_horarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_ensino` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emite_certificado` tinyint(1) NOT NULL DEFAULT '0',
  `tipo_certificado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordem` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES ('019fb29c-86b3-706d-992e-060362d09a3c','Pré-escolar','Pré-escolar',1,'Certificado de Conclusão do Pré-escolar',0,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86b7-7214-a105-c214877b9ec9','1ª','Ensino Básico',0,NULL,1,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86bb-73dd-ad2c-c1c7b64622ce','2ª','Ensino Básico',0,NULL,2,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86bf-726c-8be1-2ab8b1055add','3ª','Ensino Básico',0,NULL,3,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86c2-70ec-a6ee-9830e339dc84','4ª','Ensino Básico',0,NULL,4,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86c6-7073-9b18-d9d3e85da576','5ª','Ensino Básico',0,NULL,5,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86c9-7086-aaf1-274682fed0b5','6ª','Ensino Básico',1,'Diploma do Ensino Básico',6,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86cd-7345-8289-8d7e5fe3a2c5','7ª','Ensino Básico',0,NULL,7,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86d1-731d-a6c4-21773aee9216','8ª','Ensino Básico',0,NULL,8,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86d5-700e-95cf-825f8e46d994','9ª','Ensino Básico',1,'Diploma do Ensino Básico',9,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86d9-71ef-a847-82020e739993','10ª','Ensino Secundário',0,NULL,10,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86dd-7298-87e7-6ce9c39f87c4','11ª','Ensino Secundário',0,NULL,11,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86e0-7167-b214-09f15f47c7a7','12ª','Ensino Secundário',0,NULL,12,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86e4-701c-a183-e7c7df70a39c','13ª','Ensino Secundário',1,'Diploma do Ensino Secundário',13,'2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_classe`
--

DROP TABLE IF EXISTS `curso_classe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_classe` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_tutelado_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_ensino_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curso_classe_curso_tutelado_id_foreign` (`curso_tutelado_id`),
  KEY `curso_classe_classe_id_foreign` (`classe_id`),
  KEY `curso_classe_nivel_ensino_id_foreign` (`nivel_ensino_id`),
  CONSTRAINT `curso_classe_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `curso_classe_curso_tutelado_id_foreign` FOREIGN KEY (`curso_tutelado_id`) REFERENCES `curso_tutelado` (`id`),
  CONSTRAINT `curso_classe_nivel_ensino_id_foreign` FOREIGN KEY (`nivel_ensino_id`) REFERENCES `niveis_ensino` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_classe`
--

LOCK TABLES `curso_classe` WRITE;
/*!40000 ALTER TABLE `curso_classe` DISABLE KEYS */;
INSERT INTO `curso_classe` VALUES ('019fb2ab-a56a-7353-820d-724671e6b911','019fb2ab-a569-7058-bf0e-f62fed295905','019fb29c-86d9-71ef-a847-82020e739993','019fb29c-8750-732b-9a00-bca094068939','2026-07-30 10:57:01','2026-07-30 10:57:01'),('019fb2ab-a56a-7353-820d-724672542ec4','019fb2ab-a569-7058-bf0e-f62fed295905','019fb29c-86dd-7298-87e7-6ce9c39f87c4','019fb29c-8750-732b-9a00-bca094068939','2026-07-30 10:57:01','2026-07-30 10:57:01'),('019fb2ab-a56a-7353-820d-724672ff9d6b','019fb2ab-a569-7058-bf0e-f62fed295905','019fb29c-86e0-7167-b214-09f15f47c7a7','019fb29c-8750-732b-9a00-bca094068939','2026-07-30 10:57:01','2026-07-30 10:57:01'),('019fb2ab-a56a-7353-820d-72467324d5dc','019fb2ab-a569-7058-bf0e-f62fed295905','019fb29c-86e4-701c-a183-e7c7df70a39c','019fb29c-8750-732b-9a00-bca094068939','2026-07-30 10:57:01','2026-07-30 10:57:01');
/*!40000 ALTER TABLE `curso_classe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_classe_turno`
--

DROP TABLE IF EXISTS `curso_classe_turno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_classe_turno` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_classe_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curso_classe_turno_turno_id_foreign` (`turno_id`),
  KEY `curso_classe_turno_curso_classe_id_foreign` (`curso_classe_id`),
  CONSTRAINT `curso_classe_turno_curso_classe_id_foreign` FOREIGN KEY (`curso_classe_id`) REFERENCES `curso_classe` (`id`),
  CONSTRAINT `curso_classe_turno_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_classe_turno`
--

LOCK TABLES `curso_classe_turno` WRITE;
/*!40000 ALTER TABLE `curso_classe_turno` DISABLE KEYS */;
INSERT INTO `curso_classe_turno` VALUES ('019fb2ab-c0ee-71ce-97a5-5e89782a8104','019fb29c-86ec-71a4-a886-3a2fd3f6f5e3','019fb2ab-a56a-7353-820d-724671e6b911','2026-07-30 10:57:08','2026-07-30 10:57:08'),('019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-86f0-70bd-94f2-1dd55337c063','019fb2ab-a56a-7353-820d-724671e6b911','2026-07-30 10:57:08','2026-07-30 10:57:08'),('019fb2ab-c0f8-71b9-81ae-9dea93001135','019fb29c-86f5-7035-a800-fcf50c87a581','019fb2ab-a56a-7353-820d-724671e6b911','2026-07-30 10:57:08','2026-07-30 10:57:08'),('019fb2ac-5355-7173-8951-32fc13fd9189','019fb29c-86ec-71a4-a886-3a2fd3f6f5e3','019fb2ab-a56a-7353-820d-724672542ec4','2026-07-30 10:57:45','2026-07-30 10:57:45'),('019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-86f0-70bd-94f2-1dd55337c063','019fb2ab-a56a-7353-820d-724672542ec4','2026-07-30 10:57:45','2026-07-30 10:57:45'),('019fb2ac-536b-707c-9bb0-df5562b2cbb9','019fb29c-86f5-7035-a800-fcf50c87a581','019fb2ab-a56a-7353-820d-724672542ec4','2026-07-30 10:57:45','2026-07-30 10:57:45'),('019fb2ac-74dd-7170-a1db-1cd0e491e689','019fb29c-86ec-71a4-a886-3a2fd3f6f5e3','019fb2ab-a56a-7353-820d-724672ff9d6b','2026-07-30 10:57:54','2026-07-30 10:57:54'),('019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-86f0-70bd-94f2-1dd55337c063','019fb2ab-a56a-7353-820d-724672ff9d6b','2026-07-30 10:57:54','2026-07-30 10:57:54'),('019fb2ac-74eb-738c-8a76-9d9c6d042dec','019fb29c-86f5-7035-a800-fcf50c87a581','019fb2ab-a56a-7353-820d-724672ff9d6b','2026-07-30 10:57:54','2026-07-30 10:57:54'),('019fb2b2-ea78-7266-bd6c-711a7e377bb1','019fb29c-86ec-71a4-a886-3a2fd3f6f5e3','019fb2ab-a56a-7353-820d-72467324d5dc','2026-07-30 11:04:57','2026-07-30 11:04:57'),('019fb2b2-ea81-718f-b855-8527e9f80395','019fb29c-86f0-70bd-94f2-1dd55337c063','019fb2ab-a56a-7353-820d-72467324d5dc','2026-07-30 11:04:57','2026-07-30 11:04:57'),('019fb2b2-ea85-71a1-8fc5-a201ec13e62e','019fb29c-86f5-7035-a800-fcf50c87a581','019fb2ab-a56a-7353-820d-72467324d5dc','2026-07-30 11:04:57','2026-07-30 11:04:57');
/*!40000 ALTER TABLE `curso_classe_turno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_tutelado`
--

DROP TABLE IF EXISTS `curso_tutelado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_tutelado` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_curso_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_tutora_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curso_tutelado_instituicao_curso_id_foreign` (`instituicao_curso_id`),
  KEY `curso_tutelado_instituicao_tutora_id_foreign` (`instituicao_tutora_id`),
  CONSTRAINT `curso_tutelado_instituicao_curso_id_foreign` FOREIGN KEY (`instituicao_curso_id`) REFERENCES `instituicao_curso` (`id`),
  CONSTRAINT `curso_tutelado_instituicao_tutora_id_foreign` FOREIGN KEY (`instituicao_tutora_id`) REFERENCES `instituicoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_tutelado`
--

LOCK TABLES `curso_tutelado` WRITE;
/*!40000 ALTER TABLE `curso_tutelado` DISABLE KEYS */;
INSERT INTO `curso_tutelado` VALUES ('019fb2ab-a569-7058-bf0e-f62fed295905','019fb2ab-a565-70db-8860-b0b5b47e9496','019fb29c-8699-719d-a7f9-81009814c279','2026-07-30 10:57:01','2026-07-30 10:57:01');
/*!40000 ALTER TABLE `curso_tutelado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_tutelado_professor`
--

DROP TABLE IF EXISTS `curso_tutelado_professor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_tutelado_professor` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_tutelado_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('principal','colaborador') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'colaborador',
  `coordenador` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curso_tutelado_professor_curso_tutelado_id_professor_id_unique` (`curso_tutelado_id`,`professor_id`),
  KEY `curso_tutelado_professor_professor_id_foreign` (`professor_id`),
  CONSTRAINT `curso_tutelado_professor_curso_tutelado_id_foreign` FOREIGN KEY (`curso_tutelado_id`) REFERENCES `curso_tutelado` (`id`) ON DELETE CASCADE,
  CONSTRAINT `curso_tutelado_professor_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_tutelado_professor`
--

LOCK TABLES `curso_tutelado_professor` WRITE;
/*!40000 ALTER TABLE `curso_tutelado_professor` DISABLE KEYS */;
INSERT INTO `curso_tutelado_professor` VALUES ('019fb2b6-e37f-72ba-bb68-9e0f9171111f','019fb2ab-a569-7058-bf0e-f62fed295905','019fb2b5-042a-73da-9e67-e1342618ed14','principal',0,'2026-07-30 11:09:18','2026-07-30 11:09:18'),('019fb2b7-0567-72f1-bfe9-6fb65957df9a','019fb2ab-a569-7058-bf0e-f62fed295905','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','principal',1,'2026-07-30 11:09:26','2026-07-30 11:09:26'),('019fb2b7-23e3-72b4-804f-32787e7e3aa2','019fb2ab-a569-7058-bf0e-f62fed295905','019fb2b5-70a7-7305-b489-e5d9c36ec44c','principal',0,'2026-07-30 11:09:34','2026-07-30 11:09:34');
/*!40000 ALTER TABLE `curso_tutelado_professor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `duracao_anos` int NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES ('019fb2ab-a558-721c-9a78-1be6111bb53a','Instituto Médio Comercial De Luanda',NULL,4,1,'2026-07-30 10:57:01','2026-07-30 10:57:01');
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disciplinas`
--

DROP TABLE IF EXISTS `disciplinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disciplinas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `componente` enum('sociocultural','cientifica','tecnica') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disciplinas`
--

LOCK TABLES `disciplinas` WRITE;
/*!40000 ALTER TABLE `disciplinas` DISABLE KEYS */;
INSERT INTO `disciplinas` VALUES ('019fb29c-8700-7153-a4bc-2991438969e1','Inglês','L.Inglesa','sociocultural','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8705-7051-92ed-b3fe186d7726','Matemática','MAT','cientifica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-870a-70e4-af40-c879b6228e11','Tecnologias de Informação e Comunicação','TIC','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-870e-715c-a81a-e66f63b28042','Técnicas e Linguagem de programação','TLP','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8711-734a-8eeb-4ca22b348a19','Base de Dados','BD','cientifica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8718-7095-ae38-7b5cbeb84a65','Lingua Portuguesa','L.Portuguesa','sociocultural','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-871b-7110-af3d-dcbeb3495725','Organização e Administração de Empresas','OAE','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-871e-71ef-a6a3-aeaaacd9ec30','Formação de Atitudes Integradoras','FAI','sociocultural','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8722-7159-a980-119652d2bdb6','Nocões de Direito','ND','cientifica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8725-71a6-89a5-baac51cb3b41','Redes de Computadores','RC','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-872a-7365-bf49-1a0e05857e22','Informática Aplicada à Gestão','IAG','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-872d-723e-930f-ea54be5bc9d3','Educação Física','Ed.Fisica','sociocultural','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8731-712d-87fa-5950cf357a2a','Sistemas de Informação','SI','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8734-726d-85dc-42ba5e82dcad','Empreendedorismo','EMPREEN','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8737-70d8-aa49-af78d171f434','Projeto Tecnológico','PT','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-873a-7370-981d-8bf3878de3d1','Instalação e Manutenção de Equipamentos Informáticos','IMEI','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-873e-7186-a77a-d7ccf473ccb8','Prova de Aptidão Profissional','PAP','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8741-71bf-bbd0-090c7df523f1','Estágio Curricular Supervisionado','ECS','tecnica','2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `disciplinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `elementos_grupo_pap`
--

DROP TABLE IF EXISTS `elementos_grupo_pap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elementos_grupo_pap` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo_pap_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nota_individual` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `elementos_grupo_pap_grupo_pap_id_foreign` (`grupo_pap_id`),
  KEY `elementos_grupo_pap_aluno_id_foreign` (`aluno_id`),
  CONSTRAINT `elementos_grupo_pap_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  CONSTRAINT `elementos_grupo_pap_grupo_pap_id_foreign` FOREIGN KEY (`grupo_pap_id`) REFERENCES `grupo_pap` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `elementos_grupo_pap`
--

LOCK TABLES `elementos_grupo_pap` WRITE;
/*!40000 ALTER TABLE `elementos_grupo_pap` DISABLE KEYS */;
INSERT INTO `elementos_grupo_pap` VALUES ('019fb2fb-0476-72b9-9fb7-67173c6ae517','019fb2fb-046d-7374-9342-1ba8fbd62b6b','019fb2b4-b07f-708c-a158-2173fe4ec20a',17.00,'2026-07-30 12:23:42','2026-07-30 12:27:19');
/*!40000 ALTER TABLE `elementos_grupo_pap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_pap`
--

DROP TABLE IF EXISTS `grupo_pap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_pap` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_tutor_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_grupo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tema_grupo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_aprovacao` enum('pendente','aprovado','reprovado','melhoria-solicitada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `aprovado_por_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `comentario_aprovacao` text COLLATE utf8mb4_unicode_ci,
  `estudo_caso` text COLLATE utf8mb4_unicode_ci,
  `trabalho_grupo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pendente','em-andamento','concluido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `nota_final` decimal(5,2) DEFAULT NULL,
  `data_defesa` datetime DEFAULT NULL,
  `local_defesa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grupo_pap_turma_id_foreign` (`turma_id`),
  KEY `grupo_pap_professor_tutor_id_foreign` (`professor_tutor_id`),
  KEY `grupo_pap_aprovado_por_id_foreign` (`aprovado_por_id`),
  KEY `grupo_pap_status_aprovacao_index` (`status_aprovacao`),
  CONSTRAINT `grupo_pap_aprovado_por_id_foreign` FOREIGN KEY (`aprovado_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grupo_pap_professor_tutor_id_foreign` FOREIGN KEY (`professor_tutor_id`) REFERENCES `professores` (`id`),
  CONSTRAINT `grupo_pap_turma_id_foreign` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_pap`
--

LOCK TABLES `grupo_pap` WRITE;
/*!40000 ALTER TABLE `grupo_pap` DISABLE KEYS */;
INSERT INTO `grupo_pap` VALUES ('019fb2fb-046d-7374-9342-1ba8fbd62b6b','019fb2b3-0cca-737b-850c-e8b3d2b39a73','019fb2b5-042a-73da-9e67-e1342618ed14','Grupo I','Criação de um sistema de recrutamento de pessoal doméstico','aprovado','019fb29c-8856-70ce-9a8d-ca47027168d5','2026-07-30 12:25:48',NULL,'Sociedade Angolana',NULL,'concluido',NULL,'2026-07-30 13:27:00','21, Comercial.','2026-07-30 12:23:42','2026-07-30 12:27:19');
/*!40000 ALTER TABLE `grupo_pap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_aprovacao_pap`
--

DROP TABLE IF EXISTS `historico_aprovacao_pap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_aprovacao_pap` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo_pap_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `utilizador_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tema` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_anterior` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_novo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historico_aprovacao_pap_grupo_pap_id_foreign` (`grupo_pap_id`),
  KEY `historico_aprovacao_pap_utilizador_id_foreign` (`utilizador_id`),
  CONSTRAINT `historico_aprovacao_pap_grupo_pap_id_foreign` FOREIGN KEY (`grupo_pap_id`) REFERENCES `grupo_pap` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_aprovacao_pap_utilizador_id_foreign` FOREIGN KEY (`utilizador_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_aprovacao_pap`
--

LOCK TABLES `historico_aprovacao_pap` WRITE;
/*!40000 ALTER TABLE `historico_aprovacao_pap` DISABLE KEYS */;
INSERT INTO `historico_aprovacao_pap` VALUES ('019fb2fc-ed75-710f-9a30-fd18a90f852c','019fb2fb-046d-7374-9342-1ba8fbd62b6b','019fb29c-8856-70ce-9a8d-ca47027168d5','Criação de um sistema de recrutamento de pessoal doméstico','pendente','aprovado',NULL,'2026-07-30 12:25:48','2026-07-30 12:25:48');
/*!40000 ALTER TABLE `historico_aprovacao_pap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscricoes`
--

DROP TABLE IF EXISTS `inscricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscricoes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_classe_turno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `candidato_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pendente','apto_prova','aprovado','reprovado','reprovado_prova') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `nota_teste` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ano_lectivo_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inscricoes_curso_classe_turno_id_foreign` (`curso_classe_turno_id`),
  KEY `inscricoes_candidato_id_foreign` (`candidato_id`),
  KEY `inscricoes_ano_lectivo_id_foreign` (`ano_lectivo_id`),
  CONSTRAINT `inscricoes_ano_lectivo_id_foreign` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `ano_lectivos` (`id`),
  CONSTRAINT `inscricoes_candidato_id_foreign` FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`),
  CONSTRAINT `inscricoes_curso_classe_turno_id_foreign` FOREIGN KEY (`curso_classe_turno_id`) REFERENCES `curso_classe_turno` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscricoes`
--

LOCK TABLES `inscricoes` WRITE;
/*!40000 ALTER TABLE `inscricoes` DISABLE KEYS */;
INSERT INTO `inscricoes` VALUES ('019fb2b4-98b6-727e-ae5e-ce5b5681fdef','019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb2b4-98b2-7006-9da4-3dbf3b91cdae','aprovado','16','019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2026-07-30 11:06:47','2026-07-30 11:06:53');
/*!40000 ALTER TABLE `inscricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instituicao_curso`
--

DROP TABLE IF EXISTS `instituicao_curso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instituicao_curso` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duracao_anos` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instituicao_curso_curso_id_foreign` (`curso_id`),
  KEY `instituicao_curso_instituicao_id_foreign` (`instituicao_id`),
  CONSTRAINT `instituicao_curso_curso_id_foreign` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  CONSTRAINT `instituicao_curso_instituicao_id_foreign` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instituicao_curso`
--

LOCK TABLES `instituicao_curso` WRITE;
/*!40000 ALTER TABLE `instituicao_curso` DISABLE KEYS */;
INSERT INTO `instituicao_curso` VALUES ('019fb2ab-a565-70db-8860-b0b5b47e9496','019fb2ab-a558-721c-9a78-1be6111bb53a','019fb29c-8699-719d-a7f9-81009814c279',4,'2026-07-30 10:57:01','2026-07-30 10:57:01');
/*!40000 ALTER TABLE `instituicao_curso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instituicoes`
--

DROP TABLE IF EXISTS `instituicoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instituicoes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sigla` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('instituto','colegio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instituto',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instituicoes`
--

LOCK TABLES `instituicoes` WRITE;
/*!40000 ALTER TABLE `instituicoes` DISABLE KEYS */;
INSERT INTO `instituicoes` VALUES ('019fb29c-8699-719d-a7f9-81009814c279','Instituto Médio Comercial De Luanda','IMCL','instituto','imcl@imcl.ao','923000000','Luanda','Primeiro de Maio, Luanda, Angola',NULL,1,'Instituto Médio Comercial De Luanda',NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-869e-73c1-87ab-76e0d19527cb','Escola Secundária Modelo','ESM','colegio','geral@escola-modelo.ao','923000002','Benguela','Benguela, Angola',NULL,1,'Escola Secundária Modelo',NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86a2-7387-bc69-0f5f36aff26f','Colegio Universitário de Angola','CUA','colegio','info@universidade-demo.ao','923000003','Huambo','Huambo, Angola',NULL,1,'Colegio Universitário de Angola',NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86a7-73f9-bf94-242a0edc02f5','Complexo Escolar Luz da Sabedoria','lS','colegio','info@luzdasabedoria.ao','923000003','luanda','Luanda, Samba',NULL,1,'Complexo Escolar Luz da Sabedoria',NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `instituicoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_pagaveis`
--

DROP TABLE IF EXISTS `itens_pagaveis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itens_pagaveis` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `curso_classe_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `valor` decimal(10,2) NOT NULL,
  `frequencia` enum('mensal','anual','unico') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `itens_pagaveis_instituicao_id_foreign` (`instituicao_id`),
  KEY `itens_pagaveis_curso_classe_id_foreign` (`curso_classe_id`),
  CONSTRAINT `itens_pagaveis_curso_classe_id_foreign` FOREIGN KEY (`curso_classe_id`) REFERENCES `curso_classe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `itens_pagaveis_instituicao_id_foreign` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_pagaveis`
--

LOCK TABLES `itens_pagaveis` WRITE;
/*!40000 ALTER TABLE `itens_pagaveis` DISABLE KEYS */;
/*!40000 ALTER TABLE `itens_pagaveis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_06_01_121708_create_classes_turnos_disciplinas_table',1),(2,'0002_06_01_122212_create_niveis_ensino_table',1),(3,'0002_06_01_122213_create_instituicoes_table',1),(4,'0006_01_01_000000_create_users_table',1),(5,'0007_01_01_000002_create_jobs_table',1),(6,'0008_01_01_000001_create_cache_table',1),(7,'2024_01_01_000000_create_passkeys_table',1),(8,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(9,'2026_06_01_125641_create_professores_alunos_table',1),(10,'2026_06_01_125720_create_inscricoes_table',1),(11,'2026_06_01_125753_create_pap_table',1),(12,'2026_06_01_125810_create_notas_table',1),(13,'2026_06_01_125831_curso_tutelado_professor_table',1),(14,'2026_06_01_125937_create_classe_turno_disciplina_horarios_table',1),(15,'2026_06_01_130011_create_agent_conversations_table',1),(16,'2026_06_01_130033_create_avisos_table',1),(17,'2026_06_25_092001_add_resultado_to_turma_aluno_table',1),(18,'2026_07_05_071936_create_permission_tables',1),(19,'2026_07_13_172021_create_item_pagaveis_table',1),(20,'2026_07_15_185717_create_pagamentos_table',1),(21,'2026_07_15_190312_create_pagamento_items_table',1),(22,'2026_07_20_082106_create_regras_avaliacao_table',1),(23,'2026_07_24_153756_create_historico_aprovacao_pap_table',1),(24,'2026_07_29_123738_create_propinas_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User','019fb29c-875c-71ea-93a8-5ac2549f4634'),(2,'App\\Models\\User','019fb29c-8856-70ce-9a8d-ca47027168d5'),(4,'App\\Models\\User','019fb29c-8943-71b3-a300-a323c81c8ec0'),(2,'App\\Models\\User','019fb29c-8a26-7091-92b3-a49e90c60d3f'),(4,'App\\Models\\User','019fb29c-8b09-7304-84d4-420babdca1f9'),(2,'App\\Models\\User','019fb29c-8bee-713c-b952-8b745a9cb18e'),(4,'App\\Models\\User','019fb29c-8cd1-7216-9453-fff0f0a084f8'),(2,'App\\Models\\User','019fb29c-8db5-7098-83e3-821af41976bf'),(4,'App\\Models\\User','019fb29c-8e9a-7058-b9b2-077d79ab725b'),(7,'App\\Models\\User','019fb2b4-b07b-718b-851c-7df72454f6c9'),(6,'App\\Models\\User','019fb2b5-0414-70b4-8085-27e978ecc4b5'),(5,'App\\Models\\User','019fb2b5-2bba-72d3-9123-dff464836b16'),(6,'App\\Models\\User','019fb2b5-2bba-72d3-9123-dff464836b16'),(6,'App\\Models\\User','019fb2b5-7097-701e-b5df-c18f5c8dfbbb');
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `niveis_ensino`
--

DROP TABLE IF EXISTS `niveis_ensino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `niveis_ensino` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `niveis_ensino_nome_unique` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `niveis_ensino`
--

LOCK TABLES `niveis_ensino` WRITE;
/*!40000 ALTER TABLE `niveis_ensino` DISABLE KEYS */;
INSERT INTO `niveis_ensino` VALUES ('019fb29c-8746-70ac-baab-a42f3f93836c','Primário',0,1,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8749-7089-967e-e9c91764195c','Iº Ciclo',1,1,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8750-732b-9a00-bca094068939','IIº Ciclo',2,1,'2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `niveis_ensino` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notas`
--

DROP TABLE IF EXISTS `notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_disciplina_professor_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo` tinyint NOT NULL,
  `faltas` int NOT NULL DEFAULT '0',
  `situacao_trimestral` enum('APTO','N/APTO','recuperacao','EEF') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao_anual` enum('APTO','N/APTO','EEF') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `mac` decimal(5,2) DEFAULT NULL,
  `nota_prova_professor` decimal(5,2) DEFAULT NULL,
  `nota_prova_trimestral` decimal(5,2) DEFAULT NULL,
  `media_trimestral` decimal(5,2) DEFAULT NULL,
  `media_final` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nota` (`turma_aluno_id`,`turma_disciplina_professor_id`,`periodo`),
  KEY `notas_turma_disciplina_professor_id_foreign` (`turma_disciplina_professor_id`),
  CONSTRAINT `notas_turma_aluno_id_foreign` FOREIGN KEY (`turma_aluno_id`) REFERENCES `turma_aluno` (`id`),
  CONSTRAINT `notas_turma_disciplina_professor_id_foreign` FOREIGN KEY (`turma_disciplina_professor_id`) REFERENCES `turma_disciplina_professor` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notas`
--

LOCK TABLES `notas` WRITE;
/*!40000 ALTER TABLE `notas` DISABLE KEYS */;
INSERT INTO `notas` VALUES ('019fb2ba-759e-724a-a718-b734ccdb4513','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-0098-7380-aee6-ef3ff72b5c31',1,0,'APTO',NULL,NULL,20.00,6.00,11.00,12.30,15.30,'2026-07-30 11:13:12','2026-07-30 11:39:07'),('019fb2ba-a19d-720a-8648-fed48f2d5c9c','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-0098-7380-aee6-ef3ff72b5c31',3,0,'APTO','APTO',NULL,13.00,20.00,20.00,17.70,15.30,'2026-07-30 11:13:23','2026-07-30 11:39:07'),('019fb2ba-dede-7065-bf37-dd86f99af146','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-1a2f-711d-b2e5-13b1cda77399',1,0,'APTO',NULL,NULL,13.00,16.00,20.00,16.30,15.20,'2026-07-30 11:13:39','2026-07-30 11:13:49'),('019fb2ba-f609-70dd-9d2e-ac963a0bb118','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-1a2f-711d-b2e5-13b1cda77399',2,1,'APTO',NULL,NULL,7.00,20.00,20.00,15.70,15.20,'2026-07-30 11:13:44','2026-07-30 11:13:49'),('019fb2bb-07d9-72b6-a36a-b72315361851','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-1a2f-711d-b2e5-13b1cda77399',3,0,'APTO','APTO',NULL,3.00,18.00,20.00,13.70,15.20,'2026-07-30 11:13:49','2026-07-30 11:13:49'),('019fb2bb-4f04-704e-b203-d9eb1d161ca9','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-3d52-70fa-a693-f9ed463109d3',1,0,'APTO',NULL,NULL,20.00,19.00,15.00,18.00,18.40,'2026-07-30 11:14:07','2026-07-30 11:14:18'),('019fb2bb-643e-725b-bae9-afadbbbf2b88','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-3d52-70fa-a693-f9ed463109d3',2,0,'APTO',NULL,NULL,20.00,20.00,20.00,20.00,18.40,'2026-07-30 11:14:13','2026-07-30 11:14:18'),('019fb2bb-7a98-707a-bcd3-76911d146741','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-3d52-70fa-a693-f9ed463109d3',3,0,'APTO','APTO',NULL,12.00,20.00,20.00,17.30,18.40,'2026-07-30 11:14:18','2026-07-30 11:14:18'),('019fb2bc-371b-72f6-adcc-0d4c3ff0bbec','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-5ae2-71e0-b5d0-cccbee2915dd',1,0,'APTO',NULL,NULL,15.00,17.00,20.00,17.30,14.70,'2026-07-30 11:15:07','2026-07-30 11:15:18'),('019fb2bc-4f66-72d7-984b-eecc1bebad91','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-5ae2-71e0-b5d0-cccbee2915dd',2,0,'APTO',NULL,NULL,1.00,15.00,20.00,12.00,14.70,'2026-07-30 11:15:13','2026-07-30 11:15:18'),('019fb2bc-6170-738d-9de1-82ccc95eaed4','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-5ae2-71e0-b5d0-cccbee2915dd',3,0,'APTO','APTO',NULL,11.00,17.00,16.00,14.70,14.70,'2026-07-30 11:15:18','2026-07-30 11:15:18'),('019fb2bc-83b1-72b1-b809-bd1108c7fd6b','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-7757-70be-ba51-94dcbde98fd5',1,0,'APTO',NULL,NULL,11.00,20.00,20.00,17.00,16.60,'2026-07-30 11:15:26','2026-07-30 11:29:14'),('019fb2bc-95c5-735f-af10-816a5270b38d','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-7757-70be-ba51-94dcbde98fd5',2,0,'APTO',NULL,NULL,8.00,20.00,20.00,16.00,16.60,'2026-07-30 11:15:31','2026-07-30 11:29:14'),('019fb2bc-ac7c-7326-9124-ac273655583b','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-7757-70be-ba51-94dcbde98fd5',3,0,'APTO','APTO',NULL,19.00,20.00,11.00,16.70,16.60,'2026-07-30 11:15:37','2026-07-30 11:29:14'),('019fb2bc-e5d6-72db-8b59-cd124f14a02e','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-a2fd-73a2-877e-1364bb302a73',1,0,'APTO',NULL,NULL,13.00,20.00,20.00,17.70,16.70,'2026-07-30 11:15:51','2026-07-30 11:16:01'),('019fb2bc-f866-7175-9ba3-edcb44bdcb7e','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-a2fd-73a2-877e-1364bb302a73',2,0,'APTO',NULL,NULL,12.00,20.00,20.00,17.30,16.70,'2026-07-30 11:15:56','2026-07-30 11:16:01'),('019fb2bd-0c68-7208-b50e-f77458ba67a0','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-a2fd-73a2-877e-1364bb302a73',3,0,'APTO','APTO',NULL,12.00,18.00,15.00,15.00,16.70,'2026-07-30 11:16:01','2026-07-30 11:16:01'),('019fb2bd-813f-726d-bad6-5753b52d60bf','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-cca6-71f2-9b4b-0a1641685b14',1,0,'APTO',NULL,NULL,8.00,20.00,19.00,15.70,15.50,'2026-07-30 11:16:31','2026-07-30 11:16:40'),('019fb2bd-91ba-73d2-bdbe-96d5ed2b1088','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-cca6-71f2-9b4b-0a1641685b14',2,0,'APTO',NULL,NULL,10.00,19.00,19.00,16.00,15.50,'2026-07-30 11:16:35','2026-07-30 11:16:40'),('019fb2bd-a3a5-7072-9e02-e2dc827b47e3','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-cca6-71f2-9b4b-0a1641685b14',3,0,'APTO','APTO',NULL,12.00,20.00,12.00,14.70,15.50,'2026-07-30 11:16:40','2026-07-30 11:16:40'),('019fb2bd-e68b-701b-9188-c9c0baa69a0d','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-f5a1-7142-942d-ac9f427cb20e',1,0,'APTO',NULL,NULL,9.00,20.00,20.00,16.30,16.20,'2026-07-30 11:16:57','2026-07-30 11:17:06'),('019fb2bd-f5fa-704b-b130-124459c25c58','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-f5a1-7142-942d-ac9f427cb20e',2,0,'APTO',NULL,NULL,7.00,18.00,20.00,15.00,16.20,'2026-07-30 11:17:01','2026-07-30 11:17:06'),('019fb2be-0752-7173-b4f1-7616ce0e68d0','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-f5a1-7142-942d-ac9f427cb20e',3,0,'APTO','APTO',NULL,12.00,20.00,20.00,17.30,16.20,'2026-07-30 11:17:06','2026-07-30 11:17:06'),('019fb2c9-bcae-72f0-ac20-0bc1894cfe41','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2c9-7c6b-7050-8e62-0201f78c6050',1,0,'APTO',NULL,NULL,14.00,15.00,20.00,16.30,15.00,'2026-07-30 11:29:53','2026-07-30 11:39:29'),('019fb2c9-ce83-7261-b51b-75b7e18976cb','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2c9-7c6b-7050-8e62-0201f78c6050',2,0,'APTO',NULL,NULL,2.00,20.00,20.00,14.00,15.00,'2026-07-30 11:29:57','2026-07-30 11:39:29'),('019fb2c9-df14-70b8-9b38-89e7819d8c6e','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2c9-7c6b-7050-8e62-0201f78c6050',3,0,'APTO','APTO',NULL,12.00,20.00,12.00,14.70,15.00,'2026-07-30 11:30:02','2026-07-30 11:39:29'),('019fb2d2-332b-7215-95b5-366f7fb23f37','019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2b9-0098-7380-aee6-ef3ff72b5c31',2,0,'APTO',NULL,NULL,11.00,17.00,20.00,16.00,15.30,'2026-07-30 11:39:07','2026-07-30 11:39:07'),('019fb2d4-94d6-72ee-bd72-84b1541327f6','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-2067-706f-b9cd-651b6bffdab6',1,0,'APTO',NULL,NULL,15.00,17.00,20.00,17.30,16.60,'2026-07-30 11:41:44','2026-07-30 11:41:53'),('019fb2d4-a752-7145-893f-3c7acddbd48f','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-2067-706f-b9cd-651b6bffdab6',2,0,'APTO',NULL,NULL,7.00,20.00,17.00,14.70,16.60,'2026-07-30 11:41:48','2026-07-30 11:41:53'),('019fb2d4-ba2d-725f-8b95-5bcfe7355c63','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-2067-706f-b9cd-651b6bffdab6',3,0,'APTO','APTO',NULL,16.00,17.00,20.00,17.70,16.60,'2026-07-30 11:41:53','2026-07-30 11:41:53'),('019fb2d4-de54-7371-b168-1dc1a48b4733','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-5125-7213-be12-b3087bbe1d43',1,0,'APTO',NULL,NULL,8.00,19.00,20.00,15.70,15.10,'2026-07-30 11:42:02','2026-07-30 11:42:11'),('019fb2d4-ed81-70be-a92d-3ba6fb73e055','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-5125-7213-be12-b3087bbe1d43',2,0,'APTO',NULL,NULL,11.00,18.00,15.00,14.70,15.10,'2026-07-30 11:42:06','2026-07-30 11:42:11'),('019fb2d5-002c-70fd-9757-96f3a3c14845','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-5125-7213-be12-b3087bbe1d43',3,0,'APTO','APTO',NULL,11.00,18.00,16.00,15.00,15.10,'2026-07-30 11:42:11','2026-07-30 11:42:11'),('019fb2d5-2761-703b-ac7e-6d7ec5f65b33','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-3a22-70ff-85d2-681396cb9488',1,1,'APTO',NULL,NULL,11.00,20.00,20.00,17.00,16.90,'2026-07-30 11:42:21','2026-07-30 11:42:39'),('019fb2d5-5758-720b-8d97-c8c4d997790e','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-3a22-70ff-85d2-681396cb9488',2,0,'APTO',NULL,NULL,11.00,18.00,20.00,16.30,16.90,'2026-07-30 11:42:33','2026-07-30 11:42:39'),('019fb2d5-6dcb-716a-a684-12b6535758ed','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-3a22-70ff-85d2-681396cb9488',3,0,'APTO','APTO',NULL,12.00,20.00,20.00,17.30,16.90,'2026-07-30 11:42:39','2026-07-30 11:42:39'),('019fb2d5-9ff3-73ac-bb0d-b058a651e2d1','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-6812-7257-8a91-754290498ae6',1,0,'APTO',NULL,NULL,9.00,20.00,19.00,16.00,17.10,'2026-07-30 11:42:52','2026-07-30 11:43:01'),('019fb2d5-b23b-70c7-a656-02c7459e5509','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-6812-7257-8a91-754290498ae6',2,0,'APTO',NULL,NULL,13.00,20.00,20.00,17.70,17.10,'2026-07-30 11:42:57','2026-07-30 11:43:01'),('019fb2d5-c504-7264-9dde-75d72b82af41','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-6812-7257-8a91-754290498ae6',3,0,'APTO','APTO',NULL,13.00,20.00,20.00,17.70,17.10,'2026-07-30 11:43:01','2026-07-30 11:43:01'),('019fb2d5-f9ed-724b-aa4d-9e8fa68657d4','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-8896-708a-8c20-7ff290e7bfb3',1,0,'APTO',NULL,NULL,14.00,20.00,20.00,18.00,17.00,'2026-07-30 11:43:15','2026-07-30 11:43:25'),('019fb2d6-0dde-70d9-b720-390a09dc1366','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-8896-708a-8c20-7ff290e7bfb3',2,0,'APTO',NULL,NULL,6.00,20.00,20.00,15.30,17.00,'2026-07-30 11:43:20','2026-07-30 11:43:25'),('019fb2d6-1fdb-713e-950f-4982334574e2','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-8896-708a-8c20-7ff290e7bfb3',3,0,'APTO','APTO',NULL,13.00,20.00,20.00,17.70,17.00,'2026-07-30 11:43:25','2026-07-30 11:43:25'),('019fb2d6-4e09-704c-ba49-ea75869f99b8','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-b9f9-7279-b1f8-0f9a041962b5',1,0,'APTO',NULL,NULL,17.00,15.00,20.00,17.30,17.20,'2026-07-30 11:43:36','2026-07-30 11:43:49'),('019fb2d6-6aa8-7321-a5a7-d08c675e3b47','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-b9f9-7279-b1f8-0f9a041962b5',2,0,'APTO',NULL,NULL,15.00,19.00,20.00,18.00,17.20,'2026-07-30 11:43:44','2026-07-30 11:43:49'),('019fb2d6-80cb-7338-aca3-aaca8cb1490c','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-b9f9-7279-b1f8-0f9a041962b5',3,0,'APTO','APTO',NULL,9.00,20.00,20.00,16.30,17.20,'2026-07-30 11:43:49','2026-07-30 11:43:49'),('019fb2d8-2387-7151-b935-bd9eb9ab92fa','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-737d-7283-b036-ae5468040704',1,0,'APTO',NULL,NULL,18.00,20.00,13.00,17.00,16.40,'2026-07-30 11:45:37','2026-07-30 11:45:47'),('019fb2d8-3a8a-71e3-9656-e343e64c1aff','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-737d-7283-b036-ae5468040704',2,0,'APTO',NULL,NULL,9.00,19.00,20.00,16.00,16.40,'2026-07-30 11:45:43','2026-07-30 11:45:47'),('019fb2d8-4d89-708d-805f-8e6b6e037871','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-737d-7283-b036-ae5468040704',3,0,'APTO','APTO',NULL,11.00,18.00,20.00,16.30,16.40,'2026-07-30 11:45:47','2026-07-30 11:45:47'),('019fb2d8-dde8-723e-985f-ccf72655de6e','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-e090-701e-8c2e-3fa81cfaa70d',1,0,'APTO',NULL,NULL,13.00,18.00,20.00,17.00,15.30,'2026-07-30 11:46:24','2026-07-30 11:46:34'),('019fb2d8-edc0-7377-a91f-75729c2cff8b','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-e090-701e-8c2e-3fa81cfaa70d',2,0,'APTO',NULL,NULL,2.00,14.00,20.00,12.00,15.30,'2026-07-30 11:46:28','2026-07-30 11:46:34'),('019fb2d9-0551-71ac-ab39-b04f3976a7a7','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d3-e090-701e-8c2e-3fa81cfaa70d',3,0,'APTO','APTO',NULL,17.00,14.00,20.00,17.00,15.30,'2026-07-30 11:46:34','2026-07-30 11:46:34'),('019fb2d9-2cfa-7080-9b2b-20c303fac82c','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-0a87-72c1-9ae5-43f0ac0811ce',1,0,'APTO',NULL,NULL,11.00,20.00,20.00,17.00,17.90,'2026-07-30 11:46:45','2026-07-30 11:46:54'),('019fb2d9-3f93-7226-967d-5b2a30137d58','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-0a87-72c1-9ae5-43f0ac0811ce',2,0,'APTO',NULL,NULL,12.00,20.00,20.00,17.30,17.90,'2026-07-30 11:46:49','2026-07-30 11:46:54'),('019fb2d9-50f7-70d9-b43d-3992deea23d8','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-0a87-72c1-9ae5-43f0ac0811ce',3,0,'APTO','APTO',NULL,19.00,19.00,20.00,19.30,17.90,'2026-07-30 11:46:54','2026-07-30 11:46:54'),('019fb2e6-66b3-73ea-acc5-c5d4283ba3ef','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-43c7-7380-bd8f-2217f60739c3',1,0,'APTO',NULL,NULL,11.00,19.00,12.00,14.00,14.20,'2026-07-30 12:01:11','2026-07-30 12:01:48'),('019fb2e6-e3bc-7115-a30a-d2e9f93705ba','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-43c7-7380-bd8f-2217f60739c3',2,0,'APTO',NULL,NULL,12.00,17.00,15.00,14.70,14.20,'2026-07-30 12:01:43','2026-07-30 12:01:48'),('019fb2e6-f6f7-72bb-be79-e0f7d27a46c2','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d4-43c7-7380-bd8f-2217f60739c3',3,0,'APTO','APTO',NULL,8.00,20.00,14.00,14.00,14.20,'2026-07-30 12:01:48','2026-07-30 12:01:48'),('019fb2e7-c247-7131-b16b-e36e43f56fa6','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d8-834a-7228-bc85-ffacb94cce6b',1,0,'APTO',NULL,NULL,20.00,8.00,20.00,16.00,15.60,'2026-07-30 12:02:40','2026-07-30 12:02:49'),('019fb2e7-d470-7376-8885-4aab535d6bfa','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d8-834a-7228-bc85-ffacb94cce6b',2,0,'APTO',NULL,NULL,11.00,19.00,11.00,13.70,15.60,'2026-07-30 12:02:45','2026-07-30 12:02:49'),('019fb2e7-e599-7078-ab38-510ffd1103d2','019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2d8-834a-7228-bc85-ffacb94cce6b',3,0,'APTO','APTO',NULL,11.00,20.00,20.00,17.00,15.60,'2026-07-30 12:02:49','2026-07-30 12:02:49'),('019fb2f4-355a-73af-b542-0ee10bcbf41f','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-e769-70ec-8013-6675d499f0c5',1,0,'APTO',NULL,NULL,8.00,20.00,19.00,15.70,16.60,'2026-07-30 12:16:16','2026-07-30 12:20:54'),('019fb2f4-5ae7-732d-b252-34633b321ed4','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-e769-70ec-8013-6675d499f0c5',3,0,'APTO','APTO',NULL,11.00,18.00,20.00,16.30,16.60,'2026-07-30 12:16:26','2026-07-30 12:20:54'),('019fb2f4-9568-7002-99ab-f46e50fc36a3','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-fdbd-738f-bd60-b08b65c3a091',1,0,'APTO',NULL,NULL,17.00,19.00,20.00,18.70,18.20,'2026-07-30 12:16:41','2026-07-30 12:16:53'),('019fb2f4-a783-716d-8507-277a0b0e5826','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-fdbd-738f-bd60-b08b65c3a091',2,0,'APTO',NULL,NULL,12.00,19.00,20.00,17.00,18.20,'2026-07-30 12:16:45','2026-07-30 12:16:53'),('019fb2f4-c418-735f-8ff0-984e9dcd56da','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-fdbd-738f-bd60-b08b65c3a091',3,0,'APTO','APTO',NULL,18.00,20.00,19.00,19.00,18.20,'2026-07-30 12:16:53','2026-07-30 12:16:53'),('019fb2f4-e707-7349-b844-58f5e749dbe0','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-13c9-73ea-a46f-40f048872092',1,0,'APTO',NULL,NULL,11.00,17.00,16.00,14.70,14.60,'2026-07-30 12:17:02','2026-07-30 12:17:11'),('019fb2f4-f95d-71bb-a2e7-4978877e8882','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-13c9-73ea-a46f-40f048872092',2,0,'APTO',NULL,NULL,8.00,18.00,11.00,12.30,14.60,'2026-07-30 12:17:06','2026-07-30 12:17:11'),('019fb2f5-0b78-7204-858b-4530fdb07373','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-13c9-73ea-a46f-40f048872092',3,0,'APTO','APTO',NULL,13.00,20.00,17.00,16.70,14.60,'2026-07-30 12:17:11','2026-07-30 12:17:11'),('019fb2f5-2ac1-712a-8b08-634e448dc4a8','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-2940-7305-ad70-49c19c49fa4f',1,0,'APTO',NULL,NULL,12.00,20.00,15.00,15.70,15.40,'2026-07-30 12:17:19','2026-07-30 12:17:29'),('019fb2f5-3c47-7193-8238-1b73a027ce8e','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-2940-7305-ad70-49c19c49fa4f',2,0,'APTO',NULL,NULL,11.00,15.00,14.00,13.30,15.40,'2026-07-30 12:17:24','2026-07-30 12:17:29'),('019fb2f5-509c-728b-882b-c26c501d58e5','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-2940-7305-ad70-49c19c49fa4f',3,0,'APTO','APTO',NULL,13.00,19.00,20.00,17.30,15.40,'2026-07-30 12:17:29','2026-07-30 12:17:29'),('019fb2f5-764d-7065-a834-c1b6c3548c9b','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-4730-7166-8f95-bf54c4e024d4',1,0,'APTO',NULL,NULL,8.00,17.00,20.00,15.00,16.70,'2026-07-30 12:17:38','2026-07-30 12:17:51'),('019fb2f5-8c18-71b9-8093-1b6497bece43','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-4730-7166-8f95-bf54c4e024d4',2,0,'APTO',NULL,NULL,11.00,15.00,20.00,15.30,16.70,'2026-07-30 12:17:44','2026-07-30 12:17:51'),('019fb2f5-a5c2-708e-bce2-13f60c0b2499','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-4730-7166-8f95-bf54c4e024d4',3,0,'APTO','APTO',NULL,19.00,20.00,20.00,19.70,16.70,'2026-07-30 12:17:51','2026-07-30 12:17:51'),('019fb2f5-f847-72ff-9cd2-301869c9d7b4','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-709c-70d2-9fc1-95e9c4aff65e',1,0,'APTO',NULL,NULL,12.00,20.00,18.00,16.70,14.90,'2026-07-30 12:18:12','2026-07-30 12:18:20'),('019fb2f6-071d-7055-b60b-ca973a1e9c53','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-709c-70d2-9fc1-95e9c4aff65e',2,0,'APTO',NULL,NULL,4.00,19.00,14.00,12.30,14.90,'2026-07-30 12:18:15','2026-07-30 12:18:20'),('019fb2f6-1960-702a-a631-e0cc823ec829','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-709c-70d2-9fc1-95e9c4aff65e',3,0,'APTO','APTO',NULL,13.00,15.00,19.00,15.70,14.90,'2026-07-30 12:18:20','2026-07-30 12:18:20'),('019fb2f6-4d32-73da-9db0-37bd6a3b8c4a','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-98ef-72ac-9696-87aeb5345225',2,0,'APTO',NULL,NULL,7.00,16.00,20.00,14.30,15.00,'2026-07-30 12:18:33','2026-07-30 12:21:11'),('019fb2f6-5c42-73f4-b9d5-5f7be312b888','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-98ef-72ac-9696-87aeb5345225',3,0,'APTO','APTO',NULL,6.00,19.00,20.00,15.00,15.00,'2026-07-30 12:18:37','2026-07-30 12:21:11'),('019fb2f6-9628-72ee-a82c-c991e7c329b5','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-b787-732c-8248-dd4ffaf9318a',1,0,'APTO',NULL,NULL,12.00,17.00,20.00,16.30,16.20,'2026-07-30 12:18:52','2026-07-30 12:21:29'),('019fb2f6-cabb-71ca-b55c-1c24efee1fac','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-b787-732c-8248-dd4ffaf9318a',2,0,'APTO',NULL,NULL,6.00,20.00,20.00,15.30,16.20,'2026-07-30 12:19:06','2026-07-30 12:21:29'),('019fb2f6-f6d4-7158-96ba-6e8f6c46f73e','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-ed18-72c9-969f-f1d6a7b34a45',1,0,'APTO',NULL,NULL,16.00,20.00,20.00,18.70,17.20,'2026-07-30 12:19:17','2026-07-30 12:19:26'),('019fb2f7-0bfe-70e3-9bb0-8d61a7469425','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-ed18-72c9-969f-f1d6a7b34a45',2,0,'APTO',NULL,NULL,12.00,20.00,20.00,17.30,17.20,'2026-07-30 12:19:22','2026-07-30 12:19:26'),('019fb2f7-1c7c-71a0-a03d-63dd6f947ec2','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-ed18-72c9-969f-f1d6a7b34a45',3,0,'APTO','APTO',NULL,13.00,17.00,17.00,15.70,17.20,'2026-07-30 12:19:26','2026-07-30 12:19:26'),('019fb2f8-71b2-72bf-9c6b-ef3d81a74e30','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f2-e769-70ec-8013-6675d499f0c5',2,0,'APTO',NULL,NULL,13.00,20.00,20.00,17.70,16.60,'2026-07-30 12:20:54','2026-07-30 12:20:54'),('019fb2f8-b5f2-73cf-840f-0e6efae9386a','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-98ef-72ac-9696-87aeb5345225',1,0,'APTO',NULL,NULL,7.00,20.00,20.00,15.70,15.00,'2026-07-30 12:21:11','2026-07-30 12:21:11'),('019fb2f8-fb22-7393-947e-9e9be3970485','019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2f3-b787-732c-8248-dd4ffaf9318a',3,0,'APTO','APTO',NULL,18.00,13.00,20.00,17.00,16.20,'2026-07-30 12:21:29','2026-07-30 12:21:29'),('019fb2fb-4d53-7106-8533-52086d91db75','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-91c6-7002-affc-458290669867',1,0,'APTO',NULL,NULL,12.00,15.00,16.00,14.30,13.80,'2026-07-30 12:24:01','2026-07-30 12:24:10'),('019fb2fb-5dec-7365-996f-7fb1019cb6e0','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-91c6-7002-affc-458290669867',2,0,'APTO',NULL,NULL,2.00,16.00,16.00,11.30,13.80,'2026-07-30 12:24:05','2026-07-30 12:24:10'),('019fb2fb-6f43-7298-ae22-4c4f677225e4','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-91c6-7002-affc-458290669867',3,0,'APTO','APTO',NULL,11.00,20.00,16.00,15.70,13.80,'2026-07-30 12:24:10','2026-07-30 12:24:10'),('019fb2fb-9fc4-716c-a6ca-897e182d5d42','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-ae7c-7337-ac37-3e5b95c93453',1,0,'APTO',NULL,NULL,11.00,18.00,20.00,16.30,16.10,'2026-07-30 12:24:22','2026-07-30 12:24:32'),('019fb2fb-b237-703a-a302-88c04085bdc1','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-ae7c-7337-ac37-3e5b95c93453',2,0,'APTO',NULL,NULL,10.00,20.00,20.00,16.70,16.10,'2026-07-30 12:24:27','2026-07-30 12:24:32'),('019fb2fb-c4af-729c-96af-076c75ec6cb9','019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2fa-ae7c-7337-ac37-3e5b95c93453',3,0,'APTO','APTO',NULL,14.00,17.00,15.00,15.30,16.10,'2026-07-30 12:24:32','2026-07-30 12:24:32');
/*!40000 ALTER TABLE `notas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamento_itens`
--

DROP TABLE IF EXISTS `pagamento_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamento_itens` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pagamento_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_pagavel_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes` tinyint unsigned NOT NULL DEFAULT '0',
  `ano` smallint unsigned NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagamento_itens_periodo_unico` (`aluno_id`,`item_pagavel_id`,`ano`,`mes`),
  KEY `pagamento_itens_pagamento_id_foreign` (`pagamento_id`),
  KEY `pagamento_itens_item_pagavel_id_foreign` (`item_pagavel_id`),
  CONSTRAINT `pagamento_itens_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  CONSTRAINT `pagamento_itens_item_pagavel_id_foreign` FOREIGN KEY (`item_pagavel_id`) REFERENCES `itens_pagaveis` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pagamento_itens_pagamento_id_foreign` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamento_itens`
--

LOCK TABLES `pagamento_itens` WRITE;
/*!40000 ALTER TABLE `pagamento_itens` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagamento_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamentos`
--

DROP TABLE IF EXISTS `pagamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamentos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registado_por` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_pagamento` date NOT NULL,
  `valor_total` decimal(12,2) NOT NULL,
  `metodo` enum('dinheiro','transferencia','multicaixa','outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagamentos_aluno_id_foreign` (`aluno_id`),
  KEY `pagamentos_instituicao_id_foreign` (`instituicao_id`),
  KEY `pagamentos_registado_por_foreign` (`registado_por`),
  CONSTRAINT `pagamentos_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  CONSTRAINT `pagamentos_instituicao_id_foreign` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`),
  CONSTRAINT `pagamentos_registado_por_foreign` FOREIGN KEY (`registado_por`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamentos`
--

LOCK TABLES `pagamentos` WRITE;
/*!40000 ALTER TABLE `pagamentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passkeys`
--

DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `passkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential` json NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passkeys`
--

LOCK TABLES `passkeys` WRITE;
/*!40000 ALTER TABLE `passkeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `passkeys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'instituicoes.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(2,'instituicoes.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(3,'alunos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(4,'alunos.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(5,'alunos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(6,'alunos.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(7,'cursoclasse.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(8,'cursoclasse.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(9,'cursoclasse.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(10,'cursoclasse.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(11,'cursoclasse.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(12,'cursoclasseturno.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(13,'cursoclasseturno.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(14,'cursoclasseturno.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(15,'cursoclasseturno.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(16,'cursoclasseturno.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(17,'turnos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(18,'turnos.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(19,'turnos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(20,'turnos.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(21,'turnos.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(22,'turmas.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(23,'turmas.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(24,'turmas.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(25,'turmas.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(26,'turmas.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(27,'classeturnodisciplina.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(28,'classeturnodisciplina.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(29,'classeturnodisciplina.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(30,'classeturnodisciplina.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(31,'classeturnodisciplina.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(32,'pautas.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(33,'pautas.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(34,'notas.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(35,'notas.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(36,'notas.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(37,'grelha.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(38,'professores.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(39,'professores.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(40,'professores.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(41,'professores.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(42,'professores.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(43,'avisos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(44,'avisos.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(45,'avisos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(46,'avisos.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(47,'avisos.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(48,'inscricoes.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(49,'inscricoes.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(50,'inscricoes.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(51,'inscricoes.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(52,'inscricoes.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(53,'grupopap.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(54,'grupopap.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(55,'grupopap.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(56,'grupopap.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(57,'grupopap.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(58,'grupopap.definirData','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(59,'bancajuripap.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(60,'bancajuripap.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(61,'bancajuripap.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(62,'bancajuripap.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(63,'bancajuripap.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(64,'elementogrupopap.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(65,'elementogrupopap.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(66,'elementogrupopap.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(67,'elementogrupopap.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(68,'elementogrupopap.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(69,'elementogrupopap.atualizarNota','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(70,'cursos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(71,'cursos.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(72,'cursos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(73,'cursos.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(74,'cursos.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(75,'classes.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(76,'classes.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(77,'classes.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(78,'classes.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(79,'classes.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(80,'curso-tutelado.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(81,'curso-tutelado.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(82,'curso-tutelado.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(83,'curso-tutelado.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(84,'curso-tutelado.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(85,'notas.export','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(86,'utilizadores.gerir','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(87,'acessos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(88,'acessos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(89,'relatorios.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(90,'pagamentos.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(91,'pagamentos.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(92,'pagamentos.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(93,'pagamentos.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(94,'pagamentos.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(95,'itemspagaveis.viewAny','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(96,'itemspagaveis.view','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(97,'itemspagaveis.create','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(98,'itemspagaveis.update','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(99,'itemspagaveis.delete','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(100,'coordenador.view-curso','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(101,'coordenador.update-curso','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(102,'coordenador.manage-professores','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(103,'coordenador.manage-turmas','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(104,'coordenador.view-pautas','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(105,'coordenador.update-pautas','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(106,'coordenador.create-notas','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(107,'coordenador.update-notas','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(108,'coordenador.view-relatorios','web','2026-07-30 10:40:29','2026-07-30 10:40:29');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `professores`
--

DROP TABLE IF EXISTS `professores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `professores` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professores_user_id_foreign` (`user_id`),
  CONSTRAINT `professores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professores`
--

LOCK TABLES `professores` WRITE;
/*!40000 ALTER TABLE `professores` DISABLE KEYS */;
INSERT INTO `professores` VALUES ('019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b5-0414-70b4-8085-27e978ecc4b5',NULL,'2026-07-30 11:07:15','2026-07-30 11:07:15'),('019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b5-2bba-72d3-9123-dff464836b16',NULL,'2026-07-30 11:07:25','2026-07-30 11:07:25'),('019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2b5-7097-701e-b5df-c18f5c8dfbbb',NULL,'2026-07-30 11:07:43','2026-07-30 11:07:43');
/*!40000 ALTER TABLE `professores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propinas`
--

DROP TABLE IF EXISTS `propinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propinas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_lectivo_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_pagavel_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes` tinyint unsigned NOT NULL DEFAULT '0',
  `valor_devido` decimal(12,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `estado` enum('pendente','parcial','pago','atrasado','isento') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `propina_aluno_item_ano_mes_unique` (`aluno_id`,`item_pagavel_id`,`ano_lectivo_id`,`mes`),
  KEY `propinas_ano_lectivo_id_foreign` (`ano_lectivo_id`),
  KEY `propinas_item_pagavel_id_foreign` (`item_pagavel_id`),
  CONSTRAINT `propinas_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `propinas_ano_lectivo_id_foreign` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `ano_lectivos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `propinas_item_pagavel_id_foreign` FOREIGN KEY (`item_pagavel_id`) REFERENCES `itens_pagaveis` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propinas`
--

LOCK TABLES `propinas` WRITE;
/*!40000 ALTER TABLE `propinas` DISABLE KEYS */;
/*!40000 ALTER TABLE `propinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regras_avaliacao`
--

DROP TABLE IF EXISTS `regras_avaliacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regras_avaliacao` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel_ensino` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_minima_aprovacao` decimal(5,2) NOT NULL DEFAULT '10.00',
  `frequencia_minima` decimal(5,2) NOT NULL DEFAULT '75.00',
  `max_disciplinas_negativas` int unsigned DEFAULT NULL,
  `permite_recurso` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `nivel_ensino_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_lectivo_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regras_avaliacao_unique` (`instituicao_id`,`ano_lectivo_id`,`nivel_ensino_id`,`classe_id`),
  KEY `regras_avaliacao_nivel_ensino_id_foreign` (`nivel_ensino_id`),
  KEY `regras_avaliacao_ano_lectivo_id_foreign` (`ano_lectivo_id`),
  KEY `regras_avaliacao_classe_id_foreign` (`classe_id`),
  CONSTRAINT `regras_avaliacao_ano_lectivo_id_foreign` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `ano_lectivos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regras_avaliacao_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `regras_avaliacao_instituicao_id_foreign` FOREIGN KEY (`instituicao_id`) REFERENCES `instituicoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regras_avaliacao_nivel_ensino_id_foreign` FOREIGN KEY (`nivel_ensino_id`) REFERENCES `niveis_ensino` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regras_avaliacao`
--

LOCK TABLES `regras_avaliacao` WRITE;
/*!40000 ALTER TABLE `regras_avaliacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `regras_avaliacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(46,2),(47,2),(48,2),(49,2),(50,2),(51,2),(53,2),(54,2),(55,2),(56,2),(57,2),(58,2),(59,2),(60,2),(61,2),(62,2),(63,2),(64,2),(65,2),(66,2),(67,2),(68,2),(69,2),(70,2),(71,2),(72,2),(73,2),(74,2),(75,2),(76,2),(77,2),(78,2),(79,2),(80,2),(81,2),(82,2),(83,2),(84,2),(85,2),(86,2),(87,2),(88,2),(89,2),(90,2),(91,2),(92,2),(93,2),(94,2),(95,2),(96,2),(97,2),(98,2),(99,2),(1,3),(2,3),(3,3),(4,3),(6,3),(7,3),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3),(14,3),(15,3),(16,3),(17,3),(18,3),(22,3),(23,3),(24,3),(25,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(32,3),(33,3),(34,3),(35,3),(38,3),(39,3),(40,3),(41,3),(43,3),(44,3),(45,3),(46,3),(48,3),(49,3),(50,3),(51,3),(53,3),(54,3),(55,3),(56,3),(57,3),(58,3),(59,3),(60,3),(61,3),(62,3),(63,3),(64,3),(65,3),(66,3),(67,3),(68,3),(69,3),(70,3),(71,3),(72,3),(73,3),(74,3),(75,3),(76,3),(77,3),(78,3),(79,3),(80,3),(81,3),(82,3),(83,3),(85,3),(89,3),(90,3),(91,3),(92,3),(93,3),(94,3),(95,3),(96,3),(97,3),(98,3),(99,3),(1,4),(3,4),(4,4),(5,4),(6,4),(7,4),(8,4),(12,4),(13,4),(17,4),(18,4),(22,4),(23,4),(24,4),(25,4),(27,4),(28,4),(32,4),(33,4),(38,4),(39,4),(40,4),(41,4),(43,4),(44,4),(45,4),(46,4),(48,4),(49,4),(50,4),(51,4),(53,4),(54,4),(55,4),(56,4),(57,4),(58,4),(59,4),(60,4),(61,4),(64,4),(65,4),(66,4),(67,4),(70,4),(71,4),(72,4),(73,4),(75,4),(76,4),(77,4),(78,4),(80,4),(81,4),(85,4),(90,4),(91,4),(92,4),(93,4),(95,4),(96,4),(97,4),(98,4),(1,5),(2,5),(3,5),(4,5),(22,5),(23,5),(24,5),(25,5),(32,5),(33,5),(34,5),(35,5),(36,5),(38,5),(39,5),(40,5),(41,5),(43,5),(44,5),(45,5),(46,5),(53,5),(54,5),(55,5),(56,5),(59,5),(60,5),(61,5),(62,5),(64,5),(65,5),(66,5),(67,5),(69,5),(81,5),(83,5),(85,5),(3,6),(4,6),(22,6),(23,6),(32,6),(33,6),(34,6),(35,6),(38,6),(39,6),(44,6),(45,6),(46,6),(53,6),(54,6),(55,6),(56,6),(59,6),(60,6),(64,6),(65,6),(66,6),(67,6),(85,6),(36,7),(37,7);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'SuperAdmin','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(2,'Director','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(3,'Subdirector','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(4,'Secretaria','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(5,'Coordenador','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(6,'Professor','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(7,'Aluno','web','2026-07-30 10:40:29','2026-07-30 10:40:29'),(8,'Candidato','web','2026-07-30 10:40:29','2026-07-30 10:40:29');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('6cGvDQwYCUBLdVWvEt7wrSaKr4ynxuh6VtMdukox','019fb29c-8856-70ce-9a8d-ca47027168d5','192.168.1.233','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJoaU1iZjFsbzF0d0JDdkhsUW1QOE1sMWM3OTVpMkhxUXdRM29qR1RhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4yMTE6ODAwMFwvY2VydGlmaWNhZG9zXC8wMTlmYjJiNC1iMDdmLTcwOGMtYTE1OC0yMTczZmU0ZWMyMGFcL3ZlcmlmaWNhciIsInJvdXRlIjoiY2VydGlmaWNhZG9zLnZlcmlmaWNhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoiMDE5ZmIyOWMtODg1Ni03MGNlLTlhOGQtY2E0NzAyNzE2OGQ1In0=',1785418919),('gE7rQgeoreK8ORl02OazyprB1d7BTCAV2RT95hvl','019fb29c-8856-70ce-9a8d-ca47027168d5','127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJFb05vVFltajdRN1pnQ2Jxa2tiRG1weTZWTXh6Z1o0Um9ZdTRrWXA5IiwidXJsIjpbXSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2Rhc2hib2FyZFwvcGF1dGFzXC9jdXJzb3NcLzAxOWZiMmFiLWE1NjktNzA1OC1iZjBlLWY2MmZlZDI5NTkwNVwvdHVybWFzXC8wMTlmYjJhYi1mNmU2LTcxZjAtYWQ1OS0wN2U2ZGJkM2I1NTBcL3BhdXRhP3BlcmlvZG89ZmluYWwiLCJyb3V0ZSI6InBhdXRhcy5jdXJzb3MudHVybWFzLnBhdXRhIn0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoiMDE5ZmIyOWMtODg1Ni03MGNlLTlhOGQtY2E0NzAyNzE2OGQ1In0=',1785412970),('juKCCkILINWumgL1AE1lo1Hx8a0h5JPLXsLPakah',NULL,'192.168.1.32','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36','eyJfdG9rZW4iOiIyWDRkUHExY0V0ZTd1bjRnTTlTWWJPd1BXNjJrdklCQUVYOWY1ckFlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4yMTE6ODAwMFwvY2VydGlmaWNhZG9zXC8wMTlmYjJiNC1iMDdmLTcwOGMtYTE1OC0yMTczZmU0ZWMyMGFcL3ZlcmlmaWNhciIsInJvdXRlIjoiY2VydGlmaWNhZG9zLnZlcmlmaWNhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1785416211),('ZiUl2mrdrFNWY7CRaSSYilSAnFNzviEbdGaRp8j8','019fb29c-8856-70ce-9a8d-ca47027168d5','192.168.1.211','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJiM0huOHNjUHpjYWg2U3BrOUdNNXRyVTBoRE1oWXo4SlVjRXRvTW1RIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4yMTE6ODAwMFwvZGFzaGJvYXJkXC9pbnN0aXR1aWNvZXNcLzAxOWZiMjljLTg2OTktNzE5ZC1hN2Y5LTgxMDA5ODE0YzI3OVwvY3Vyc29zLXR1dGVsYWRvc1wvMDE5ZmIyYWItYTU2OS03MDU4LWJmMGUtZjYyZmVkMjk1OTA1XC9jbGFzc2VzXC8wMTlmYjJhYi1hNTZhLTczNTMtODIwZC03MjQ2NzMyNGQ1ZGNcL3R1cm5vc1wvMDE5ZmIyYjItZWE4MS03MThmLWI4NTUtODUyN2U5ZjgwMzk1XC90dXJtYXNcLzAxOWZiMmIzLTBjY2EtNzM3Yi04NTBjLWU4YjNkMmIzOWE3M1wvYWx1bm9zXC8wMTlmYjJiNC1iMDdmLTcwOGMtYTE1OC0yMTczZmU0ZWMyMGFcL2NlcnRpZmljYWRvIiwicm91dGUiOiJjZXJ0aWZpY2Fkby5nZXJhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoiMDE5ZmIyOWMtODg1Ni03MGNlLTlhOGQtY2E0NzAyNzE2OGQ1In0=',1785418855);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turma_aluno`
--

DROP TABLE IF EXISTS `turma_aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turma_aluno` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aluno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `situacao` enum('activo','recurso','pap_concluido','concluido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `resultado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `turma_aluno_turma_id_foreign` (`turma_id`),
  KEY `turma_aluno_aluno_id_foreign` (`aluno_id`),
  CONSTRAINT `turma_aluno_aluno_id_foreign` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  CONSTRAINT `turma_aluno_turma_id_foreign` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turma_aluno`
--

LOCK TABLES `turma_aluno` WRITE;
/*!40000 ALTER TABLE `turma_aluno` DISABLE KEYS */;
INSERT INTO `turma_aluno` VALUES ('019fb2b6-a8da-73af-a28d-2bc24b14b7e9','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','019fb2b4-b07f-708c-a158-2173fe4ec20a',0,'activo','transita','2026-07-30 11:09:03','2026-07-30 11:39:54'),('019fb2d2-e81e-73a8-984a-8c08f6f1e2ff','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','019fb2b4-b07f-708c-a158-2173fe4ec20a',0,'activo','transita','2026-07-30 11:39:54','2026-07-30 12:10:30'),('019fb2ee-ed92-7090-9e9e-a1e4f9af5718','019fb2b2-bd50-730e-a387-64b54a0f28b1','019fb2b4-b07f-708c-a158-2173fe4ec20a',0,'activo','transita','2026-07-30 12:10:30','2026-07-30 12:21:57'),('019fb2f9-69eb-700f-8dd1-9c5839c60652','019fb2b3-0cca-737b-850c-e8b3d2b39a73','019fb2b4-b07f-708c-a158-2173fe4ec20a',1,'activo','transita','2026-07-30 12:21:57','2026-07-30 12:24:32');
/*!40000 ALTER TABLE `turma_aluno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turma_disciplina_professor`
--

DROP TABLE IF EXISTS `turma_disciplina_professor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turma_disciplina_professor` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_turno_disciplina_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `professor_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turma_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `turma_disciplina_professor_classe_turno_disciplina_id_foreign` (`classe_turno_disciplina_id`),
  KEY `turma_disciplina_professor_professor_id_foreign` (`professor_id`),
  KEY `turma_disciplina_professor_turma_id_foreign` (`turma_id`),
  CONSTRAINT `turma_disciplina_professor_classe_turno_disciplina_id_foreign` FOREIGN KEY (`classe_turno_disciplina_id`) REFERENCES `classe_turno_disciplina` (`id`),
  CONSTRAINT `turma_disciplina_professor_professor_id_foreign` FOREIGN KEY (`professor_id`) REFERENCES `professores` (`id`),
  CONSTRAINT `turma_disciplina_professor_turma_id_foreign` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turma_disciplina_professor`
--

LOCK TABLES `turma_disciplina_professor` WRITE;
/*!40000 ALTER TABLE `turma_disciplina_professor` DISABLE KEYS */;
INSERT INTO `turma_disciplina_professor` VALUES ('019fb2b9-0098-7380-aee6-ef3ff72b5c31','019fb2b8-da32-7371-9b04-452665052b91','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:11:36','2026-07-30 11:11:36'),('019fb2b9-1a2f-711d-b2e5-13b1cda77399','019fb2b8-da37-7230-bc7b-5bb1071f95a7','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:11:43','2026-07-30 11:11:43'),('019fb2b9-3d52-70fa-a693-f9ed463109d3','019fb2b8-da3f-73f6-8819-7a1bfffc80e2','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:11:52','2026-07-30 11:11:52'),('019fb2b9-5ae2-71e0-b5d0-cccbee2915dd','019fb2b8-da3b-7230-9aee-e65d36fb86d0','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:11:59','2026-07-30 11:11:59'),('019fb2b9-7757-70be-ba51-94dcbde98fd5','019fb2b8-da46-7223-a43d-3cdae153c7ef','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:12:06','2026-07-30 11:12:06'),('019fb2b9-a2fd-73a2-877e-1364bb302a73','019fb2b8-da42-72d0-a699-e1e3459c81f1','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:12:18','2026-07-30 11:12:18'),('019fb2b9-cca6-71f2-9b4b-0a1641685b14','019fb2b8-da49-73c6-a746-fd7fc2451fdc','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:12:28','2026-07-30 11:12:28'),('019fb2b9-f5a1-7142-942d-ac9f427cb20e','019fb2b8-da4d-72a2-9f32-27f7c96768ca','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:12:39','2026-07-30 11:12:39'),('019fb2c9-7c6b-7050-8e62-0201f78c6050','019fb2b8-da29-7106-bd55-c107df6638a7','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','2026-07-30 11:29:36','2026-07-30 11:29:36'),('019fb2d3-2067-706f-b9cd-651b6bffdab6','019fb2c8-1fef-7153-8724-af9fce13bc38','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:08','2026-07-30 11:40:08'),('019fb2d3-3a22-70ff-85d2-681396cb9488','019fb2c8-1ff3-73f2-841d-7da9cdeff44c','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:15','2026-07-30 11:40:15'),('019fb2d3-5125-7213-be12-b3087bbe1d43','019fb2c8-1ffb-71bc-a098-ddabbe4dd2f3','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:21','2026-07-30 11:40:21'),('019fb2d3-6812-7257-8a91-754290498ae6','019fb2c8-1ff7-7180-aeb0-3da16a0a1155','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:27','2026-07-30 11:40:27'),('019fb2d3-8896-708a-8c20-7ff290e7bfb3','019fb2c8-1fe6-70ee-acea-0ea6d0fc17ac','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:35','2026-07-30 11:40:35'),('019fb2d3-b9f9-7279-b1f8-0f9a041962b5','019fb2c8-2002-7294-8ad0-5f6bfe076a9b','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:47','2026-07-30 11:40:47'),('019fb2d3-e090-701e-8c2e-3fa81cfaa70d','019fb2c8-2010-70b8-aab7-eb9a3814522a','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:40:57','2026-07-30 11:40:57'),('019fb2d4-0a87-72c1-9ae5-43f0ac0811ce','019fb2c8-200d-7061-b93f-b872fb85db1f','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:41:08','2026-07-30 11:41:08'),('019fb2d4-43c7-7380-bd8f-2217f60739c3','019fb2c8-2008-70c7-a12f-8abaec914c9f','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:41:23','2026-07-30 11:41:23'),('019fb2d4-737d-7283-b036-ae5468040704','019fb2c8-1ffe-7296-b42d-2a63ce6fcfe7','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:41:35','2026-07-30 11:41:35'),('019fb2d8-834a-7228-bc85-ffacb94cce6b','019fb2c8-2013-70ce-ba7d-c3659fa74ee7','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','2026-07-30 11:46:01','2026-07-30 11:46:01'),('019fb2f2-e769-70ec-8013-6675d499f0c5','019fb2f2-ce82-73e4-b486-c1802fe83cc0','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:14:51','2026-07-30 12:14:51'),('019fb2f2-fdbd-738f-bd60-b08b65c3a091','019fb2f2-ce94-7338-b338-e67b7fa983cf','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:14:56','2026-07-30 12:14:56'),('019fb2f3-13c9-73ea-a46f-40f048872092','019fb2f2-ce9e-71af-b836-151bf8c46231','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:02','2026-07-30 12:15:02'),('019fb2f3-2940-7305-ad70-49c19c49fa4f','019fb2f2-ce8f-705f-957a-155f8018395c','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:08','2026-07-30 12:15:08'),('019fb2f3-4730-7166-8f95-bf54c4e024d4','019fb2f2-ce98-7211-b22a-20cb535b3c95','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:15','2026-07-30 12:15:15'),('019fb2f3-709c-70d2-9fc1-95e9c4aff65e','019fb2f2-ce8b-72d3-b08b-158620c556da','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:26','2026-07-30 12:15:26'),('019fb2f3-98ef-72ac-9696-87aeb5345225','019fb2f2-ce9b-7055-9ddf-50c6d82e2cce','019fb2b5-70a7-7305-b489-e5d9c36ec44c','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:36','2026-07-30 12:15:36'),('019fb2f3-b787-732c-8248-dd4ffaf9318a','019fb2f2-cea7-7308-b0f0-16c42a36bee3','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:44','2026-07-30 12:15:44'),('019fb2f3-ed18-72c9-969f-f1d6a7b34a45','019fb2f2-cea3-70d4-b591-ebf11ac88c68','019fb2b5-2bc9-73ac-a621-bec7fe6c8781','019fb2b2-bd50-730e-a387-64b54a0f28b1','2026-07-30 12:15:58','2026-07-30 12:15:58'),('019fb2fa-91c6-7002-affc-458290669867','019fb2fa-6ad5-7256-b971-f204147b4123','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-0cca-737b-850c-e8b3d2b39a73','2026-07-30 12:23:13','2026-07-30 12:23:13'),('019fb2fa-ae7c-7337-ac37-3e5b95c93453','019fb2fa-6acc-7248-ad8a-97476a69cde4','019fb2b5-042a-73da-9e67-e1342618ed14','019fb2b3-0cca-737b-850c-e8b3d2b39a73','2026-07-30 12:23:20','2026-07-30 12:23:20');
/*!40000 ALTER TABLE `turma_disciplina_professor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turmas`
--

DROP TABLE IF EXISTS `turmas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turmas` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_alunos` int DEFAULT NULL,
  `curso_classe_turno_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ano_lectivo_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_turma_ano` (`curso_classe_turno_id`,`ano_lectivo_id`,`nome`),
  KEY `turmas_ano_lectivo_id_foreign` (`ano_lectivo_id`),
  CONSTRAINT `turmas_ano_lectivo_id_foreign` FOREIGN KEY (`ano_lectivo_id`) REFERENCES `ano_lectivos` (`id`),
  CONSTRAINT `turmas_curso_classe_turno_id_foreign` FOREIGN KEY (`curso_classe_turno_id`) REFERENCES `curso_classe_turno` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turmas`
--

LOCK TABLES `turmas` WRITE;
/*!40000 ALTER TABLE `turmas` DISABLE KEYS */;
INSERT INTO `turmas` VALUES ('019fb2ab-f6e6-71f0-ad59-07e6dbd3b550','ATI',120,'019fb2ab-c0f5-73e2-91a8-bab2b8f4cd08','019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2026-07-30 10:57:22','2026-07-30 10:57:22'),('019fb2b2-bd50-730e-a387-64b54a0f28b1','ATI',120,'019fb2ac-74e6-702f-9abb-2c8870951de2','019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2026-07-30 11:04:46','2026-07-30 11:04:46'),('019fb2b3-0cca-737b-850c-e8b3d2b39a73','ATI',120,'019fb2b2-ea81-718f-b855-8527e9f80395','019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2026-07-30 11:05:06','2026-07-30 11:05:06'),('019fb2b3-4fdf-73f1-a020-8864b8c7d4c4','ATI',120,'019fb2ac-535f-7032-856a-715cbe52672a','019fb29c-86ac-70d7-a7fc-92cdd6fc3c49','2026-07-30 11:05:23','2026-07-30 11:05:23');
/*!40000 ALTER TABLE `turmas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turnos`
--

LOCK TABLES `turnos` WRITE;
/*!40000 ALTER TABLE `turnos` DISABLE KEYS */;
INSERT INTO `turnos` VALUES ('019fb29c-86ec-71a4-a886-3a2fd3f6f5e3','Manhã','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86f0-70bd-94f2-1dd55337c063','Tarde','2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-86f5-7035-a800-fcf50c87a581','Noite','2026-07-30 10:40:30','2026-07-30 10:40:30');
/*!40000 ALTER TABLE `turnos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instituicao_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_bi_unique` (`bi`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  UNIQUE KEY `users_facebook_id_unique` (`facebook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('019fb29c-875c-71ea-93a8-5ac2549f4634',NULL,'Super Admin','super@sge.ao',NULL,'900000000',NULL,NULL,NULL,'2026-07-30 10:40:30','$2y$12$/a3Bo0qdkrA5qDb1a3pPZuG4AqVuNTS0DhBs1xhykmtru9KvxquaG',NULL,NULL,NULL,NULL,'2026-07-30 10:40:30','2026-07-30 10:40:30'),('019fb29c-8856-70ce-9a8d-ca47027168d5','019fb29c-8699-719d-a7f9-81009814c279','Director IMCL','director.imcl@gestao.ao',NULL,'923000001',NULL,NULL,NULL,'2026-07-30 10:40:30','$2y$12$YNLDKsGvAhw9SRRSODGCn.puMTDRXl4lLnnTAnIEKirZ/3gVixeW2',NULL,NULL,NULL,NULL,'2026-07-30 10:40:31','2026-07-30 10:40:31'),('019fb29c-8943-71b3-a300-a323c81c8ec0','019fb29c-8699-719d-a7f9-81009814c279','Secretaria IMCL','secretaria.imcl@gestao.ao',NULL,'923000002',NULL,NULL,NULL,'2026-07-30 10:40:31','$2y$12$lHnKP020dRSPWZjYThq5GuOkfqGPysqK9TNPD1AltrFWEd35yJEyu',NULL,NULL,NULL,NULL,'2026-07-30 10:40:31','2026-07-30 10:40:31'),('019fb29c-8a26-7091-92b3-a49e90c60d3f','019fb29c-869e-73c1-87ab-76e0d19527cb','Director ESM','director.esm@gestao.ao',NULL,'923000001',NULL,NULL,NULL,'2026-07-30 10:40:31','$2y$12$2IB546.sjkRDSi7pryK7T.97hMHWEnrTccLcGV6YQrEN4bBaeMaQS',NULL,NULL,NULL,NULL,'2026-07-30 10:40:31','2026-07-30 10:40:31'),('019fb29c-8b09-7304-84d4-420babdca1f9','019fb29c-869e-73c1-87ab-76e0d19527cb','Secretaria ESM','secretaria.esm@gestao.ao',NULL,'923000002',NULL,NULL,NULL,'2026-07-30 10:40:31','$2y$12$ELYaWZPwOodLLhYALEmf0O6h5meLI15Hf/MV7xj.sbeHGi61aksmq',NULL,NULL,NULL,NULL,'2026-07-30 10:40:31','2026-07-30 10:40:31'),('019fb29c-8bee-713c-b952-8b745a9cb18e','019fb29c-86a2-7387-bc69-0f5f36aff26f','Director CUA','director.cua@gestao.ao',NULL,'923000001',NULL,NULL,NULL,'2026-07-30 10:40:31','$2y$12$tsnv0/5YPFlWm4wZjHGw2OI9YILZ2z6qw4yZ68w6/FOrtM.1zbVlu',NULL,NULL,NULL,NULL,'2026-07-30 10:40:31','2026-07-30 10:40:31'),('019fb29c-8cd1-7216-9453-fff0f0a084f8','019fb29c-86a2-7387-bc69-0f5f36aff26f','Secretaria CUA','secretaria.cua@gestao.ao',NULL,'923000002',NULL,NULL,NULL,'2026-07-30 10:40:31','$2y$12$jDOJOyBYIN466PYMu6NqcOe.zmaLhtv3gWWZ8Uu524p1WF3ZndTV.',NULL,NULL,NULL,NULL,'2026-07-30 10:40:32','2026-07-30 10:40:32'),('019fb29c-8db5-7098-83e3-821af41976bf','019fb29c-86a7-73f9-bf94-242a0edc02f5','Director lS','director.ls@gestao.ao',NULL,'923000001',NULL,NULL,NULL,'2026-07-30 10:40:32','$2y$12$TL5s4iqDEwKpB0MDVNh5uOvIwC5LGbSz7ZXLvsrt8.hiFZy2UOw0y',NULL,NULL,NULL,NULL,'2026-07-30 10:40:32','2026-07-30 10:40:32'),('019fb29c-8e9a-7058-b9b2-077d79ab725b','019fb29c-86a7-73f9-bf94-242a0edc02f5','Secretaria lS','secretaria.ls@gestao.ao',NULL,'923000002',NULL,NULL,NULL,'2026-07-30 10:40:32','$2y$12$/01GlunQ3fKFCHS4j/EZ2.4FOhNL3IxPPrHt.fW30NJd.mvKyYea2',NULL,NULL,NULL,NULL,'2026-07-30 10:40:32','2026-07-30 10:40:32'),('019fb2b4-b07b-718b-851c-7df72454f6c9','019fb29c-8699-719d-a7f9-81009814c279','Paulina Capitão','capitaopaulinafernando@gmail.com','LA454I5409553','+244935001358',NULL,NULL,NULL,NULL,'$2y$12$spUGmUgT9D5Ms/czRy0ftudbiURdKJDCvtV6MME.MpxAkYiWhNmwy',NULL,NULL,NULL,NULL,'2026-07-30 11:06:53','2026-07-30 11:06:53'),('019fb2b5-0414-70b4-8085-27e978ecc4b5','019fb29c-8699-719d-a7f9-81009814c279','Joaquim Marcial Mbango','marcialmbango@gmail.com','020619207LA055','935001358',NULL,NULL,NULL,NULL,'$2y$12$F6rFlYiuQgNe4TLu3NZEP.TEBt.SQLY0hb6YELqXfbmLuH2CT6YCa',NULL,NULL,NULL,NULL,'2026-07-30 11:07:15','2026-07-30 11:07:15'),('019fb2b5-2bba-72d3-9123-dff464836b16','019fb29c-8699-719d-a7f9-81009814c279','Ximino Miranda','ximinomiranda@gmail.com','0202439207LA055','935001358',NULL,NULL,NULL,NULL,'$2y$12$IpfMfStqyJF6JJm4Uck8NORAEnYzV23S3e4ntRG.jUmSGfjr69QHa',NULL,NULL,NULL,NULL,'2026-07-30 11:07:25','2026-07-30 11:07:25'),('019fb2b5-7097-701e-b5df-c18f5c8dfbbb','019fb29c-8699-719d-a7f9-81009814c279','Eugênio Monteiro','eugeniomonteiro@gmail.com','0202439207LA05512','935001358',NULL,NULL,NULL,NULL,'$2y$12$7DtYa1DMSRRta8VBfJY1k.4Bp8DL5vbsdsZwi5B/74q9PGanCfACS',NULL,NULL,NULL,NULL,'2026-07-30 11:07:43','2026-07-30 11:07:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-30 16:48:05
