-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: vanriti
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `vanriti`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `vanriti` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `vanriti`;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` varchar(255) DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `activity_logs_entity_entity_id_index` (`entity`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'App\\Models\\Admin',1,'product_created','App\\Models\\Product',1,'Product \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\" was created.',NULL,'{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:24:26','2026-09-08 04:24:26'),(2,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:24:49','2026-09-08 04:24:49'),(3,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',1,'Product \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\" was updated.','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"draft\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":10,\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"draft\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"10\",\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:24:53','2026-09-08 04:24:53'),(4,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',1,'Product \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\" was updated.','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"draft\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":10,\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"10\",\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:25:21','2026-09-08 04:25:21'),(5,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:27:59','2026-09-08 04:27:59'),(6,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:28:09','2026-09-08 04:28:09'),(7,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:28:15','2026-09-08 04:28:15'),(8,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:28:41','2026-09-08 04:28:41'),(9,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',1,'Product \"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\" was updated.','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":10,\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','{\"name\":\"VANRITI Multani Mitti Powder for Face & Skin | Natural Clay Face Pack | Oil Control & Skin Cleansing | 100% Pure Multani Mitti\",\"slug\":\"vanriti-multani-mitti-powder-for-face-skin-natural-clay-face-pack-oil-control-skin-cleansing-100-pure-multani-mitti\",\"sku\":\"VNRT001\",\"product_type_id\":null,\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"10\",\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 04:30:36','2026-09-08 04:30:36'),(10,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',50,'Image added to \"VANRITI Beetroot Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:38:29','2026-09-08 05:38:29'),(11,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',50,'Image added to \"VANRITI Beetroot Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:38:37','2026-09-08 05:38:37'),(12,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',50,'Image added to \"VANRITI Beetroot Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:38:47','2026-09-08 05:38:47'),(13,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',50,'Product \"VANRITI Beetroot Powder\" was updated.','{\"name\":\"VANRITI Beetroot Powder\",\"slug\":\"vanriti-beetroot-powder\",\"sku\":\"VNRT050\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Beetroot Powder\",\"slug\":\"vanriti-beetroot-powder\",\"sku\":\"VNRT050\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:43:33','2026-09-08 05:43:33'),(14,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',50,'Product \"VANRITI Beetroot Powder\" was updated.','{\"name\":\"VANRITI Beetroot Powder\",\"slug\":\"vanriti-beetroot-powder\",\"sku\":\"VNRT050\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Beetroot Powder\",\"slug\":\"vanriti-beetroot-powder\",\"sku\":\"VNRT050\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":false}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:45:18','2026-09-08 05:45:18'),(15,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',49,'Product \"VANRITI Neem Powder\" was updated.','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:50:36','2026-09-08 05:50:36'),(16,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',49,'Image added to \"VANRITI Neem Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:50:54','2026-09-08 05:50:54'),(17,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',49,'Product \"VANRITI Neem Powder\" was updated.','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:50:58','2026-09-08 05:50:58'),(18,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',49,'Product \"VANRITI Neem Powder\" was updated.','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Neem Powder\",\"slug\":\"vanriti-neem-powder\",\"sku\":\"VNRT049\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":true,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:51:21','2026-09-08 05:51:21'),(19,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',48,'Image added to \"VANRITI Rose Petal Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:52:32','2026-09-08 05:52:32'),(20,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',48,'Product \"VANRITI Rose Petal Powder\" was updated.','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:53:08','2026-09-08 05:53:08'),(21,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',48,'Product \"VANRITI Rose Petal Powder\" was updated.','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:53:28','2026-09-08 05:53:28'),(22,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',48,'Product \"VANRITI Rose Petal Powder\" was updated.','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Rose Petal Powder\",\"slug\":\"vanriti-rose-petal-powder\",\"sku\":\"VNRT048\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:54:17','2026-09-08 05:54:17'),(23,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',47,'Image added to \"VANRITI Moringa Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:54:30','2026-09-08 05:54:30'),(24,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',47,'Product \"VANRITI Moringa Powder\" was updated.','{\"name\":\"VANRITI Moringa Powder\",\"slug\":\"vanriti-moringa-powder\",\"sku\":\"VNRT047\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Moringa Powder\",\"slug\":\"vanriti-moringa-powder\",\"sku\":\"VNRT047\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:54:38','2026-09-08 05:54:38'),(25,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',46,'Image added to \"VANRITI Hibiscus Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:54:49','2026-09-08 05:54:49'),(26,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',46,'Product \"VANRITI Hibiscus Powder\" was updated.','{\"name\":\"VANRITI Hibiscus Powder\",\"slug\":\"vanriti-hibiscus-powder\",\"sku\":\"VNRT046\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Hibiscus Powder\",\"slug\":\"vanriti-hibiscus-powder\",\"sku\":\"VNRT046\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:55:25','2026-09-08 05:55:25'),(27,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',46,'Product \"VANRITI Hibiscus Powder\" was updated.','{\"name\":\"VANRITI Hibiscus Powder\",\"slug\":\"vanriti-hibiscus-powder\",\"sku\":\"VNRT046\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Hibiscus Powder\",\"slug\":\"vanriti-hibiscus-powder\",\"sku\":\"VNRT046\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:56:30','2026-09-08 05:56:30'),(28,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',45,'Image added to \"VANRITI Shikakai Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:56:41','2026-09-08 05:56:41'),(29,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',45,'Product \"VANRITI Shikakai Powder\" was updated.','{\"name\":\"VANRITI Shikakai Powder\",\"slug\":\"vanriti-shikakai-powder\",\"sku\":\"VNRT045\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Shikakai Powder\",\"slug\":\"vanriti-shikakai-powder\",\"sku\":\"VNRT045\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:59:37','2026-09-08 05:59:37'),(30,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',44,'Image added to \"VANRITI Reetha Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 05:59:48','2026-09-08 05:59:48'),(31,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',43,'Image added to \"VANRITI Amla Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 06:00:05','2026-09-08 06:00:05'),(32,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',42,'Image added to \"VANRITI Bhringraj Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 06:22:07','2026-09-08 06:22:07'),(33,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',42,'Product \"VANRITI Bhringraj Powder\" was updated.','{\"name\":\"VANRITI Bhringraj Powder\",\"slug\":\"vanriti-bhringraj-powder\",\"sku\":\"VNRT042\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Bhringraj Powder\",\"slug\":\"vanriti-bhringraj-powder\",\"sku\":\"VNRT042\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 06:22:10','2026-09-08 06:22:10'),(34,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',41,'Image added to \"VANRITI Multani Mitti Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 06:22:34','2026-09-08 06:22:34'),(35,'App\\Models\\Admin',1,'product_updated','App\\Models\\Product',41,'Product \"VANRITI Multani Mitti Powder\" was updated.','{\"name\":\"VANRITI Multani Mitti Powder\",\"slug\":\"vanriti-multani-mitti-powder\",\"sku\":\"VNRT041\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.00\",\"gst_rate\":\"5.00\",\"stock\":100,\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','{\"name\":\"VANRITI Multani Mitti Powder\",\"slug\":\"vanriti-multani-mitti-powder\",\"sku\":\"VNRT041\",\"status\":\"active\",\"mrp\":\"299.00\",\"selling_price\":\"99.00\",\"discount_percent\":\"66.90\",\"gst_rate\":\"5.00\",\"stock\":\"100\",\"is_featured\":false,\"is_bestseller\":false,\"is_new_arrival\":true}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 06:22:41','2026-09-08 06:22:41'),(36,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',4,'Image added to \"VANRITI Amla Bhringraj Hair Care Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 07:57:10','2026-09-08 07:57:10'),(37,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',1,'Image added to \"VANRITI Amla Reetha Shikakai Hair Cleanse Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 07:57:59','2026-09-08 07:57:59'),(38,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',40,'Image added to \"VANRITI Moringa Beetroot Amla Hibiscus Wellness Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 08:02:54','2026-09-08 08:02:54'),(39,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',39,'Image added to \"VANRITI Moringa Amla Hibiscus Wellness Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 08:07:16','2026-09-08 08:07:16'),(40,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',38,'Image added to \"VANRITI Amla Hibiscus Wellness Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 08:09:25','2026-09-08 08:09:25'),(41,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',37,'Image added to \"VANRITI Amla Beetroot Hibiscus Wellness Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 08:11:33','2026-09-08 08:11:33'),(42,'App\\Models\\Admin',1,'product_image_added','App\\Models\\Product',36,'Image added to \"VANRITI Moringa Beetroot Hibiscus Wellness Powder\".',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 08:13:40','2026-09-08 08:13:40'),(43,'App\\Models\\Admin',1,'order_status_changed','App\\Models\\Order',3,'Order status changed to confirmed.','{\"status\":\"pending\"}','{\"status\":\"confirmed\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-08 12:33:47','2026-09-08 12:33:47');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `pincode` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `type` enum('home','office','other') NOT NULL DEFAULT 'home',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_user_id_is_default_index` (`user_id`,`is_default`),
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,1,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','home',0,'2026-09-08 12:29:23','2026-09-08 12:31:23'),(2,1,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','home',0,'2026-09-08 12:29:26','2026-09-08 12:31:23'),(3,1,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','home',0,'2026-09-08 12:29:51','2026-09-08 12:31:23'),(4,1,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','home',1,'2026-09-08 12:31:23','2026-09-08 12:31:23');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role`
--

DROP TABLE IF EXISTS `admin_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_role` (
  `admin_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`admin_id`,`role_id`),
  KEY `admin_role_role_id_foreign` (`role_id`),
  CONSTRAINT `admin_role_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role`
--

