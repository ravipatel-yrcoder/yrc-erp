DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity_type` enum('lead','customer','sales_order') NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `activity_type` enum('call','email','meeting','todo') NOT NULL,
  `summary` varchar(255) NOT NULL,
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `description` text,
  `status` enum('pending','in_progress','completed','cancelled','skipped') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `outcome` text,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_assigned_due` (`company_id`,`assigned_to`,`status`,`due_date`),
  KEY `idx_company_due` (`company_id`,`due_date`),
  CONSTRAINT `fk_activities_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Migration: run these ALTER statements on an existing activities table
-- ALTER TABLE activities RENAME COLUMN `related_type` TO `entity_type`;
-- ALTER TABLE activities RENAME COLUMN `related_id`   TO `entity_id`;
-- ALTER TABLE activities RENAME COLUMN `type`         TO `activity_type`;
-- ALTER TABLE activities RENAME COLUMN `note`         TO `description`;
-- ALTER TABLE activities RENAME COLUMN `done_at`      TO `completed_at`;
-- ALTER TABLE activities ADD COLUMN `status` enum('pending','in_progress','completed','cancelled','skipped') NOT NULL DEFAULT 'pending' AFTER `description`;
-- ALTER TABLE activities ADD COLUMN `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium' AFTER `status`;
-- ALTER TABLE activities ADD COLUMN `started_at` datetime DEFAULT NULL;
-- ALTER TABLE activities ADD COLUMN `completed_by` bigint unsigned DEFAULT NULL;
-- ALTER TABLE activities ADD CONSTRAINT `fk_activities_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
-- UPDATE activities SET status = 'completed' WHERE is_done = 1;
-- UPDATE activities SET status = 'pending'   WHERE is_done = 0;
-- ALTER TABLE activities DROP COLUMN `is_done`;
-- DROP INDEX `idx_related` ON activities;
-- DROP INDEX `idx_assigned_due` ON activities;
-- CREATE INDEX `idx_entity` ON activities (`entity_type`, `entity_id`);
-- CREATE INDEX `idx_assigned_due` ON activities (`company_id`, `assigned_to`, `status`, `due_date`);


DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity` enum('activity','crm_lead_history') NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int unsigned NOT NULL DEFAULT '0',
  `mime_type` varchar(100) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity`,`entity_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `auth_tokens`;
CREATE TABLE `auth_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_type` enum('access','refresh') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) DEFAULT '0',
  `device_info` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash_token_type` (`token_hash`,`token_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `contact_email` varchar(191) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `plan` enum('free','basic','pro','enterprise') DEFAULT 'free',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `timezone` varchar(50) DEFAULT 'UTC',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'INR',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `company_locations`;
