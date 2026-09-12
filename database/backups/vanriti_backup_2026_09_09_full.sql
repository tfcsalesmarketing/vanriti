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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_histories`
--

LOCK TABLES `order_status_histories` WRITE;
/*!40000 ALTER TABLE `order_status_histories` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
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

-- Dump completed on 2026-09-09 14:14:35