LOCK TABLES `admin_role` WRITE;
/*!40000 ALTER TABLE `admin_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'VANRITI Admin','admin@vanriti.com','$2y$12$eOZu8tyvtohGKCLTDIIQxumkYJrhHa/3svKVgiasspVRx9TgejPpa',NULL,NULL,1,'active','2026-09-08 23:54:32','::1',NULL,'2026-09-07 02:42:46','2026-09-08 23:54:32');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `mobile_image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `type` enum('hero','promotional','section') NOT NULL DEFAULT 'hero',
  `position` varchar(255) NOT NULL DEFAULT 'home_top',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_type_position_status_index` (`type`,`position`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (2,NULL,NULL,'banners/LMLjQcCS8MtJ2luw5rZGLjkExTEqjE4K59WfJkXS.png',NULL,'http://localhost/vanriti/','hero','home_top',0,'active',NULL,NULL,'2026-09-08 01:13:31','2026-09-08 01:14:42');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_category_id` bigint(20) unsigned DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blogs_slug_unique` (`slug`),
  KEY `blogs_blog_category_id_foreign` (`blog_category_id`),
  KEY `blogs_admin_id_foreign` (`admin_id`),
  KEY `blogs_status_published_at_index` (`status`,`published_at`),
  CONSTRAINT `blogs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blogs_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('vanriti-cache-dashboard.sales.by.day','a:2:{s:10:\"2026-09-08\";s:7:\"1833.50\";s:10:\"2026-09-09\";s:7:\"1420.65\";}',1788942974);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
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
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `mrp` decimal(12,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_item_unique` (`cart_id`,`product_id`,`product_variant_id`),
  KEY `cart_items_product_id_foreign` (`product_id`),
  KEY `cart_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_type` varchar(255) NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_owner_unique` (`owner_type`,`owner_id`,`session_id`),
  KEY `carts_owner_type_owner_id_index` (`owner_type`,`owner_id`),
  KEY `carts_session_id_index` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (3,'App\\Models\\User',1,NULL,'2026-09-08 10:22:00','2026-09-08 10:22:00');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_status_sort_order_index` (`parent_id`,`status`,`sort_order`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Hair Care','hair-care','Hair Care Products','categories/IT2OtchVD06w3XdiRKa3NHEFQKmrFu183Lyrg0vq.webp',NULL,NULL,NULL,'active',0,'2026-09-08 04:05:21','2026-09-08 04:05:21'),(2,NULL,'Skin Care','skin-care','Skin Care Products','categories/A5EM6QDpbZKNwvBD1nixzwaS0DjroOWQQlv3E9LQ.webp',NULL,NULL,NULL,'active',0,'2026-09-08 04:06:45','2026-09-08 04:06:45'),(3,NULL,'Health & Wellness','health-and-wellness','Health & Wellness Products','categories/myQ8QkpAkWLI5DobSir2YhM6hikcY0vNRHotkpUo.webp',NULL,NULL,NULL,'active',0,'2026-09-08 04:08:22','2026-09-08 04:08:22'),(4,1,'Hair Care Blends','hair-care-blends','Hair Care Blends',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:14:22','2026-09-08 04:14:22'),(5,1,'Hair Cleansing Powders','hair-cleansing-powders','Hair Cleansing Powders',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:15:09','2026-09-08 04:15:09'),(6,1,'Hair Powders','hair-powders','Hair Powders',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:15:27','2026-09-08 04:15:27'),(7,2,'Face Packs & Masks','face-packs-masks','Face Packs & Masks',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:15:46','2026-09-08 04:15:46'),(8,2,'Skin Care Blends','skin-care-blends','Skin Care Blends',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:16:08','2026-09-08 04:16:08'),(9,2,'Skin Care Powders','skin-care-powders','Skin Care Powders',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:16:24','2026-09-08 04:16:24'),(10,3,'Herbal Powders','herbal-powders','Herbal Powders',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:16:54','2026-09-08 04:16:54'),(11,3,'Wellness Blends','wellness-blends','Wellness Blends',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:17:07','2026-09-08 04:17:07'),(12,3,'Nutritional Powders','nutritional-powders','Nutritional Powders',NULL,NULL,NULL,NULL,'active',0,'2026-09-08 04:17:19','2026-09-08 04:17:19');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_categories`
--

DROP TABLE IF EXISTS `coupon_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_categories` (
  `coupon_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`category_id`),
  KEY `coupon_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `coupon_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_categories_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_categories`
--

LOCK TABLES `coupon_categories` WRITE;
/*!40000 ALTER TABLE `coupon_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_products`
--

DROP TABLE IF EXISTS `coupon_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_products` (
  `coupon_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`product_id`),
  KEY `coupon_products_product_id_foreign` (`product_id`),
  CONSTRAINT `coupon_products_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_products`
--

LOCK TABLES `coupon_products` WRITE;
/*!40000 ALTER TABLE `coupon_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usages`
--

DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupon_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coupon_usages_user_id_foreign` (`user_id`),
  KEY `coupon_usages_coupon_id_user_id_index` (`coupon_id`,`user_id`),
  KEY `coupon_usages_order_id_index` (`order_id`),
  CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usages`
--

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_cart_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `per_customer_limit` int(11) NOT NULL DEFAULT 1,
  `first_order_only` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_is_active_expires_at_index` (`is_active`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
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
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stockable_type` varchar(255) NOT NULL,
  `stockable_id` bigint(20) unsigned NOT NULL,
  `stock_on_hand` int(11) NOT NULL DEFAULT 0,
  `reserved` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_unique` (`stockable_type`,`stockable_id`),
  KEY `inventories_stockable_type_stockable_id_index` (`stockable_type`,`stockable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventories`
--

LOCK TABLES `inventories` WRITE;
/*!40000 ALTER TABLE `inventories` DISABLE KEYS */;
INSERT INTO `inventories` VALUES (1,'App\\Models\\Product',49,95,0,5,'2026-09-08 12:13:32','2026-09-09 01:53:56'),(2,'App\\Models\\Product',50,90,0,5,'2026-09-08 12:13:32','2026-09-09 01:54:46'),(3,'App\\Models\\Product',1,99,0,5,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(4,'App\\Models\\Product',16,99,0,5,'2026-09-09 01:55:43','2026-09-09 01:55:43');
/*!40000 ALTER TABLE `inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stockable_type` varchar(255) DEFAULT NULL,
  `stockable_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `type` enum('purchase','sale','adjustment','return','reversal') NOT NULL DEFAULT 'adjustment',
  `quantity_change` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_stockable_type_stockable_id_index` (`stockable_type`,`stockable_id`),
  KEY `inventory_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_transactions_admin_id_foreign` (`admin_id`),
  KEY `inventory_transactions_stockable_type_stockable_id_type_index` (`stockable_type`,`stockable_id`,`type`),
  CONSTRAINT `inventory_transactions_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
INSERT INTO `inventory_transactions` VALUES (1,'App\\Models\\Product',49,'App\\Models\\Order',1,'sale',-1,100,99,'Order VAN-2026-000001',NULL,'2026-09-08 12:13:32','2026-09-08 12:13:32'),(2,'App\\Models\\Product',50,'App\\Models\\Order',1,'sale',-1,100,99,'Order VAN-2026-000001',NULL,'2026-09-08 12:13:32','2026-09-08 12:13:32'),(3,'App\\Models\\Product',50,'App\\Models\\Order',2,'sale',-1,99,98,'Order VAN-2026-000002',NULL,'2026-09-08 12:19:18','2026-09-08 12:19:18'),(4,'App\\Models\\Product',50,'App\\Models\\Order',3,'sale',-1,98,97,'Order VAN-2026-000003',NULL,'2026-09-08 12:33:16','2026-09-08 12:33:16'),(5,'App\\Models\\Product',50,'App\\Models\\Order',4,'sale',-1,97,96,'Order VAN-2026-000004',NULL,'2026-09-08 12:38:36','2026-09-08 12:38:36'),(6,'App\\Models\\Product',50,'App\\Models\\Order',5,'sale',-1,96,95,'Order VAN-2026-000005',NULL,'2026-09-08 13:06:03','2026-09-08 13:06:03'),(7,'App\\Models\\Product',49,'App\\Models\\Order',6,'sale',-1,99,98,'Order VAN-2026-000006',NULL,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(8,'App\\Models\\Product',50,'App\\Models\\Order',6,'sale',-2,95,93,'Order VAN-2026-000006',NULL,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(9,'App\\Models\\Product',1,'App\\Models\\Order',6,'sale',-1,100,99,'Order VAN-2026-000006',NULL,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(10,'App\\Models\\Product',49,'App\\Models\\Order',7,'sale',-1,98,97,'Order VAN-2026-000007',NULL,'2026-09-09 01:09:14','2026-09-09 01:09:14'),(11,'App\\Models\\Product',50,'App\\Models\\Order',8,'sale',-1,93,92,'Order VAN-2026-000008',NULL,'2026-09-09 01:17:33','2026-09-09 01:17:33'),(12,'App\\Models\\Product',49,'App\\Models\\Order',9,'sale',-1,97,96,'Order VAN-2026-000009',NULL,'2026-09-09 01:19:37','2026-09-09 01:19:37'),(13,'App\\Models\\Product',50,'App\\Models\\Order',10,'sale',-1,92,91,'Order VAN-2026-000010',NULL,'2026-09-09 01:40:44','2026-09-09 01:40:44'),(14,'App\\Models\\Product',49,'App\\Models\\Order',11,'sale',-1,96,95,'Order VAN-2026-000011',NULL,'2026-09-09 01:53:56','2026-09-09 01:53:56'),(15,'App\\Models\\Product',50,'App\\Models\\Order',12,'sale',-1,91,90,'Order VAN-2026-000012',NULL,'2026-09-09 01:54:46','2026-09-09 01:54:46'),(16,'App\\Models\\Product',16,'App\\Models\\Order',13,'sale',-1,100,99,'Order VAN-2026-000013',NULL,'2026-09-09 01:55:43','2026-09-09 01:55:43');
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"c5f99039-9625-49b3-bc06-886a36a4f0f1\",\"displayName\":\"App\\\\Notifications\\\\OrderStatusNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:1;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:41:\\\"App\\\\Notifications\\\\OrderStatusNotification\\\":3:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:1:{i:0;s:4:\\\"user\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"status\\\";s:9:\\\"confirmed\\\";s:2:\\\"id\\\";s:36:\\\"ea823555-d75b-4bd7-aa14-dc3a97be243b\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:8:\\\"database\\\";}}\",\"batchId\":null},\"createdAt\":1788890628,\"delay\":null}',0,NULL,1788890628,1788890628),(2,'default','{\"uuid\":\"3a1a39a3-e5fd-47ac-8124-9b15562aa05c\",\"displayName\":\"App\\\\Notifications\\\\OrderStatusNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:1;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:41:\\\"App\\\\Notifications\\\\OrderStatusNotification\\\":3:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:3;s:9:\\\"relations\\\";a:1:{i:0;s:4:\\\"user\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:6:\\\"status\\\";s:9:\\\"confirmed\\\";s:2:\\\"id\\\";s:36:\\\"ea823555-d75b-4bd7-aa14-dc3a97be243b\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\",\"batchId\":null},\"createdAt\":1788890628,\"delay\":null}',0,NULL,1788890628,1788890628);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_09_072022_add_failed_at_to_payments_table',1),(2,'2026_09_09_073535_add_shipmojo_fields_to_shipments_table',2),(3,'2026_09_09_074258_seed_shipmojo_settings',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_subscribed` tinyint(1) NOT NULL DEFAULT 1,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletters_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletters`
--

LOCK TABLES `newsletters` WRITE;
/*!40000 ALTER TABLE `newsletters` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notifiable_type_notifiable_id_read_at_index` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,49,NULL,'VANRITI Neem Powder',NULL,'VNRT049','products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png','Hair Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 12:13:32','2026-09-08 12:13:32'),(2,1,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 12:13:32','2026-09-08 12:13:32'),(3,2,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 12:19:18','2026-09-08 12:19:18'),(4,3,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 12:33:16','2026-09-08 12:33:16'),(5,4,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 12:38:36','2026-09-08 12:38:36'),(6,5,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 13:06:03','2026-09-08 13:06:03'),(7,6,49,NULL,'VANRITI Neem Powder',NULL,'VNRT049','products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png','Hair Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(8,6,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',2,299.00,99.00,400.00,5.00,9.90,198.00,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(9,6,1,NULL,'VANRITI Amla Reetha Shikakai Hair Cleanse Powder',NULL,'VNRT001','products/Me0zFLMIO9V9QpbsHD4Ewh9y1bMFInv0XGOY2rhk.png',NULL,1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(10,7,49,NULL,'VANRITI Neem Powder',NULL,'VNRT049','products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png','Hair Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:09:14','2026-09-09 01:09:14'),(11,8,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:17:33','2026-09-09 01:17:33'),(12,9,49,NULL,'VANRITI Neem Powder',NULL,'VNRT049','products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png','Hair Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:19:37','2026-09-09 01:19:37'),(13,10,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:40:44','2026-09-09 01:40:44'),(14,11,49,NULL,'VANRITI Neem Powder',NULL,'VNRT049','products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png','Hair Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:53:56','2026-09-09 01:53:56'),(15,12,50,NULL,'VANRITI Beetroot Powder',NULL,'VNRT050','products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png','Skin Care Powders',1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:54:46','2026-09-09 01:54:46'),(16,13,16,NULL,'VANRITI Multani Mitti Rose Petal Face Pack Powder',NULL,'VNRT016',NULL,NULL,1,299.00,99.00,200.00,5.00,4.95,99.00,'2026-09-09 01:55:43','2026-09-09 01:55:43');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_histories`
--

DROP TABLE IF EXISTS `order_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_status_histories_admin_id_foreign` (`admin_id`),
  KEY `order_status_histories_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `order_status_histories_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_histories`
--

LOCK TABLES `order_status_histories` WRITE;
/*!40000 ALTER TABLE `order_status_histories` DISABLE KEYS */;
INSERT INTO `order_status_histories` VALUES (1,1,'pending',NULL,'Order placed successfully.',1,'2026-09-08 12:13:32','2026-09-08 12:13:32'),(2,2,'pending',NULL,'Order placed successfully.',1,'2026-09-08 12:19:18','2026-09-08 12:19:18'),(3,3,'pending',NULL,'Order placed successfully.',1,'2026-09-08 12:33:16','2026-09-08 12:33:16'),(4,3,'confirmed','pending',NULL,1,'2026-09-08 12:33:47','2026-09-08 12:33:47'),(5,4,'pending',NULL,'Order placed successfully.',1,'2026-09-08 12:38:36','2026-09-08 12:38:36'),(6,5,'pending',NULL,'Order placed successfully.',1,'2026-09-08 13:06:03','2026-09-08 13:06:03'),(7,6,'pending',NULL,'Order placed successfully.',1,'2026-09-08 13:56:16','2026-09-08 13:56:16'),(8,7,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:09:14','2026-09-09 01:09:14'),(9,8,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:17:33','2026-09-09 01:17:33'),(10,9,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:19:37','2026-09-09 01:19:37'),(11,10,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:40:44','2026-09-09 01:40:44'),(12,11,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:53:56','2026-09-09 01:53:56'),(13,12,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:54:46','2026-09-09 01:54:46'),(14,13,'pending',NULL,'Order placed successfully.',1,'2026-09-09 01:55:43','2026-09-09 01:55:43');
/*!40000 ALTER TABLE `order_status_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) unsigned DEFAULT NULL,
  `billing_name` varchar(255) NOT NULL,
  `billing_mobile` varchar(255) NOT NULL,
  `billing_address_line1` varchar(255) NOT NULL,
  `billing_address_line2` varchar(255) DEFAULT NULL,
  `billing_landmark` varchar(255) DEFAULT NULL,
  `billing_city` varchar(255) NOT NULL,
  `billing_state` varchar(255) NOT NULL,
  `billing_pincode` varchar(255) NOT NULL,
  `billing_country` varchar(255) NOT NULL DEFAULT 'India',
  `shipping_name` varchar(255) NOT NULL,
  `shipping_mobile` varchar(255) NOT NULL,
  `shipping_address_line1` varchar(255) NOT NULL,
  `shipping_address_line2` varchar(255) DEFAULT NULL,
  `shipping_landmark` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(255) NOT NULL,
  `shipping_state` varchar(255) NOT NULL,
  `shipping_pincode` varchar(255) NOT NULL,
  `shipping_country` varchar(255) NOT NULL DEFAULT 'India',
  `is_billing_same` tinyint(1) NOT NULL DEFAULT 1,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `coupon_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','processing','paid','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','failed') NOT NULL DEFAULT 'pending',
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_coupon_id_foreign` (`coupon_id`),
  KEY `orders_user_id_order_status_payment_status_index` (`user_id`,`order_status`,`payment_status`),
  KEY `orders_created_at_index` (`created_at`),
  CONSTRAINT `orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'VAN-2026-000001',1,NULL,'Test User','9898989898','Avjhssj','jbjhb','nbjh','hjhgjhg','kjghjhg','121001','India','Test User','9898989898','Avjhssj','jbjhb','nbjh','hjhgjhg','kjghjhg','121001','India',1,198.00,400.00,0.00,149.00,9.90,356.90,356.90,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-08 12:13:32','2026-09-08 12:13:33'),(2,'VAN-2026-000002',1,NULL,'Test User','7878787878','test','test','test','test','haryana','121001','India','Test User','7878787878','test','test','test','test','haryana','121001','India',1,99.00,200.00,0.00,149.00,4.95,252.95,252.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-08 12:19:18','2026-09-08 12:19:18'),(3,'VAN-2026-000003',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'cod','pending','confirmed',NULL,NULL,NULL,'2026-09-08 12:33:16','2026-09-08 12:33:47'),(4,'VAN-2026-000004',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India',1,99.00,200.00,0.00,149.00,4.95,252.95,252.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-08 12:38:36','2026-09-08 12:38:37'),(5,'VAN-2026-000005',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India',1,99.00,200.00,0.00,149.00,4.95,252.95,252.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-08 13:06:03','2026-09-08 13:06:06'),(6,'VAN-2026-000006',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India',1,396.00,800.00,0.00,99.00,19.80,514.80,514.80,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-08 13:56:16','2026-09-08 13:56:17'),(7,'VAN-2026-000007',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:09:14','2026-09-09 01:09:16'),(8,'VAN-2026-000008',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:17:33','2026-09-09 01:17:33'),(9,'VAN-2026-000009',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:19:37','2026-09-09 01:19:37'),(10,'VAN-2026-000010',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:40:44','2026-09-09 01:40:45'),(11,'VAN-2026-000011',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,0.00,202.95,'razorpay','paid','pending',NULL,NULL,NULL,'2026-09-09 01:53:56','2026-09-09 01:54:16'),(12,'VAN-2026-000012',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India','Avkash','7878787878','hgh','hghg','hgh','Faridabad','Haryana','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:54:46','2026-09-09 01:54:46'),(13,'VAN-2026-000013',1,NULL,'Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India','Avkash','7878787878','hgh','hghg','hgh','jhghg','jghg','121001','India',1,99.00,200.00,0.00,99.00,4.95,202.95,202.95,0.00,'razorpay','processing','pending',NULL,NULL,NULL,'2026-09-09 01:55:43','2026-09-09 01:55:44');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (2,'About Us','about-us','<style>\r\n/* =========================================================\r\n   VANRITI — PREMIUM ABOUT US PAGE\r\n   Font intentionally NOT defined.\r\n   Inherits website\'s existing font.\r\n   ========================================================= */\r\n\r\n.vanriti-about {\r\n    --vr-forest: #263D25;\r\n    --vr-olive: #6F8736;\r\n    --vr-ivory: #F7F4EA;\r\n    --vr-cream: #FBFAF6;\r\n    --vr-text: #34392F;\r\n    --vr-muted: #73786F;\r\n    --vr-border: #E5E6DD;\r\n\r\n    color: var(--vr-text);\r\n    line-height: 1.8;\r\n    overflow: hidden;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   HERO\r\n   ========================================================= */\r\n\r\n.vr-about-hero {\r\n    min-height: 650px;\r\n    display: flex;\r\n    align-items: center;\r\n    position: relative;\r\n    background: var(--vr-ivory);\r\n    overflow: hidden;\r\n}\r\n\r\n.vr-about-hero-content {\r\n    width: 52%;\r\n    max-width: 650px;\r\n    padding: 90px 7%;\r\n    position: relative;\r\n    z-index: 2;\r\n}\r\n\r\n.vr-about-eyebrow {\r\n    display: inline-flex;\r\n    align-items: center;\r\n    gap: 12px;\r\n    margin-bottom: 22px;\r\n    color: var(--vr-olive);\r\n    font-size: 11px;\r\n    font-weight: 600;\r\n    letter-spacing: 2.8px;\r\n    text-transform: uppercase;\r\n}\r\n\r\n.vr-about-eyebrow::before {\r\n    content: \"\";\r\n    width: 32px;\r\n    height: 1px;\r\n    background: var(--vr-olive);\r\n}\r\n\r\n.vr-about-hero h1 {\r\n    margin: 0;\r\n    color: var(--vr-forest);\r\n    font-size: clamp(48px, 5.5vw, 76px);\r\n    font-weight: 500;\r\n    line-height: 1.05;\r\n    letter-spacing: -2px;\r\n}\r\n\r\n.vr-about-hero h1 span {\r\n    display: block;\r\n    color: var(--vr-olive);\r\n}\r\n\r\n.vr-about-hero p {\r\n    max-width: 520px;\r\n    margin: 27px 0 0;\r\n    color: var(--vr-muted);\r\n    font-size: 17px;\r\n    line-height: 1.85;\r\n}\r\n\r\n.vr-about-hero-image {\r\n    position: absolute;\r\n    top: 0;\r\n    right: 0;\r\n    width: 50%;\r\n    height: 100%;\r\n    overflow: hidden;\r\n}\r\n\r\n.vr-about-hero-image img {\r\n    width: 100%;\r\n    height: 100%;\r\n    object-fit: cover;\r\n    display: block;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   INTRODUCTION\r\n   ========================================================= */\r\n\r\n.vr-about-intro {\r\n    max-width: 900px;\r\n    margin: 0 auto;\r\n    padding: 105px 24px;\r\n    text-align: center;\r\n}\r\n\r\n.vr-about-small-label {\r\n    margin-bottom: 16px;\r\n    color: var(--vr-olive);\r\n    font-size: 10px;\r\n    font-weight: 600;\r\n    letter-spacing: 2.5px;\r\n    text-transform: uppercase;\r\n}\r\n\r\n.vr-about-intro h2 {\r\n    margin: 0 auto 24px;\r\n    max-width: 760px;\r\n    color: var(--vr-forest);\r\n    font-size: clamp(32px, 4vw, 48px);\r\n    font-weight: 500;\r\n    line-height: 1.25;\r\n    letter-spacing: -1px;\r\n}\r\n\r\n.vr-about-intro p {\r\n    max-width: 700px;\r\n    margin: 0 auto;\r\n    color: var(--vr-muted);\r\n    font-size: 16px;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   STORY SECTION\r\n   ========================================================= */\r\n\r\n.vr-about-story {\r\n    background: var(--vr-cream);\r\n    padding: 100px 7%;\r\n}\r\n\r\n.vr-about-story-inner {\r\n    max-width: 1180px;\r\n    margin: 0 auto;\r\n    display: grid;\r\n    grid-template-columns: 1fr 1fr;\r\n    align-items: center;\r\n    gap: 85px;\r\n}\r\n\r\n.vr-about-story-image {\r\n    position: relative;\r\n}\r\n\r\n.vr-about-story-image img {\r\n    width: 100%;\r\n    aspect-ratio: 4 / 5;\r\n    object-fit: cover;\r\n    display: block;\r\n}\r\n\r\n.vr-about-story-image::after {\r\n    content: \"\";\r\n    position: absolute;\r\n    width: 75px;\r\n    height: 75px;\r\n    right: -18px;\r\n    bottom: -18px;\r\n    border-right: 1px solid var(--vr-olive);\r\n    border-bottom: 1px solid var(--vr-olive);\r\n}\r\n\r\n.vr-about-story-content {\r\n    max-width: 520px;\r\n}\r\n\r\n.vr-about-story-content h2 {\r\n    margin: 0 0 22px;\r\n    color: var(--vr-forest);\r\n    font-size: 38px;\r\n    font-weight: 500;\r\n    line-height: 1.25;\r\n}\r\n\r\n.vr-about-story-content p {\r\n    margin: 0 0 18px;\r\n    color: var(--vr-muted);\r\n    font-size: 15px;\r\n}\r\n\r\n.vr-about-signature {\r\n    margin-top: 30px;\r\n    padding-top: 22px;\r\n    border-top: 1px solid var(--vr-border);\r\n    color: var(--vr-forest);\r\n    font-size: 13px;\r\n    font-weight: 600;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   BRAND VALUES\r\n   ========================================================= */\r\n\r\n.vr-about-values {\r\n    padding: 105px 7%;\r\n    background: #fff;\r\n}\r\n\r\n.vr-about-values-header {\r\n    max-width: 650px;\r\n    margin: 0 auto 60px;\r\n    text-align: center;\r\n}\r\n\r\n.vr-about-values-header h2 {\r\n    margin: 0 0 18px;\r\n    color: var(--vr-forest);\r\n    font-size: 40px;\r\n    font-weight: 500;\r\n}\r\n\r\n.vr-about-values-header p {\r\n    margin: 0;\r\n    color: var(--vr-muted);\r\n    font-size: 15px;\r\n}\r\n\r\n.vr-values-grid {\r\n    max-width: 1100px;\r\n    margin: 0 auto;\r\n    display: grid;\r\n    grid-template-columns: repeat(4, 1fr);\r\n    border-top: 1px solid var(--vr-border);\r\n    border-bottom: 1px solid var(--vr-border);\r\n}\r\n\r\n.vr-value {\r\n    padding: 42px 28px;\r\n    text-align: center;\r\n    border-right: 1px solid var(--vr-border);\r\n}\r\n\r\n.vr-value:last-child {\r\n    border-right: none;\r\n}\r\n\r\n.vr-value-number {\r\n    margin-bottom: 18px;\r\n    color: var(--vr-olive);\r\n    font-size: 12px;\r\n    letter-spacing: 2px;\r\n}\r\n\r\n.vr-value h3 {\r\n    margin: 0 0 12px;\r\n    color: var(--vr-forest);\r\n    font-size: 19px;\r\n    font-weight: 600;\r\n}\r\n\r\n.vr-value p {\r\n    margin: 0;\r\n    color: var(--vr-muted);\r\n    font-size: 13px;\r\n    line-height: 1.7;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   BOTANICAL SECTION\r\n   ========================================================= */\r\n\r\n.vr-about-botanicals {\r\n    background: var(--vr-ivory);\r\n    padding: 100px 7%;\r\n}\r\n\r\n.vr-botanicals-inner {\r\n    max-width: 1180px;\r\n    margin: 0 auto;\r\n    display: grid;\r\n    grid-template-columns: .8fr 1.2fr;\r\n    gap: 80px;\r\n    align-items: center;\r\n}\r\n\r\n.vr-botanicals-content h2 {\r\n    margin: 0 0 20px;\r\n    color: var(--vr-forest);\r\n    font-size: 40px;\r\n    font-weight: 500;\r\n    line-height: 1.25;\r\n}\r\n\r\n.vr-botanicals-content p {\r\n    margin: 0 0 20px;\r\n    color: var(--vr-muted);\r\n    font-size: 15px;\r\n}\r\n\r\n.vr-botanicals-list {\r\n    margin-top: 30px;\r\n    display: grid;\r\n    grid-template-columns: repeat(2, 1fr);\r\n    gap: 0;\r\n    border-top: 1px solid var(--vr-border);\r\n}\r\n\r\n.vr-botanical-item {\r\n    padding: 14px 0;\r\n    border-bottom: 1px solid var(--vr-border);\r\n    color: var(--vr-forest);\r\n    font-size: 13px;\r\n}\r\n\r\n.vr-botanical-item::before {\r\n    content: \"•\";\r\n    margin-right: 9px;\r\n    color: var(--vr-olive);\r\n}\r\n\r\n.vr-botanicals-image img {\r\n    width: 100%;\r\n    aspect-ratio: 16 / 10;\r\n    object-fit: cover;\r\n    display: block;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   BRAND PROMISE\r\n   ========================================================= */\r\n\r\n.vr-about-promise {\r\n    padding: 120px 24px;\r\n    text-align: center;\r\n    background: var(--vr-forest);\r\n    color: #fff;\r\n}\r\n\r\n.vr-promise-label {\r\n    margin-bottom: 18px;\r\n    color: #B2C27A;\r\n    font-size: 10px;\r\n    font-weight: 600;\r\n    letter-spacing: 3px;\r\n    text-transform: uppercase;\r\n}\r\n\r\n.vr-about-promise h2 {\r\n    max-width: 800px;\r\n    margin: 0 auto;\r\n    color: #fff;\r\n    font-size: clamp(35px, 4vw, 52px);\r\n    font-weight: 500;\r\n    line-height: 1.25;\r\n}\r\n\r\n.vr-about-promise p {\r\n    max-width: 620px;\r\n    margin: 24px auto 0;\r\n    color: rgba(255,255,255,.72);\r\n    font-size: 15px;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   CTA\r\n   ========================================================= */\r\n\r\n.vr-about-cta {\r\n    padding: 90px 24px;\r\n    text-align: center;\r\n    background: #fff;\r\n}\r\n\r\n.vr-about-cta h2 {\r\n    margin: 0 0 15px;\r\n    color: var(--vr-forest);\r\n    font-size: 34px;\r\n    font-weight: 500;\r\n}\r\n\r\n.vr-about-cta p {\r\n    margin: 0 auto 28px;\r\n    max-width: 550px;\r\n    color: var(--vr-muted);\r\n    font-size: 15px;\r\n}\r\n\r\n.vr-about-button {\r\n    display: inline-flex;\r\n    align-items: center;\r\n    justify-content: center;\r\n    padding: 13px 30px;\r\n    background: var(--vr-forest);\r\n    color: #fff;\r\n    text-decoration: none;\r\n    font-size: 13px;\r\n    font-weight: 600;\r\n    letter-spacing: .4px;\r\n    transition: all .25s ease;\r\n}\r\n\r\n.vr-about-button:hover {\r\n    background: var(--vr-olive);\r\n    color: #fff;\r\n}\r\n\r\n\r\n/* =========================================================\r\n   MOBILE\r\n   ========================================================= */\r\n\r\n@media (max-width: 900px) {\r\n\r\n    .vr-about-hero {\r\n        min-height: auto;\r\n        display: block;\r\n    }\r\n\r\n    .vr-about-hero-content {\r\n        width: 100%;\r\n        max-width: 700px;\r\n        padding: 70px 7%;\r\n    }\r\n\r\n    .vr-about-hero-image {\r\n        position: relative;\r\n        width: 100%;\r\n        height: 480px;\r\n    }\r\n\r\n    .vr-about-story {\r\n        padding: 70px 7%;\r\n    }\r\n\r\n    .vr-about-story-inner {\r\n        grid-template-columns: 1fr;\r\n        gap: 55px;\r\n    }\r\n\r\n    .vr-about-story-content {\r\n        max-width: none;\r\n    }\r\n\r\n    .vr-values-grid {\r\n        grid-template-columns: repeat(2, 1fr);\r\n    }\r\n\r\n    .vr-value:nth-child(2) {\r\n        border-right: none;\r\n    }\r\n\r\n    .vr-value:nth-child(-n+2) {\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-about-botanicals {\r\n        padding: 70px 7%;\r\n    }\r\n\r\n    .vr-botanicals-inner {\r\n        grid-template-columns: 1fr;\r\n        gap: 50px;\r\n    }\r\n}\r\n\r\n\r\n@media (max-width: 600px) {\r\n\r\n    .vanriti-about {\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-about-hero-content {\r\n        padding: 55px 22px;\r\n    }\r\n\r\n    .vr-about-hero h1 {\r\n        font-size: 45px;\r\n        letter-spacing: -1px;\r\n    }\r\n\r\n    .vr-about-hero p {\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-about-hero-image {\r\n        height: 380px;\r\n    }\r\n\r\n    .vr-about-intro {\r\n        padding: 70px 22px;\r\n    }\r\n\r\n    .vr-about-intro h2 {\r\n        font-size: 32px;\r\n    }\r\n\r\n    .vr-about-intro p {\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-about-story {\r\n        padding: 65px 22px;\r\n    }\r\n\r\n    .vr-about-story-content h2 {\r\n        font-size: 31px;\r\n    }\r\n\r\n    .vr-about-values {\r\n        padding: 70px 22px;\r\n    }\r\n\r\n    .vr-about-values-header {\r\n        margin-bottom: 40px;\r\n    }\r\n\r\n    .vr-about-values-header h2 {\r\n        font-size: 32px;\r\n    }\r\n\r\n    .vr-values-grid {\r\n        grid-template-columns: 1fr;\r\n    }\r\n\r\n    .vr-value {\r\n        border-right: none !important;\r\n        border-bottom: 1px solid var(--vr-border);\r\n        padding: 32px 20px;\r\n    }\r\n\r\n    .vr-value:last-child {\r\n        border-bottom: none;\r\n    }\r\n\r\n    .vr-about-botanicals {\r\n        padding: 65px 22px;\r\n    }\r\n\r\n    .vr-botanicals-content h2 {\r\n        font-size: 32px;\r\n    }\r\n\r\n    .vr-botanicals-list {\r\n        grid-template-columns: 1fr;\r\n    }\r\n\r\n    .vr-about-promise {\r\n        padding: 85px 22px;\r\n    }\r\n\r\n    .vr-about-cta {\r\n        padding: 70px 22px;\r\n    }\r\n}\r\n</style>\r\n\r\n\r\n<div class=\"vanriti-about\">\r\n\r\n\r\n    <!-- =====================================================\r\n         HERO\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-hero\">\r\n\r\n        <div class=\"vr-about-hero-content\">\r\n\r\n            <div class=\"vr-about-eyebrow\">\r\n                The VANRITI Story\r\n            </div>\r\n\r\n            <h1>\r\n                Nature,\r\n                <span>Thoughtfully.</span>\r\n            </h1>\r\n\r\n            <p>\r\n                VANRITI was created with a simple belief — that everyday\r\n                beauty and wellness can be inspired by the timeless wisdom\r\n                of nature.\r\n            </p>\r\n\r\n        </div>\r\n\r\n        <!-- Replace with your own VANRITI lifestyle image -->\r\n\r\n        <div class=\"vr-about-hero-image\">\r\n            <img\r\n                src=\"/images/about/vanriti-about-hero.jpg\"\r\n                alt=\"VANRITI natural botanical products\"\r\n            >\r\n        </div>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         INTRO\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-intro\">\r\n\r\n        <div class=\"vr-about-small-label\">\r\n            Who We Are\r\n        </div>\r\n\r\n        <h2>\r\n            Rooted in nature. Created for modern everyday rituals.\r\n        </h2>\r\n\r\n        <p>\r\n            VANRITI is a natural beauty and wellness brand inspired by\r\n            traditional botanical ingredients and the simplicity of\r\n            plant-based care. We bring familiar ingredients into thoughtfully\r\n            designed products made for contemporary lifestyles.\r\n        </p>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         STORY\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-story\">\r\n\r\n        <div class=\"vr-about-story-inner\">\r\n\r\n            <div class=\"vr-about-story-image\">\r\n\r\n                <!-- Replace with your own image -->\r\n\r\n                <img\r\n                    src=\"/images/about/vanriti-story.jpg\"\r\n                    alt=\"Natural ingredients used by VANRITI\"\r\n                >\r\n\r\n            </div>\r\n\r\n\r\n            <div class=\"vr-about-story-content\">\r\n\r\n                <div class=\"vr-about-small-label\">\r\n                    Our Philosophy\r\n                </div>\r\n\r\n                <h2>\r\n                    We believe good care begins with good ingredients.\r\n                </h2>\r\n\r\n                <p>\r\n                    For generations, botanical ingredients have been part\r\n                    of everyday Indian beauty and wellness traditions.\r\n                    VANRITI takes inspiration from this heritage while\r\n                    creating products suited to today\'s routines.\r\n                </p>\r\n\r\n                <p>\r\n                    From individual botanical powders to thoughtfully\r\n                    combined formulations, our focus is on simplicity,\r\n                    transparency and the natural character of each ingredient.\r\n                </p>\r\n\r\n                <p>\r\n                    We believe that choosing natural care should feel\r\n                    uncomplicated — not overwhelming.\r\n                </p>\r\n\r\n                <div class=\"vr-about-signature\">\r\n                    VANRITI — PURE BY NATURE\r\n                </div>\r\n\r\n            </div>\r\n\r\n        </div>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         VALUES\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-values\">\r\n\r\n        <div class=\"vr-about-values-header\">\r\n\r\n            <div class=\"vr-about-small-label\">\r\n                What Guides Us\r\n            </div>\r\n\r\n            <h2>\r\n                Our Principles\r\n            </h2>\r\n\r\n            <p>\r\n                Four simple principles shape the way we think about\r\n                VANRITI and the products we create.\r\n            </p>\r\n\r\n        </div>\r\n\r\n\r\n        <div class=\"vr-values-grid\">\r\n\r\n            <div class=\"vr-value\">\r\n\r\n                <div class=\"vr-value-number\">\r\n                    01\r\n                </div>\r\n\r\n                <h3>\r\n                    Nature First\r\n                </h3>\r\n\r\n                <p>\r\n                    We draw inspiration from botanical ingredients and\r\n                    traditional plant-based practices.\r\n                </p>\r\n\r\n            </div>\r\n\r\n\r\n            <div class=\"vr-value\">\r\n\r\n                <div class=\"vr-value-number\">\r\n                    02\r\n                </div>\r\n\r\n                <h3>\r\n                    Simplicity\r\n                </h3>\r\n\r\n                <p>\r\n                    We believe everyday care should be straightforward,\r\n                    purposeful and easy to understand.\r\n                </p>\r\n\r\n            </div>\r\n\r\n\r\n            <div class=\"vr-value\">\r\n\r\n                <div class=\"vr-value-number\">\r\n                    03\r\n                </div>\r\n\r\n                <h3>\r\n                    Transparency\r\n                </h3>\r\n\r\n                <p>\r\n                    Clear product information helps customers make\r\n                    confident and informed choices.\r\n                </p>\r\n\r\n            </div>\r\n\r\n\r\n            <div class=\"vr-value\">\r\n\r\n                <div class=\"vr-value-number\">\r\n                    04\r\n                </div>\r\n\r\n                <h3>\r\n                    Thoughtful Care\r\n                </h3>\r\n\r\n                <p>\r\n                    Every product is developed with attention to its\r\n                    purpose, presentation and everyday usability.\r\n                </p>\r\n\r\n            </div>\r\n\r\n        </div>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         BOTANICALS\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-botanicals\">\r\n\r\n        <div class=\"vr-botanicals-inner\">\r\n\r\n\r\n            <div class=\"vr-botanicals-content\">\r\n\r\n                <div class=\"vr-about-small-label\">\r\n                    Inspired By Nature\r\n                </div>\r\n\r\n                <h2>\r\n                    Familiar botanicals. Modern rituals.\r\n                </h2>\r\n\r\n                <p>\r\n                    VANRITI celebrates ingredients that have long been\r\n                    familiar in Indian households and traditional care\r\n                    practices.\r\n                </p>\r\n\r\n                <p>\r\n                    Our range brings these botanical ingredients into\r\n                    convenient formats for modern beauty and wellness\r\n                    routines.\r\n                </p>\r\n\r\n\r\n                <div class=\"vr-botanicals-list\">\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Amla\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Bhringraj\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Reetha\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Shikakai\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Hibiscus\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Moringa\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Rose\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Neem\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Multani Mitti\r\n                    </div>\r\n\r\n                    <div class=\"vr-botanical-item\">\r\n                        Beetroot\r\n                    </div>\r\n\r\n                </div>\r\n\r\n            </div>\r\n\r\n\r\n            <div class=\"vr-botanicals-image\">\r\n\r\n                <!-- Replace with botanical/product image -->\r\n\r\n                <img\r\n                    src=\"/images/about/vanriti-botanicals.jpg\"\r\n                    alt=\"VANRITI botanical ingredients\"\r\n                >\r\n\r\n            </div>\r\n\r\n        </div>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         PROMISE\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-promise\">\r\n\r\n        <div class=\"vr-promise-label\">\r\n            Our Promise\r\n        </div>\r\n\r\n        <h2>\r\n            Pure ingredients.\r\n            Thoughtful products.\r\n            A simpler approach to nature.\r\n        </h2>\r\n\r\n        <p>\r\n            We are building VANRITI with the intention of making natural\r\n            beauty and wellness a simple, beautiful part of everyday life.\r\n        </p>\r\n\r\n    </section>\r\n\r\n\r\n    <!-- =====================================================\r\n         CTA\r\n         ===================================================== -->\r\n\r\n    <section class=\"vr-about-cta\">\r\n\r\n        <div class=\"vr-about-small-label\">\r\n            Discover VANRITI\r\n        </div>\r\n\r\n        <h2>\r\n            Find your natural ritual.\r\n        </h2>\r\n\r\n        <p>\r\n            Explore our collection of botanical beauty and wellness products\r\n            created for everyday care.\r\n        </p>\r\n\r\n        <a href=\"/shop\" class=\"vr-about-button\">\r\n            Explore Our Products\r\n        </a>\r\n\r\n    </section>\r\n\r\n\r\n</div>','About VANRITI - Natural Beauty & Wellness','Discover the story behind VANRITI — handcrafted skincare, herbal teas and wellness essentials, made with nature in mind.','published','2026-09-08 08:43:18','2026-09-08 09:36:23'),(3,'Privacy Policy','privacy-policy','<style>\r\n    /* =========================================================\r\n   VANRITI — PREMIUM PRIVACY POLICY\r\n   Font intentionally NOT defined.\r\n   Inherits website\'s existing font.\r\n   ========================================================= */\r\n\r\n    .vanriti-privacy {\r\n        --vr-forest: #263d25;\r\n        --vr-olive: #6f8736;\r\n        --vr-ivory: #f7f4ea;\r\n        --vr-cream: #fbfaf6;\r\n        --vr-text: #34392f;\r\n        --vr-muted: #73786f;\r\n        --vr-border: #e5e6dd;\r\n\r\n        max-width: 1180px;\r\n        margin: 0 auto;\r\n        padding: 0 24px 90px;\r\n        color: var(--vr-text);\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   HERO\r\n   ========================================================= */\r\n\r\n    .vr-privacy-hero {\r\n        text-align: center;\r\n        padding: 75px 20px 65px;\r\n        max-width: 850px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-privacy-eyebrow {\r\n        display: inline-flex;\r\n        align-items: center;\r\n        gap: 12px;\r\n        margin-bottom: 18px;\r\n        color: var(--vr-olive);\r\n        font-size: 11px;\r\n        font-weight: 600;\r\n        letter-spacing: 2.8px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-privacy-eyebrow::before,\r\n    .vr-privacy-eyebrow::after {\r\n        content: \"\";\r\n        width: 28px;\r\n        height: 1px;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    .vr-privacy-hero h1 {\r\n        margin: 0;\r\n        color: var(--vr-forest);\r\n        font-size: clamp(42px, 5vw, 64px);\r\n        font-weight: 500;\r\n        letter-spacing: -1.5px;\r\n        line-height: 1.08;\r\n    }\r\n\r\n    .vr-privacy-hero p {\r\n        max-width: 650px;\r\n        margin: 22px auto 0;\r\n        color: var(--vr-muted);\r\n        font-size: 16px;\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   PRIVACY PRINCIPLES\r\n   ========================================================= */\r\n\r\n    .vr-privacy-principles {\r\n        max-width: 900px;\r\n        margin: 0 auto 75px;\r\n        padding: 30px 0;\r\n        border-top: 1px solid var(--vr-border);\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-privacy-principles-grid {\r\n        display: grid;\r\n        grid-template-columns: repeat(3, 1fr);\r\n        gap: 35px;\r\n    }\r\n\r\n    .vr-privacy-principle {\r\n        text-align: center;\r\n    }\r\n\r\n    .vr-privacy-principle-number {\r\n        width: 38px;\r\n        height: 38px;\r\n        margin: 0 auto 11px;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        border: 1px solid var(--vr-olive);\r\n        border-radius: 50%;\r\n        color: var(--vr-forest);\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-privacy-principle strong {\r\n        display: block;\r\n        color: var(--vr-forest);\r\n        font-size: 13px;\r\n        font-weight: 600;\r\n    }\r\n\r\n    .vr-privacy-principle span {\r\n        display: block;\r\n        margin-top: 4px;\r\n        color: var(--vr-muted);\r\n        font-size: 12px;\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTENT\r\n   ========================================================= */\r\n\r\n    .vr-privacy-content {\r\n        max-width: 900px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-privacy-section {\r\n        padding: 42px 0;\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-privacy-section:first-child {\r\n        padding-top: 0;\r\n    }\r\n\r\n    .vr-privacy-section:last-child {\r\n        border-bottom: none;\r\n    }\r\n\r\n    .vr-privacy-label {\r\n        margin-bottom: 9px;\r\n        color: var(--vr-olive);\r\n        font-size: 10px;\r\n        font-weight: 600;\r\n        letter-spacing: 2px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-privacy-section h2 {\r\n        margin: 0 0 16px;\r\n        color: var(--vr-forest);\r\n        font-size: 29px;\r\n        font-weight: 500;\r\n        line-height: 1.3;\r\n    }\r\n\r\n    .vr-privacy-section h3 {\r\n        margin: 25px 0 10px;\r\n        color: var(--vr-forest);\r\n        font-size: 17px;\r\n        font-weight: 600;\r\n    }\r\n\r\n    .vr-privacy-section p {\r\n        margin: 0 0 15px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-privacy-section p:last-child {\r\n        margin-bottom: 0;\r\n    }\r\n\r\n    /* =========================================================\r\n   LIST\r\n   ========================================================= */\r\n\r\n    .vr-privacy-list {\r\n        margin: 18px 0 0;\r\n        padding: 0;\r\n        list-style: none;\r\n    }\r\n\r\n    .vr-privacy-list li {\r\n        position: relative;\r\n        margin-bottom: 12px;\r\n        padding-left: 22px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-privacy-list li::before {\r\n        content: \"\";\r\n        position: absolute;\r\n        left: 2px;\r\n        top: 12px;\r\n        width: 5px;\r\n        height: 5px;\r\n        border-radius: 50%;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   PRIVACY NOTE\r\n   ========================================================= */\r\n\r\n    .vr-privacy-note {\r\n        margin-top: 22px;\r\n        padding: 18px 22px;\r\n        background: var(--vr-ivory);\r\n        border-left: 2px solid var(--vr-olive);\r\n    }\r\n\r\n    .vr-privacy-note p {\r\n        margin: 0;\r\n        color: var(--vr-forest);\r\n        font-size: 14px;\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTACT\r\n   ========================================================= */\r\n\r\n    .vr-privacy-contact {\r\n        margin-top: 24px;\r\n        padding: 28px 30px;\r\n        background: var(--vr-cream);\r\n        border: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-privacy-contact-title {\r\n        margin-bottom: 15px;\r\n        color: var(--vr-forest);\r\n        font-size: 20px;\r\n    }\r\n\r\n    .vr-privacy-contact p {\r\n        margin-bottom: 8px;\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-privacy-contact a {\r\n        color: var(--vr-forest);\r\n        text-decoration: none;\r\n        border-bottom: 1px solid rgba(38, 61, 37, 0.25);\r\n    }\r\n\r\n    .vr-privacy-contact a:hover {\r\n        color: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   LAST UPDATED\r\n   ========================================================= */\r\n\r\n    .vr-privacy-updated {\r\n        margin-top: 35px !important;\r\n        padding-top: 20px;\r\n        border-top: 1px solid var(--vr-border);\r\n        color: var(--vr-muted) !important;\r\n        font-size: 11px !important;\r\n        letter-spacing: 0.5px;\r\n    }\r\n\r\n    /* =========================================================\r\n   MOBILE\r\n   ========================================================= */\r\n\r\n    @media (max-width: 767px) {\r\n        .vanriti-privacy {\r\n            padding: 0 18px 60px;\r\n        }\r\n\r\n        .vr-privacy-hero {\r\n            padding: 50px 5px 45px;\r\n        }\r\n\r\n        .vr-privacy-hero h1 {\r\n            font-size: 40px;\r\n        }\r\n\r\n        .vr-privacy-hero p {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-privacy-principles {\r\n            margin-bottom: 50px;\r\n        }\r\n\r\n        .vr-privacy-principles-grid {\r\n            grid-template-columns: 1fr;\r\n            gap: 25px;\r\n        }\r\n\r\n        .vr-privacy-section {\r\n            padding: 34px 0;\r\n        }\r\n\r\n        .vr-privacy-section h2 {\r\n            font-size: 25px;\r\n        }\r\n\r\n        .vr-privacy-section p,\r\n        .vr-privacy-list li {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-privacy-contact {\r\n            padding: 22px;\r\n        }\r\n    }\r\n</style>\r\n\r\n<div class=\"vanriti-privacy\">\r\n    <!-- HERO -->\r\n\r\n    <header class=\"vr-privacy-hero\">\r\n        <div class=\"vr-privacy-eyebrow\">Your Privacy Matters</div>\r\n\r\n        <h1>Privacy Policy</h1>\r\n\r\n        <p>\r\n            Your trust matters to us. This policy explains how VANRITI collects, uses and protects information when you\r\n            use our website and services.\r\n        </p>\r\n    </header>\r\n\r\n    <!-- PRIVACY PRINCIPLES -->\r\n\r\n    <div class=\"vr-privacy-principles\">\r\n        <div class=\"vr-privacy-principles-grid\">\r\n            <div class=\"vr-privacy-principle\">\r\n                <div class=\"vr-privacy-principle-number\">01</div>\r\n\r\n                <strong>Transparency</strong>\r\n\r\n                <span> Clear information about data use </span>\r\n            </div>\r\n\r\n            <div class=\"vr-privacy-principle\">\r\n                <div class=\"vr-privacy-principle-number\">02</div>\r\n\r\n                <strong>Protection</strong>\r\n\r\n                <span> Reasonable safeguards for your information </span>\r\n            </div>\r\n\r\n            <div class=\"vr-privacy-principle\">\r\n                <div class=\"vr-privacy-principle-number\">03</div>\r\n\r\n                <strong>Respect</strong>\r\n\r\n                <span> Responsible handling of your information </span>\r\n            </div>\r\n        </div>\r\n    </div>\r\n\r\n    <!-- CONTENT -->\r\n\r\n    <div class=\"vr-privacy-content\">\r\n        <!-- 01 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">01 — Introduction</div>\r\n\r\n            <h2>Introduction</h2>\r\n\r\n            <p>\r\n                <strong>VANRITI</strong>, operated by TFC Sales &amp; Marketing, respects your privacy and is committed\r\n                to protecting the personal information you provide while using our website.\r\n            </p>\r\n\r\n            <p>\r\n                This Privacy Policy explains what information we may collect, how we use it, how we protect it and the\r\n                choices available to you.\r\n            </p>\r\n\r\n            <p>By using the VANRITI website, you acknowledge the practices described in this Privacy Policy.</p>\r\n        </section>\r\n\r\n        <!-- 02 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">02 — Information We Collect</div>\r\n\r\n            <h2>Information We Collect</h2>\r\n\r\n            <p>\r\n                Depending on how you interact with our website, we may collect information necessary to provide our\r\n                products and services.\r\n            </p>\r\n\r\n            <h3>Information You Provide</h3>\r\n\r\n            <ul class=\"vr-privacy-list\">\r\n                <li>Name and contact information.</li>\r\n\r\n                <li>Email address and mobile number.</li>\r\n\r\n                <li>Billing and shipping address.</li>\r\n\r\n                <li>Information provided when creating an account.</li>\r\n\r\n                <li>Information provided while placing an order.</li>\r\n\r\n                <li>Customer support messages, feedback and other communications.</li>\r\n            </ul>\r\n\r\n            <h3>Information Collected Automatically</h3>\r\n\r\n            <p>\r\n                When you browse our website, certain technical information may be collected automatically, such as\r\n                device information, browser type, IP address, pages visited, referring pages and general website usage\r\n                information.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 03 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">03 — How We Use Information</div>\r\n\r\n            <h2>How We Use Your Information</h2>\r\n\r\n            <p>Information collected through our website may be used for purposes including:</p>\r\n\r\n            <ul class=\"vr-privacy-list\">\r\n                <li>Processing and fulfilling your orders.</li>\r\n\r\n                <li>Delivering products to the address provided by you.</li>\r\n\r\n                <li>Providing order updates and shipment information.</li>\r\n\r\n                <li>Responding to customer enquiries and support requests.</li>\r\n\r\n                <li>Managing customer accounts and website functionality.</li>\r\n\r\n                <li>Improving our products, services and website experience.</li>\r\n\r\n                <li>Detecting and preventing fraudulent, unauthorized or potentially harmful activities.</li>\r\n\r\n                <li>Complying with applicable legal and regulatory requirements.</li>\r\n            </ul>\r\n        </section>\r\n\r\n        <!-- 04 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">04 — Payments</div>\r\n\r\n            <h2>Payment Information</h2>\r\n\r\n            <p>\r\n                Where online payment services are used, payment transactions may be processed through third-party\r\n                payment service providers.\r\n            </p>\r\n\r\n            <p>\r\n                VANRITI does not intend to store complete payment card details such as full card numbers or CVV\r\n                information on its own systems unless specifically required and lawfully permitted.\r\n            </p>\r\n\r\n            <div class=\"vr-privacy-note\">\r\n                <p>\r\n                    Payment information may be handled directly by the applicable payment service provider according to\r\n                    its own privacy and security policies.\r\n                </p>\r\n            </div>\r\n        </section>\r\n\r\n        <!-- 05 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">05 — Cookies</div>\r\n\r\n            <h2>Cookies &amp; Similar Technologies</h2>\r\n\r\n            <p>\r\n                Our website may use cookies and similar technologies to help maintain sessions, remember preferences,\r\n                understand website usage and improve the overall user experience.\r\n            </p>\r\n\r\n            <p>\r\n                You may be able to control or disable cookies through your browser settings. Some website features may\r\n                not function correctly if certain cookies are disabled.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 06 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">06 — Sharing Information</div>\r\n\r\n            <h2>When We Share Information</h2>\r\n\r\n            <p>We do not sell your personal information as a business asset.</p>\r\n\r\n            <p>\r\n                Information may be shared with trusted third parties where reasonably necessary to provide our services,\r\n                such as:\r\n            </p>\r\n\r\n            <ul class=\"vr-privacy-list\">\r\n                <li>Delivery and logistics partners.</li>\r\n\r\n                <li>Payment service providers.</li>\r\n\r\n                <li>Website hosting, technology and service providers.</li>\r\n\r\n                <li>Customer support or operational service providers.</li>\r\n\r\n                <li>Government authorities or other parties where required by applicable law.</li>\r\n            </ul>\r\n\r\n            <p>\r\n                Third-party service providers may process information according to their own applicable terms and\r\n                privacy policies.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 07 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">07 — Data Security</div>\r\n\r\n            <h2>How We Protect Your Information</h2>\r\n\r\n            <p>\r\n                We take reasonable technical and organizational measures to protect personal information against\r\n                unauthorized access, misuse, alteration, disclosure or destruction.\r\n            </p>\r\n\r\n            <p>\r\n                However, no method of transmitting or storing information over the internet can be guaranteed to be\r\n                completely secure.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 08 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">08 — Data Retention</div>\r\n\r\n            <h2>How Long We Keep Information</h2>\r\n\r\n            <p>\r\n                We retain personal information only for as long as reasonably necessary for the purposes for which it\r\n                was collected, including fulfilling orders, providing customer support, maintaining business records and\r\n                complying with applicable legal obligations.\r\n            </p>\r\n\r\n            <p>\r\n                When information is no longer required, it may be securely deleted, anonymized or otherwise disposed of\r\n                where appropriate.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 09 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">09 — Your Choices</div>\r\n\r\n            <h2>Your Privacy Choices</h2>\r\n\r\n            <p>Depending on applicable law, you may have rights or choices relating to your personal information.</p>\r\n\r\n            <ul class=\"vr-privacy-list\">\r\n                <li>Request access to certain personal information we hold about you.</li>\r\n\r\n                <li>Request correction of inaccurate or incomplete information.</li>\r\n\r\n                <li>Request deletion of information where legally applicable.</li>\r\n\r\n                <li>Opt out of certain promotional communications.</li>\r\n            </ul>\r\n\r\n            <p>Requests may be subject to applicable legal, security and operational requirements.</p>\r\n        </section>\r\n\r\n        <!-- 10 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">10 — Communications</div>\r\n\r\n            <h2>Marketing Communications</h2>\r\n\r\n            <p>\r\n                We may occasionally send service-related communications necessary for your orders, account or use of our\r\n                website.\r\n            </p>\r\n\r\n            <p>\r\n                Where permitted and applicable, we may also send promotional communications about products, offers or\r\n                updates.\r\n            </p>\r\n\r\n            <p>\r\n                You may opt out of promotional communications by following the unsubscribe instructions provided in the\r\n                relevant communication or by contacting us.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 11 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">11 — Children\'s Privacy</div>\r\n\r\n            <h2>Children\'s Privacy</h2>\r\n\r\n            <p>\r\n                Our website is not intentionally designed to collect personal information directly from children without\r\n                appropriate involvement or authorization from a parent or legal guardian.\r\n            </p>\r\n\r\n            <p>\r\n                If you believe that a child has provided personal information through our website inappropriately,\r\n                please contact us so that we can review the matter.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 12 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">12 — Third-Party Services</div>\r\n\r\n            <h2>Third-Party Websites &amp; Services</h2>\r\n\r\n            <p>Our website may use or link to third-party services, websites or platforms.</p>\r\n\r\n            <p>\r\n                VANRITI is not responsible for the privacy practices, content or security of third-party websites or\r\n                services. We encourage you to review their respective privacy policies before providing personal\r\n                information.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 13 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">13 — Policy Updates</div>\r\n\r\n            <h2>Changes to This Privacy Policy</h2>\r\n\r\n            <p>\r\n                We may update this Privacy Policy from time to time to reflect changes in our services, website\r\n                functionality, business practices or applicable legal requirements.\r\n            </p>\r\n\r\n            <p>Any updated version will be published on this page with the revised effective or updated date.</p>\r\n        </section>\r\n\r\n        <!-- 14 -->\r\n\r\n        <section class=\"vr-privacy-section\">\r\n            <div class=\"vr-privacy-label\">14 — Contact</div>\r\n\r\n            <h2>Questions About Your Privacy?</h2>\r\n\r\n            <p>\r\n                If you have questions, concerns or requests relating to this Privacy Policy or the handling of your\r\n                personal information, please contact us.\r\n            </p>\r\n\r\n            <div class=\"vr-privacy-contact\">\r\n                <div class=\"vr-privacy-contact-title\">VANRITI Customer Support</div>\r\n\r\n                <p>\r\n                    <strong>Email:</strong>\r\n                    <a href=\"mailto:support@vanriti.com\"> support@vanriti.com </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>Phone:</strong>\r\n                    <a href=\"tel:+911293509181\"> +91 1293509181 </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>TFC Sales &amp; Marketing</strong><br />\r\n                    12, 1st Floor, Ramnik Complex-II,<br />\r\n                    Tikona Park, NIT-1,<br />\r\n                    Faridabad, Haryana – 121001, India\r\n                </p>\r\n            </div>\r\n\r\n            <p class=\"vr-privacy-updated\">Last Updated: September 2026</p>\r\n        </section>\r\n    </div>\r\n</div>','Privacy Policy - VANRITI','Read how VANRITI collects, uses and protects your personal information.','published','2026-09-08 08:43:18','2026-09-08 09:34:26'),(4,'Terms & Conditions','terms-and-conditions','<style>\r\n    /* =========================================================\r\n   VANRITI — PREMIUM TERMS & CONDITIONS\r\n   Font intentionally NOT defined.\r\n   Inherits website\'s existing font.\r\n   ========================================================= */\r\n\r\n    .vanriti-terms {\r\n        --vr-forest: #263d25;\r\n        --vr-olive: #6f8736;\r\n        --vr-ivory: #f7f4ea;\r\n        --vr-cream: #fbfaf6;\r\n        --vr-text: #34392f;\r\n        --vr-muted: #73786f;\r\n        --vr-border: #e5e6dd;\r\n\r\n        max-width: 1180px;\r\n        margin: 0 auto;\r\n        padding: 0 24px 90px;\r\n        color: var(--vr-text);\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   HERO\r\n   ========================================================= */\r\n\r\n    .vr-terms-hero {\r\n        text-align: center;\r\n        padding: 75px 20px 65px;\r\n        max-width: 850px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-terms-eyebrow {\r\n        display: inline-flex;\r\n        align-items: center;\r\n        gap: 12px;\r\n        margin-bottom: 18px;\r\n        color: var(--vr-olive);\r\n        font-size: 11px;\r\n        font-weight: 600;\r\n        letter-spacing: 2.8px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-terms-eyebrow::before,\r\n    .vr-terms-eyebrow::after {\r\n        content: \"\";\r\n        width: 28px;\r\n        height: 1px;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    .vr-terms-hero h1 {\r\n        margin: 0;\r\n        color: var(--vr-forest);\r\n        font-size: clamp(42px, 5vw, 64px);\r\n        font-weight: 500;\r\n        letter-spacing: -1.5px;\r\n        line-height: 1.08;\r\n    }\r\n\r\n    .vr-terms-hero p {\r\n        max-width: 620px;\r\n        margin: 22px auto 0;\r\n        color: var(--vr-muted);\r\n        font-size: 16px;\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTENT\r\n   ========================================================= */\r\n\r\n    .vr-terms-content {\r\n        max-width: 900px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-terms-section {\r\n        padding: 42px 0;\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-terms-section:first-child {\r\n        padding-top: 0;\r\n    }\r\n\r\n    .vr-terms-section:last-child {\r\n        border-bottom: none;\r\n    }\r\n\r\n    .vr-terms-label {\r\n        margin-bottom: 9px;\r\n        color: var(--vr-olive);\r\n        font-size: 10px;\r\n        font-weight: 600;\r\n        letter-spacing: 2px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-terms-section h2 {\r\n        margin: 0 0 16px;\r\n        color: var(--vr-forest);\r\n        font-size: 29px;\r\n        font-weight: 500;\r\n        line-height: 1.3;\r\n    }\r\n\r\n    .vr-terms-section h3 {\r\n        margin: 25px 0 10px;\r\n        color: var(--vr-forest);\r\n        font-size: 17px;\r\n        font-weight: 600;\r\n    }\r\n\r\n    .vr-terms-section p {\r\n        margin: 0 0 15px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-terms-section p:last-child {\r\n        margin-bottom: 0;\r\n    }\r\n\r\n    /* =========================================================\r\n   LIST\r\n   ========================================================= */\r\n\r\n    .vr-terms-list {\r\n        margin: 18px 0 0;\r\n        padding: 0;\r\n        list-style: none;\r\n    }\r\n\r\n    .vr-terms-list li {\r\n        position: relative;\r\n        margin-bottom: 12px;\r\n        padding-left: 22px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-terms-list li::before {\r\n        content: \"\";\r\n        position: absolute;\r\n        left: 2px;\r\n        top: 12px;\r\n        width: 5px;\r\n        height: 5px;\r\n        border-radius: 50%;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   IMPORTANT NOTICE\r\n   ========================================================= */\r\n\r\n    .vr-terms-note {\r\n        margin-top: 22px;\r\n        padding: 18px 22px;\r\n        background: var(--vr-ivory);\r\n        border-left: 2px solid var(--vr-olive);\r\n    }\r\n\r\n    .vr-terms-note p {\r\n        margin: 0;\r\n        color: var(--vr-forest);\r\n        font-size: 14px;\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTACT\r\n   ========================================================= */\r\n\r\n    .vr-terms-contact {\r\n        margin-top: 24px;\r\n        padding: 28px 30px;\r\n        background: var(--vr-cream);\r\n        border: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-terms-contact-title {\r\n        margin-bottom: 15px;\r\n        color: var(--vr-forest);\r\n        font-size: 20px;\r\n    }\r\n\r\n    .vr-terms-contact p {\r\n        margin-bottom: 8px;\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-terms-contact a {\r\n        color: var(--vr-forest);\r\n        text-decoration: none;\r\n        border-bottom: 1px solid rgba(38, 61, 37, 0.25);\r\n    }\r\n\r\n    .vr-terms-contact a:hover {\r\n        color: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   LAST UPDATED\r\n   ========================================================= */\r\n\r\n    .vr-terms-updated {\r\n        margin-top: 35px !important;\r\n        padding-top: 20px;\r\n        border-top: 1px solid var(--vr-border);\r\n        color: var(--vr-muted) !important;\r\n        font-size: 11px !important;\r\n        letter-spacing: 0.5px;\r\n    }\r\n\r\n    /* =========================================================\r\n   MOBILE\r\n   ========================================================= */\r\n\r\n    @media (max-width: 767px) {\r\n        .vanriti-terms {\r\n            padding: 0 18px 60px;\r\n        }\r\n\r\n        .vr-terms-hero {\r\n            padding: 50px 5px 45px;\r\n        }\r\n\r\n        .vr-terms-hero h1 {\r\n            font-size: 40px;\r\n        }\r\n\r\n        .vr-terms-hero p {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-terms-section {\r\n            padding: 34px 0;\r\n        }\r\n\r\n        .vr-terms-section h2 {\r\n            font-size: 25px;\r\n        }\r\n\r\n        .vr-terms-section p,\r\n        .vr-terms-list li {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-terms-contact {\r\n            padding: 22px;\r\n        }\r\n    }\r\n</style>\r\n\r\n<div class=\"vanriti-terms\">\r\n    <!-- HERO -->\r\n\r\n    <header class=\"vr-terms-hero\">\r\n        <div class=\"vr-terms-eyebrow\">Legal Information</div>\r\n\r\n        <h1>Terms &amp; Conditions</h1>\r\n\r\n        <p>Please read these terms carefully before using the VANRITI website or placing an order with us.</p>\r\n    </header>\r\n\r\n    <!-- CONTENT -->\r\n\r\n    <div class=\"vr-terms-content\">\r\n        <!-- 01 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">01 — Introduction</div>\r\n\r\n            <h2>Introduction</h2>\r\n\r\n            <p>\r\n                Welcome to <strong>VANRITI</strong>. These Terms &amp; Conditions govern your access to and use of the\r\n                VANRITI website, products, services and related features.\r\n            </p>\r\n\r\n            <p>\r\n                By accessing our website, browsing our products or placing an order, you agree to be bound by these\r\n                Terms &amp; Conditions. If you do not agree with any part of these terms, please do not use our website\r\n                or services.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 02 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">02 — Website Usage</div>\r\n\r\n            <h2>Use of Our Website</h2>\r\n\r\n            <p>\r\n                You agree to use the VANRITI website only for lawful purposes and in a manner that does not infringe\r\n                upon the rights of VANRITI or any third party.\r\n            </p>\r\n\r\n            <ul class=\"vr-terms-list\">\r\n                <li>\r\n                    You must provide accurate and complete information when creating an account or placing an order.\r\n                </li>\r\n\r\n                <li>You must not use the website for fraudulent, unlawful or unauthorized activities.</li>\r\n\r\n                <li>You must not attempt to interfere with the security, functionality or operation of the website.</li>\r\n\r\n                <li>\r\n                    You must not reproduce, copy, modify or commercially exploit website content without appropriate\r\n                    authorization.\r\n                </li>\r\n            </ul>\r\n        </section>\r\n\r\n        <!-- 03 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">03 — Products</div>\r\n\r\n            <h2>Products &amp; Product Information</h2>\r\n\r\n            <p>\r\n                VANRITI makes reasonable efforts to ensure that product descriptions, images, prices, availability and\r\n                other information displayed on the website are accurate and up to date.\r\n            </p>\r\n\r\n            <p>\r\n                However, product images may vary slightly from the actual product due to photography, screen settings,\r\n                packaging updates or other factors.\r\n            </p>\r\n\r\n            <p>Product availability may change without prior notice.</p>\r\n        </section>\r\n\r\n        <!-- 04 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">04 — Orders</div>\r\n\r\n            <h2>Orders &amp; Acceptance</h2>\r\n\r\n            <p>\r\n                Placing an order on the VANRITI website constitutes a request to purchase the selected products. An\r\n                order is subject to acceptance and successful processing by VANRITI.\r\n            </p>\r\n\r\n            <p>\r\n                We reserve the right to refuse, cancel or limit an order where necessary, including in situations\r\n                involving product availability, pricing errors, suspected fraudulent activity or other operational\r\n                reasons.\r\n            </p>\r\n\r\n            <div class=\"vr-terms-note\">\r\n                <p>\r\n                    <strong>Important:</strong>\r\n                    An order confirmation does not necessarily guarantee that the order will be fulfilled if an\r\n                    unforeseen issue is subsequently identified.\r\n                </p>\r\n            </div>\r\n        </section>\r\n\r\n        <!-- 05 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">05 — Pricing &amp; Payment</div>\r\n\r\n            <h2>Pricing &amp; Payment</h2>\r\n\r\n            <p>Product prices displayed on the website are subject to change without prior notice.</p>\r\n\r\n            <p>\r\n                Applicable taxes, shipping charges and other charges, where applicable, will be displayed during the\r\n                checkout process.\r\n            </p>\r\n\r\n            <p>\r\n                Customers are responsible for providing accurate payment and billing information when placing an order.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 06 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">06 — Shipping</div>\r\n\r\n            <h2>Shipping &amp; Delivery</h2>\r\n\r\n            <p>Orders are shipped to the delivery address provided by the customer during checkout.</p>\r\n\r\n            <p>\r\n                Delivery timelines are estimates and may vary depending on location, courier availability, weather,\r\n                holidays and other circumstances beyond our reasonable control.\r\n            </p>\r\n\r\n            <p>\r\n                For complete information regarding order processing, delivery timelines, tracking and shipping-related\r\n                matters, please refer to our <strong>Shipping Policy</strong>.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 07 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">07 — Cancellation</div>\r\n\r\n            <h2>Order Cancellation</h2>\r\n\r\n            <p>Orders may generally be cancelled only before they have been shipped.</p>\r\n\r\n            <p>Once an order has been dispatched, cancellation may no longer be possible.</p>\r\n\r\n            <p>\r\n                Any applicable refund following an approved cancellation will be processed according to our applicable\r\n                refund procedures.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 08 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">08 — Returns &amp; Refunds</div>\r\n\r\n            <h2>Returns &amp; Refunds</h2>\r\n\r\n            <p>\r\n                Returns, replacements and refunds are subject to the applicable VANRITI Return &amp; Refund Policy and\r\n                the eligibility conditions specified for the relevant product.\r\n            </p>\r\n\r\n            <p>Customers should review the Return &amp; Refund Policy before purchasing products where applicable.</p>\r\n        </section>\r\n\r\n        <!-- 09 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">09 — Intellectual Property</div>\r\n\r\n            <h2>Intellectual Property</h2>\r\n\r\n            <p>\r\n                Unless otherwise stated, all content available on the VANRITI website, including logos, brand names,\r\n                text, graphics, images, product photography, designs, layouts and other materials, is owned by or\r\n                licensed to VANRITI.\r\n            </p>\r\n\r\n            <p>\r\n                Such content may not be copied, reproduced, distributed, modified or commercially exploited without\r\n                prior written permission.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 10 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">10 — User Content</div>\r\n\r\n            <h2>User-Submitted Content</h2>\r\n\r\n            <p>\r\n                If you submit reviews, feedback, photographs or other content through the website, you agree that the\r\n                content should be lawful, accurate and not infringe the rights of any third party.\r\n            </p>\r\n\r\n            <p>\r\n                VANRITI reserves the right to remove content that is unlawful, offensive, misleading, abusive or\r\n                otherwise inappropriate.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 11 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">11 — Liability</div>\r\n\r\n            <h2>Limitation of Liability</h2>\r\n\r\n            <p>\r\n                VANRITI will make reasonable efforts to maintain the availability and functionality of its website and\r\n                services.\r\n            </p>\r\n\r\n            <p>\r\n                However, we cannot guarantee that the website will always be uninterrupted, error-free, secure or\r\n                available at all times.\r\n            </p>\r\n\r\n            <p>\r\n                To the extent permitted by applicable law, VANRITI shall not be responsible for losses arising from\r\n                circumstances beyond its reasonable control.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 12 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">12 — External Links</div>\r\n\r\n            <h2>Third-Party Links &amp; Services</h2>\r\n\r\n            <p>\r\n                The website may contain links to third-party websites or services. These links may be provided for\r\n                convenience and do not necessarily imply endorsement by VANRITI.\r\n            </p>\r\n\r\n            <p>\r\n                VANRITI is not responsible for the content, availability, security or policies of third-party websites.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 13 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">13 — Privacy</div>\r\n\r\n            <h2>Privacy</h2>\r\n\r\n            <p>Your use of the VANRITI website may involve the collection and processing of certain information.</p>\r\n\r\n            <p>\r\n                Please refer to our <strong>Privacy Policy</strong> for information about how we collect, use and\r\n                protect personal information.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 14 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">14 — Changes</div>\r\n\r\n            <h2>Changes to These Terms</h2>\r\n\r\n            <p>\r\n                VANRITI may update or modify these Terms &amp; Conditions from time to time to reflect changes in our\r\n                services, website, business practices or applicable requirements.\r\n            </p>\r\n\r\n            <p>\r\n                Updated terms will be published on this page. Your continued use of the website after changes are\r\n                published constitutes acceptance of the updated terms.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 15 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">15 — Governing Law</div>\r\n\r\n            <h2>Governing Law</h2>\r\n\r\n            <p>\r\n                These Terms &amp; Conditions shall be governed by and interpreted in accordance with the applicable laws\r\n                of India.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- 16 -->\r\n\r\n        <section class=\"vr-terms-section\">\r\n            <div class=\"vr-terms-label\">16 — Contact</div>\r\n\r\n            <h2>Need Help?</h2>\r\n\r\n            <p>If you have any questions regarding these Terms &amp; Conditions, please contact us.</p>\r\n\r\n            <div class=\"vr-terms-contact\">\r\n                <div class=\"vr-terms-contact-title\">VANRITI Customer Support</div>\r\n\r\n                <p>\r\n                    <strong>Email:</strong>\r\n                    <a href=\"mailto:support@vanriti.com\"> support@vanriti.com </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>Phone:</strong>\r\n                    <a href=\"tel:+911293509181\"> +91 1293509181 </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>TFC Sales &amp; Marketing</strong><br />\r\n                    12, 1st Floor, Ramnik Complex-II,<br />\r\n                    Tikona Park, NIT-1,<br />\r\n                    Faridabad, Haryana – 121001, India\r\n                </p>\r\n            </div>\r\n\r\n            <p class=\"vr-terms-updated\">Last Updated: September 2026</p>\r\n        </section>\r\n    </div>\r\n</div>','Terms & Conditions - VANRITI','The terms and conditions that govern the use of VANRITI and purchases made on our store.','published','2026-09-08 08:43:18','2026-09-08 09:34:11'),(5,'Shipping & Delivery','shipping-and-delivery','<style>\r\n    /* =========================================================\r\n   VANRITI — PREMIUM SHIPPING POLICY\r\n   ========================================================= */\r\n\r\n    .vanriti-shipping {\r\n        --vr-forest: #263d25;\r\n        --vr-olive: #6f8736;\r\n        --vr-ivory: #f7f4ea;\r\n        --vr-cream: #fbfaf6;\r\n        --vr-text: #34392f;\r\n        --vr-muted: #73786f;\r\n        --vr-border: #e5e6dd;\r\n\r\n        max-width: 90%;\r\n        margin: 0 auto;\r\n        padding: 0 24px 90px;\r\n        color: var(--vr-text);\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   HERO\r\n   ========================================================= */\r\n\r\n    .vr-shipping-hero {\r\n        text-align: center;\r\n        padding: 75px 20px 65px;\r\n        max-width: 850px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-shipping-eyebrow {\r\n        display: inline-flex;\r\n        align-items: center;\r\n        gap: 12px;\r\n        margin-bottom: 18px;\r\n        color: var(--vr-olive);\r\n        font-size: 11px;\r\n        font-weight: 600;\r\n        letter-spacing: 2.8px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-shipping-eyebrow::before,\r\n    .vr-shipping-eyebrow::after {\r\n        content: \"\";\r\n        width: 28px;\r\n        height: 1px;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    .vr-shipping-hero h1 {\r\n        margin: 0;\r\n        color: var(--vr-forest);\r\n        font-size: clamp(42px, 5vw, 64px);\r\n        font-weight: 500;\r\n        letter-spacing: -1.5px;\r\n        line-height: 1.08;\r\n    }\r\n\r\n    .vr-shipping-hero p {\r\n        max-width: 610px;\r\n        margin: 22px auto 0;\r\n        color: var(--vr-muted);\r\n        font-size: 16px;\r\n        line-height: 1.8;\r\n    }\r\n\r\n    /* =========================================================\r\n   SHIPPING JOURNEY\r\n   ========================================================= */\r\n\r\n    .vr-shipping-journey {\r\n        max-width: 900px;\r\n        margin: 0 auto 75px;\r\n        padding: 30px 0;\r\n        border-top: 1px solid var(--vr-border);\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-journey {\r\n        display: grid;\r\n        grid-template-columns: 1fr auto 1fr auto 1fr;\r\n        align-items: center;\r\n        gap: 22px;\r\n    }\r\n\r\n    .vr-journey-step {\r\n        text-align: center;\r\n    }\r\n\r\n    .vr-journey-number {\r\n        width: 38px;\r\n        height: 38px;\r\n        margin: 0 auto 11px;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        border: 1px solid var(--vr-olive);\r\n        border-radius: 50%;\r\n        color: var(--vr-forest);\r\n        font-family: Georgia, serif;\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-journey-step strong {\r\n        display: block;\r\n        color: var(--vr-forest);\r\n        font-size: 13px;\r\n        font-weight: 600;\r\n        letter-spacing: 0.3px;\r\n    }\r\n\r\n    .vr-journey-step span {\r\n        display: block;\r\n        margin-top: 3px;\r\n        color: var(--vr-muted);\r\n        font-size: 12px;\r\n    }\r\n\r\n    .vr-journey-line {\r\n        width: 55px;\r\n        height: 1px;\r\n        background: var(--vr-border);\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTENT\r\n   ========================================================= */\r\n\r\n    .vr-shipping-content {\r\n        max-width: 900px;\r\n        margin: 0 auto;\r\n    }\r\n\r\n    .vr-shipping-section {\r\n        padding: 42px 0;\r\n        border-bottom: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-shipping-section:first-child {\r\n        padding-top: 0;\r\n    }\r\n\r\n    .vr-shipping-section:last-child {\r\n        border-bottom: none;\r\n    }\r\n\r\n    .vr-section-label {\r\n        margin-bottom: 9px;\r\n        color: var(--vr-olive);\r\n        font-size: 10px;\r\n        font-weight: 600;\r\n        letter-spacing: 2px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-shipping-section h2 {\r\n        margin: 0 0 16px;\r\n        color: var(--vr-forest);\r\n        font-family: Georgia, \"Times New Roman\", serif;\r\n        font-size: 29px;\r\n        font-weight: 500;\r\n        line-height: 1.3;\r\n    }\r\n\r\n    .vr-shipping-section p {\r\n        margin: 0 0 15px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-shipping-section p:last-child {\r\n        margin-bottom: 0;\r\n    }\r\n\r\n    /* =========================================================\r\n   LIST\r\n   ========================================================= */\r\n\r\n    .vr-shipping-list {\r\n        margin: 18px 0 0;\r\n        padding: 0;\r\n        list-style: none;\r\n    }\r\n\r\n    .vr-shipping-list li {\r\n        position: relative;\r\n        margin-bottom: 12px;\r\n        padding-left: 22px;\r\n        color: var(--vr-text);\r\n        font-size: 15px;\r\n    }\r\n\r\n    .vr-shipping-list li::before {\r\n        content: \"\";\r\n        position: absolute;\r\n        left: 2px;\r\n        top: 12px;\r\n        width: 5px;\r\n        height: 5px;\r\n        border-radius: 50%;\r\n        background: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   NOTE\r\n   ========================================================= */\r\n\r\n    .vr-shipping-note {\r\n        margin-top: 22px;\r\n        padding: 18px 22px;\r\n        background: var(--vr-ivory);\r\n        border-left: 2px solid var(--vr-olive);\r\n    }\r\n\r\n    .vr-shipping-note p {\r\n        margin: 0;\r\n        font-size: 14px;\r\n        color: var(--vr-forest);\r\n    }\r\n\r\n    /* =========================================================\r\n   DELIVERY TABLE\r\n   ========================================================= */\r\n\r\n    .vr-delivery-table {\r\n        width: 100%;\r\n        margin-top: 24px;\r\n        border-collapse: collapse;\r\n    }\r\n\r\n    .vr-delivery-table th {\r\n        padding: 14px 0;\r\n        border-bottom: 1px solid var(--vr-forest);\r\n        color: var(--vr-forest);\r\n        text-align: left;\r\n        font-size: 11px;\r\n        font-weight: 600;\r\n        letter-spacing: 1.2px;\r\n        text-transform: uppercase;\r\n    }\r\n\r\n    .vr-delivery-table td {\r\n        padding: 17px 0;\r\n        border-bottom: 1px solid var(--vr-border);\r\n        color: var(--vr-text);\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-delivery-table td:last-child,\r\n    .vr-delivery-table th:last-child {\r\n        text-align: right;\r\n    }\r\n\r\n    /* =========================================================\r\n   CONTACT\r\n   ========================================================= */\r\n\r\n    .vr-contact {\r\n        margin-top: 24px;\r\n        padding: 28px 30px;\r\n        background: var(--vr-cream);\r\n        border: 1px solid var(--vr-border);\r\n    }\r\n\r\n    .vr-contact-title {\r\n        margin-bottom: 15px;\r\n        color: var(--vr-forest);\r\n        font-family: Georgia, serif;\r\n        font-size: 20px;\r\n    }\r\n\r\n    .vr-contact p {\r\n        margin-bottom: 8px;\r\n        font-size: 14px;\r\n    }\r\n\r\n    .vr-contact a {\r\n        color: var(--vr-forest);\r\n        text-decoration: none;\r\n        border-bottom: 1px solid rgba(38, 61, 37, 0.25);\r\n    }\r\n\r\n    .vr-contact a:hover {\r\n        color: var(--vr-olive);\r\n    }\r\n\r\n    /* =========================================================\r\n   LAST UPDATED\r\n   ========================================================= */\r\n\r\n    .vr-last-updated {\r\n        margin-top: 35px;\r\n        color: var(--vr-muted) !important;\r\n        font-size: 11px !important;\r\n        letter-spacing: 0.5px;\r\n    }\r\n\r\n    /* =========================================================\r\n   MOBILE\r\n   ========================================================= */\r\n\r\n    @media (max-width: 767px) {\r\n        .vanriti-shipping {\r\n            padding: 0 18px 60px;\r\n        }\r\n\r\n        .vr-shipping-hero {\r\n            padding: 50px 5px 45px;\r\n        }\r\n\r\n        .vr-shipping-hero h1 {\r\n            font-size: 40px;\r\n        }\r\n\r\n        .vr-shipping-hero p {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-shipping-journey {\r\n            margin-bottom: 50px;\r\n        }\r\n\r\n        .vr-journey {\r\n            grid-template-columns: 1fr;\r\n            gap: 17px;\r\n        }\r\n\r\n        .vr-journey-line {\r\n            width: 1px;\r\n            height: 25px;\r\n            margin: 0 auto;\r\n        }\r\n\r\n        .vr-shipping-section {\r\n            padding: 34px 0;\r\n        }\r\n\r\n        .vr-shipping-section h2 {\r\n            font-size: 25px;\r\n        }\r\n\r\n        .vr-shipping-section p,\r\n        .vr-shipping-list li {\r\n            font-size: 14px;\r\n        }\r\n\r\n        .vr-delivery-table th,\r\n        .vr-delivery-table td {\r\n            font-size: 13px;\r\n        }\r\n\r\n        .vr-contact {\r\n            padding: 22px;\r\n        }\r\n    }\r\n</style>\r\n\r\n<div class=\"vanriti-shipping\">\r\n    <!-- HERO -->\r\n    <header class=\"vr-shipping-hero\">\r\n        <div class=\"vr-shipping-eyebrow\">Delivery Information</div>\r\n\r\n        <h1>Shipping Policy</h1>\r\n\r\n        <p>\r\n            Simple, transparent delivery — from our hands to your home. We carefully prepare every VANRITI order so it\r\n            reaches you safely and in good condition.\r\n        </p>\r\n    </header>\r\n\r\n    <!-- SHIPPING JOURNEY -->\r\n    <div class=\"vr-shipping-journey\">\r\n        <div class=\"vr-journey\">\r\n            <div class=\"vr-journey-step\">\r\n                <div class=\"vr-journey-number\">01</div>\r\n                <strong>Order Confirmed</strong>\r\n                <span>Your order is received</span>\r\n            </div>\r\n\r\n            <div class=\"vr-journey-line\"></div>\r\n\r\n            <div class=\"vr-journey-step\">\r\n                <div class=\"vr-journey-number\">02</div>\r\n                <strong>Packed &amp; Dispatched</strong>\r\n                <span>Carefully prepared for delivery</span>\r\n            </div>\r\n\r\n            <div class=\"vr-journey-line\"></div>\r\n\r\n            <div class=\"vr-journey-step\">\r\n                <div class=\"vr-journey-number\">03</div>\r\n                <strong>Delivered</strong>\r\n                <span>At your doorstep</span>\r\n            </div>\r\n        </div>\r\n    </div>\r\n\r\n    <!-- CONTENT -->\r\n    <div class=\"vr-shipping-content\">\r\n        <!-- OUR COMMITMENT -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">01 — Our Commitment</div>\r\n\r\n            <h2>Our Shipping Commitment</h2>\r\n\r\n            <p>\r\n                At <strong>VANRITI</strong>, we carefully pack every order to help ensure that your products reach you\r\n                safely and in good condition. Orders are processed and shipped to the delivery address provided during\r\n                checkout.\r\n            </p>\r\n\r\n            <p>We currently ship orders across India through our logistics and delivery partners.</p>\r\n        </section>\r\n\r\n        <!-- ORDER PROCESSING -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">02 — Processing</div>\r\n\r\n            <h2>Order Processing</h2>\r\n\r\n            <p>\r\n                Orders are generally processed after successful confirmation of the order and payment, where applicable.\r\n            </p>\r\n\r\n            <ul class=\"vr-shipping-list\">\r\n                <li>\r\n                    Orders are normally processed within\r\n                    <strong>1–2 working days</strong>.\r\n                </li>\r\n\r\n                <li>Orders are dispatched after successful processing and verification.</li>\r\n\r\n                <li>Orders placed on Sundays or public holidays may be processed on the next working day.</li>\r\n\r\n                <li>\r\n                    Once your order has been dispatched, tracking information may be provided through the contact\r\n                    details supplied during checkout.\r\n                </li>\r\n            </ul>\r\n\r\n            <div class=\"vr-shipping-note\">\r\n                <p>\r\n                    <strong>Please note:</strong>\r\n                    Order processing time and delivery time are different. Delivery timelines begin after the order has\r\n                    been dispatched.\r\n                </p>\r\n            </div>\r\n        </section>\r\n\r\n        <!-- DELIVERY -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">03 — Delivery</div>\r\n\r\n            <h2>Estimated Delivery Time</h2>\r\n\r\n            <p>\r\n                Delivery time may vary depending on your location, courier availability, weather conditions, holidays\r\n                and other circumstances beyond our control.\r\n            </p>\r\n\r\n            <table class=\"vr-delivery-table\">\r\n                <thead>\r\n                    <tr>\r\n                        <th>Delivery Location</th>\r\n                        <th>Estimated Time</th>\r\n                    </tr>\r\n                </thead>\r\n\r\n                <tbody>\r\n                    <tr>\r\n                        <td>Metro Cities</td>\r\n                        <td>2–5 working days</td>\r\n                    </tr>\r\n\r\n                    <tr>\r\n                        <td>Non-Metro Cities</td>\r\n                        <td>3–7 working days</td>\r\n                    </tr>\r\n\r\n                    <tr>\r\n                        <td>Remote / Difficult-to-Reach Areas</td>\r\n                        <td>5–12 working days</td>\r\n                    </tr>\r\n                </tbody>\r\n            </table>\r\n        </section>\r\n\r\n        <!-- TRACKING -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">04 — Tracking</div>\r\n\r\n            <h2>Order Tracking</h2>\r\n\r\n            <p>\r\n                Once your order has been dispatched, you may receive tracking details through the contact information\r\n                associated with your order.\r\n            </p>\r\n\r\n            <p>You can use the tracking information to check the current status of your shipment.</p>\r\n\r\n            <p>\r\n                Tracking information may take some time to become active after the shipment has been handed over to the\r\n                courier partner.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- ADDRESS -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">05 — Delivery Details</div>\r\n\r\n            <h2>Delivery Address</h2>\r\n\r\n            <p>\r\n                Please ensure that your shipping address, mobile number and other delivery details are correct and\r\n                complete before placing your order.\r\n            </p>\r\n\r\n            <p>\r\n                VANRITI cannot be held responsible for delays or failed deliveries caused by an incorrect, incomplete or\r\n                inaccessible delivery address provided by the customer.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- DELIVERY ATTEMPTS -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">06 — Delivery Attempts</div>\r\n\r\n            <h2>Delivery Attempts</h2>\r\n\r\n            <p>Our courier partners may make multiple delivery attempts depending on their operational policies.</p>\r\n\r\n            <p>\r\n                If the customer is unavailable, provides incorrect delivery information, or does not respond to the\r\n                courier partner, the shipment may be returned to the sender.\r\n            </p>\r\n\r\n            <p>\r\n                If an order is returned because of an incorrect address, unavailability or other customer-related\r\n                reasons, additional shipping or re-dispatch charges may apply where applicable.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- DELAYS -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">07 — Delays</div>\r\n\r\n            <h2>Shipping Delays</h2>\r\n\r\n            <p>\r\n                Although we work with our delivery partners to deliver orders within the estimated timeframe, unforeseen\r\n                circumstances may occasionally cause delays.\r\n            </p>\r\n\r\n            <p>\r\n                Delays may occur due to severe weather, natural disasters, transportation disruptions, strikes, public\r\n                holidays, high shipment volumes, remote-location restrictions or other events beyond our reasonable\r\n                control.\r\n            </p>\r\n\r\n            <p>In such situations, we request your patience while the courier partner completes delivery.</p>\r\n        </section>\r\n\r\n        <!-- DAMAGED PACKAGE -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">08 — Package Condition</div>\r\n\r\n            <h2>Damaged or Tampered Package</h2>\r\n\r\n            <p>\r\n                If your package appears visibly damaged, opened or tampered with at the time of delivery, please inspect\r\n                it carefully before accepting the shipment.\r\n            </p>\r\n\r\n            <p>\r\n                If you receive a damaged or tampered product, please contact us as soon as possible with your order\r\n                details and clear photographs or video evidence of the package and product.\r\n            </p>\r\n\r\n            <div class=\"vr-shipping-note\">\r\n                <p>\r\n                    For faster assistance, keep the original packaging and shipping label until your concern has been\r\n                    reviewed.\r\n                </p>\r\n            </div>\r\n        </section>\r\n\r\n        <!-- CANCELLATION -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">09 — Cancellation</div>\r\n\r\n            <h2>Order Cancellation</h2>\r\n\r\n            <p>Orders may generally be cancelled only before they have been shipped.</p>\r\n\r\n            <p>\r\n                Once an order has been dispatched, cancellation may no longer be possible. For further information\r\n                regarding cancellations, returns and refunds, please refer to our applicable\r\n                <strong>Return &amp; Refund Policy</strong>.\r\n            </p>\r\n        </section>\r\n\r\n        <!-- CONTACT -->\r\n        <section class=\"vr-shipping-section\">\r\n            <div class=\"vr-section-label\">10 — Assistance</div>\r\n\r\n            <h2>Need Help?</h2>\r\n\r\n            <p>If you have questions regarding your shipment or delivery, please contact our support team.</p>\r\n\r\n            <div class=\"vr-contact\">\r\n                <div class=\"vr-contact-title\">VANRITI Customer Support</div>\r\n\r\n                <p>\r\n                    <strong>Email:</strong>\r\n                    <a href=\"mailto:support@vanriti.com\"> support@vanriti.com </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>Phone:</strong>\r\n                    <a href=\"tel:+911293509181\"> +91 1293509181 </a>\r\n                </p>\r\n\r\n                <p>\r\n                    <strong>TFC Sales &amp; Marketing</strong><br />\r\n                    12, 1st Floor, Ramnik Complex-II,<br />\r\n                    Tikona Park, NIT-1,<br />\r\n                    Faridabad, Haryana – 121001, India\r\n                </p>\r\n            </div>\r\n\r\n            <p class=\"vr-last-updated\">Last Updated: September 2026</p>\r\n        </section>\r\n    </div>\r\n</div>','Shipping & Delivery - VANRITI','Shipping timelines, charges and delivery details for orders placed on VANRITI.','published','2026-09-08 08:43:18','2026-09-08 09:33:56'),(6,'Return & Refund Policy','return-policy','<div class=\"vr-section\" style=\"background:var(--vr-cream);\">\n    <div class=\"container\">\n        <nav class=\"vr-breadcrumb mb-4\" aria-label=\"breadcrumb\">\n            <ol class=\"breadcrumb mb-0\">\n                <li class=\"breadcrumb-item\"><a href=\"/\">Home</a></li>\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">Return &amp; Refund Policy</li>\n            </ol>\n        </nav>\n        <div class=\"vr-kicker mb-3\">PURE BY NATURE</div>\n        <h1 class=\"vr-section-title mb-4\">Return &amp; Refund Policy</h1>\n        <div class=\"d-grid gap-4 small\">\n            <section>\n                <h5 class=\"fw-bold mb-2\">1. Eligibility</h5>\n                <p class=\"mb-0\">Returns are accepted within 7 days of delivery. Items must be unused, unopened and in their original packaging.</p>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">2. How to request a return</h5>\n                <ul class=\"mb-0 ps-3\">\n                    <li>Log in to your account and go to <em>My Orders</em>.</li>\n                    <li>Select the order and click <strong>Request Return</strong>.</li>\n                    <li>Choose the items and reason for return.</li>\n                    <li>Our team will review and approve within 24&ndash;48 hours.</li>\n                </ul>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">3. Return shipping</h5>\n                <p class=\"mb-0\">Once approved, we will arrange a pickup from your address at no extra cost. Please pack the items securely in their original packaging.</p>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">4. Refunds</h5>\n                <ul class=\"mb-0 ps-3\">\n                    <li>Refunds are processed within 5&ndash;7 business days after we receive and inspect the returned items.</li>\n                    <li>Online payments are refunded to the original payment method.</li>\n                    <li>COD orders are refunded via bank transfer or store credit.</li>\n                </ul>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">5. Non-returnable items</h5>\n                <p class=\"mb-0\">Products that are opened, used, or damaged due to customer handling are not eligible for return. Gift cards and promotional items are also non-returnable.</p>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">6. Damaged or wrong items</h5>\n                <p class=\"mb-0\">If you receive a damaged or incorrect item, contact us within 48 hours of delivery with photos. We will arrange an immediate replacement or full refund.</p>\n            </section>\n            <section>\n                <h5 class=\"fw-bold mb-2\">7. Contact</h5>\n                <p class=\"mb-0\">For any return or refund questions, reach us at info@vanriti.com or call +91 01293509181.</p>\n            </section>\n        </div>\n    </div>\n</div>','Return & Refund Policy - VANRITI','Learn about our return and refund policy for orders placed on VANRITI.','published','2026-09-08 09:43:47','2026-09-08 09:43:47');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
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
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `gateway` varchar(255) DEFAULT NULL,
  `method` varchar(255) NOT NULL DEFAULT 'cod',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','paid','failed','cancelled','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,'order_TZd4V4BOsrfw1b','razorpay','razorpay',356.90,'pending','{\"amount\":35690,\"amount_due\":35690,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788889412,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZd4V4BOsrfw1b\",\"notes\":{\"order_id\":1,\"order_number\":\"VAN-2026-000001\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000001\",\"status\":\"created\"}',NULL,NULL,'2026-09-08 12:13:32','2026-09-08 12:13:33'),(2,2,'order_TZdAZvfAnJuQVv','razorpay','razorpay',252.95,'pending','{\"amount\":25295,\"amount_due\":25295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788889757,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZdAZvfAnJuQVv\",\"notes\":{\"order_id\":2,\"order_number\":\"VAN-2026-000002\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000002\",\"status\":\"created\"}',NULL,NULL,'2026-09-08 12:19:18','2026-09-08 12:19:18'),(3,3,'COD-OOQ3O0MVYEUC','cod','cod',202.95,'pending',NULL,NULL,NULL,'2026-09-08 12:33:16','2026-09-08 12:33:16'),(4,4,'order_TZdUykEH8gjSRU','razorpay','razorpay',252.95,'pending','{\"amount\":25295,\"amount_due\":25295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788890916,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZdUykEH8gjSRU\",\"notes\":{\"order_id\":4,\"order_number\":\"VAN-2026-000004\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000004\",\"status\":\"created\"}',NULL,NULL,'2026-09-08 12:38:36','2026-09-08 12:38:37'),(5,5,'order_TZdy0EQMiTKPur','razorpay','razorpay',252.95,'pending','{\"amount\":25295,\"amount_due\":25295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788892564,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZdy0EQMiTKPur\",\"notes\":{\"order_id\":5,\"order_number\":\"VAN-2026-000005\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000005\",\"status\":\"created\"}',NULL,NULL,'2026-09-08 13:06:04','2026-09-08 13:06:06'),(6,6,'order_TZep1NWN7J5r1o','razorpay','razorpay',514.80,'pending','{\"amount\":51480,\"amount_due\":51480,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788895576,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZep1NWN7J5r1o\",\"notes\":{\"order_id\":6,\"order_number\":\"VAN-2026-000006\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000006\",\"status\":\"created\"}',NULL,NULL,'2026-09-08 13:56:16','2026-09-08 13:56:17'),(7,7,'order_TZqHukXi0NHRld','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788935955,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZqHukXi0NHRld\",\"notes\":{\"order_id\":7,\"order_number\":\"VAN-2026-000007\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000007\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:09:14','2026-09-09 01:09:16'),(8,8,'order_TZqQfyKY5QfBfZ','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788936452,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZqQfyKY5QfBfZ\",\"notes\":{\"order_id\":8,\"order_number\":\"VAN-2026-000008\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000008\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:17:33','2026-09-09 01:17:33'),(9,9,'order_TZqSrOWwgoElv6','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788936576,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZqSrOWwgoElv6\",\"notes\":{\"order_id\":9,\"order_number\":\"VAN-2026-000009\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000009\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:19:37','2026-09-09 01:19:37'),(10,10,'order_TZqpAut9Bg2JjL','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788937844,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZqpAut9Bg2JjL\",\"notes\":{\"order_id\":10,\"order_number\":\"VAN-2026-000010\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000010\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:40:44','2026-09-09 01:40:45'),(11,11,'order_TZr37DMGk1ZPHV','razorpay','razorpay',202.95,'paid','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788938636,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZr37DMGk1ZPHV\",\"notes\":{\"order_id\":11,\"order_number\":\"VAN-2026-000011\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000011\",\"status\":\"created\"}','2026-09-09 01:54:16',NULL,'2026-09-09 01:53:56','2026-09-09 01:54:16'),(12,12,'order_TZr3zRP6UXVu6n','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788938685,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZr3zRP6UXVu6n\",\"notes\":{\"order_id\":12,\"order_number\":\"VAN-2026-000012\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000012\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:54:46','2026-09-09 01:54:46'),(13,13,'order_TZr505obI0xkOv','razorpay','razorpay',202.95,'pending','{\"amount\":20295,\"amount_due\":20295,\"amount_paid\":0,\"attempts\":0,\"created_at\":1788938743,\"currency\":\"INR\",\"entity\":\"order\",\"id\":\"order_TZr505obI0xkOv\",\"notes\":{\"order_id\":13,\"order_number\":\"VAN-2026-000013\"},\"offer_id\":null,\"receipt\":\"VAN-2026-000013\",\"status\":\"created\"}',NULL,NULL,'2026-09-09 01:55:43','2026-09-09 01:55:44');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_role` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_role`
--

LOCK TABLES `permission_role` WRITE;
/*!40000 ALTER TABLE `permission_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `permission_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_category`
--

DROP TABLE IF EXISTS `product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_category` (
  `product_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `product_category_category_id_foreign` (`category_id`),
  CONSTRAINT `product_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_category_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category`
--

LOCK TABLES `product_category` WRITE;
/*!40000 ALTER TABLE `product_category` DISABLE KEYS */;
INSERT INTO `product_category` VALUES (1,2,0),(2,2,0),(3,2,0),(4,1,0),(5,1,0),(6,1,0),(7,1,0),(8,1,0),(9,2,0),(10,1,0),(11,2,0),(12,1,0),(13,1,0),(14,1,0),(15,1,0),(16,4,0),(17,4,0),(18,4,0),(19,4,0),(20,4,0),(21,4,0),(22,4,0),(23,4,0),(24,4,0),(25,4,0),(26,4,0),(27,4,0),(28,4,0),(29,4,0),(30,4,0),(31,8,0),(32,8,0),(33,8,0),(34,8,0),(35,8,0),(36,8,0),(37,8,0),(38,8,0),(39,8,0),(40,8,0),(41,6,1),(42,3,1),(43,3,0),(44,2,0),(45,2,1),(46,3,1),(46,10,0),(46,12,0),(47,9,1),(48,7,1),(49,6,1),(50,9,1),(50,10,0);
/*!40000 ALTER TABLE `product_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `owner_type` varchar(255) DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `type` enum('main','gallery','thumbnail') NOT NULL DEFAULT 'gallery',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_owner_type_owner_id_index` (`owner_type`,`owner_id`),
  KEY `product_images_product_id_type_sort_order_index` (`product_id`,`type`,`sort_order`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (6,50,NULL,NULL,'products/ZmUjQEB0B5UGhoaGDAxuglRWU2jD08mXNkGtOnjT.png',NULL,'gallery',1,'2026-09-08 05:38:29','2026-09-08 05:38:29'),(7,50,NULL,NULL,'products/J5aX5DBGWl7CylYBMvtTnyhhQzpe46S9JLUcvrth.png',NULL,'gallery',2,'2026-09-08 05:38:37','2026-09-08 05:38:37'),(8,50,NULL,NULL,'products/FwnRWRDaUMahGAq0GyYTrMRha1OdtfEaj5K5BGxm.png',NULL,'gallery',3,'2026-09-08 05:38:47','2026-09-08 05:38:47'),(9,49,NULL,NULL,'products/vt4t6d9vJA59471EaWBeUNSNDQTgPu63j5G4wmxE.png',NULL,'gallery',1,'2026-09-08 05:50:54','2026-09-08 05:50:54'),(10,48,NULL,NULL,'products/8K5u7HR95b8HIrzmIvuUAsjIiFoKplX9rm714qXR.png',NULL,'gallery',1,'2026-09-08 05:52:32','2026-09-08 05:52:32'),(11,47,NULL,NULL,'products/a0FwcVcyPab6q9iE2xS0N840X8lixeBXoZsv0uEi.png',NULL,'gallery',1,'2026-09-08 05:54:30','2026-09-08 05:54:30'),(12,46,NULL,NULL,'products/zwPTM3rhdRg4HkicGf1jpVxYmG6Ju5iFndcYNWA0.jpg',NULL,'gallery',1,'2026-09-08 05:54:49','2026-09-08 05:54:49'),(13,45,NULL,NULL,'products/VUJhtOn6nHoF3lmVGPBkD8hLCG25ZnhxrS6jmxGW.png',NULL,'gallery',1,'2026-09-08 05:56:41','2026-09-08 05:56:41'),(14,44,NULL,NULL,'products/1lII7kfjES0YnAv3YgUIjRXK2esv1ppx6mZS3RZH.png',NULL,'gallery',1,'2026-09-08 05:59:48','2026-09-08 05:59:48'),(15,43,NULL,NULL,'products/mWlDIAb6AoG4Z5x67rawJLCBweGFOLDhbBP02fSh.png',NULL,'gallery',1,'2026-09-08 06:00:05','2026-09-08 06:00:05'),(16,42,NULL,NULL,'products/RQOcaXQnq8GmLwvLmN6qvW9JzTIRhxrJriKQqxYB.png',NULL,'gallery',1,'2026-09-08 06:22:07','2026-09-08 06:22:07'),(17,41,NULL,NULL,'products/64VZVp7iVgEy2bB3K1jk0a43QfZotQn7YZlCwEs1.jpg',NULL,'gallery',1,'2026-09-08 06:22:34','2026-09-08 06:22:34'),(18,4,NULL,NULL,'products/Mi82l7qlReHv4bMb1wvs6QvYXmK0qr3gVPeIgrHm.png',NULL,'gallery',1,'2026-09-08 07:57:10','2026-09-08 07:57:10'),(19,1,NULL,NULL,'products/Me0zFLMIO9V9QpbsHD4Ewh9y1bMFInv0XGOY2rhk.png',NULL,'gallery',1,'2026-09-08 07:57:59','2026-09-08 07:57:59'),(20,40,NULL,NULL,'products/bUzZbapzx3oImermdYOm91k9LREZn3r52AbgM4CR.png',NULL,'gallery',1,'2026-09-08 08:02:54','2026-09-08 08:02:54'),(21,39,NULL,NULL,'products/dIVL5xDI5WemO0Ny9wWDhmGeCruXjz9V3nqX1YBc.png',NULL,'gallery',1,'2026-09-08 08:07:16','2026-09-08 08:07:16'),(22,38,NULL,NULL,'products/fqt3RDDqDDewy4Hf9CJycGLoDBYuuhJSx1ahDfbb.png',NULL,'gallery',1,'2026-09-08 08:09:25','2026-09-08 08:09:25'),(23,37,NULL,NULL,'products/Cbox1ZeVqJFGVwnjKqadNaXRaltMkeyqgTPovPJS.png',NULL,'gallery',1,'2026-09-08 08:11:33','2026-09-08 08:11:33'),(24,36,NULL,NULL,'products/egNtLoEfzhcDfcF4mili9Fcgm9ZSihGs6fkgpFST.png',NULL,'gallery',1,'2026-09-08 08:13:40','2026-09-08 08:13:40');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `weight` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `hsn_code` varchar(255) DEFAULT NULL,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_status_index` (`product_id`,`status`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `how_to_use` text DEFAULT NULL,
  `directions` text DEFAULT NULL,
  `warnings` text DEFAULT NULL,
  `precautions` text DEFAULT NULL,
  `disclaimer` text DEFAULT NULL,
  `net_quantity` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `hsn_code` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `manufacturer_address` varchar(255) DEFAULT NULL,
  `country_of_origin` varchar(255) DEFAULT NULL,
  `shelf_life` varchar(255) DEFAULT NULL,
  `expiry_info` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `search_keywords` text DEFAULT NULL,
  `status` enum('draft','active','inactive') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_bestseller` tinyint(1) NOT NULL DEFAULT 0,
  `is_new_arrival` tinyint(1) NOT NULL DEFAULT 0,
  `total_sold` int(11) NOT NULL DEFAULT 0,
  `review_count` int(11) NOT NULL DEFAULT 0,
  `review_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_status_is_featured_is_bestseller_is_new_arrival_index` (`status`,`is_featured`,`is_bestseller`,`is_new_arrival`),
  KEY `products_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'VANRITI Amla Reetha Shikakai Hair Cleanse Powder','vanriti-amla-reetha-shikakai-hair-cleanse-powder','VNRT001',NULL,'Herbal hair cleansing powder blend with Amla, Reetha and Shikakai.','VANRITI Amla Reetha Shikakai Hair Cleanse Powder is a traditional herbal powder blend designed for use in natural hair cleansing routines.','Amla, Reetha and Shikakai blend; Plant-based powder; For hair care routines','Amla 33.33%, Reetha 33.33%, Shikakai 33.34%','Supports a simple natural hair care routine and cleansing ritual.','Mix the required quantity with water to make a smooth paste. Apply to wet hair and scalp, massage gently and rinse thoroughly.','Use as required as part of a regular hair care routine.','For external use only. Avoid contact with eyes. Stop use if irritation occurs.','Perform a patch test before first use. Keep container tightly closed and store in a cool, dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,99,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Shikakai Hair Cleanse Powder 200g','Natural herbal Amla Reetha Shikakai powder blend for hair care and cleansing routines.','amla reetha shikakai powder, herbal hair powder, hair cleansing powder, natural hair care','amla powder reetha powder shikakai powder hair cleanser herbal hair care vanriti','active',0,0,1,0,0,0.00,NULL,'2026-09-08 13:56:16',NULL),(2,'VANRITI Amla Reetha Shikakai Bhringraj Hair Care Powder','vanriti-amla-reetha-shikakai-bhringraj-hair-care-powder','VNRT002',NULL,'Herbal hair care powder blend with Amla, Reetha, Shikakai and Bhringraj.','VANRITI Amla Reetha Shikakai Bhringraj Hair Care Powder combines four traditional herbal powders for a natural hair care routine.','Amla, Reetha, Shikakai and Bhringraj; Herbal powder blend; External use','Amla 25%, Reetha 25%, Shikakai 25%, Bhringraj 25%','Supports a traditional herbal hair care routine.','Mix with water to form a paste. Apply to hair and scalp, leave for a suitable period and rinse thoroughly.','Use according to your preferred hair care routine.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place away from moisture.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Shikakai Bhringraj Hair Care Powder 200g','Herbal Amla Reetha Shikakai and Bhringraj powder blend for natural hair care.','amla reetha shikakai bhringraj powder, herbal hair powder, hair care powder','amla reetha shikakai bhringraj hair care vanriti herbal powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(3,'VANRITI Amla Reetha Shikakai Bhringraj Hibiscus Hair Care Powder','vanriti-amla-reetha-shikakai-bhringraj-hibiscus-hair-care-powder','VNRT003',NULL,'Five-herb hair care powder blend with Amla, Reetha, Shikakai, Bhringraj and Hibiscus.','VANRITI five-herb hair care powder combines traditional plant powders for a simple natural hair care routine.','Five herbal powders; Traditional hair care blend; 200g pack','Amla 20%, Reetha 20%, Shikakai 20%, Bhringraj 20%, Hibiscus 20%','Supports a natural and traditional hair care routine.','Mix with water to form a smooth paste. Apply to hair and scalp and rinse thoroughly after use.','Use as part of a regular hair care routine.','For external use only. Avoid contact with eyes.','Patch test before use. Keep dry and tightly sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Shikakai Bhringraj Hibiscus Powder','Traditional five-herb powder blend for a natural hair care routine.','amla reetha shikakai bhringraj hibiscus powder, hair care powder','amla reetha shikakai bhringraj hibiscus vanriti hair powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(4,'VANRITI Amla Bhringraj Hair Care Powder','vanriti-amla-bhringraj-hair-care-powder','VNRT004',NULL,'Herbal Amla and Bhringraj powder blend for hair care routines.','VANRITI Amla Bhringraj Hair Care Powder is a simple two-herb blend suitable for traditional hair care routines.','Two-herb blend; Amla and Bhringraj; Natural powder','Amla 50%, Bhringraj 50%','Supports a simple traditional hair care routine.','Mix with water or another suitable hair care ingredient to form a paste and apply to hair.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store dry and sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Bhringraj Hair Care Powder 200g','Amla and Bhringraj herbal powder blend for a natural hair care routine.','amla bhringraj powder, herbal hair powder, natural hair care powder','amla bhringraj vanriti hair care herbal powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(5,'VANRITI Bhringraj Hibiscus Hair Care Powder','vanriti-bhringraj-hibiscus-hair-care-powder','VNRT005',NULL,'Herbal Bhringraj and Hibiscus powder blend for hair care.','VANRITI Bhringraj Hibiscus Hair Care Powder combines two traditional botanical powders for use in hair care routines.','Bhringraj and Hibiscus; Herbal powder; External use','Bhringraj 50%, Hibiscus 50%','Supports a traditional herbal hair care routine.','Mix with water to form a paste and apply to hair and scalp. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and keep dry.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Bhringraj Hibiscus Hair Care Powder 200g','Bhringraj and Hibiscus herbal powder blend for natural hair care routines.','bhringraj hibiscus powder, hair care powder, herbal hair powder','bhringraj hibiscus vanriti hair care powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(6,'VANRITI Amla Hibiscus Hair Care Powder','vanriti-amla-hibiscus-hair-care-powder','VNRT006',NULL,'Herbal Amla and Hibiscus powder blend for hair care.','VANRITI Amla Hibiscus Hair Care Powder is a two-herb botanical blend suitable for natural hair care routines.','Amla and Hibiscus; Botanical powder blend; 200g','Amla 50%, Hibiscus 50%','Supports a simple botanical hair care routine.','Mix with water to form a smooth paste and apply to hair. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Hibiscus Hair Care Powder 200g','Amla and Hibiscus herbal powder blend for natural hair care.','amla hibiscus powder, herbal hair care powder, natural hair powder','amla hibiscus vanriti hair care powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(7,'VANRITI Amla Bhringraj Hibiscus Hair Mask Powder','vanriti-amla-bhringraj-hibiscus-hair-mask-powder','VNRT007',NULL,'Herbal hair mask powder blend with Amla, Bhringraj and Hibiscus.','VANRITI Amla Bhringraj Hibiscus Hair Mask Powder is formulated as a botanical powder blend for hair mask routines.','Three-herb blend; Hair mask powder; Plant-based ingredients','Amla 40%, Bhringraj 30%, Hibiscus 30%','Supports a traditional hair mask routine.','Mix with water or a suitable base to form a paste. Apply to hair and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Bhringraj Hibiscus Hair Mask Powder','Botanical Amla Bhringraj and Hibiscus powder blend for hair mask routines.','amla bhringraj hibiscus hair mask, herbal hair mask powder','amla bhringraj hibiscus vanriti hair mask','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(8,'VANRITI Amla Bhringraj Shikakai Hair Care Powder','vanriti-amla-bhringraj-shikakai-hair-care-powder','VNRT008',NULL,'Herbal Amla, Bhringraj and Shikakai powder blend.','VANRITI Amla Bhringraj Shikakai Hair Care Powder combines three traditional botanical powders for hair care.','Three-herb hair care blend; Herbal powder; External use','Amla 40%, Bhringraj 30%, Shikakai 30%','Supports a natural hair care routine.','Mix with water to make a paste. Apply to hair and scalp and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Keep moisture away.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Bhringraj Shikakai Hair Care Powder','Amla Bhringraj and Shikakai herbal powder blend for hair care routines.','amla bhringraj shikakai powder, herbal hair powder','amla bhringraj shikakai vanriti hair care','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(9,'VANRITI Bhringraj Shikakai Reetha Hair Cleanse Powder','vanriti-bhringraj-shikakai-reetha-hair-cleanse-powder','VNRT009',NULL,'Herbal cleansing powder blend with Bhringraj, Shikakai and Reetha.','VANRITI Bhringraj Shikakai Reetha Hair Cleanse Powder is a botanical powder blend for natural hair cleansing routines.','Bhringraj, Shikakai and Reetha; Herbal cleanser; Powder format','Bhringraj 40%, Shikakai 30%, Reetha 30%','Supports a traditional hair cleansing routine.','Mix with water to form a paste. Apply to wet hair and scalp, then rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store dry and sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Bhringraj Shikakai Reetha Hair Cleanse Powder','Bhringraj Shikakai and Reetha herbal powder blend for hair cleansing.','bhringraj shikakai reetha powder, hair cleansing powder','bhringraj shikakai reetha vanriti hair cleanser','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(10,'VANRITI Amla Reetha Bhringraj Hair Care Powder','vanriti-amla-reetha-bhringraj-hair-care-powder','VNRT010',NULL,'Herbal Amla, Reetha and Bhringraj powder blend for hair care.','VANRITI Amla Reetha Bhringraj Hair Care Powder combines three traditional botanical powders.','Three-herb blend; Amla, Reetha and Bhringraj; 200g','Amla 40%, Reetha 30%, Bhringraj 30%','Supports a natural hair care routine.','Mix with water to make a paste. Apply to hair and scalp and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store away from moisture.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Bhringraj Hair Care Powder','Natural Amla Reetha and Bhringraj herbal powder blend for hair care.','amla reetha bhringraj powder, herbal hair care powder','amla reetha bhringraj vanriti hair powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(11,'VANRITI Shikakai Reetha Hair Cleansing Powder','vanriti-shikakai-reetha-hair-cleansing-powder','VNRT011',NULL,'Traditional Shikakai and Reetha herbal hair cleansing powder.','VANRITI Shikakai Reetha Hair Cleansing Powder is a simple two-herb blend for natural hair cleansing routines.','Shikakai and Reetha; Hair cleansing blend; Herbal powder','Shikakai 50%, Reetha 50%','Supports a traditional hair cleansing routine.','Mix with water to form a paste, apply to wet hair and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and keep product dry.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Shikakai Reetha Hair Cleansing Powder 200g','Shikakai and Reetha herbal powder blend for traditional hair cleansing.','shikakai reetha powder, hair cleansing powder, herbal hair cleanser','shikakai reetha vanriti hair cleansing powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(12,'VANRITI Amla Shikakai Hair Care Powder','vanriti-amla-shikakai-hair-care-powder','VNRT012',NULL,'Herbal Amla and Shikakai powder blend for hair care.','VANRITI Amla Shikakai Hair Care Powder combines two traditional botanical powders for a simple hair care routine.','Amla and Shikakai; Two-herb blend; Powder format','Amla 50%, Shikakai 50%','Supports a natural hair care routine.','Mix with water to form a smooth paste and apply to hair and scalp. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Keep away from moisture.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Shikakai Hair Care Powder 200g','Amla and Shikakai herbal powder blend for a natural hair care routine.','amla shikakai powder, herbal hair care powder','amla shikakai vanriti hair powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(13,'VANRITI Amla Reetha Hair Care Powder','vanriti-amla-reetha-hair-care-powder','VNRT013',NULL,'Herbal Amla and Reetha powder blend for hair care.','VANRITI Amla Reetha Hair Care Powder is a simple botanical blend for traditional hair care routines.','Amla and Reetha; Herbal powder; External use','Amla 50%, Reetha 50%','Supports a natural hair care routine.','Mix with water to form a paste and apply to hair. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Hair Care Powder 200g','Amla and Reetha herbal powder blend for natural hair care.','amla reetha powder, herbal hair care powder','amla reetha vanriti hair powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(14,'VANRITI Reetha Shikakai Hibiscus Hair Care Powder','vanriti-reetha-shikakai-hibiscus-hair-care-powder','VNRT014',NULL,'Herbal Reetha, Shikakai and Hibiscus powder blend for hair care.','VANRITI Reetha Shikakai Hibiscus Hair Care Powder combines three botanical powders for a traditional hair care routine.','Reetha, Shikakai and Hibiscus; Herbal blend; 200g','Reetha 35%, Shikakai 35%, Hibiscus 30%','Supports a natural hair care routine.','Mix with water to form a paste and apply to hair. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and keep product dry.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Reetha Shikakai Hibiscus Hair Care Powder','Reetha Shikakai and Hibiscus botanical powder blend for hair care.','reetha shikakai hibiscus powder, herbal hair care powder','reetha shikakai hibiscus vanriti hair powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(15,'VANRITI Amla Reetha Shikakai Hibiscus Hair Care Powder','vanriti-amla-reetha-shikakai-hibiscus-hair-care-powder','VNRT015',NULL,'Herbal Amla, Reetha, Shikakai and Hibiscus powder blend.','VANRITI Amla Reetha Shikakai Hibiscus Hair Care Powder combines four traditional botanical powders.','Four-herb blend; Traditional hair care ingredients; 200g','Amla 25%, Reetha 25%, Shikakai 25%, Hibiscus 25%','Supports a traditional botanical hair care routine.','Mix with water to form a paste and apply to hair. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Keep tightly closed and dry.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Reetha Shikakai Hibiscus Hair Care Powder','Traditional four-herb powder blend for natural hair care routines.','amla reetha shikakai hibiscus powder, herbal hair powder','amla reetha shikakai hibiscus vanriti hair care','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(16,'VANRITI Multani Mitti Rose Petal Face Pack Powder','vanriti-multani-mitti-rose-petal-face-pack-powder','VNRT016',NULL,'Natural Multani Mitti and Rose Petal powder blend for face pack routines.','VANRITI Multani Mitti Rose Petal Face Pack Powder combines clay and rose petal powder for a traditional skincare routine.','Multani Mitti and Rose; Face pack powder; External use','Multani Mitti 60%, Rose Petal 40%','Supports cleansing and refreshing skincare routines.','Mix with water or a suitable base to form a smooth paste. Apply evenly to clean skin and rinse after drying.','Use as required as part of a skincare routine.','For external use only. Avoid contact with eyes. Do not apply to broken skin.','Patch test before use. Stop use if irritation occurs.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,99,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Rose Petal Face Pack Powder','Multani Mitti and Rose Petal powder blend for traditional face pack routines.','multani mitti rose powder, face pack powder, natural face pack','multani mitti rose petal vanriti face pack powder','active',0,0,1,0,0,0.00,NULL,'2026-09-09 01:55:43',NULL),(17,'VANRITI Multani Mitti Neem Face Pack Powder','vanriti-multani-mitti-neem-face-pack-powder','VNRT017',NULL,'Multani Mitti and Neem herbal face pack powder blend.','VANRITI Multani Mitti Neem Face Pack Powder is a traditional botanical powder blend for skincare routines.','Multani Mitti and Neem; Face pack; External use','Multani Mitti 70%, Neem 30%','Supports a simple cleansing skincare routine.','Mix with water to form a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Do not use on broken or irritated skin.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Neem Face Pack Powder 200g','Multani Mitti and Neem powder blend for a traditional skincare routine.','multani mitti neem powder, neem face pack, natural face pack powder','multani mitti neem vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(18,'VANRITI Multani Mitti Beetroot Rose Face Pack Powder','vanriti-multani-mitti-beetroot-rose-face-pack-powder','VNRT018',NULL,'Multani Mitti, Beetroot and Rose Petal face pack powder blend.','VANRITI Multani Mitti Beetroot Rose Face Pack Powder combines traditional clay with botanical powders for skincare routines.','Clay and botanical blend; Face pack powder; External use','Multani Mitti 50%, Beetroot 25%, Rose Petal 25%','Supports a refreshing skincare routine.','Mix with water to form a paste and apply to clean skin. Rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Beetroot Rose Face Pack Powder','Multani Mitti Beetroot and Rose Petal powder blend for face pack routines.','multani mitti beetroot rose powder, face pack powder','multani mitti beetroot rose vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(19,'VANRITI Neem Rose Petal Face Pack Powder','vanriti-neem-rose-petal-face-pack-powder','VNRT019',NULL,'Neem and Rose Petal botanical face pack powder blend.','VANRITI Neem Rose Petal Face Pack Powder is a botanical powder blend for a traditional skincare routine.','Neem and Rose Petal; Botanical face pack; Powder format','Neem 50%, Rose Petal 50%','Supports a simple botanical skincare routine.','Mix with water to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Do not apply to irritated skin.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Neem Rose Petal Face Pack Powder','Neem and Rose Petal botanical powder blend for skincare routines.','neem rose powder, rose face pack, neem face pack powder','neem rose vanriti face pack powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(20,'VANRITI Multani Mitti Neem Rose Face Pack Powder','vanriti-multani-mitti-neem-rose-face-pack-powder','VNRT020',NULL,'Multani Mitti, Neem and Rose Petal face pack powder blend.','VANRITI Multani Mitti Neem Rose Face Pack Powder combines clay, neem and rose powders for traditional skincare routines.','Multani Mitti, Neem and Rose; Face pack powder; 200g','Multani Mitti 50%, Neem 25%, Rose Petal 25%','Supports a cleansing and refreshing skincare routine.','Mix with water to make a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and discontinue if irritation occurs.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Neem Rose Face Pack Powder','Multani Mitti Neem and Rose Petal powder blend for natural skincare.','multani mitti neem rose powder, face pack powder','multani mitti neem rose vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(21,'VANRITI Multani Mitti Beetroot Face Pack Powder','vanriti-multani-mitti-beetroot-face-pack-powder','VNRT021',NULL,'Multani Mitti and Beetroot face pack powder blend.','VANRITI Multani Mitti Beetroot Face Pack Powder is a simple clay and botanical powder blend for skincare routines.','Multani Mitti and Beetroot; Face pack powder; External use','Multani Mitti 70%, Beetroot 30%','Supports a simple skincare routine.','Mix with water to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Beetroot Face Pack Powder 200g','Multani Mitti and Beetroot powder blend for face pack routines.','multani mitti beetroot powder, beetroot face pack, face pack powder','multani mitti beetroot vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(22,'VANRITI Rose Petal Beetroot Face Pack Powder','vanriti-rose-petal-beetroot-face-pack-powder','VNRT022',NULL,'Rose Petal and Beetroot botanical face pack powder blend.','VANRITI Rose Petal Beetroot Face Pack Powder combines two botanical powders for a natural skincare routine.','Rose Petal and Beetroot; Botanical powder; Face pack','Rose Petal 60%, Beetroot 40%','Supports a refreshing botanical skincare routine.','Mix with water or a suitable base to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Keep dry and sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Rose Petal Beetroot Face Pack Powder','Rose Petal and Beetroot botanical powder blend for skincare routines.','rose beetroot powder, face pack powder, rose petal face pack','rose petal beetroot vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(23,'VANRITI Multani Mitti Neem Beetroot Face Pack Powder','vanriti-multani-mitti-neem-beetroot-face-pack-powder','VNRT023',NULL,'Multani Mitti, Neem and Beetroot face pack powder blend.','VANRITI Multani Mitti Neem Beetroot Face Pack Powder combines traditional clay with botanical powders.','Clay and botanical blend; Face pack powder; External use','Multani Mitti 50%, Neem 25%, Beetroot 25%','Supports a simple cleansing skincare routine.','Mix with water to form a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Do not use on broken skin.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Neem Beetroot Face Pack Powder','Multani Mitti Neem and Beetroot powder blend for skincare routines.','multani mitti neem beetroot powder, face pack powder','multani mitti neem beetroot vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(24,'VANRITI Multani Mitti Rose Neem Beetroot Face Pack Powder','vanriti-multani-mitti-rose-neem-beetroot-face-pack-powder','VNRT024',NULL,'Four-ingredient Multani Mitti, Rose, Neem and Beetroot face pack powder.','VANRITI Multani Mitti Rose Neem Beetroot Face Pack Powder combines clay and botanical powders for a traditional skincare routine.','Four-ingredient blend; Face pack powder; External use','Multani Mitti 40%, Rose Petal 20%, Neem 20%, Beetroot 20%','Supports a natural skincare routine.','Mix with water to make a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store dry and sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Rose Neem Beetroot Face Pack Powder','Multani Mitti Rose Neem and Beetroot botanical face pack blend.','multani mitti rose neem beetroot powder, face pack','multani mitti rose neem beetroot vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(25,'VANRITI Neem Rose Beetroot Face Pack Powder','vanriti-neem-rose-beetroot-face-pack-powder','VNRT025',NULL,'Neem, Rose Petal and Beetroot botanical face pack powder.','VANRITI Neem Rose Beetroot Face Pack Powder combines three botanical powders for a natural skincare routine.','Neem, Rose and Beetroot; Botanical blend; Face pack','Neem 40%, Rose Petal 30%, Beetroot 30%','Supports a simple botanical skincare routine.','Mix with water to make a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and keep dry.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Neem Rose Beetroot Face Pack Powder','Neem Rose Petal and Beetroot botanical powder blend for skincare.','neem rose beetroot powder, face pack powder','neem rose beetroot vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(26,'VANRITI Multani Mitti Hibiscus Rose Face Pack Powder','vanriti-multani-mitti-hibiscus-rose-face-pack-powder','VNRT026',NULL,'Multani Mitti, Hibiscus and Rose Petal face pack powder blend.','VANRITI Multani Mitti Hibiscus Rose Face Pack Powder combines traditional clay and botanical powders.','Multani Mitti, Hibiscus and Rose; Face pack; 200g','Multani Mitti 50%, Hibiscus 25%, Rose Petal 25%','Supports a refreshing skincare routine.','Mix with water to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Hibiscus Rose Face Pack Powder','Multani Mitti Hibiscus and Rose Petal powder blend for skincare.','multani mitti hibiscus rose powder, face pack powder','multani mitti hibiscus rose vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(27,'VANRITI Amla Rose Petal Face Pack Powder','vanriti-amla-rose-petal-face-pack-powder','VNRT027',NULL,'Amla and Rose Petal botanical face pack powder blend.','VANRITI Amla Rose Petal Face Pack Powder combines Amla and Rose Petal powders for a traditional skincare routine.','Amla and Rose Petal; Botanical blend; Face pack powder','Amla 50%, Rose Petal 50%','Supports a natural skincare routine.','Mix with water to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store dry and sealed.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Rose Petal Face Pack Powder','Amla and Rose Petal botanical powder blend for traditional skincare.','amla rose powder, face pack powder, rose petal face pack','amla rose petal vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(28,'VANRITI Amla Neem Face Pack Powder','vanriti-amla-neem-face-pack-powder','VNRT028',NULL,'Amla and Neem botanical face pack powder blend.','VANRITI Amla Neem Face Pack Powder combines two traditional botanical powders for skincare routines.','Amla and Neem; Botanical face pack; External use','Amla 50%, Neem 50%','Supports a simple botanical skincare routine.','Mix with water to make a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Do not apply to irritated skin.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Neem Face Pack Powder 200g','Amla and Neem botanical powder blend for a natural skincare routine.','amla neem powder, face pack powder, herbal face pack','amla neem vanriti face pack powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(29,'VANRITI Amla Rose Neem Face Pack Powder','vanriti-amla-rose-neem-face-pack-powder','VNRT029',NULL,'Amla, Rose Petal and Neem botanical face pack powder.','VANRITI Amla Rose Neem Face Pack Powder combines three traditional botanical powders for skincare.','Amla, Rose and Neem; Botanical face pack; Powder format','Amla 40%, Rose Petal 30%, Neem 30%','Supports a simple natural skincare routine.','Mix with water to form a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a dry place.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Rose Neem Face Pack Powder','Amla Rose Petal and Neem botanical powder blend for skincare routines.','amla rose neem powder, face pack powder, herbal face pack','amla rose neem vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(30,'VANRITI Multani Mitti Amla Rose Face Pack Powder','vanriti-multani-mitti-amla-rose-face-pack-powder','VNRT030',NULL,'Multani Mitti, Amla and Rose Petal face pack powder blend.','VANRITI Multani Mitti Amla Rose Face Pack Powder combines clay and botanical powders for a traditional skincare routine.','Multani Mitti, Amla and Rose; Face pack powder; External use','Multani Mitti 50%, Amla 25%, Rose Petal 25%','Supports a cleansing and refreshing skincare routine.','Mix with water to form a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use and discontinue if irritation occurs.','This product is intended for cosmetic and personal care use. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Amla Rose Face Pack Powder','Multani Mitti Amla and Rose Petal botanical face pack powder blend.','multani mitti amla rose powder, face pack powder','multani mitti amla rose vanriti face pack','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(31,'VANRITI Moringa Amla Daily Wellness Powder','vanriti-moringa-amla-daily-wellness-powder','VNRT031',NULL,'Moringa and Amla powder blend for daily wellness routines.','VANRITI Moringa Amla Daily Wellness Powder combines Moringa and Amla powders as part of a general wellness routine.','Moringa and Amla; Botanical powder blend; 200g','Moringa 50%, Amla 50%','Provides a convenient botanical powder option for a daily wellness routine.','Use only as directed on the final product label. Mix the recommended quantity with a suitable food or beverage if applicable.','Consume only according to the final approved product label.','Do not use if the product is damaged or contaminated. Keep out of reach of children.','Consult a qualified healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Amla Daily Wellness Powder 200g','Moringa and Amla botanical powder blend for a convenient daily wellness routine.','moringa amla powder, moringa powder, amla powder, wellness powder','moringa amla vanriti wellness powder herbal powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(32,'VANRITI Moringa Beetroot Wellness Powder','vanriti-moringa-beetroot-wellness-powder','VNRT032',NULL,'Moringa and Beetroot powder blend for general wellness routines.','VANRITI Moringa Beetroot Wellness Powder combines two botanical powders in a convenient blend.','Moringa and Beetroot; Botanical powder blend; 200g','Moringa 50%, Beetroot 50%','Provides a convenient botanical powder option for general wellness routines.','Use only according to the final product label. Mix the recommended quantity with a suitable food or beverage if applicable.','Consume only as directed on the final approved label.','Keep out of reach of children. Store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having an existing medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Beetroot Wellness Powder 200g','Moringa and Beetroot botanical powder blend for general wellness routines.','moringa beetroot powder, wellness powder, botanical powder','moringa beetroot vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(33,'VANRITI Amla Beetroot Wellness Powder','vanriti-amla-beetroot-wellness-powder','VNRT033',NULL,'Amla and Beetroot botanical powder blend for wellness routines.','VANRITI Amla Beetroot Wellness Powder combines Amla and Beetroot powders for a convenient general wellness blend.','Amla and Beetroot; Botanical powder; 200g','Amla 50%, Beetroot 50%','Provides a convenient botanical powder option for a daily wellness routine.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children and store in a cool dry place.','Consult a qualified healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Beetroot Wellness Powder 200g','Amla and Beetroot botanical powder blend for general wellness use.','amla beetroot powder, wellness powder, botanical powder','amla beetroot vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(34,'VANRITI Moringa Amla Beetroot Wellness Powder','vanriti-moringa-amla-beetroot-wellness-powder','VNRT034',NULL,'Moringa, Amla and Beetroot botanical wellness powder blend.','VANRITI Moringa Amla Beetroot Wellness Powder combines three botanical powders for general wellness routines.','Three botanical ingredients; Convenient powder blend; 200g','Moringa 40%, Amla 30%, Beetroot 30%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep dry and out of reach of children.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Amla Beetroot Wellness Powder','Moringa Amla and Beetroot botanical powder blend for general wellness.','moringa amla beetroot powder, wellness powder, herbal powder','moringa amla beetroot vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(35,'VANRITI Moringa Amla Beetroot Hibiscus Wellness Powder','vanriti-moringa-amla-beetroot-hibiscus-wellness-powder','VNRT035',NULL,'Four-ingredient Moringa, Amla, Beetroot and Hibiscus wellness powder blend.','VANRITI Moringa Amla Beetroot Hibiscus Wellness Powder combines four botanical powders in one convenient blend.','Four botanical ingredients; Powder blend; 200g','Moringa 35%, Amla 25%, Beetroot 25%, Hibiscus 15%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children. Store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Amla Beetroot Hibiscus Wellness Powder','Moringa Amla Beetroot and Hibiscus botanical powder blend.','moringa amla beetroot hibiscus powder, wellness powder','moringa amla beetroot hibiscus vanriti wellness','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(36,'VANRITI Moringa Beetroot Hibiscus Wellness Powder','vanriti-moringa-beetroot-hibiscus-wellness-powder','VNRT036',NULL,'Moringa, Beetroot and Hibiscus botanical wellness powder blend.','VANRITI Moringa Beetroot Hibiscus Wellness Powder combines three botanical powders for general wellness routines.','Three botanical ingredients; Wellness powder blend; 200g','Moringa 40%, Beetroot 35%, Hibiscus 25%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children and store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Beetroot Hibiscus Wellness Powder','Moringa Beetroot and Hibiscus botanical powder blend for wellness routines.','moringa beetroot hibiscus powder, wellness powder','moringa beetroot hibiscus vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(37,'VANRITI Amla Beetroot Hibiscus Wellness Powder','vanriti-amla-beetroot-hibiscus-wellness-powder','VNRT037',NULL,'Amla, Beetroot and Hibiscus botanical wellness powder blend.','VANRITI Amla Beetroot Hibiscus Wellness Powder combines three botanical powders for general wellness routines.','Three botanical ingredients; Wellness powder; 200g','Amla 40%, Beetroot 35%, Hibiscus 25%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children and store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Beetroot Hibiscus Wellness Powder','Amla Beetroot and Hibiscus botanical powder blend for wellness routines.','amla beetroot hibiscus powder, wellness powder','amla beetroot hibiscus vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(38,'VANRITI Amla Hibiscus Wellness Powder','vanriti-amla-hibiscus-wellness-powder','VNRT038',NULL,'Amla and Hibiscus botanical wellness powder blend.','VANRITI Amla Hibiscus Wellness Powder combines two botanical powders in a convenient general wellness blend.','Amla and Hibiscus; Botanical wellness blend; 200g','Amla 60%, Hibiscus 40%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children. Store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Hibiscus Wellness Powder 200g','Amla and Hibiscus botanical powder blend for a general wellness routine.','amla hibiscus powder, wellness powder, botanical powder','amla hibiscus vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(39,'VANRITI Moringa Amla Hibiscus Wellness Powder','vanriti-moringa-amla-hibiscus-wellness-powder','VNRT039',NULL,'Moringa, Amla and Hibiscus botanical wellness powder blend.','VANRITI Moringa Amla Hibiscus Wellness Powder combines three botanical powders for general wellness routines.','Three botanical ingredients; Wellness powder blend; 200g','Moringa 40%, Amla 35%, Hibiscus 25%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children. Store in a cool dry place.','Consult a healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Amla Hibiscus Wellness Powder','Moringa Amla and Hibiscus botanical powder blend for general wellness.','moringa amla hibiscus powder, wellness powder','moringa amla hibiscus vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(40,'VANRITI Moringa Beetroot Amla Hibiscus Wellness Powder','vanriti-moringa-beetroot-amla-hibiscus-wellness-powder','VNRT040',NULL,'Four-ingredient Moringa, Beetroot, Amla and Hibiscus wellness powder blend.','VANRITI Moringa Beetroot Amla Hibiscus Wellness Powder combines four botanical powders in a convenient general wellness blend.','Four botanical ingredients; Wellness powder blend; 200g','Moringa 30%, Beetroot 30%, Amla 25%, Hibiscus 15%','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final product label.','Consume according to the final approved product label.','Keep out of reach of children. Store in a cool dry place.','Consult a qualified healthcare professional before use if pregnant, breastfeeding, taking medication or having a medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.00,5.00,100,5,'121212','TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Beetroot Amla Hibiscus Wellness Powder','Moringa Beetroot Amla and Hibiscus botanical powder blend for wellness.','moringa beetroot amla hibiscus powder, wellness powder','moringa beetroot amla hibiscus vanriti wellness powder','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(41,'VANRITI Multani Mitti Powder','vanriti-multani-mitti-powder','VNRT041',NULL,'Pure Multani Mitti powder for face packs and skincare routines.','VANRITI Multani Mitti Powder is a naturally derived clay powder suitable for face packs and skincare routines. It can be used as part of a simple traditional skincare ritual.','100% Multani Mitti; Natural clay powder; Suitable for face pack routines','100% Multani Mitti (Fuller\'s Earth) Powder','Supports cleansing and refreshing skincare routines.','Mix the required quantity with water or a suitable base to form a smooth paste. Apply evenly to clean skin and rinse thoroughly.','Use as required as part of a regular skincare routine.','For external use only. Avoid contact with eyes. Do not apply to broken or irritated skin.','Patch test before first use. Stop use if irritation occurs. Store in a cool, dry place.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Multani Mitti Powder 200g','Natural Multani Mitti powder for face packs and traditional skincare routines.','multani mitti powder, fuller earth powder, face pack powder, natural clay','multani mitti vanriti face pack powder fuller earth skincare clay','active',0,0,1,0,0,0.00,NULL,'2026-09-08 06:22:41',NULL),(42,'VANRITI Bhringraj Powder','vanriti-bhringraj-powder','VNRT042',NULL,'Pure Bhringraj powder for traditional hair care routines.','VANRITI Bhringraj Powder is a botanical powder suitable for traditional hair care routines and herbal hair mask preparations.','100% Bhringraj Powder; Botanical ingredient; Hair care use','100% Bhringraj Powder','Supports a traditional botanical hair care routine.','Mix the required quantity with water or a suitable hair care base to form a paste. Apply to hair and scalp and rinse thoroughly.','Use as required as part of a hair care routine.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place away from moisture.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Bhringraj Powder 200g','Bhringraj botanical powder for traditional hair care routines.','bhringraj powder, bhringraj hair powder, herbal hair powder','bhringraj vanriti hair care powder herbal powder','active',0,0,1,0,0,0.00,NULL,'2026-09-08 06:22:10',NULL),(43,'VANRITI Amla Powder','vanriti-amla-powder','VNRT043',NULL,'Pure Amla powder for traditional hair and skincare routines.','VANRITI Amla Powder is a botanical powder suitable for traditional hair care, face pack and personal care routines.','100% Amla Powder; Botanical ingredient; Multi-purpose use','100% Amla Powder','Supports traditional hair and personal care routines.','For external use, mix the required quantity with water or a suitable base to form a paste. Use according to your chosen routine.','Use as required.','For external use only unless the final product label specifically permits another use. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Amla Powder 200g','Natural Amla powder for traditional hair and personal care routines.','amla powder, amla hair powder, indian gooseberry powder','amla vanriti herbal powder hair care skincare','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(44,'VANRITI Reetha Powder','vanriti-reetha-powder','VNRT044',NULL,'Pure Reetha powder for traditional hair cleansing routines.','VANRITI Reetha Powder is a botanical powder commonly used as part of traditional hair cleansing and hair care routines.','100% Reetha Powder; Botanical cleanser; Hair care use','100% Reetha Powder','Supports a traditional hair cleansing routine.','Mix the required quantity with water to make a paste or suitable preparation. Apply to wet hair and rinse thoroughly.','Use as required as part of a hair care routine.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place and protect from moisture.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.00,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Reetha Powder 200g','Reetha botanical powder for traditional hair cleansing routines.','reetha powder, soapnut powder, reetha hair powder, herbal cleanser','reetha vanriti soapnut powder hair cleansing','active',0,0,1,0,0,0.00,NULL,NULL,NULL),(45,'VANRITI Shikakai Powder','vanriti-shikakai-powder','VNRT045',NULL,'Pure Shikakai powder for traditional hair care routines.','VANRITI Shikakai Powder is a botanical powder suitable for traditional hair cleansing and hair care preparations.','100% Shikakai Powder; Botanical ingredient; Hair care use','100% Shikakai Powder','Supports a traditional hair care and cleansing routine.','Mix the required quantity with water to form a paste. Apply to wet hair and scalp and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place away from moisture.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Shikakai Powder 200g','Natural Shikakai powder for traditional hair cleansing and hair care.','shikakai powder, shikakai hair powder, herbal hair cleanser','shikakai vanriti hair powder herbal hair care','active',0,0,1,0,0,0.00,NULL,'2026-09-08 05:59:37',NULL),(46,'VANRITI Hibiscus Powder','vanriti-hibiscus-powder','VNRT046',NULL,'Pure Hibiscus powder for traditional hair and skincare routines.','VANRITI Hibiscus Powder is a botanical powder suitable for traditional hair masks, hair care and personal care routines.','100% Hibiscus Powder; Botanical ingredient; Hair and personal care','100% Hibiscus Powder','Supports a traditional botanical hair and personal care routine.','Mix the required quantity with water or a suitable base to form a paste. Apply according to your chosen routine and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before use. Store in a cool, dry place.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Hibiscus Powder 200g','Hibiscus botanical powder for traditional hair and personal care routines.','hibiscus powder, hibiscus hair powder, herbal hair powder','hibiscus vanriti hair care powder botanical powder','active',0,0,1,0,0,0.00,NULL,'2026-09-08 05:55:25',NULL),(47,'VANRITI Moringa Powder','vanriti-moringa-powder','VNRT047',NULL,'Pure Moringa powder for general wellness and dietary routines.','VANRITI Moringa Powder is a botanical powder intended for use as part of a general wellness and dietary routine.','100% Moringa Powder; Botanical ingredient; Wellness use','100% Moringa Powder','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final approved product label. Mix the recommended quantity with a suitable food or beverage if applicable.','Consume only according to the final approved product label.','Keep out of reach of children. Do not use if product is damaged or contaminated.','Consult a qualified healthcare professional before use if pregnant, breastfeeding, taking medication or having an existing medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Moringa Powder 200g','Natural Moringa powder for general wellness and dietary routines.','moringa powder, moringa leaf powder, moringa herbal powder','moringa vanriti wellness powder botanical powder','active',0,0,1,0,0,0.00,NULL,'2026-09-08 05:54:38',NULL),(48,'VANRITI Rose Petal Powder','vanriti-rose-petal-powder','VNRT048',NULL,'Pure Rose Petal powder for traditional face pack and skincare routines.','VANRITI Rose Petal Powder is a botanical powder suitable for traditional face packs and personal care routines.','100% Rose Petal Powder; Botanical ingredient; Skincare use','100% Rose Petal Powder','Supports a refreshing and traditional skincare routine.','Mix the required quantity with water or a suitable base to form a smooth paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes.','Patch test before first use. Store in a cool, dry place away from moisture.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,100,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Rose Petal Powder 200g','Rose Petal botanical powder for traditional face packs and skincare routines.','rose petal powder, rose powder, face pack powder, herbal skincare','rose petal vanriti face pack powder skincare','active',0,0,1,0,0,0.00,NULL,'2026-09-08 05:53:08',NULL),(49,'VANRITI Neem Powder','vanriti-neem-powder','VNRT049',NULL,'Pure Neem powder for traditional skincare and personal care routines.','VANRITI Neem Powder is a botanical powder suitable for traditional face packs and personal care routines.','100% Neem Powder; Botanical ingredient; Skincare use','100% Neem Powder','Supports a simple traditional skincare routine.','Mix the required quantity with water or a suitable base to form a paste. Apply to clean skin and rinse thoroughly.','Use as required.','For external use only. Avoid contact with eyes. Do not apply to broken or irritated skin.','Patch test before use. Stop use if irritation occurs. Store in a cool, dry place.','This product is intended for cosmetic and personal care use only. It is not intended to diagnose, treat, cure or prevent any disease.','200','gm',299.00,99.00,66.90,5.00,95,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Neem Powder 200g','Natural Neem powder for traditional skincare and personal care routines.','neem powder, neem face pack, herbal neem powder, skincare powder','neem vanriti face pack powder herbal skincare','active',1,0,1,0,0,0.00,NULL,'2026-09-09 01:53:56',NULL),(50,'VANRITI Beetroot Powder','vanriti-beetroot-powder','VNRT050',NULL,'Pure Beetroot powder for general wellness and dietary routines.','VANRITI Beetroot Powder is a botanical powder suitable for use as part of a general wellness and dietary routine.','100% Beetroot Powder; Botanical ingredient; Wellness use','100% Beetroot Powder','Provides a convenient botanical powder option for general wellness routines.','Use only as directed on the final approved product label. Mix the recommended quantity with a suitable food or beverage if applicable.','Consume only according to the final approved product label.','Keep out of reach of children. Store in a cool, dry place.','Consult a qualified healthcare professional before use if pregnant, breastfeeding, taking medication or having an existing medical condition.','This product is intended for general wellness and dietary use only. It is not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.','200','gm',299.00,99.00,66.90,5.00,90,5,NULL,'TFC Sales & Marketing','12 1st Floor Ramnik Complex-II Tikona Park NIT-1 Faridabad Haryana 121001 India','India','18 Months','See product packaging for batch number and expiry details.',NULL,'VANRITI Beetroot Powder 200g','Natural Beetroot powder for general wellness and dietary routines.','beetroot powder, beet root powder, beetroot wellness powder','beetroot vanriti wellness powder botanical powder','active',1,0,0,0,0,0.00,NULL,'2026-09-09 01:54:46',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refund_transactions`
--

DROP TABLE IF EXISTS `refund_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refund_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refund_id` bigint(20) unsigned NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('initiated','processing','success','failed') NOT NULL DEFAULT 'initiated',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refund_transactions_refund_id_foreign` (`refund_id`),
  CONSTRAINT `refund_transactions_refund_id_foreign` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refund_transactions`
--

LOCK TABLES `refund_transactions` WRITE;
/*!40000 ALTER TABLE `refund_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `refund_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refund_number` varchar(255) NOT NULL,
  `return_request_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('full','partial') NOT NULL DEFAULT 'full',
  `status` enum('requested','under_review','approved','processing','completed','rejected') NOT NULL DEFAULT 'requested',
  `method` varchar(255) DEFAULT NULL,
  `gateway_reference` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_refund_number_unique` (`refund_number`),
  KEY `refunds_return_request_id_foreign` (`return_request_id`),
  KEY `refunds_payment_id_foreign` (`payment_id`),
  KEY `refunds_user_id_foreign` (`user_id`),
  KEY `refunds_order_id_status_user_id_index` (`order_id`,`status`,`user_id`),
  CONSTRAINT `refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_return_request_id_foreign` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_items`
--

DROP TABLE IF EXISTS `return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_request_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `condition` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_items_return_request_id_foreign` (`return_request_id`),
  KEY `return_items_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `return_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_items_return_request_id_foreign` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_items`
--

LOCK TABLES `return_items` WRITE;
/*!40000 ALTER TABLE `return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_requests`
--

DROP TABLE IF EXISTS `return_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(255) NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('requested','under_review','approved','rejected','pickup_scheduled','picked_up','returned') NOT NULL DEFAULT 'requested',
  `admin_note` text DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_requests_return_number_unique` (`return_number`),
  KEY `return_requests_user_id_foreign` (`user_id`),
  KEY `return_requests_order_id_status_user_id_index` (`order_id`,`status`,`user_id`),
  CONSTRAINT `return_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_requests`
--

LOCK TABLES `return_requests` WRITE;
/*!40000 ALTER TABLE `return_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `return_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','hidden') NOT NULL DEFAULT 'pending',
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_unique` (`product_id`,`user_id`,`order_item_id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `reviews_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_user` (
  `role_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
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
INSERT INTO `sessions` VALUES ('1tXXRqRY3GKZQFLCnBJ46JZXSHJYmaI1Fa7LxQGW',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoibDhYZm9RMDlJbjJmTktBMll5M0NyZDJ2NTJXQ1Z2eERtWVE2aFQxUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly9sb2NhbGhvc3QvdmFucml0aSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MjoibG9naW5fYWRtaW5fNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1788942502);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'general','store_name','VANRITI','Store Name','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(2,'general','store_tagline','Natural Beauty & Wellness','Tagline','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(3,'general','support_email','support@vanriti.com','Customer Support Email','email','2026-09-08 08:25:54','2026-09-09 02:52:05'),(4,'general','store_email','info@vanriti.com','Store Email','email','2026-09-08 08:25:54','2026-09-09 02:52:05'),(5,'general','store_phone','+91 01293509181','Phone','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(6,'general','whatsapp_number','+91 7303971707','WhatsApp Number','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(7,'general','store_address','12 1st Floor Ramnik Complex Tikona Park NIT-1 Faridabad 121001','Business Address','textarea','2026-09-08 08:25:54','2026-09-09 02:52:05'),(8,'general','gst_number','06AXYPG2957R1ZD','GST Number','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(9,'general','facebook_url','https://facebook.com/vanriti','Facebook','url','2026-09-08 08:25:54','2026-09-09 02:52:05'),(10,'general','instagram_url','https://instagram.com/vanriti','Instagram','url','2026-09-08 08:25:54','2026-09-09 02:52:05'),(11,'general','twitter_url','https://twitter.com/vanriti','Twitter / X','url','2026-09-08 08:25:54','2026-09-09 02:52:05'),(12,'general','youtube_url','https://youtube.com/@vanriti','YouTube','url','2026-09-08 08:25:54','2026-09-09 02:52:05'),(13,'general','linkedin_url','https://linkedin.com/company/vanriti','LinkedIn','url','2026-09-08 08:25:54','2026-09-09 02:52:05'),(14,'shipping','shipping_charge','99','Standard Shipping Charge','number','2026-09-08 08:25:54','2026-09-09 02:52:05'),(15,'shipping','free_shipping_threshold','999','Free Shipping Above','number','2026-09-08 08:25:54','2026-09-09 02:52:05'),(16,'shipping','cod_available','1','Allow Cash on Delivery','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(17,'shipping','estimated_days','3-7','Estimated Delivery (days)','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(18,'shipping','allow_pincode_check','1','Enable Pincode Check','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(19,'tax','tax_type','inclusive','GST Display (inclusive/exclusive)','select','2026-09-08 08:25:54','2026-09-09 02:52:05'),(20,'tax','default_gst_rate','5','Default GST Rate %','number','2026-09-08 08:25:54','2026-09-09 02:52:05'),(21,'orders','min_order_amount','1','Minimum Order Amount','number','2026-09-08 08:25:54','2026-09-09 02:52:05'),(22,'orders','auto_confirm_orders','1','Auto-confirm COD orders','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(23,'returns','return_window_days','7','Return Window (days)','number','2026-09-08 08:25:54','2026-09-09 02:52:05'),(24,'returns','return_policy','Items can be returned within 7 days of delivery, provided they are unused and in original packaging.','Return Policy','textarea','2026-09-08 08:25:54','2026-09-09 02:52:05'),(25,'payment','cod_enabled','1','Cash on Delivery Enabled','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(26,'payment','online_payment_enabled','1','Online Payment Enabled','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(27,'payment','razorpay_enabled','1','Razorpay Enabled','boolean','2026-09-08 08:25:54','2026-09-09 02:52:05'),(28,'payment','razorpay_key_id','rzp_test_TZaoayCyHihaWV','Razorpay Key ID','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(29,'payment','razorpay_key_secret','bHV6fyZNNO6PlOEyZ0biHOdD','Razorpay Key Secret','password','2026-09-08 08:25:54','2026-09-09 02:52:05'),(30,'seo','meta_title','VANRITI - Natural Beauty & Wellness Products','Default Meta Title','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(31,'seo','meta_description','Discover premium natural beauty, personal care, herbal and wellness products crafted with care.','Default Meta Description','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(32,'seo','meta_keywords','vanriti, natural beauty, ayurvedic, herbal wellness, skincare, haircare','Default Meta Keywords','text','2026-09-08 08:25:54','2026-09-09 02:52:05'),(33,'shipmojo','shipmojo_enabled','1','Enable ShipMojo','boolean',NULL,'2026-09-09 02:52:05'),(34,'shipmojo','shipmojo_public_key','MfLRWj1pXSt0CyGseH27','ShipMojo Public Key','text',NULL,'2026-09-09 02:52:05'),(35,'shipmojo','shipmojo_private_key','BFG6tYU4LZQRly2PNxuh','ShipMojo Private Key','password',NULL,'2026-09-09 02:52:05'),(36,'shipmojo','shipmojo_warehouse_id','144680','Default Warehouse ID','text',NULL,'2026-09-09 02:52:05'),(37,'shipmojo','shipmojo_warehouse_pincode','121001','Warehouse Pincode (for rates)','text',NULL,'2026-09-09 02:52:05'),(38,'shipmojo','shipmojo_auto_push','1','Auto-Push Orders to ShipMojo','boolean',NULL,'2026-09-09 02:52:05'),(39,'shipmojo','shipmojo_auto_assign','1','Auto-Assign Courier After Push','boolean',NULL,'2026-09-09 02:52:05'),(40,'shipmojo','shipmojo_default_weight_grams','500','Default Weight (grams)','number',NULL,'2026-09-09 02:52:05'),(41,'shipmojo','shipmojo_default_length','20','Default Box Length (cm)','number',NULL,'2026-09-09 02:52:05'),(42,'shipmojo','shipmojo_default_width','15','Default Box Width (cm)','number',NULL,'2026-09-09 02:52:05'),(43,'shipmojo','shipmojo_default_height','10','Default Box Height (cm)','number',NULL,'2026-09-09 02:52:05');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment_tracking_events`
--

DROP TABLE IF EXISTS `shipment_tracking_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipment_tracking_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_tracking_events_shipment_id_status_index` (`shipment_id`,`status`),
  CONSTRAINT `shipment_tracking_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_tracking_events`
--

LOCK TABLES `shipment_tracking_events` WRITE;
/*!40000 ALTER TABLE `shipment_tracking_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipment_tracking_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `courier` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `awb_number` varchar(255) DEFAULT NULL,
  `shipmojo_order_id` varchar(255) DEFAULT NULL,
  `shipmojo_reference_id` varchar(255) DEFAULT NULL,
  `lr_number` varchar(255) DEFAULT NULL,
  `courier_service` varchar(255) DEFAULT NULL,
  `shipmojo_pushed_at` timestamp NULL DEFAULT NULL,
  `shipping_method` varchar(255) NOT NULL DEFAULT 'standard',
  `status` enum('pending','packed','shipped','out_for_delivery','delivered','returning','returned','failed') NOT NULL DEFAULT 'pending',
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `estimated_delivery` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `shipping_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipments_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test User','test@test.com',NULL,NULL,'active','2026-09-09 00:36:17',NULL,'$2y$12$VdXsOm/zeKKTidzjl2LQpeHG3qiA7ZtBP5JStc56e5Z1fMZb/Y5Cy',NULL,'2026-09-08 09:48:35','2026-09-09 00:36:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist_items`
--

DROP TABLE IF EXISTS `wishlist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wishlist_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_items_wishlist_id_product_id_unique` (`wishlist_id`,`product_id`),
  KEY `wishlist_items_product_id_foreign` (`product_id`),
  CONSTRAINT `wishlist_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_items_wishlist_id_foreign` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist_items`
--

LOCK TABLES `wishlist_items` WRITE;
/*!40000 ALTER TABLE `wishlist_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_type` varchar(255) NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_owner_unique` (`owner_type`,`owner_id`,`session_id`),
  KEY `wishlists_owner_type_owner_id_index` (`owner_type`,`owner_id`),
  KEY `wishlists_session_id_index` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES (1,'App\\Models\\User',1,NULL,'2026-09-08 12:37:56','2026-09-08 12:37:56');
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-09 13:59:40