CREATE TABLE `company_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` enum('head_office','branch','warehouse','store','factory','workshop','customer_site','vendor_site','virtual') NOT NULL DEFAULT 'head_office',
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `is_main` enum('0','1') NOT NULL DEFAULT '1',
  `status` enum('active','inactive','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `crm_lead_history`;
CREATE TABLE `crm_lead_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `log_type` enum('created','note','stage_change','status_updated','activity_done','system','updated_notes','updated_details','assigned_changed','converted_to_customer','linked_to_customer','quotation_created','quotation_confirmed','quotation_cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_event` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `crm_leads`;
CREATE TABLE `crm_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lead_code` varchar(50) NOT NULL,
  `stage_id` bigint unsigned DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` enum('active','won','lost') NOT NULL DEFAULT 'active',
  `probability` tinyint unsigned NOT NULL DEFAULT '10',
  `salutation` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) NOT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(10) DEFAULT 'IN',
  `expected_revenue` decimal(15,2) DEFAULT NULL,
  `expected_close_date` date DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `lead_type` varchar(50) DEFAULT NULL,
  `product_interest` json DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `tags` json DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `lost_reason` varchar(255) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead_code` (`company_id`,`lead_code`),
  KEY `idx_stage` (`company_id`,`stage_id`),
  KEY `idx_status` (`company_id`,`status`),
  KEY `idx_assigned` (`company_id`,`assigned_to`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `idx_company_stage_sort` (`company_id`,`stage_id`,`sort_order`),
  KEY `idx_external_id` (`company_id`,`source`,`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `crm_stages`;
CREATE TABLE `crm_stages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `probability` tinyint unsigned NOT NULL DEFAULT '0',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_won` tinyint(1) NOT NULL DEFAULT '0',
  `is_lost` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_sort` (`company_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `customer_addresses`;
CREATE TABLE `customer_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `address_type` enum('billing','shipping','other') NOT NULL DEFAULT 'billing',
  `label` varchar(100) DEFAULT NULL,
  `attention` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(10) NOT NULL DEFAULT 'IN',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ca_company_customer` (`company_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `customer_contacts`;
CREATE TABLE `customer_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `title` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cc_company_customer` (`company_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `customer_groups`;
CREATE TABLE `customer_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cg_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_group_id` bigint unsigned DEFAULT NULL,
  `customer_type` enum('company','individual') NOT NULL DEFAULT 'company',
  `salutation` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `display_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `currency_code` varchar(10) NOT NULL DEFAULT 'INR',
  `payment_term_id` bigint unsigned DEFAULT NULL,
  `credit_limit` decimal(15,4) DEFAULT NULL,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customers_company` (`company_id`),
  KEY `idx_customers_group` (`customer_group_id`),
  KEY `idx_customers_price_list` (`price_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_adjustments`;
CREATE TABLE `inv_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `adjustment_type` enum('initial','increase','decrease') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_location` (`location_id`),
  KEY `idx_type` (`adjustment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_lot_history`;
CREATE TABLE `inv_lot_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `lot_id` bigint unsigned NOT NULL,
  `changed_field` varchar(100) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `changed_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_lot` (`lot_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_field` (`changed_field`),
  KEY `idx_company_product_lot` (`company_id`,`product_id`,`lot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_lot_stock`;
CREATE TABLE `inv_lot_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `lot_id` bigint unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reserved_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `picked_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`location_id`,`product_id`,`lot_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_lot_id` (`lot_id`),
  KEY `idx_location_product` (`location_id`,`product_id`),
  KEY `idx_lot_location` (`lot_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_lots`;
CREATE TABLE `inv_lots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `lot_number` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive','archived','quarantine','on_hold','rejected','expired','consumed','scrapped') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`product_id`,`lot_number`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_mfg_date` (`manufacture_date`),
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_product_stock`;
CREATE TABLE `inv_product_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `on_hand_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reserved_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id_location_id_product_id` (`company_id`,`location_id`,`product_id`),
  KEY `fk_inventory_stock_location` (`location_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_inventory_stock_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_stock_location` FOREIGN KEY (`location_id`) REFERENCES `company_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_sequence_patterns`;
CREATE TABLE `inv_sequence_patterns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL COMMENT 'NULL for Global Pattern',
  `name` varchar(255) DEFAULT NULL,
  `pattern` varchar(100) NOT NULL,
  `last_number` bigint NOT NULL DEFAULT '0',
  `reset_period` enum('none','monthly','yearly') NOT NULL DEFAULT 'none',
  `sequence_type` enum('lot','serial','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'both',
  `padding` int NOT NULL DEFAULT '6',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_serial_history`;
CREATE TABLE `inv_serial_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `changed_field` varchar(100) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `changed_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_serial` (`serial_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_field` (`changed_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_serial_stock`;
CREATE TABLE `inv_serial_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `reserved_for_document` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`serial_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_serial_id` (`serial_id`),
  KEY `idx_location_product` (`location_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_serials`;
CREATE TABLE `inv_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `serial_number` varchar(150) NOT NULL,
  `lot_id` bigint unsigned DEFAULT NULL,
  `status` enum('in_stock','reserved','picked','in_transit','sold','returned','quarantine','on_hold','repair','lost','scrapped','consumed') NOT NULL DEFAULT 'in_stock',
  `qc_status` enum('pending','pass','fail') DEFAULT NULL,
  `warranty_start` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`serial_number`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_lot_id` (`lot_id`),
  KEY `idx_status` (`status`),
  KEY `idx_warranty` (`warranty_end`),
  KEY `idx_serial_number` (`company_id`,`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `inv_stock_movements`;
CREATE TABLE `inv_stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `movement_type` enum('adjust_in','adjust_out','transfer_in','transfer_out','purchase_receipt','sale','return_from_customer','return_to_supplier','consume','produce','scrap') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `old_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `qty_change` decimal(15,2) NOT NULL DEFAULT '0.00',
  `new_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `payment_terms`;
CREATE TABLE `payment_terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `days` int unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_name` (`company_id`,`name`),
  KEY `idx_company` (`company_id`),
  KEY `idx_days` (`days`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `price_list_items`;
CREATE TABLE `price_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `price_list_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `unit_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `min_qty` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pli_list_product` (`price_list_id`,`product_id`),
  KEY `idx_pli_company_list` (`company_id`,`price_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `price_lists`;
CREATE TABLE `price_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `currency_code` varchar(10) NOT NULL DEFAULT 'INR',
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pl_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `parent_id` bigint DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text,
  `status` enum('active','inactive','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `product_masters`;
CREATE TABLE `product_masters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` bigint DEFAULT NULL,
  `type` enum('goods','service','combo') NOT NULL DEFAULT 'goods',
  `structure_type` enum('simple','variable') NOT NULL DEFAULT 'simple',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `status` enum('active','inactive','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `product_masters_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `product_taxes`;
CREATE TABLE `product_taxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `tax_id` bigint unsigned NOT NULL,
  `apply_on` enum('sale','purchase') NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_tax` (`company_id`,`product_id`,`tax_id`,`apply_on`),
  KEY `fk_product_tax_product` (`product_id`),
  KEY `fk_product_tax_tax` (`tax_id`),
  KEY `fk_product_tax_created_by` (`created_by`),
  KEY `idx_product_tax_company_product` (`company_id`,`product_id`),
  KEY `idx_product_tax_company_tax` (`company_id`,`tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `product_uoms`;
CREATE TABLE `product_uoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_uom_id` bigint unsigned NOT NULL,
  `conversion_factor` decimal(18,4) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_base` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `master_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text,
  `base_uom_id` bigint unsigned NOT NULL,
  `cost_price` decimal(15,4) DEFAULT NULL,
  `sale_price` decimal(15,4) DEFAULT NULL,
  `stock_tracking_method` enum('none','quantity','lot','serial') DEFAULT 'none',
  `barcode` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_grn_history`;
CREATE TABLE `purchase_order_grn_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `purchase_order_grn_id` bigint unsigned NOT NULL,
  `log_type` enum('created','received','cancelled','status_changed','updated_line_items') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_grn` (`purchase_order_grn_id`),
  KEY `idx_event` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_grn_item_lots`;
CREATE TABLE `purchase_order_grn_item_lots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_grn_item_id` bigint unsigned NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `vendor_lot_number` varchar(100) DEFAULT NULL,
  `received_qty` decimal(15,2) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufactured_date` date DEFAULT NULL,
  `status` enum('available','quarantine') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grn_item` (`purchase_order_grn_item_id`),
  KEY `idx_lot_number` (`lot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_grn_item_serials`;
CREATE TABLE `purchase_order_grn_item_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_grn_id` bigint unsigned NOT NULL,
  `purchase_order_grn_item_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('available','quarantine','received') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_serial` (`company_id`,`serial_number`),
  KEY `idx_grn` (`purchase_order_grn_id`),
  KEY `idx_grn_item` (`purchase_order_grn_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Run this on existing installations:
-- ALTER TABLE `purchase_order_grn_item_serials`
--   ADD COLUMN `purchase_order_grn_id` BIGINT UNSIGNED NOT NULL AFTER `id`,
--   ADD KEY `idx_grn` (`purchase_order_grn_id`),
--   MODIFY COLUMN `status` ENUM('available','quarantine','received') NOT NULL DEFAULT 'available';


DROP TABLE IF EXISTS `purchase_order_grn_items`;
CREATE TABLE `purchase_order_grn_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_grn_id` bigint unsigned NOT NULL,
  `purchase_order_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `ordered_qty` decimal(15,2) NOT NULL,
  `received_qty` decimal(15,2) NOT NULL,
  `rejected_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grn` (`purchase_order_grn_id`),
  KEY `idx_po_item` (`purchase_order_item_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_grn_movements`;
CREATE TABLE `purchase_order_grn_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `purchase_order_grn_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `tracking_type` enum('quantity','lot','serial') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_grn` (`purchase_order_grn_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_location` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_grns`;
CREATE TABLE `purchase_order_grns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `purchase_order_id` bigint unsigned NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `status` enum('draft','in_transit','received','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `received_date` date DEFAULT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `in_transit_date` date DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `vendor_document_number` varchar(100) DEFAULT NULL,
  `vendor_document_date` date DEFAULT NULL,
  `notes` text,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_grn` (`company_id`,`grn_number`),
  KEY `idx_company` (`company_id`),
  KEY `idx_purchase_order` (`purchase_order_id`),
  KEY `idx_location` (`location_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_history`;
CREATE TABLE `purchase_order_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `purchase_order_id` bigint unsigned NOT NULL,
  `log_type` enum('created','updated_details','updated_line_items','status_changed','received','cancelled','message','attachment_added') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_po` (`purchase_order_id`),
  KEY `idx_event` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ordered_qty` decimal(15,2) NOT NULL,
  `product_uom_id` bigint unsigned NOT NULL,
  `conversion_factor_snapshot` decimal(18,2) NOT NULL,
  `uom_code` varchar(10) NOT NULL,
  `received_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `unit_price` decimal(15,4) NOT NULL,
  `discount_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_info` json DEFAULT NULL,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax_info` json DEFAULT NULL,
  `line_total` decimal(15,4) NOT NULL,
  `expense_account_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po` (`purchase_order_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `receiving_type` enum('inventory','drop_ship') NOT NULL DEFAULT 'inventory',
  `receiving_location_id` bigint unsigned DEFAULT NULL,
  `delivery_address_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `delivery_address_snapshot` json DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `confirmation_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `shipment_preference` varchar(100) DEFAULT NULL,
  `status` enum('draft','confirmed','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'draft',
  `notes` text,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`po_number`),
  KEY `idx_company` (`company_id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_location` (`location_id`),
  KEY `idx_receiving_location` (`receiving_location_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_deliveries`;
CREATE TABLE `sales_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `dn_number` varchar(50) NOT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `back_order_of` bigint unsigned DEFAULT NULL,
  `fulfilment_type` enum('pickup','ship','drop_ship','internal_transfer','third_party') NOT NULL,
  `status` enum('draft','dispatched','delivered','cancelled','returned','lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `dispatch_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_address_snapshot` text COMMENT 'JSON snapshot of shipping address',
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dn_number` (`company_id`,`dn_number`),
  KEY `idx_sd_company` (`company_id`),
  KEY `idx_sd_sales_order` (`sales_order_id`),
  KEY `idx_sd_customer` (`customer_id`),
  KEY `idx_sd_status` (`status`),
  KEY `idx_sd_back_order_of` (`back_order_of`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_delivery_history`;
CREATE TABLE `sales_delivery_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sales_delivery_id` bigint unsigned NOT NULL,
  `log_type` enum('created','updated_details','updated_items','status_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdh_delivery` (`sales_delivery_id`),
  KEY `idx_sdh_company` (`company_id`),
  KEY `idx_event` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_delivery_item_lots`;
CREATE TABLE `sales_delivery_item_lots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sales_delivery_id` bigint unsigned NOT NULL,
  `sales_delivery_item_id` bigint unsigned NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdil_delivery` (`sales_delivery_id`),
  KEY `idx_sdil_item` (`sales_delivery_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_delivery_item_serials`;
CREATE TABLE `sales_delivery_item_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sales_delivery_id` bigint unsigned NOT NULL,
  `sales_delivery_item_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdis_delivery` (`sales_delivery_id`),
  KEY `idx_sdis_item` (`sales_delivery_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_delivery_items`;
CREATE TABLE `sales_delivery_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_delivery_id` bigint unsigned NOT NULL,
  `sales_order_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `description` text,
  `dispatched_qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `uom_code` varchar(20) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdi_delivery` (`sales_delivery_id`),
  KEY `idx_sdi_so_item` (`sales_order_item_id`),
  KEY `idx_sdi_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_order_history`;
CREATE TABLE `sales_order_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sales_order_id` bigint unsigned NOT NULL,
  `log_type` enum('created','updated_details','updated_line_items','status_changed','dn_created','dn_updated','dn_status_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_soh_order` (`sales_order_id`),
  KEY `idx_soh_company` (`company_id`),
  KEY `idx_event` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_order_items`;
CREATE TABLE `sales_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `description` text,
  `ordered_qty` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `delivered_qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `product_uom_id` bigint unsigned DEFAULT NULL,
  `uom_code` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_info` json DEFAULT NULL,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax_info` json DEFAULT NULL,
  `line_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `line_status` enum('pending','partial','fulfilled') NOT NULL DEFAULT 'pending',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_order` (`sales_order_id`),
  KEY `idx_soi_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sales_orders`;
CREATE TABLE `sales_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `so_number` varchar(50) NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `salesperson_id` bigint unsigned DEFAULT NULL,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `order_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `payment_term_id` bigint unsigned DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('draft','confirmed','in_progress','cancelled','partially_dispatched','dispatched','partially_delivered','delivered') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `billing_address_snapshot` text COMMENT 'JSON snapshot of billing address at time of order',
  `shipping_address_snapshot` text COMMENT 'JSON snapshot of shipping address at time of order',
  `subtotal` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_info` json DEFAULT NULL,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `notes` text,
  `internal_notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_so_number` (`company_id`,`so_number`),
  KEY `idx_so_company` (`company_id`),
  KEY `idx_so_customer` (`customer_id`),
  KEY `idx_so_status` (`status`),
  KEY `idx_so_order_date` (`order_date`),
  KEY `idx_lead_id` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `sequences`;
CREATE TABLE `sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sequence_key` varchar(50) NOT NULL,
  `pattern` varchar(20) DEFAULT NULL,
  `padding` int NOT NULL DEFAULT '7',
  `last_number` bigint unsigned NOT NULL DEFAULT '0',
  `reset_period` enum('none','monthly','yearly') NOT NULL DEFAULT 'none',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_sequence_key` (`sequence_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `taxes`;
CREATE TABLE `taxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tax_type` enum('percentage','fixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'percentage',
  `rate` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `price_included` tinyint(1) NOT NULL DEFAULT '0',
  `apply_on` enum('purchase','sale','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'both',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_company_tax_code` (`company_id`,`code`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_is_active` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `uoms`;
CREATE TABLE `uoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `role` enum('admin','editor','user') DEFAULT 'user',
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `vendor_addresses`;
CREATE TABLE `vendor_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `address_type` enum('billing','shipping','registered','branch','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `attention` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address_line1` text NOT NULL,
  `address_line2` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_type` (`address_type`),
  KEY `idx_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `vendor_contacts`;
CREATE TABLE `vendor_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `salutation` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `vendors`;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `vendor_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `vendor_type` enum('company','person') NOT NULL DEFAULT 'company',
  `legal_name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `currency_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'INR',
  `payment_term_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  `status` enum('active','inactive','blocked','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_vendor_code` (`company_id`,`vendor_code`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_vendor_type` (`vendor_type`),
  KEY `idx_legal_name` (`legal_name`(250)),
  KEY `idx_display_name` (`display_name`(250)),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_term` (`payment_term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `webhook_integrations`;
CREATE TABLE `webhook_integrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `source` varchar(50) NOT NULL COMMENT 'e.g. indiamart, justdial, wordpress',
  `token` varchar(64) NOT NULL COMMENT 'company-level secret token embedded in the webhook URL',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_source` (`token`,`source`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `webhook_logs`;
CREATE TABLE `webhook_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` int unsigned DEFAULT NULL COMMENT 'NULL when token does not match any integration',
  `company_id` int unsigned DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `token` varchar(64) NOT NULL,
  `http_method` varchar(10) NOT NULL,
  `headers` json DEFAULT NULL COMMENT 'sanitised inbound request headers',
  `raw_payload` longtext COMMENT 'exact body as received',
  `parsed_payload` json DEFAULT NULL COMMENT 'normalised payload after source adapter runs',
  `status` enum('received','processing','processed','retrying','failed','ignored') NOT NULL DEFAULT 'received',
  `failure_reason` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `retry_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_company_source` (`company_id`,`source`),
  KEY `idx_status` (`status`),
  KEY `idx_received_at` (`received_at`),
  KEY `idx_retry` (`status`,`retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- =================================================================
-- SUBSCRIPTIONS, RBAC, MODULE & FEATURE ACCESS, MENU
-- =================================================================


-- -----------------------------------------------------------------
-- MASTER / LOOKUP TABLES
-- -----------------------------------------------------------------

-- created_by / updated_by nullable - NULL when inserted via seed/migration,
-- populated once a platform admin UI exists.

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `key`         varchar(50) NOT NULL,
  `name`        varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon`        varchar(100) DEFAULT NULL,
  `sort_order`  tinyint unsigned NOT NULL DEFAULT 0,
  `is_active`   tinyint(1) NOT NULL DEFAULT 1,
  `created_by`  bigint unsigned DEFAULT NULL,
  `updated_by`  bigint unsigned DEFAULT NULL,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- menu_group values:
--   main      : primary nav item, nested inside its module section
--   top_level : renders as its own top-level sidebar section (e.g. Products)
--               appears before its owning module section, shown when any module
--               that cross-includes it is active
--   settings  : config/setup page, rendered in a separate group within module section
--   reports   : reporting page, rendered in a separate group within module section
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id`   bigint unsigned NOT NULL,
  `key`         varchar(100) NOT NULL,
  `name`        varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `route`       varchar(191) DEFAULT NULL,
  `menu_order`  tinyint unsigned NOT NULL DEFAULT 0,
  `menu_group`  enum('main','top_level','settings','reports') NOT NULL DEFAULT 'main',
  `is_active`   tinyint(1) NOT NULL DEFAULT 1,
  `created_by`  bigint unsigned DEFAULT NULL,
  `updated_by`  bigint unsigned DEFAULT NULL,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`),
  KEY `idx_module` (`module_id`),
  CONSTRAINT `fk_features_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Static system table - defines which features a company unlocks when they subscribe
-- to a module. Handles cross-module access e.g. CRM subscription includes sales.quotations.
-- Insert/delete only - no updates, so no updated_by.
DROP TABLE IF EXISTS `module_feature_includes`;
CREATE TABLE `module_feature_includes` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id`  bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_module_feature` (`module_id`, `feature_id`),
  KEY `idx_module` (`module_id`),
  KEY `idx_feature` (`feature_id`),
  CONSTRAINT `fk_mfi_module`  FOREIGN KEY (`module_id`)  REFERENCES `modules`  (`id`),
  CONSTRAINT `fk_mfi_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- -----------------------------------------------------------------
-- SUBSCRIPTION TABLES
-- -----------------------------------------------------------------

-- Plan catalog. Prices here are list/default prices.
-- Actual agreed prices are always stored on company_subscriptions.
DROP TABLE IF EXISTS `subscription_plans`;
CREATE TABLE `subscription_plans` (
  `id`                       bigint unsigned NOT NULL AUTO_INCREMENT,
  `name`                     varchar(100) NOT NULL,
  `slug`                     varchar(50) NOT NULL,
  `description`              text DEFAULT NULL,
  `max_modules`              tinyint unsigned DEFAULT NULL,  -- NULL = unlimited, 1 = One App
  `free_users_included`      tinyint unsigned NOT NULL DEFAULT 3,
  `base_price_monthly`       decimal(15,4) NOT NULL DEFAULT 0.0000,
  `extra_user_price_monthly` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_active`                tinyint(1) NOT NULL DEFAULT 1,
  `created_by`               bigint unsigned DEFAULT NULL,
  `updated_by`               bigint unsigned DEFAULT NULL,
  `created_at`               datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- One row per subscription period per company.
-- Multiple rows per company allowed - old rows kept as history.
-- Only one row per company has is_current = 1, enforced in service layer not DB.
-- Agreed prices stored here override the plan list price (pilot = 0, negotiated = custom).
-- created_by nullable - signup is system-created (NULL), upgrades are user-initiated.
DROP TABLE IF EXISTS `company_subscriptions`;
CREATE TABLE `company_subscriptions` (
  `id`                       bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id`               bigint unsigned NOT NULL,
  `plan_id`                  bigint unsigned NOT NULL,
  `is_current`               tinyint(1) NOT NULL DEFAULT 1,
  `status`                   enum('trial','pilot','active','past_due','cancelled','suspended') NOT NULL DEFAULT 'trial',
  `billing_cycle`            enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `agreed_base_price`        decimal(15,4) NOT NULL DEFAULT 0.0000,
  `agreed_extra_user_price`  decimal(15,4) NOT NULL DEFAULT 0.0000,
  `free_users_included`      tinyint unsigned NOT NULL DEFAULT 3,  -- copied from plan at signup
  `purchased_extra_seats`    tinyint unsigned NOT NULL DEFAULT 0,  -- incremented on each paid add
  `razorpay_customer_id`     varchar(100) DEFAULT NULL,
  `razorpay_subscription_id` varchar(100) DEFAULT NULL,
  `trial_ends_at`            datetime DEFAULT NULL,
  `pilot_until`              datetime DEFAULT NULL,              -- NULL = indefinite pilot
  `current_period_start`     datetime DEFAULT NULL,
  `current_period_end`       datetime DEFAULT NULL,
  `notes`                    text DEFAULT NULL,
  `created_by`               bigint unsigned DEFAULT NULL,
  `updated_by`               bigint unsigned DEFAULT NULL,
  `created_at`               datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_current` (`company_id`, `is_current`),
  KEY `idx_status` (`status`),
  KEY `idx_plan` (`plan_id`),
  CONSTRAINT `fk_cs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_cs_plan`    FOREIGN KEY (`plan_id`)    REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Which modules a company has active, scoped to a subscription row.
-- When a plan changes: old subscription row kept, new subscription + new module rows created.
-- Active modules = rows where subscription.is_current = 1.
-- Programmatically created as part of subscription flow - no updated_by needed.
DROP TABLE IF EXISTS `company_subscription_modules`;
CREATE TABLE `company_subscription_modules` (
  `id`              bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id`      bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `module_id`       bigint unsigned NOT NULL,
  `is_active`       tinyint(1) NOT NULL DEFAULT 1,
  `activated_at`    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_module` (`subscription_id`, `module_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_module` (`module_id`),
  CONSTRAINT `fk_csm_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `company_subscriptions` (`id`),
  CONSTRAINT `fk_csm_module`       FOREIGN KEY (`module_id`)       REFERENCES `modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Immutable audit log for every seat add or remove.
-- triggered_by serves as created_by. Never update rows in this table.
-- prorated_amount NULL for removals - no refund, change effective next period.
DROP TABLE IF EXISTS `subscription_seat_events`;
CREATE TABLE `subscription_seat_events` (
  `id`              bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id`      bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `event_type`      enum('add','remove') NOT NULL,
  `seats_before`    tinyint unsigned NOT NULL,
  `seats_after`     tinyint unsigned NOT NULL,
  `effective_at`    datetime NOT NULL,
  `period_start`    datetime NOT NULL,
  `period_end`      datetime NOT NULL,
  `prorated_amount` decimal(15,4) DEFAULT NULL,
  `triggered_by`    bigint unsigned NOT NULL,
  `created_at`      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_subscription` (`subscription_id`),
  CONSTRAINT `fk_sse_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `company_subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- -----------------------------------------------------------------
-- RBAC TABLES
-- -----------------------------------------------------------------

-- Roles defined per company.
-- is_system = 1 : seeded at signup, cannot be deleted by the company.
-- is_super  = 1 : bypasses all module and feature access checks (admin role).
DROP TABLE IF EXISTS `company_roles`;
CREATE TABLE `company_roles` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id`  bigint unsigned NOT NULL,
  `name`        varchar(100) NOT NULL,
  `slug`        varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system`   tinyint(1) NOT NULL DEFAULT 0,
  `is_super`    tinyint(1) NOT NULL DEFAULT 0,
  `status`      enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`  bigint unsigned DEFAULT NULL,
  `updated_by`  bigint unsigned DEFAULT NULL,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_slug` (`company_id`, `slug`),
  KEY `idx_company` (`company_id`),
  CONSTRAINT `fk_cr_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- User to role mapping - many-to-many.
-- A user can hold multiple roles simultaneously.
-- Insert/delete only - no updates, so no updated_by.
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id`    bigint unsigned NOT NULL,
  `role_id`    bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_company` (`company_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `company_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Grants a role access to a module or a specific feature.
-- access_type = 'module'  : access_id references modules.id
--   Grants access to all features that module exposes via module_feature_includes.
-- access_type = 'feature' : access_id references features.id
--   Grants access to one specific feature only e.g. sales.quotations without full Sales.
-- access_id is a polymorphic reference - no DB-level FK constraint is possible.
-- Service_AccessControl::grantRoleAccess() validates access_id against the correct
-- master table and verifies the company subscription includes the feature before inserting.
-- Insert/delete only - no updates, so no updated_by.
DROP TABLE IF EXISTS `role_access_grants`;
CREATE TABLE `role_access_grants` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id`  bigint unsigned NOT NULL,
  `role_id`     bigint unsigned NOT NULL,
  `access_type` enum('module','feature') NOT NULL,
  `access_id`   bigint unsigned NOT NULL,
  `created_by`  bigint unsigned DEFAULT NULL,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_access` (`role_id`, `access_type`, `access_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_role` (`role_id`),
  CONSTRAINT `fk_rag_role` FOREIGN KEY (`role_id`) REFERENCES `company_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- -----------------------------------------------------------------
-- PERMISSIONS - PHASE 2
-- Add these tables when granular permission control is required.
-- -----------------------------------------------------------------

-- Granular permission registry - one row per feature x action.
-- created_by nullable - NULL when inserted via seed/migration.
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_id` bigint unsigned NOT NULL,
  `action`     varchar(50) NOT NULL,
  `label`      varchar(191) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm` (`feature_id`, `action`),
  KEY `idx_feature` (`feature_id`),
  CONSTRAINT `fk_perm_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Insert/delete only - no updates, so no updated_by.
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id`            bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id`       bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_by`    bigint unsigned DEFAULT NULL,
  `created_at`    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`role_id`, `permission_id`),
  KEY `idx_role` (`role_id`),
  CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `company_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- -----------------------------------------------------------------
-- CHANGES TO EXISTING TABLES
-- -----------------------------------------------------------------

-- Remove hard-coded role enum. Role is now driven by user_roles + company_roles.
-- Add created_by for audit trail on who added the user to the company.
ALTER TABLE `users`
  DROP COLUMN `role`,
  ADD COLUMN `created_by` bigint unsigned DEFAULT NULL AFTER `company_id`;

-- Drop companies.plan - plan is now tracked via company_subscriptions.plan_id.
-- Nothing in the codebase reads this column yet so safe to drop immediately.
ALTER TABLE `companies`
  DROP COLUMN `plan`;


-- -----------------------------------------------------------------
-- SEED DATA
-- -----------------------------------------------------------------

-- Modules
-- sort_order controls the order of module sections in the sidebar.
-- top_level features (Products) render before their owning module section.
INSERT INTO `modules` (`key`, `name`, `icon`, `sort_order`) VALUES
  ('crm',        'CRM',        'bx bx-stats',          1),
  ('sales',      'Sales',      'bx bx-cart',           2),
  ('inventory',  'Inventory',  'bx bx-buildings',      3),
  ('purchasing', 'Purchasing', 'bx bx-purchase-tag',   4);


-- Features
-- menu_group = 'top_level' : renders as its own top-level sidebar section,
--   appears before its owning module section, shown when any module
--   that cross-includes it is active (Sales, Inventory, Purchasing all include Products).
INSERT INTO `features` (`module_id`, `key`, `name`, `route`, `menu_order`, `menu_group`) VALUES

  -- CRM - main
  ((SELECT id FROM modules WHERE `key` = 'crm'), 'crm.pipeline',              'Pipeline',    '/crm/pipeline',        1, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'crm'), 'crm.leads',                 'Leads',       '/crm/leads',           2, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'crm'), 'crm.customers',             'Customers',   '/customers',           3, 'main'),
  -- CRM - settings (rendered under Manage section in sidebar)
  ((SELECT id FROM modules WHERE `key` = 'crm'), 'crm.settings.stages',       'Stages',      '/crm/stages',          1, 'settings'),
  ((SELECT id FROM modules WHERE `key` = 'crm'), 'crm.settings.integrations', 'Pull Leads',  '/crm/integrations',    2, 'settings'),

  -- Sales - main
  ((SELECT id FROM modules WHERE `key` = 'sales'), 'sales.customers',   'Customers',   '/customers',            1, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'sales'), 'sales.quotations',  'Quotations',  '/sales/quotations',     2, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'sales'), 'sales.orders',      'Sales Orders','/sales/orders',         3, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'sales'), 'sales.deliveries',  'Deliveries',  '/sales/deliveries',     4, 'main'),

  -- Inventory - top_level (Products renders as its own sidebar section)
  ((SELECT id FROM modules WHERE `key` = 'inventory'), 'inventory.products',            'Products',   '/products',           1, 'top_level'),
  ((SELECT id FROM modules WHERE `key` = 'inventory'), 'inventory.products.categories', 'Categories', '/products/categories',2, 'top_level'),
  -- Inventory - main
  ((SELECT id FROM modules WHERE `key` = 'inventory'), 'inventory.adjustments',         'Adjustments','/inv/adjustments',    1, 'main'),
  -- Inventory - settings
  ((SELECT id FROM modules WHERE `key` = 'inventory'), 'inventory.settings.general',    'General',    '/settings/inventory', 1, 'settings'),

  -- Purchasing - main
  ((SELECT id FROM modules WHERE `key` = 'purchasing'), 'purchasing.vendors',   'Vendors',          '/vendors',           1, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'purchasing'), 'purchasing.orders',    'Purchase Orders',  '/purchase/orders',   2, 'main'),
  ((SELECT id FROM modules WHERE `key` = 'purchasing'), 'purchasing.receipts',  'Purchase Receives','/purchase/receipts', 3, 'main');


-- Module Feature Includes
-- Each INSERT defines what a subscriber of that module can access,
-- including cross-module features.

-- CRM: own features + quotations from Sales + customer record from Sales
INSERT INTO `module_feature_includes` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` IN (
  'crm.pipeline', 'crm.leads', 'crm.settings.stages', 'crm.settings.integrations',
  'sales.quotations',
  'sales.customers'
);

-- Sales: own features + Products cross-module (needed to build orders/quotations)
INSERT INTO `module_feature_includes` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'sales' AND f.`key` IN (
  'sales.customers', 'sales.quotations', 'sales.orders', 'sales.deliveries',
  'inventory.products', 'inventory.products.categories'
);

-- Inventory: own features only (owns Products - no cross-module needed)
INSERT INTO `module_feature_includes` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'inventory' AND f.`key` IN (
  'inventory.products', 'inventory.products.categories',
  'inventory.adjustments',
  'inventory.settings.general'
);

-- Purchasing: own features + Products cross-module (needed to build POs)
INSERT INTO `module_feature_includes` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'purchasing' AND f.`key` IN (
  'purchasing.vendors', 'purchasing.orders', 'purchasing.receipts',
  'inventory.products', 'inventory.products.categories'
);


-- Subscription plans - prices set to 0.0000 until finalised
INSERT INTO `subscription_plans`
  (`name`, `slug`, `max_modules`, `free_users_included`, `base_price_monthly`, `extra_user_price_monthly`)
VALUES
  ('One App',  'one_app',  1,    3, 0.0000, 0.0000),
  ('All Apps', 'all_apps', NULL, 5, 0.0000, 0.0000);


-- Phase 3: Company Registration
-- Remove unique company name constraint (email is the unique identifier)
ALTER TABLE `companies` DROP INDEX `unique_name`;

-- Add registration fields to users
ALTER TABLE `users`
  ADD COLUMN `first_name` varchar(100) NOT NULL DEFAULT '' AFTER `company_id`,
  ADD COLUMN `last_name`  varchar(100) DEFAULT NULL AFTER `first_name`,
  ADD COLUMN `phone`      varchar(50)  DEFAULT NULL AFTER `email`,
  ADD COLUMN `email_verification_token`      varchar(64)  DEFAULT NULL AFTER `email_verified_at`,
  ADD COLUMN `email_verification_expires_at` datetime     DEFAULT NULL AFTER `email_verification_token`,
  MODIFY COLUMN `status` enum('active','inactive','banned','pending') DEFAULT 'active';


-- Phase 4: Company Profile
ALTER TABLE `companies`
  ADD COLUMN `state` varchar(100) DEFAULT NULL AFTER `city`;


-- Admin Module: rename module_feature_includes to module_feature_map
RENAME TABLE `module_feature_includes` TO `module_feature_map`;

-- Admin Module: menus table for sidebar navigation management
CREATE TABLE `menus` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id`  bigint unsigned DEFAULT NULL,
  `feature_id` bigint unsigned DEFAULT NULL,
  `label`      varchar(100) NOT NULL,
  `icon`       varchar(100) DEFAULT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_feature` (`feature_id`),
  CONSTRAINT `fk_menus_parent`  FOREIGN KEY (`parent_id`)  REFERENCES `menus` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_menus_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Seed initial menu structure (run AFTER features are created via /admin/features)
-- Adjust keys to match the keys you used when seeding features.

-- Group headers (no feature_id — collapse toggles)
INSERT INTO `menus` (parent_id, feature_id, label, icon, sort_order, is_visible) VALUES
(NULL, NULL, 'CRM',        'bx bx-stats',   10, 1),
(NULL, NULL, 'Sales',      'bx bx-cart',    20, 1),
(NULL, NULL, 'Purchasing', 'bx bx-package', 30, 1),
(NULL, NULL, 'Inventory',  'bx bx-cube',    40, 1);

-- CRM children (adjust keys to match your features)
INSERT INTO `menus` (parent_id, feature_id, label, icon, sort_order, is_visible)
SELECT (SELECT id FROM menus WHERE label='CRM' AND parent_id IS NULL LIMIT 1),
       f.id, f.name, NULL, 1, 1
FROM features f WHERE f.`key` = 'crm.pipeline';

INSERT INTO `menus` (parent_id, feature_id, label, icon, sort_order, is_visible)
SELECT (SELECT id FROM menus WHERE label='CRM' AND parent_id IS NULL LIMIT 1),
       f.id, f.name, NULL, 2, 1
FROM features f WHERE f.`key` = 'crm.leads';

-- Add unique constraint to module_feature_map to prevent duplicate entries
ALTER TABLE `module_feature_map` ADD UNIQUE KEY `uq_module_feature` (`module_id`, `feature_id`);

-- Allow module_id to be NULL for core and super_admin features
ALTER TABLE `features` MODIFY COLUMN `module_id` INT(11) NULL DEFAULT NULL;

-- Drop FK, make module_id nullable, re-add FK (NULL allowed for core/super_admin features)
ALTER TABLE `features` DROP FOREIGN KEY `fk_features_module`;
ALTER TABLE `features` MODIFY COLUMN `module_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `features` ADD CONSTRAINT `fk_features_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL;

-- Add won_revenue to crm_leads for CRM pipeline reporting
ALTER TABLE `crm_leads` ADD COLUMN `won_revenue` decimal(15,4) NULL AFTER `expected_revenue`;

-- Add title column to crm_leads for lead subject/inquiry description
ALTER TABLE `crm_leads` ADD COLUMN `title` varchar(255) NULL AFTER `lead_code`;

-- Add crm.leads.export feature for independent export permission management
INSERT INTO `features` (`module_id`, `key`, `name`, `route`, `menu_order`, `menu_group`)
VALUES (
    (SELECT id FROM modules WHERE `key` = 'crm'),
    'crm.leads.export',
    'Export Leads',
    NULL,
    0,
    'main'
);

INSERT INTO `module_feature_map` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` = 'crm.leads.export';

-- Add adjustment_label and adjustment_amount to sales_orders for order-level adjustments (handling charges, rounding, etc.)
ALTER TABLE `sales_orders`
    ADD COLUMN `adjustment_label`  VARCHAR(100)  NULL           AFTER `discount_amount`,
    ADD COLUMN `adjustment_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `adjustment_label`;

-- Add order_discount_allocated and taxable_amount to sales_order_items for ERP-grade return/tax/report accuracy.
-- order_discount_allocated: this line's proportional share of the order-level discount (residual-to-last method).
-- taxable_amount: effective tax base after all discounts (item + order); 0 for non-taxable items.
ALTER TABLE `sales_order_items`
    ADD COLUMN `order_discount_allocated` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `discount_info`,
    ADD COLUMN `taxable_amount`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `order_discount_allocated`;

-- Add delivery_type to sales_orders; mirrors delivery note fulfilment_type ENUM values (pickup / ship).
ALTER TABLE `sales_orders`
    ADD COLUMN `delivery_type` ENUM('pickup','ship') NOT NULL DEFAULT 'pickup' AFTER `payment_terms`;

-- Add inv.items feature for the Inventory Items screen (/inv/items).
INSERT INTO `features` (`module_id`, `key`, `name`, `route`, `menu_order`, `menu_group`)
VALUES (
    (SELECT id FROM modules WHERE `key` = 'inventory'),
    'inv.items',
    'Inventory Items',
    '/inv/items',
    0,
    'main'
);

INSERT INTO `module_feature_map` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'inventory' AND f.`key` = 'inv.items';


-- ============================================================
-- RBAC Redesign — Phase 1: Schema Changes
-- Date: 2026-05-21
-- ============================================================

-- 1.1 Modify features table: remove old route/menu columns, add access_level + is_scopeable + sort_order
-- NOTE: route_type was never in this table — not dropped here.
-- NOTE: access_level is new — added here so subsequent AFTER references work.
ALTER TABLE `features`
  DROP COLUMN `route`,
  DROP COLUMN `menu_order`,
  DROP COLUMN `menu_group`,
  ADD COLUMN `access_level` ENUM('core','subscription') NOT NULL DEFAULT 'subscription' AFTER `is_active`,
  ADD COLUMN `is_scopeable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `access_level`,
  ADD COLUMN `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_scopeable`;

-- 1.2 Add data_scope to role_permissions
ALTER TABLE `role_permissions`
  ADD COLUMN `data_scope` ENUM('own','team','all') NOT NULL DEFAULT 'all' AFTER `permission_id`;

-- 1.3 Drop role_access_grants (replaced by role_permissions + permissions)
DROP TABLE IF EXISTS `role_access_grants`;

-- 1.4 Create teams
CREATE TABLE IF NOT EXISTS `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 1.5 Create team_members
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_user` (`team_id`,`user_id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_tm_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- ============================================================
-- RBAC Redesign — Phase 2: Clear old feature/permission data
-- ============================================================

DELETE FROM `role_permissions`;
DELETE FROM `permissions`;
DELETE FROM `module_feature_map`;
DELETE FROM `features`;


-- ============================================================
-- Phase 2 — Seed: Features
-- NOTE: Subscription features use SELECT to resolve module_id by key.
--       Verify module keys match your modules table before running.
--       Common keys: crm, sales, purchase, inventory
-- ============================================================

-- Core features (NULL module_id — always accessible, no subscription gate)
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`) VALUES
  (NULL, 'company_settings',     'Company Settings',      'core', 0, 1, 1),
  (NULL, 'company_locations',    'Locations',             'core', 0, 2, 1),
  (NULL, 'company_users',        'Users',                 'core', 0, 3, 1),
  (NULL, 'company_roles_mgmt',   'Roles & Permissions',   'core', 0, 4, 1),
  (NULL, 'company_teams',        'Teams',                 'core', 0, 5, 1),
  (NULL, 'company_subscription', 'Subscription & Billing','core', 0, 6, 1),
  (NULL, 'activities',           'Activities',            'core', 1, 1, 1),
  (NULL, 'attachments',          'Attachments',           'core', 0, 1, 1);

-- CRM module features
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`)
SELECT m.id, 'crm_leads',        'CRM Leads',        'subscription', 1, 1, 1 FROM `modules` m WHERE m.`key` = 'crm' UNION ALL
SELECT m.id, 'crm_stages',       'CRM Stages',       'subscription', 0, 2, 1 FROM `modules` m WHERE m.`key` = 'crm' UNION ALL
SELECT m.id, 'crm_integrations', 'CRM Integrations', 'subscription', 0, 3, 1 FROM `modules` m WHERE m.`key` = 'crm';

-- Sales module features
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`)
SELECT m.id, 'sales_orders',     'Sales Orders',     'subscription', 1, 1, 1 FROM `modules` m WHERE m.`key` = 'sales' UNION ALL
SELECT m.id, 'sales_deliveries', 'Sales Deliveries', 'subscription', 1, 2, 1 FROM `modules` m WHERE m.`key` = 'sales' UNION ALL
SELECT m.id, 'customers',        'Customers',        'subscription', 0, 3, 1 FROM `modules` m WHERE m.`key` = 'sales';

-- Purchase module features
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`)
SELECT m.id, 'purchase_orders',   'Purchase Orders',   'subscription', 0, 1, 1 FROM `modules` m WHERE m.`key` = 'purchasing' UNION ALL
SELECT m.id, 'purchase_receipts', 'Purchase Receipts', 'subscription', 0, 2, 1 FROM `modules` m WHERE m.`key` = 'purchasing' UNION ALL
SELECT m.id, 'vendors',           'Vendors',           'subscription', 0, 3, 1 FROM `modules` m WHERE m.`key` = 'purchasing';

-- Inventory module features
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`)
SELECT m.id, 'inventory_items',        'Inventory Items',        'subscription', 0, 1, 1 FROM `modules` m WHERE m.`key` = 'inventory' UNION ALL
SELECT m.id, 'inventory_adjustments',  'Inventory Adjustments',  'subscription', 0, 2, 1 FROM `modules` m WHERE m.`key` = 'inventory' UNION ALL
SELECT m.id, 'inventory_movements',    'Stock Movements',         'subscription', 0, 3, 1 FROM `modules` m WHERE m.`key` = 'inventory' UNION ALL
SELECT m.id, 'inventory_stock',        'Stock Management',        'subscription', 0, 4, 1 FROM `modules` m WHERE m.`key` = 'inventory' UNION ALL
SELECT m.id, 'products',               'Products',                'subscription', 0, 5, 1 FROM `modules` m WHERE m.`key` = 'inventory' UNION ALL
SELECT m.id, 'product_categories',     'Product Categories',      'subscription', 0, 6, 1 FROM `modules` m WHERE m.`key` = 'inventory';


-- ============================================================
-- Phase 2 — Seed: module_feature_map
-- ============================================================

-- Primary module mapping (each feature under its owning module)
INSERT INTO `module_feature_map` (`module_id`, `feature_id`)
SELECT f.module_id, f.id FROM `features` f WHERE f.module_id IS NOT NULL;

-- Cross-module fallback: customers is also accessible via CRM (point-4 sidebar logic)
INSERT IGNORE INTO `module_feature_map` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` = 'customers';

-- Cross-module fallback: sales_orders accessible via CRM as "Quotations"
INSERT IGNORE INTO `module_feature_map` (`module_id`, `feature_id`, `display_name`)
SELECT m.id, f.id, 'Quotations' FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` = 'sales_orders';

-- NOTE: Activities and Attachments (module_id IS NULL / core) are shown in the
-- "Shared Features" section of the permissions drawer and are NOT mapped to
-- specific modules in module_feature_map.


-- ============================================================
-- Phase 2 — Seed: Permissions
-- ============================================================

-- crm_leads
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',    'View Leads'          FROM `features` WHERE `key` = 'crm_leads' UNION ALL
SELECT id, 'write',   'Create & Edit Leads' FROM `features` WHERE `key` = 'crm_leads' UNION ALL
SELECT id, 'delete',  'Delete Leads'        FROM `features` WHERE `key` = 'crm_leads' UNION ALL
SELECT id, 'convert', 'Convert Leads'       FROM `features` WHERE `key` = 'crm_leads';

-- crm_stages
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Stages'          FROM `features` WHERE `key` = 'crm_stages' UNION ALL
SELECT id, 'write',  'Create & Edit Stages' FROM `features` WHERE `key` = 'crm_stages' UNION ALL
SELECT id, 'delete', 'Delete Stages'        FROM `features` WHERE `key` = 'crm_stages';

-- crm_integrations
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Integrations'          FROM `features` WHERE `key` = 'crm_integrations' UNION ALL
SELECT id, 'write',  'Create & Edit Integrations' FROM `features` WHERE `key` = 'crm_integrations' UNION ALL
SELECT id, 'delete', 'Delete Integrations'        FROM `features` WHERE `key` = 'crm_integrations';

-- sales_orders
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',       'View Sales Orders'          FROM `features` WHERE `key` = 'sales_orders' UNION ALL
SELECT id, 'write',      'Create & Edit Sales Orders' FROM `features` WHERE `key` = 'sales_orders' UNION ALL
SELECT id, 'delete',     'Delete Sales Orders'        FROM `features` WHERE `key` = 'sales_orders' UNION ALL
SELECT id, 'confirm',    'Confirm Sales Orders'       FROM `features` WHERE `key` = 'sales_orders' UNION ALL
SELECT id, 'cancel',     'Cancel Sales Orders'        FROM `features` WHERE `key` = 'sales_orders' UNION ALL
SELECT id, 'send_email', 'Send Sales Order Email'     FROM `features` WHERE `key` = 'sales_orders';

-- sales_deliveries
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',          'View Deliveries'              FROM `features` WHERE `key` = 'sales_deliveries' UNION ALL
SELECT id, 'write',         'Create & Edit Deliveries'     FROM `features` WHERE `key` = 'sales_deliveries' UNION ALL
SELECT id, 'delete',        'Delete Deliveries'            FROM `features` WHERE `key` = 'sales_deliveries' UNION ALL
SELECT id, 'dispatch',      'Dispatch Deliveries'          FROM `features` WHERE `key` = 'sales_deliveries' UNION ALL
SELECT id, 'mark_complete', 'Mark Delivered/Returned/Lost' FROM `features` WHERE `key` = 'sales_deliveries' UNION ALL
SELECT id, 'cancel',        'Cancel Deliveries'            FROM `features` WHERE `key` = 'sales_deliveries';

-- customers
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Customers'          FROM `features` WHERE `key` = 'customers' UNION ALL
SELECT id, 'write',  'Create & Edit Customers' FROM `features` WHERE `key` = 'customers' UNION ALL
SELECT id, 'delete', 'Delete Customers'        FROM `features` WHERE `key` = 'customers';

-- purchase_orders
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Purchase Orders'          FROM `features` WHERE `key` = 'purchase_orders' UNION ALL
SELECT id, 'write',  'Create & Edit Purchase Orders' FROM `features` WHERE `key` = 'purchase_orders' UNION ALL
SELECT id, 'delete', 'Delete Purchase Orders'        FROM `features` WHERE `key` = 'purchase_orders' UNION ALL
SELECT id, 'cancel', 'Cancel Purchase Orders'        FROM `features` WHERE `key` = 'purchase_orders';

-- purchase_receipts
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Purchase Receipts'          FROM `features` WHERE `key` = 'purchase_receipts' UNION ALL
SELECT id, 'write',  'Create & Edit Purchase Receipts' FROM `features` WHERE `key` = 'purchase_receipts' UNION ALL
SELECT id, 'delete', 'Delete Purchase Receipts'        FROM `features` WHERE `key` = 'purchase_receipts';

-- inventory_items
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read', 'View Inventory Items' FROM `features` WHERE `key` = 'inventory_items';

-- inventory_adjustments
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read', 'View Inventory Adjustments' FROM `features` WHERE `key` = 'inventory_adjustments';

-- inventory_movements
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read', 'View Stock Movements' FROM `features` WHERE `key` = 'inventory_movements';

-- inventory_stock
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',  'View Stock'   FROM `features` WHERE `key` = 'inventory_stock' UNION ALL
SELECT id, 'write', 'Adjust Stock' FROM `features` WHERE `key` = 'inventory_stock';

-- products
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Products'          FROM `features` WHERE `key` = 'products' UNION ALL
SELECT id, 'write',  'Create & Edit Products' FROM `features` WHERE `key` = 'products' UNION ALL
SELECT id, 'delete', 'Delete Products'        FROM `features` WHERE `key` = 'products';

-- product_categories
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Categories'          FROM `features` WHERE `key` = 'product_categories' UNION ALL
SELECT id, 'write',  'Create & Edit Categories' FROM `features` WHERE `key` = 'product_categories' UNION ALL
SELECT id, 'delete', 'Delete Categories'        FROM `features` WHERE `key` = 'product_categories';

-- vendors
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Vendors'          FROM `features` WHERE `key` = 'vendors' UNION ALL
SELECT id, 'write',  'Create & Edit Vendors' FROM `features` WHERE `key` = 'vendors' UNION ALL
SELECT id, 'delete', 'Delete Vendors'        FROM `features` WHERE `key` = 'vendors';

-- activities
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',          'View Activities'          FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'write',         'Create & Edit Activities' FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'delete',        'Delete Activities'        FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'mark_complete', 'Mark Activity Complete'   FROM `features` WHERE `key` = 'activities';

-- attachments
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Attachments'   FROM `features` WHERE `key` = 'attachments' UNION ALL
SELECT id, 'write',  'Upload Attachments' FROM `features` WHERE `key` = 'attachments' UNION ALL
SELECT id, 'delete', 'Delete Attachments' FROM `features` WHERE `key` = 'attachments';

-- company_settings
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',  'View Company Settings' FROM `features` WHERE `key` = 'company_settings' UNION ALL
SELECT id, 'write', 'Edit Company Settings' FROM `features` WHERE `key` = 'company_settings';

-- company_locations
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Locations'          FROM `features` WHERE `key` = 'company_locations' UNION ALL
SELECT id, 'write',  'Create & Edit Locations' FROM `features` WHERE `key` = 'company_locations' UNION ALL
SELECT id, 'delete', 'Delete Locations'        FROM `features` WHERE `key` = 'company_locations';

-- company_users
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',  'View Users'          FROM `features` WHERE `key` = 'company_users' UNION ALL
SELECT id, 'write', 'Create & Edit Users' FROM `features` WHERE `key` = 'company_users';

-- company_roles_mgmt
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Roles & Permissions'          FROM `features` WHERE `key` = 'company_roles_mgmt' UNION ALL
SELECT id, 'write',  'Create & Edit Roles & Permissions' FROM `features` WHERE `key` = 'company_roles_mgmt' UNION ALL
SELECT id, 'delete', 'Delete Roles'                      FROM `features` WHERE `key` = 'company_roles_mgmt';

-- company_teams
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',   'View Teams'          FROM `features` WHERE `key` = 'company_teams' UNION ALL
SELECT id, 'write',  'Create & Edit Teams' FROM `features` WHERE `key` = 'company_teams' UNION ALL
SELECT id, 'delete', 'Delete Teams'        FROM `features` WHERE `key` = 'company_teams';

-- company_subscription
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',  'View Subscription'   FROM `features` WHERE `key` = 'company_subscription' UNION ALL
SELECT id, 'write', 'Manage Subscription' FROM `features` WHERE `key` = 'company_subscription';


-- ============================================================
-- Phase 2 Supplement — Safe to run after Phase 2 (idempotent)
-- ============================================================

-- Add display_name column to module_feature_map (per-module label override)
ALTER TABLE `module_feature_map`
  ADD COLUMN `display_name` VARCHAR(100) NULL DEFAULT NULL AFTER `feature_id`;

-- Cross-module fallback entries (customers + sales_orders accessible via CRM)
INSERT IGNORE INTO `module_feature_map` (`module_id`, `feature_id`)
SELECT m.id, f.id FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` = 'customers';

INSERT IGNORE INTO `module_feature_map` (`module_id`, `feature_id`, `display_name`)
SELECT m.id, f.id, 'Quotations' FROM `modules` m JOIN `features` f ON 1=1
WHERE m.`key` = 'crm' AND f.`key` = 'sales_orders';

-- Remove any old Activities/Attachments module_feature_map entries that were
-- added in earlier iterations — these now live in the Shared Features section only
DELETE mfm FROM `module_feature_map` mfm
JOIN `features` f ON f.id = mfm.feature_id
WHERE f.`key` IN ('activities', 'attachments');

-- role_module_activations: which modules each role has explicitly enabled
CREATE TABLE IF NOT EXISTS `role_module_activations` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`    INT NOT NULL,
  `module_id`  INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_module` (`role_id`, `module_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- RBAC Refinements — Admin Tier + Feature Name Cleanup
-- ============================================================

-- 1. Add is_admin flag to company_roles (admin roles can be granted admin-level features)
ALTER TABLE `company_roles`
  ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_super`;

-- 2. Expand ENUM temporarily to support both old and new values during migration
ALTER TABLE `features`
  MODIFY COLUMN `access_level` ENUM('core','subscription','public','admin','super_admin') NOT NULL DEFAULT 'public';

-- 3. Strip module prefix from feature names
UPDATE `features` SET `name` = 'Leads'        WHERE `key` = 'crm_leads';
UPDATE `features` SET `name` = 'Stages'        WHERE `key` = 'crm_stages';
UPDATE `features` SET `name` = 'Integrations'  WHERE `key` = 'crm_integrations';
UPDATE `features` SET `name` = 'Orders'        WHERE `key` = 'sales_orders';
UPDATE `features` SET `name` = 'Deliveries'    WHERE `key` = 'sales_deliveries';
UPDATE `features` SET `name` = 'Orders'        WHERE `key` = 'purchase_orders';
UPDATE `features` SET `name` = 'Receipts'      WHERE `key` = 'purchase_receipts';
UPDATE `features` SET `name` = 'Items'         WHERE `key` = 'inventory_items';
UPDATE `features` SET `name` = 'Adjustments'   WHERE `key` = 'inventory_adjustments';
UPDATE `features` SET `name` = 'Movements'     WHERE `key` = 'inventory_movements';
UPDATE `features` SET `name` = 'Stock'         WHERE `key` = 'inventory_stock';

-- 4. Assign new access_level tiers
UPDATE `features` SET `access_level` = 'super_admin' WHERE `key` = 'company_subscription';
UPDATE `features` SET `access_level` = 'admin' WHERE `key` IN (
  'company_settings', 'company_locations', 'company_users',
  'company_roles_mgmt', 'company_teams', 'crm_stages', 'crm_integrations'
);
-- Everything still on legacy values becomes public
UPDATE `features` SET `access_level` = 'public' WHERE `access_level` IN ('core', 'subscription');

-- 5. Drop legacy ENUM values now that all rows are migrated
ALTER TABLE `features`
  MODIFY COLUMN `access_level` ENUM('public','admin','super_admin') NOT NULL DEFAULT 'public';

-- 6. Add write permission to inventory_adjustments
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'write', 'Make Adjustments' FROM `features` WHERE `key` = 'inventory_adjustments';

-- ============================================================
-- Attachments & Activities — remove as standalone features
-- Permissions now derived from parent object (lead, order, etc.)
-- ============================================================

-- Delete role_permissions for these features (in case any were granted)
DELETE rp FROM `role_permissions` rp
JOIN `permissions` p ON p.id = rp.permission_id
JOIN `features` f ON f.id = p.feature_id
WHERE f.`key` IN ('activities', 'attachments');

-- Delete permissions
DELETE p FROM `permissions` p
JOIN `features` f ON f.id = p.feature_id
WHERE f.`key` IN ('activities', 'attachments');

-- Delete features
DELETE FROM `features` WHERE `key` IN ('activities', 'attachments');


-- ============================================================
-- Role Model Simplification — drop is_system + is_super, add is_company to users
-- ============================================================

-- 1. Migrate: any is_super role becomes is_admin=1
UPDATE `company_roles` SET `is_admin` = 1 WHERE `is_super` = 1;

-- 2. Drop old flags from company_roles
ALTER TABLE `company_roles`
  DROP COLUMN `is_system`,
  DROP COLUMN `is_super`;

-- 3. Add is_company flag to users (one per company — the account owner)
ALTER TABLE `users`
  ADD COLUMN `is_company` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- 4. Mark the company owner: earliest user assigned to the Admin role per company
UPDATE `users` u
INNER JOIN (
    SELECT MIN(ur.user_id) AS user_id, ur.company_id
    FROM   `user_roles` ur
    JOIN   `company_roles` cr ON cr.id = ur.role_id AND cr.is_admin = 1
    GROUP  BY ur.company_id
) t ON t.user_id = u.id AND t.company_id = u.company_id
SET u.is_company = 1;

-- ============================================================
-- Deliveries inherit data access scope from parent Sales Order
-- Deliveries have no independent ownership — scope is derived
-- from the parent SO's salesperson_id / created_by columns.
-- ============================================================
UPDATE `features` SET `is_scopeable` = 0 WHERE `key` = 'sales_deliveries';

-- ============================================================
-- Merge inventory_stock into inventory_items
-- Both represent the same concept: viewing and adjusting stock.
-- inventory_items becomes the single permission for all stock routes.
-- ============================================================

-- 1. Add write (adjust stock) permission to inventory_items
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'write', 'Adjust Stock' FROM `features` WHERE `key` = 'inventory_items';

-- 2. Migrate existing role grants from inventory_stock → inventory_items
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `data_scope`, `created_by`)
SELECT rp.role_id, p2.id, rp.data_scope, rp.created_by
FROM `role_permissions` rp
JOIN `permissions` p  ON p.id  = rp.permission_id
JOIN `features`    f  ON f.id  = p.feature_id  AND f.key  = 'inventory_stock'
JOIN `permissions` p2 ON p2.action = p.action
JOIN `features`    f2 ON f2.id = p2.feature_id AND f2.key = 'inventory_items';

-- 3. Remove all grants for inventory_stock
DELETE rp FROM `role_permissions` rp
JOIN `permissions` p ON p.id = rp.permission_id
JOIN `features`    f ON f.id = p.feature_id AND f.key = 'inventory_stock';

-- 4. Remove inventory_stock permissions rows
DELETE p FROM `permissions` p
JOIN `features` f ON f.id = p.feature_id AND f.key = 'inventory_stock';

-- 5. Remove module_feature_map entry (FK constraint blocks feature deletion)
DELETE mfm FROM `module_feature_map` mfm
JOIN `features` f ON f.id = mfm.feature_id AND f.key = 'inventory_stock';

-- 6. Remove inventory_stock feature
DELETE FROM `features` WHERE `key` = 'inventory_stock';

-- ============================================================
-- Drop inventory_items.write — adjust stock is gated by
-- inventory_adjustments.write instead (cleaner semantics).
-- Migrate any existing grants before removing the permission.
-- ============================================================

-- 1. Migrate inventory_items.write grants → inventory_adjustments.write
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `data_scope`, `created_by`)
SELECT rp.role_id, p2.id, rp.data_scope, rp.created_by
FROM `role_permissions` rp
JOIN `permissions` p  ON p.id  = rp.permission_id
JOIN `features`    f  ON f.id  = p.feature_id  AND f.key  = 'inventory_items'      AND p.action = 'write'
JOIN `permissions` p2 ON p2.action = 'write'
JOIN `features`    f2 ON f2.id = p2.feature_id AND f2.key = 'inventory_adjustments';

-- 2. Remove inventory_items.write grants
DELETE rp FROM `role_permissions` rp
JOIN `permissions` p ON p.id = rp.permission_id
JOIN `features`    f ON f.id = p.feature_id AND f.key = 'inventory_items' AND p.action = 'write';

-- 3. Remove the inventory_items write permission row
DELETE p FROM `permissions` p
JOIN `features` f ON f.id = p.feature_id AND f.key = 'inventory_items' AND p.action = 'write';


-- ============================================================
-- System Module + Activities Feature
-- A system module is always active — no subscription required,
-- no role_module_activations entry needed. Custom roles can
-- still be granted/denied individual activity permissions.
-- ============================================================

-- 1. Add is_system flag to modules table
ALTER TABLE `modules`
  ADD COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- 2. Insert System module
INSERT INTO `modules` (`key`, `name`, `icon`, `sort_order`, `is_system`, `is_active`) VALUES
  ('system', 'System', 'bx bx-cog', 0, 1, 1);

-- 3. Insert Activities feature (linked to system module, scopeable)
INSERT INTO `features` (`module_id`, `key`, `name`, `access_level`, `is_scopeable`, `sort_order`, `is_active`)
SELECT m.id, 'activities', 'Activities', 'public', 1, 1, 1 FROM `modules` m WHERE m.`key` = 'system';

-- 4. Insert permissions for activities
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
SELECT id, 'read',          'View Activities'          FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'write',         'Create & Edit Activities' FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'delete',        'Delete Activities'        FROM `features` WHERE `key` = 'activities' UNION ALL
SELECT id, 'mark_complete', 'Mark Activity Complete'   FROM `features` WHERE `key` = 'activities';

-- ============================================================
-- Login rate limiting
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_rate_limits` (
  `id`              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ip`              VARCHAR(45)      NOT NULL,
  `attempts`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `blocked_until`   DATETIME         NULL     DEFAULT NULL,
  `last_attempt_at` DATETIME         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Password resets
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `token_hash` VARCHAR(64)     NOT NULL,
  `expires_at` DATETIME        NOT NULL,
  `used_at`    DATETIME        NULL DEFAULT NULL,
  `created_at` DATETIME        NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_id`    (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Company: legal & tax fields + branding (logo, signature)
ALTER TABLE `companies`
    ADD COLUMN `legal_name`     VARCHAR(255) DEFAULT NULL AFTER `name`,
    ADD COLUMN `website`        VARCHAR(255) DEFAULT NULL AFTER `email`,
    ADD COLUMN `gstin`          VARCHAR(15)  DEFAULT NULL AFTER `zipcode`,
    ADD COLUMN `pan`            VARCHAR(10)  DEFAULT NULL AFTER `gstin`,
    ADD COLUMN `tan`            VARCHAR(10)  DEFAULT NULL AFTER `pan`,
    ADD COLUMN `cin`            VARCHAR(21)  DEFAULT NULL AFTER `tan`,
    ADD COLUMN `logo_path`      VARCHAR(500) DEFAULT NULL AFTER `cin`,
    ADD COLUMN `signature_path` VARCHAR(500) DEFAULT NULL AFTER `logo_path`;

-- Quotation/Order separation: origin_type, quote_date, quote_sent, converted_at
ALTER TABLE `sales_orders`
    ADD COLUMN `origin_type`    ENUM('quotation','order') NOT NULL DEFAULT 'order' AFTER `lead_id`,
    ADD COLUMN `quote_date`     DATE          DEFAULT NULL AFTER `order_date`,
    ADD COLUMN `quote_sent`     TINYINT(1)    NOT NULL DEFAULT 0,
    ADD COLUMN `quote_sent_at`  DATETIME      DEFAULT NULL,
    ADD COLUMN `converted_at`   DATETIME      DEFAULT NULL;

-- Data migration: classify existing records
-- Existing draft records were all created as quotations under the old draft=quotation convention
UPDATE `sales_orders` SET `origin_type` = 'quotation', `quote_date` = `order_date` WHERE `status` = 'draft';
-- All other records were confirmed/delivered directly — treat as orders
UPDATE `sales_orders` SET `origin_type` = 'order' WHERE `status` != 'draft';
