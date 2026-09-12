-- GST-inclusive invoice fields for VANRITI
-- Run in phpMyAdmin (prod: u878792290_vanriti) — replaces `php artisan migrate`
-- after the code is deployed. Safe to run on an empty or existing orders table.

ALTER TABLE orders
    ADD COLUMN taxable_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal,
    ADD COLUMN cgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tax_amount,
    ADD COLUMN sgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cgst_amount,
    ADD COLUMN igst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sgst_amount,
    ADD COLUMN coupon_code VARCHAR(255) NULL AFTER coupon_discount,
    ADD COLUMN coupon_type VARCHAR(255) NULL AFTER coupon_code,
    ADD COLUMN coupon_value DECIMAL(12,2) NULL AFTER coupon_type;

ALTER TABLE order_items
    ADD COLUMN taxable_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_price;

-- Business state for CGST/SGST (intra-state) vs IGST (inter-state) decision.
-- Matches GSTIN "06..." (Haryana, Faridabad warehouse).
INSERT INTO settings (`key`, `value`, `group`, `label`, `type`)
SELECT 'business_state', 'Haryana', 'tax', 'Business State (GST)', 'text'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'business_state');