-- =====================================================================
-- VANRITI — TRUNCATE ALL TABLES EXCEPT ADMIN/ROLE, PAGES & SETTINGS
-- Generated 2026-09-13.
--
-- KEPT (NOT truncated):  admins, admin_role, roles, permissions,
--                         permission_role, role_user, pages, settings
-- NOT truncated (bookkeeping): migrations
-- otp_codes is NOT listed — run cleanup_drop_dead_schema.sql first
-- (it drops that table).
--
-- TRUNCATE resets AUTO_INCREMENT to zero on every table.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `addresses`;
TRUNCATE TABLE `analytics_conversions`;
TRUNCATE TABLE `banners`;
TRUNCATE TABLE `blogs`;
TRUNCATE TABLE `blog_categories`;
TRUNCATE TABLE `cache`;
TRUNCATE TABLE `cache_locks`;
TRUNCATE TABLE `carts`;
TRUNCATE TABLE `cart_items`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `contact_messages`;
TRUNCATE TABLE `coupons`;
TRUNCATE TABLE `coupon_categories`;
TRUNCATE TABLE `coupon_products`;
TRUNCATE TABLE `coupon_usages`;
TRUNCATE TABLE `dadi_conversations`;
TRUNCATE TABLE `dadi_messages`;
TRUNCATE TABLE `dadi_product_profiles`;
TRUNCATE TABLE `dadi_recommendation_events`;
TRUNCATE TABLE `failed_jobs`;
TRUNCATE TABLE `faqs`;
TRUNCATE TABLE `inventories`;
TRUNCATE TABLE `inventory_transactions`;
TRUNCATE TABLE `jobs`;
TRUNCATE TABLE `job_batches`;
TRUNCATE TABLE `media`;
TRUNCATE TABLE `newsletters`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `order_status_histories`;
TRUNCATE TABLE `password_reset_tokens`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `products`;
TRUNCATE TABLE `product_category`;
TRUNCATE TABLE `product_images`;
TRUNCATE TABLE `product_variants`;
TRUNCATE TABLE `refunds`;
TRUNCATE TABLE `refund_transactions`;
TRUNCATE TABLE `return_items`;
TRUNCATE TABLE `return_requests`;
TRUNCATE TABLE `reviews`;
TRUNCATE TABLE `sessions`;
TRUNCATE TABLE `shipments`;
TRUNCATE TABLE `shipment_tracking_events`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `wishlists`;
TRUNCATE TABLE `wishlist_items`;

SET FOREIGN_KEY_CHECKS = 1;