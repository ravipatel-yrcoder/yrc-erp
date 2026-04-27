DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `related_type` enum('lead','customer','sales_order') NOT NULL,
  `related_id` bigint unsigned NOT NULL,
  `type` enum('call','email','meeting','todo') NOT NULL,
  `summary` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `due_time` time DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `note` text,
  `is_done` tinyint(1) NOT NULL DEFAULT '0',
  `done_at` datetime DEFAULT NULL,
  `outcome` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_related` (`related_type`,`related_id`),
  KEY `idx_assigned_due` (`company_id`,`assigned_to`,`is_done`,`due_date`),
  KEY `idx_company_due` (`company_id`,`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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
  `purchase_order_grn_item_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('available','quarantine') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_serial` (`company_id`,`serial_number`),
  KEY `idx_grn_item` (`purchase_order_grn_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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

-- Top-level direct links (Dashboard — no children)
INSERT INTO `menus` (parent_id, feature_id, label, icon, sort_order, is_visible)
SELECT NULL, f.id, 'Dashboard', 'bx bx-home-smile', 1, 1
FROM features f WHERE f.`key` = 'core.dashboard' LIMIT 1;

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
