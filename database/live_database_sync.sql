-- ============================================================================
-- COMPLETE LIVE DATABASE SYNCHRONIZATION SCRIPT FOR VANRITI & TFC SALES
-- Run this script in phpMyAdmin, MySQL Workbench, or MySQL CLI on your live DB.
-- SAFE & IDEMPOTENT: Will not overwrite existing data or fail on duplicate columns.
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------------------------------------------------------
-- 1. TEMPORARY HELPER PROCEDURES FOR SAFE IDEMPOTENT SCHEMA UPDATES
-- ----------------------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS `AddColumnIfMissing`$$
CREATE PROCEDURE `AddColumnIfMissing`(
    IN tbl_name VARCHAR(64),
    IN col_name VARCHAR(64),
    IN col_def TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tbl_name
        AND COLUMN_NAME = col_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl_name, '` ADD COLUMN `', col_name, '` ', col_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
DROP PROCEDURE IF EXISTS `AddIndexIfMissing`$$
CREATE PROCEDURE `AddIndexIfMissing`(
    IN tbl_name VARCHAR(64),
    IN idx_name VARCHAR(64),
    IN idx_cols TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tbl_name
        AND INDEX_NAME = idx_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl_name, '` ADD INDEX `', idx_name, '` (', idx_cols, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- ----------------------------------------------------------------------------
-- 2. UPDATE EXISTING CORE TABLES (USERS, ORDERS, CARTS, SHIPMENTS, PRODUCTS)
-- ----------------------------------------------------------------------------
CALL AddColumnIfMissing('users', 'phone', "VARCHAR(20) NULL AFTER `email`");
CALL AddColumnIfMissing('users', 'phone_verified_at', "TIMESTAMP NULL AFTER `phone`");
CALL AddColumnIfMissing('users', 'avatar', "VARCHAR(255) NULL AFTER `phone_verified_at`");
CALL AddColumnIfMissing('users', 'status', "ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER `avatar`");
CALL AddColumnIfMissing('users', 'last_login_at', "TIMESTAMP NULL AFTER `status`");
CALL AddIndexIfMissing('users', 'idx_users_phone', '`phone`');

CALL AddColumnIfMissing('orders', 'shipping_method', "VARCHAR(50) NULL DEFAULT 'standard'");
CALL AddColumnIfMissing('orders', 'tracking_url', "VARCHAR(1000) NULL AFTER `shipping_method`");
CALL AddColumnIfMissing('orders', 'invoice_number', "VARCHAR(50) NULL AFTER `order_number`");
CALL AddColumnIfMissing('orders', 'invoice_generated_at', "TIMESTAMP NULL AFTER `invoice_number`");
CALL AddColumnIfMissing('orders', 'whatsapp_confirmation_sent_at', "TIMESTAMP NULL");
CALL AddColumnIfMissing('orders', 'whatsapp_recovery_sent_at', "TIMESTAMP NULL");
CALL AddColumnIfMissing('orders', 'whatsapp_shipped_sent_at', "TIMESTAMP NULL");
CALL AddColumnIfMissing('orders', 'whatsapp_delivered_sent_at', "TIMESTAMP NULL");
CALL AddIndexIfMissing('orders', 'idx_orders_invoice', '`invoice_number`');

CALL AddColumnIfMissing('carts', 'whatsapp_recovery_sent_at', "TIMESTAMP NULL");

CALL AddColumnIfMissing('shipments', 'tracking_url', "VARCHAR(1000) NULL AFTER `awb_number`");

CALL AddColumnIfMissing('products', 'weight', "DECIMAL(8,2) NULL DEFAULT 100.00");
ALTER TABLE `product_images` MODIFY COLUMN `type` VARCHAR(30) NOT NULL DEFAULT 'gallery';

-- ----------------------------------------------------------------------------
-- 3. CREATE FULL SCHEMA TABLES IF NOT PRESENT
-- ----------------------------------------------------------------------------
-- Table: otp_codes
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `purpose` varchar(40) NOT NULL DEFAULT 'login',
  `channel` enum('whatsapp','email','sms') NOT NULL DEFAULT 'whatsapp',
  `hashed_code` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otp_codes_phone_purpose_index` (`phone`,`purpose`),
  KEY `otp_codes_expires_at_index` (`expires_at`),
  KEY `otp_codes_email_purpose_index` (`email`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: stock_notifications
CREATE TABLE IF NOT EXISTS `stock_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('pending','notified','cancelled') NOT NULL DEFAULT 'pending',
  `channel` varchar(20) NOT NULL DEFAULT 'whatsapp',
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_notifications_product_id_status_index` (`product_id`,`status`),
  KEY `stock_notifications_product_variant_id_status_index` (`product_variant_id`,`status`),
  KEY `stock_notifications_phone_status_index` (`phone`,`status`),
  KEY `stock_notifications_product_variant_id_foreign` (`product_variant_id`),
  KEY `stock_notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_notifications_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_notifications_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_member_id_sequences
CREATE TABLE IF NOT EXISTS `tfc_member_id_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prefix` varchar(10) NOT NULL DEFAULT 'TFC',
  `last_number` bigint(20) unsigned NOT NULL DEFAULT 10000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tfc_member_id_sequences_prefix_unique` (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tfc_member_id_sequences` (`id`, `prefix`, `last_number`, `created_at`, `updated_at`)
VALUES (1, 'TFC', 10000, NOW(), NOW());

-- Table: direct_selling_members
CREATE TABLE IF NOT EXISTS `direct_selling_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `member_id` varchar(32) NOT NULL,
  `sponsor_id` bigint(20) unsigned DEFAULT NULL,
  `placement_parent_id` bigint(20) unsigned DEFAULT NULL,
  `placement_position` enum('orange','blue') DEFAULT NULL,
  `registration_date` date NOT NULL,
  `membership_status` enum('pending','active','suspended','terminated') NOT NULL DEFAULT 'active',
  `activation_status` enum('inactive','active') NOT NULL DEFAULT 'inactive',
  `kyc_status` enum('not_submitted','submitted','under_review','approved','rejected','resubmission_required') NOT NULL DEFAULT 'not_submitted',
  `mobile_verification_status` enum('unverified','verified') NOT NULL DEFAULT 'unverified',
  `registered_ip` varchar(45) DEFAULT NULL,
  `first_login_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `direct_selling_members_user_id_unique` (`user_id`),
  UNIQUE KEY `direct_selling_members_member_id_unique` (`member_id`),
  UNIQUE KEY `uq_tfc_placement_slot` (`placement_parent_id`,`placement_position`),
  KEY `direct_selling_members_sponsor_id_index` (`sponsor_id`),
  KEY `direct_selling_members_placement_parent_id_index` (`placement_parent_id`),
  KEY `direct_selling_members_placement_position_index` (`placement_position`),
  KEY `direct_selling_members_registration_date_index` (`registration_date`),
  KEY `direct_selling_members_membership_status_index` (`membership_status`),
  KEY `direct_selling_members_activation_status_index` (`activation_status`),
  KEY `direct_selling_members_kyc_status_index` (`kyc_status`),
  KEY `direct_selling_members_mobile_verification_status_index` (`mobile_verification_status`),
  CONSTRAINT `direct_selling_members_placement_parent_id_foreign` FOREIGN KEY (`placement_parent_id`) REFERENCES `direct_selling_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `direct_selling_members_sponsor_id_foreign` FOREIGN KEY (`sponsor_id`) REFERENCES `direct_selling_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `direct_selling_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: member_tree_nodes
CREATE TABLE IF NOT EXISTS `member_tree_nodes` (
  `ancestor_id` bigint(20) unsigned NOT NULL,
  `descendant_id` bigint(20) unsigned NOT NULL,
  `depth` int(10) unsigned NOT NULL,
  `group_leg` enum('orange','blue','self') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ancestor_id`,`descendant_id`),
  KEY `member_tree_nodes_group_leg_index` (`group_leg`),
  KEY `idx_ancestor_leg_depth` (`ancestor_id`,`group_leg`,`depth`),
  KEY `idx_descendant_depth` (`descendant_id`,`depth`),
  CONSTRAINT `member_tree_nodes_ancestor_id_foreign` FOREIGN KEY (`ancestor_id`) REFERENCES `direct_selling_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_tree_nodes_descendant_id_foreign` FOREIGN KEY (`descendant_id`) REFERENCES `direct_selling_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: member_kyc
CREATE TABLE IF NOT EXISTS `member_kyc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `direct_selling_member_id` bigint(20) unsigned NOT NULL,
  `document_type` enum('pan','aadhaar','voter_id','passport') NOT NULL DEFAULT 'pan',
  `document_number` text NOT NULL,
  `document_number_last4` varchar(4) DEFAULT NULL,
  `front_image_path` varchar(500) NOT NULL,
  `back_image_path` varchar(500) DEFAULT NULL,
  `pan_number` text DEFAULT NULL,
  `pan_number_last4` varchar(4) DEFAULT NULL,
  `pan_image_path` varchar(500) DEFAULT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `account_number` text DEFAULT NULL,
  `account_number_last4` varchar(4) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(150) DEFAULT NULL,
  `status` enum('submitted','under_review','approved','rejected','resubmission_required') NOT NULL DEFAULT 'submitted',
  `rejection_reason` text DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_kyc_direct_selling_member_id_index` (`direct_selling_member_id`),
  KEY `member_kyc_document_number_last4_index` (`document_number_last4`),
  KEY `member_kyc_status_index` (`status`),
  KEY `member_kyc_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `member_kyc_direct_selling_member_id_foreign` FOREIGN KEY (`direct_selling_member_id`) REFERENCES `direct_selling_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_kyc_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_settings
CREATE TABLE IF NOT EXISTS `tfc_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tfc_settings_key_unique` (`key`),
  KEY `tfc_settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_pages
CREATE TABLE IF NOT EXISTS `tfc_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tfc_pages_slug_unique` (`slug`),
  KEY `tfc_pages_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_menus
CREATE TABLE IF NOT EXISTS `tfc_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `location` enum('header','footer_col1','footer_col2','footer_legal') NOT NULL DEFAULT 'header',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `open_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tfc_menus_location_index` (`location`),
  KEY `tfc_menus_parent_id_foreign` (`parent_id`),
  CONSTRAINT `tfc_menus_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `tfc_menus` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_banners
CREATE TABLE IF NOT EXISTS `tfc_banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `mobile_image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `link_text` varchar(255) DEFAULT NULL,
  `position` enum('hero','opportunity','cta') NOT NULL DEFAULT 'hero',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tfc_banners_position_index` (`position`),
  KEY `tfc_banners_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_blogs
CREATE TABLE IF NOT EXISTS `tfc_blogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tfc_blogs_slug_unique` (`slug`),
  KEY `tfc_blogs_status_index` (`status`),
  KEY `tfc_blogs_published_at_index` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_inquiries
CREATE TABLE IF NOT EXISTS `tfc_inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('general','seller_application') NOT NULL DEFAULT 'general',
  `status` enum('new','in_progress','resolved','closed') NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tfc_inquiries_type_index` (`type`),
  KEY `tfc_inquiries_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tfc_grievance_tickets
CREATE TABLE IF NOT EXISTS `tfc_grievance_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `member_id` varchar(50) DEFAULT NULL,
  `category` enum('order','commission','product','code_of_ethics','other') NOT NULL DEFAULT 'other',
  `description` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('open','under_investigation','redressed','rejected') NOT NULL DEFAULT 'open',
  `redressal_remarks` text DEFAULT NULL,
  `redressed_by` varchar(255) DEFAULT NULL,
  `redressed_at` timestamp NULL DEFAULT NULL,
  `sla_deadline` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tfc_grievance_tickets_ticket_number_unique` (`ticket_number`),
  KEY `tfc_grievance_tickets_category_index` (`category`),
  KEY `tfc_grievance_tickets_status_index` (`status`),
  KEY `tfc_grievance_tickets_sla_deadline_index` (`sla_deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_templates
CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meta_template_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('AUTHENTICATION','MARKETING','UTILITY') NOT NULL,
  `channel` varchar(30) DEFAULT NULL,
  `status` enum('APPROVED','PENDING','REJECTED','PAUSED','DISABLED') NOT NULL DEFAULT 'PENDING',
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `header_type` enum('TEXT','IMAGE','DOCUMENT','VIDEO','LOCATION') DEFAULT NULL,
  `header_text` text DEFAULT NULL,
  `body_text` text NOT NULL,
  `footer_text` text DEFAULT NULL,
  `buttons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`buttons`)),
  `rejected_reason` text DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_templates_meta_template_id_index` (`meta_template_id`),
  KEY `whatsapp_templates_status_index` (`status`),
  KEY `whatsapp_templates_category_index` (`category`),
  KEY `whatsapp_templates_channel_index` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_conversations
CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tfc_inquiry_id` bigint(20) unsigned DEFAULT NULL,
  `tfc_grievance_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','resolved','archived','spam') NOT NULL DEFAULT 'active',
  `channel` varchar(30) NOT NULL DEFAULT 'vanriti',
  `unread_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `last_message_preview` text DEFAULT NULL,
  `bot_paused_until` timestamp NULL DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_conversations_user_id_index` (`user_id`),
  KEY `whatsapp_conversations_assigned_admin_id_index` (`assigned_admin_id`),
  KEY `whatsapp_conversations_status_index` (`status`),
  KEY `whatsapp_conversations_last_message_at_index` (`last_message_at`),
  KEY `whatsapp_conversations_channel_index` (`channel`),
  KEY `whatsapp_conversations_tfc_inquiry_id_foreign` (`tfc_inquiry_id`),
  KEY `whatsapp_conversations_tfc_grievance_id_foreign` (`tfc_grievance_id`),
  CONSTRAINT `whatsapp_conversations_assigned_admin_id_foreign` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_conversations_tfc_grievance_id_foreign` FOREIGN KEY (`tfc_grievance_id`) REFERENCES `tfc_grievance_tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_conversations_tfc_inquiry_id_foreign` FOREIGN KEY (`tfc_inquiry_id`) REFERENCES `tfc_inquiries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_messages
CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `whatsapp_conversation_id` bigint(20) unsigned NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `message_type` enum('text','image','document','audio','video','interactive','template','location','contacts','sticker') NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `media_url` text DEFAULT NULL,
  `media_mime_type` varchar(100) DEFAULT NULL,
  `meta_message_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','delivered','read','failed') NOT NULL DEFAULT 'pending',
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `sent_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_messages_whatsapp_conversation_id_index` (`whatsapp_conversation_id`),
  KEY `whatsapp_messages_direction_index` (`direction`),
  KEY `whatsapp_messages_meta_message_id_index` (`meta_message_id`),
  KEY `whatsapp_messages_status_index` (`status`),
  KEY `whatsapp_messages_sent_at_index` (`sent_at`),
  KEY `whatsapp_messages_sent_by_admin_id_foreign` (`sent_by_admin_id`),
  CONSTRAINT `whatsapp_messages_sent_by_admin_id_foreign` FOREIGN KEY (`sent_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_messages_whatsapp_conversation_id_foreign` FOREIGN KEY (`whatsapp_conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_bot_rules
CREATE TABLE IF NOT EXISTS `whatsapp_bot_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `trigger_type` enum('exact','contains','starts_with','regex','any') NOT NULL DEFAULT 'contains',
  `trigger_keywords` text NOT NULL,
  `action_type` enum('reply_text','reply_template','reply_interactive','assign_agent','close_chat') NOT NULL DEFAULT 'reply_text',
  `channel` varchar(30) NOT NULL DEFAULT 'vanriti',
  `reply_text` text DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `interactive_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interactive_payload`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_bot_rules_is_active_priority_index` (`is_active`,`priority`),
  KEY `whatsapp_bot_rules_channel_index` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_campaigns
CREATE TABLE IF NOT EXISTS `whatsapp_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `audience_type` enum('all','purchased','abandoned_cart','custom','inactive') NOT NULL DEFAULT 'all',
  `audience_filter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audience_filter`)),
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `status` enum('draft','scheduled','processing','completed','failed','cancelled') NOT NULL DEFAULT 'draft',
  `channel` varchar(30) NOT NULL DEFAULT 'vanriti',
  `total_recipients` int(10) unsigned NOT NULL DEFAULT 0,
  `sent_count` int(10) unsigned NOT NULL DEFAULT 0,
  `delivered_count` int(10) unsigned NOT NULL DEFAULT 0,
  `read_count` int(10) unsigned NOT NULL DEFAULT 0,
  `failed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_campaigns_template_id_index` (`template_id`),
  KEY `whatsapp_campaigns_status_index` (`status`),
  KEY `whatsapp_campaigns_scheduled_at_index` (`scheduled_at`),
  KEY `whatsapp_campaigns_channel_index` (`channel`),
  KEY `whatsapp_campaigns_created_by_foreign` (`created_by`),
  CONSTRAINT `whatsapp_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_campaigns_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `whatsapp_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_campaign_recipients
