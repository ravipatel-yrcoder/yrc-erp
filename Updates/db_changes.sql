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
  `currency` varchar(10) DEFAULT 'USD',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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


CREATE TABLE `crm_lead_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `log_type` enum('created','note','stage_change','activity_done','conversion','system') NOT NULL,
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


CREATE TABLE `crm_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lead_code` varchar(50) NOT NULL,
  `stage_id` bigint unsigned DEFAULT NULL,
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
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2026-04-08: attachments for activities and crm lead notes
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


-- 2026-04-08: add sort_order to crm_leads for persistent kanban ordering
ALTER TABLE `crm_leads`
    ADD COLUMN `sort_order` int unsigned NOT NULL DEFAULT 0 AFTER `stage_id`,
    ADD KEY `idx_company_stage_sort` (`company_id`, `stage_id`, `sort_order`);


-- 2026-04-09: expand crm_lead_history log_type enum — add specific conversion types,
--             remove unused 'conversion' placeholder, add types used by service layer
ALTER TABLE `crm_lead_history`
    MODIFY COLUMN `log_type` enum(
        'created',
        'note',
        'stage_change',
        'activity_done',
        'system',
        'updated_notes',
        'updated_details',
        'assigned_changed',
        'converted_to_customer',
        'linked_to_customer',
        'quotation_created',
        'quotation_confirmed',
        'quotation_cancelled'
    ) NOT NULL;

-- 2026-04-09: link sales_orders back to a CRM lead (nullable)
ALTER TABLE `sales_orders`
    ADD COLUMN `lead_id` bigint unsigned DEFAULT NULL AFTER `customer_id`,
    ADD KEY `idx_lead_id` (`lead_id`);


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


CREATE TABLE `sales_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `so_number` varchar(50) NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
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
  KEY `idx_so_order_date` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `sequence_key` varchar(50) NOT NULL,
  `pattern` varchar(20) DEFAULT NULL,
  `padding` int NOT NULL DEFAULT '6',
  `last_number` bigint unsigned NOT NULL DEFAULT '0',
  `reset_period` enum('none','monthly','yearly') NOT NULL DEFAULT 'none',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_sequence_key` (`sequence_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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


CREATE TABLE `uoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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


-- Webhook integrations: one row per company+source, stores the company token
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


-- Webhook logs: raw audit trail of every inbound webhook call
CREATE TABLE `webhook_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` int unsigned DEFAULT NULL COMMENT 'NULL when token does not match any integration',
  `company_id` int unsigned DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `token` varchar(64) NOT NULL,
  `http_method` varchar(10) NOT NULL,
  `headers` json DEFAULT NULL COMMENT 'sanitised inbound request headers',
  `raw_payload` longtext DEFAULT NULL COMMENT 'exact body as received',
  `parsed_payload` json DEFAULT NULL COMMENT 'normalised payload after source adapter runs',
  `status` enum('received','processing','processed','failed','ignored') NOT NULL DEFAULT 'received',
  `failure_reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_company_source` (`company_id`,`source`),
  KEY `idx_status` (`status`),
  KEY `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;