-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: dow_zousanzy_cn
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '图标路径',
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '跳转链接',
  `is_popup` tinyint(1) DEFAULT '0' COMMENT '是否弹窗展示 0=跳转 1=弹窗',
  `order` int(11) DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (72,'uploads/cards/card_1767104035_6953de2355957.png','https://dd.zousanzy.cn/dd/img/0.jpg',1,0,'2026-01-06 22:46:21','2026-01-06 22:46:21'),(73,'uploads/cards/card_1767104655_6953e08fc8b5f.png','https://dow.zousanzy.cn/img/wx.png',1,1,'2026-01-06 22:46:21','2026-01-06 22:46:21'),(74,'uploads/cards/card_1767107583_640a0dd78a07d541.png','https://space.bilibili.com/3690979882174551',0,2,'2026-01-06 22:46:21','2026-01-06 22:46:21'),(75,'uploads/cards/card_1767107913_fab18acc205cd0ca.png','https://github.com/by-029/zxsDoc',0,3,'2026-01-06 22:46:21','2026-01-06 22:46:21'),(76,'uploads/cards/card_1767107913_dec2dbc939effb31.png','https://gitee.com/by-029/zxsDoc',0,4,'2026-01-06 22:46:21','2026-01-06 22:46:21');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chapters`
--

DROP TABLE IF EXISTS `chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '所属项目ID',
  `parent_id` int(11) DEFAULT '0' COMMENT '父章节ID，0表示顶级',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '章节标题',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL友好标识',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Markdown内容',
  `html_content` longtext COLLATE utf8mb4_unicode_ci COMMENT '渲染后的HTML内容',
  `order` int(11) DEFAULT '0' COMMENT '排序序号',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `parent_id` (`parent_id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chapters`
--

LOCK TABLES `chapters` WRITE;
/*!40000 ALTER TABLE `chapters` DISABLE KEYS */;
INSERT INTO `chapters` VALUES (64,10,0,'开始使用','20260105-001-001','','',1,'2026-01-05 20:12:08','2026-01-05 20:12:08'),(65,10,64,'1.1程序简介','20260105-001-001-001','','',1,'2026-01-05 20:12:22','2026-01-05 20:12:22'),(66,10,64,' 1.2用户协议','20260105-001-001-002','','',2,'2026-01-05 20:12:35','2026-01-05 20:12:35');
/*!40000 ALTER TABLE `chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nav_menus`
--

DROP TABLE IF EXISTS `nav_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nav_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '菜单名称',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '跳转链接',
  `order` int(11) DEFAULT '0' COMMENT '排序序号',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nav_menus`
--

LOCK TABLES `nav_menus` WRITE;
/*!40000 ALTER TABLE `nav_menus` DISABLE KEYS */;
INSERT INTO `nav_menus` VALUES (8,'首页','index.php',1,'2025-12-27 12:02:05','2025-12-27 12:35:15'),(9,'走小散','https://know.zousanzy.cn',2,'2025-12-27 12:36:30','2025-12-27 14:31:46');
/*!40000 ALTER TABLE `nav_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '项目名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '项目描述',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL友好标识',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Logo路径',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (10,'走小散今日热榜文档中心','梦开始的地方\r\n感谢支持走小散的你们！\r\n2025-11.23','20260105-001',NULL,'2026-01-05 20:11:17','2026-01-05 20:11:17');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设置键名',
  `setting_value` text COLLATE utf8mb4_unicode_ci COMMENT '设置值',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_logo','uploads/logo/logo_1767099108_6953cae4b9240.png','2025-12-30 20:51:48'),(2,'site_name','走小散项目文档系统','2025-12-29 22:21:14'),(10,'copyright_info','<div class=\"simple-announcement\">\r\n  <div class=\"welcome-icon\">\r\n    <i class=\"fas fa-hand-peace\"></i>\r\n  </div>\r\n  <div class=\"welcome-message\">\r\n    Copyright 2025-2026\r\n   走小散 All rights reserved\r\n  </div>\r\n</div>\r\n\r\n<style>\r\n  /* 简洁公告样式 */\r\n  .simple-announcement {\r\n    background: linear-gradient(135deg, #f5f7fa, #e4e8f0);\r\n    border-radius: 10px;\r\n    padding: 15px 20px;\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 15px;\r\n    box-shadow: 0 3px 10px rgba(0,0,0,0.1);\r\n    max-width: 300px;\r\n    margin: 20px auto;\r\n    border-left: 4px solid #4a90e2;\r\n  }\r\n  \r\n  .welcome-icon {\r\n    font-size: 1.8rem;\r\n    color: #4a90e2;\r\n  }\r\n  \r\n  .welcome-message {\r\n    font-size: 1.2rem;\r\n    font-weight: 600;\r\n    color: #333;\r\n  }\r\n</style>\r\n\r\n<!-- 引入Font Awesome图标库 -->\r\n<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">','2025-12-30 15:10:11'),(37,'admin_password','admin123','2026-01-06 22:45:42');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2025-12-25 23:46:06');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'dow_zousanzy_cn'
--

--
-- Dumping routines for database 'dow_zousanzy_cn'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-06 22:46:53