CREATE TABLE IF NOT EXISTS `whatsapp_campaign_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','delivered','read','failed') NOT NULL DEFAULT 'pending',
  `meta_message_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_campaign_recipients_campaign_id_status_index` (`campaign_id`,`status`),
  KEY `whatsapp_campaign_recipients_phone_index` (`phone`),
  KEY `whatsapp_campaign_recipients_meta_message_id_index` (`meta_message_id`),
  KEY `whatsapp_campaign_recipients_user_id_foreign` (`user_id`),
  CONSTRAINT `whatsapp_campaign_recipients_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `whatsapp_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_campaign_recipients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_checkout_sessions
CREATE TABLE IF NOT EXISTS `whatsapp_checkout_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `cart_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `session_token` varchar(64) NOT NULL,
  `status` enum('initiated','items_selected','address_provided','payment_pending','completed','expired') NOT NULL DEFAULT 'initiated',
  `customer_name` varchar(255) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_interaction_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_checkout_sessions_session_token_unique` (`session_token`),
  KEY `whatsapp_checkout_sessions_phone_status_index` (`phone`,`status`),
  KEY `whatsapp_checkout_sessions_cart_id_foreign` (`cart_id`),
  KEY `whatsapp_checkout_sessions_order_id_foreign` (`order_id`),
  CONSTRAINT `whatsapp_checkout_sessions_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_checkout_sessions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_quick_replies
CREATE TABLE IF NOT EXISTS `whatsapp_quick_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `shortcut` varchar(50) NOT NULL,
  `body` text NOT NULL,
  `channel` varchar(30) NOT NULL DEFAULT 'vanriti',
  `category` varchar(50) DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_quick_replies_shortcut_unique` (`shortcut`),
  KEY `whatsapp_quick_replies_channel_index` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: whatsapp_webhook_logs
CREATE TABLE IF NOT EXISTS `whatsapp_webhook_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_webhook_logs_event_type_index` (`event_type`),
  KEY `whatsapp_webhook_logs_phone_index` (`phone`),
  KEY `whatsapp_webhook_logs_processed_index` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. MULTI-CHANNEL LINKAGES FOR WHATSAPP INBOX & AUTOMATION
-- ----------------------------------------------------------------------------
CALL AddColumnIfMissing('whatsapp_conversations', 'channel', "VARCHAR(30) DEFAULT 'vanriti' AFTER `status`");
CALL AddColumnIfMissing('whatsapp_conversations', 'tfc_inquiry_id', "BIGINT UNSIGNED NULL AFTER `user_id`");
CALL AddColumnIfMissing('whatsapp_conversations', 'tfc_grievance_id', "BIGINT UNSIGNED NULL AFTER `tfc_inquiry_id`");
CALL AddColumnIfMissing('whatsapp_campaigns', 'channel', "VARCHAR(30) DEFAULT 'vanriti' AFTER `status`");
CALL AddColumnIfMissing('whatsapp_bot_rules', 'channel', "VARCHAR(30) DEFAULT 'vanriti' AFTER `action_type`");
CALL AddColumnIfMissing('whatsapp_quick_replies', 'channel', "VARCHAR(30) DEFAULT 'vanriti' AFTER `category`");
CALL AddColumnIfMissing('whatsapp_templates', 'channel', "VARCHAR(30) NULL AFTER `category`");

-- ----------------------------------------------------------------------------
-- 5. CLEANUP TEMPORARY PROCEDURES & RESTORE CHECKS
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `AddColumnIfMissing`;
DROP PROCEDURE IF EXISTS `AddIndexIfMissing`;
SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- SYNCHRONIZATION SCRIPT FINISHED SUCCESSFULLY
-- ============================================================================
