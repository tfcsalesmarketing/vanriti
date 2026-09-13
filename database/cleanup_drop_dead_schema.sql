-- =====================================================================
-- VANRITI — DROP DEAD TABLE + DEAD COLUMNS (live `vanriti` DB)
-- Generated 2026-09-13 after a full DB-vs-migrations and code-usage audit.
-- Take a database backup before running. Run as root on 127.0.0.1:3306.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) DEAD TABLE: otp_codes
--    - its migrations were deleted (Fast2SMS removal)
--    - ZERO references anywhere in code
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `otp_codes`;

-- ---------------------------------------------------------------------
-- 2) DEAD / ORPHAN COLUMNS (never read or written by app code)
-- ---------------------------------------------------------------------

-- users.phone_verified_at: migration was deleted; zero code references
ALTER TABLE `users` DROP COLUMN `phone_verified_at`;

-- products: created by migration but no admin form, blade or service uses them
ALTER TABLE `products` DROP COLUMN `expiry_info`;
ALTER TABLE `products` DROP COLUMN `video_url`;

-- product_variants: legacy columns; variant images are stored via the
-- product_images polymorphic owner (ProductVariant), and no code reads
-- image/dimensions/barcode/hsn_code on the variant row itself
ALTER TABLE `product_variants` DROP COLUMN `image`;
ALTER TABLE `product_variants` DROP COLUMN `dimensions`;
ALTER TABLE `product_variants` DROP COLUMN `barcode`;
ALTER TABLE `product_variants` DROP COLUMN `hsn_code`;

-- return_requests.customer_note: never written by any controller
ALTER TABLE `return_requests` DROP COLUMN `customer_note`;

-- banners.subtitle: not even in Banner::$fillable and never rendered
ALTER TABLE `banners` DROP COLUMN `subtitle`;

-- ---------------------------------------------------------------------
-- 3) DRIFT CHECK — "add missing columns"
--    Verifies the DB still has every column the app code needs.
--    Currently returns ZERO rows (nothing missing → nothing to add).
-- ---------------------------------------------------------------------
SELECT
    t.table_name,
    t.column_name,
    'MISSING COLUMN - add it' AS status
FROM (
    -- key columns the app code depends on (recent/less obvious ones)
    SELECT 'users'               AS table_name, 'email'             AS column_name
UNION ALL SELECT 'users',               'phone'
UNION ALL SELECT 'users',               'avatar'
UNION ALL SELECT 'users',               'status'
UNION ALL SELECT 'users',               'last_login_at'
UNION ALL SELECT 'payments',            'failed_at'
UNION ALL SELECT 'orders',              'taxable_amount'
UNION ALL SELECT 'orders',              'cgst_amount'
UNION ALL SELECT 'orders',              'sgst_amount'
UNION ALL SELECT 'orders',              'igst_amount'
UNION ALL SELECT 'orders',              'coupon_code'
UNION ALL SELECT 'orders',              'coupon_type'
UNION ALL SELECT 'orders',              'coupon_value'
UNION ALL SELECT 'order_items',         'taxable_amount'
UNION ALL SELECT 'shipments',           'awb_number'
UNION ALL SELECT 'shipments',           'shipmojo_order_id'
UNION ALL SELECT 'shipments',           'shipmojo_reference_id'
UNION ALL SELECT 'shipments',           'lr_number'
UNION ALL SELECT 'shipments',           'courier_service'
UNION ALL SELECT 'shipments',           'shipmojo_pushed_at'
UNION ALL SELECT 'analytics_conversions', 'meta_state'
UNION ALL SELECT 'analytics_conversions', 'meta_sent_at'
UNION ALL SELECT 'analytics_conversions', 'meta_attempts'
) t
LEFT JOIN information_schema.COLUMNS c
    ON c.TABLE_SCHEMA = 'vanriti'
   AND c.TABLE_NAME   = t.table_name
   AND c.COLUMN_NAME  = t.column_name
WHERE c.COLUMN_NAME IS NULL;