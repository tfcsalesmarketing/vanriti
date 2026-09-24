-- Meta Pixel ID setting for VANRITI (Facebook advertising)
-- Run in phpMyAdmin (prod: u878792290_vanriti) after the code is deployed.
-- Safe to re-run; only creates the row if it does not already exist.
-- Once the row exists, paste the Pixel ID in Admin -> Settings -> SEO -> Meta Pixel ID.

INSERT INTO settings (`key`, `value`, `group`, `label`, `type`)
SELECT 'meta_pixel_id', '', 'seo', 'Meta Pixel ID', 'text'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'meta_pixel_id');