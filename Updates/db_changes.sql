SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

SET NAMES utf8mb4;

CREATE TABLE `activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity_type` enum('lead','customer','sales_order') NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `activity_type` enum('call','email','meeting','todo') NOT NULL,
  `summary` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `due_time` time DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `description` text,
  `status` enum('pending','in_progress','completed','cancelled','skipped') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `completed_at` datetime DEFAULT NULL,
  `outcome` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_due` (`company_id`,`due_date`),
  KEY `fk_activities_completed_by` (`completed_by`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_assigned_due` (`company_id`,`assigned_to`,`status`,`due_date`),
  CONSTRAINT `fk_activities_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `entity` enum('activity','crm_lead_history','sales_order_history') NOT NULL,
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
  `legal_name` varchar(255) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `tan` varchar(10) DEFAULT NULL,
  `cin` varchar(21) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `signature_path` varchar(500) DEFAULT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `contact_email` varchar(191) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `timezone` varchar(50) DEFAULT 'UTC',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'INR',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
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


CREATE TABLE `company_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_slug` (`company_id`,`slug`),
  KEY `idx_company` (`company_id`),
  CONSTRAINT `fk_cr_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `company_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int unsigned NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_setting` (`company_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `company_subscription_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `module_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `activated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_module` (`subscription_id`,`module_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_module` (`module_id`),
  CONSTRAINT `fk_csm_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  CONSTRAINT `fk_csm_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `company_subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `company_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('trial','pilot','active','past_due','cancelled','suspended') NOT NULL DEFAULT 'trial',
  `billing_cycle` enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `agreed_base_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `agreed_extra_user_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `free_users_included` tinyint unsigned NOT NULL DEFAULT '3',
  `purchased_extra_seats` tinyint unsigned NOT NULL DEFAULT '0',
  `razorpay_customer_id` varchar(100) DEFAULT NULL,
  `razorpay_subscription_id` varchar(100) DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `pilot_until` datetime DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `notes` text,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_current` (`company_id`,`is_current`),
  KEY `idx_status` (`status`),
  KEY `idx_plan` (`plan_id`),
  CONSTRAINT `fk_cs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_cs_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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


CREATE TABLE `crm_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lead_code` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
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
  `expected_revenue` decimal(15,4) DEFAULT NULL,
  `won_revenue` decimal(15,4) DEFAULT NULL,
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


CREATE TABLE `features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int DEFAULT NULL,
  `key` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `route` varchar(191) DEFAULT NULL,
  `route_type` enum('front','api','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'front',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `access_level` enum('public','admin','super_admin') NOT NULL DEFAULT 'public',
  `is_scopeable` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module_id`)
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
  `lot_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `log_type` enum('created','reserved','reservation_released','consumed','produced','dispatched','received','returned_to_stock','location_moved','adjustment_in','adjustment_out','scrapped','status_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_lot` (`lot_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created` (`created_at`)
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_seq_company_product_type` (`company_id`,`product_id`,`sequence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `inv_serial_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `log_type` enum('created','reserved','reservation_released','consumed','produced','dispatched','received','returned_to_stock','location_moved','adjustment_in','adjustment_out','scrapped','status_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_serial` (`serial_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created` (`created_at`)
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


CREATE TABLE `login_rate_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `blocked_until` datetime DEFAULT NULL,
  `last_attempt_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `module_feature_map` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_module_feature` (`module_id`,`feature_id`),
  KEY `idx_module` (`module_id`),
  KEY `idx_feature` (`feature_id`),
  CONSTRAINT `fk_mfi_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`),
  CONSTRAINT `fk_mfi_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `password_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`)
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


CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_id` bigint unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `label` varchar(191) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm` (`feature_id`,`action`),
  KEY `idx_feature` (`feature_id`),
  CONSTRAINT `fk_perm_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`)
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
  `purchase_order_grn_id` bigint unsigned NOT NULL,
  `purchase_order_grn_item_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('available','quarantine','received') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_serial` (`company_id`,`serial_number`),
  KEY `idx_grn_item` (`purchase_order_grn_item_id`),
  KEY `idx_grn` (`purchase_order_grn_id`)
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


CREATE TABLE `role_module_activations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `module_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_module` (`role_id`,`module_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `data_scope` enum('own','team','all') NOT NULL DEFAULT 'all',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_perm` (`role_id`,`permission_id`),
  KEY `idx_role` (`role_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `company_roles` (`id`) ON DELETE CASCADE
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
  `log_type` enum('created','updated_details','updated_line_items','status_changed','dn_created','dn_updated','dn_status_changed','email_sent') NOT NULL,
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
  `order_discount_allocated` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `taxable_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
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
  `lead_id` bigint unsigned DEFAULT NULL,
  `origin_type` enum('quotation','order') NOT NULL DEFAULT 'order',
  `reference` varchar(100) DEFAULT NULL,
  `salesperson_id` bigint unsigned DEFAULT NULL,
  `price_list_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `order_date` date DEFAULT NULL,
  `quote_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `quote_sent` tinyint(1) NOT NULL DEFAULT '0',
  `quote_sent_at` datetime DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `payment_term_id` bigint unsigned DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `delivery_type` enum('pickup','ship') NOT NULL DEFAULT 'pickup',
  `status` enum('draft','confirmed','in_progress','cancelled','partially_dispatched','dispatched','partially_delivered','delivered') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'draft',
  `billing_address_snapshot` text COMMENT 'JSON snapshot of billing address at time of order',
  `shipping_address_snapshot` text COMMENT 'JSON snapshot of shipping address at time of order',
  `subtotal` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `item_discount_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `subtotal_after_item_discount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `order_discount_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount_info` json DEFAULT NULL,
  `tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `round_off_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `grand_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `adjustment_label` varchar(255) DEFAULT NULL,
  `adjustment_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
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


CREATE TABLE `subscription_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  `max_modules` tinyint unsigned DEFAULT NULL,
  `free_users_included` tinyint unsigned NOT NULL DEFAULT '3',
  `base_price_monthly` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `extra_user_price_monthly` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `subscription_seat_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `event_type` enum('add','remove') NOT NULL,
  `seats_before` tinyint unsigned NOT NULL,
  `seats_after` tinyint unsigned NOT NULL,
  `effective_at` datetime NOT NULL,
  `period_start` datetime NOT NULL,
  `period_end` datetime NOT NULL,
  `prorated_amount` decimal(15,4) DEFAULT NULL,
  `triggered_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_subscription` (`subscription_id`),
  CONSTRAINT `fk_sse_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `company_subscriptions` (`id`)
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


CREATE TABLE `team_members` (
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


CREATE TABLE `teams` (
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


CREATE TABLE `uoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`,`role_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_company` (`company_id`),
  KEY `fk_ur_role` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `company_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL DEFAULT '',
  `last_name` varchar(100) DEFAULT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `status` enum('active','inactive','banned','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'active',
  `is_company` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email_verification_expires_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


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


-- ============================================================
-- Manufacturing Module — Phase 1: BOM
-- ============================================================

CREATE TABLE `manufacturing_boms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `output_qty` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_company_product` (`company_id`,`product_id`),
  KEY `idx_default` (`company_id`,`product_id`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `manufacturing_bom_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `bom_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `qty` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `product_uom_id` bigint unsigned DEFAULT NULL,
  `uom_code` varchar(10) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bom` (`bom_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_mbi_bom` FOREIGN KEY (`bom_id`) REFERENCES `manufacturing_boms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Seed: manufacturing module
INSERT INTO `modules` (`key`, `name`, `description`, `icon`, `sort_order`, `is_active`, `is_system`)
VALUES ('manufacturing', 'Manufacturing', 'Bill of Materials and Production Orders', 'bx-factory', 7, 1, 0);

-- Seed: manufacturing_boms feature
INSERT INTO `features` (`module_id`, `key`, `name`, `description`, `route`, `route_type`, `is_active`, `access_level`, `sort_order`)
VALUES (
  (SELECT id FROM modules WHERE `key` = 'manufacturing'),
  'manufacturing_boms', 'Bill of Materials', 'Manage Bills of Materials', '/manufacturing/boms', 'both', 1, 'public', 1
);

-- Seed: module_feature_map
INSERT INTO `module_feature_map` (`module_id`, `feature_id`)
VALUES (
  (SELECT id FROM modules WHERE `key` = 'manufacturing'),
  (SELECT id FROM features WHERE `key` = 'manufacturing_boms')
);

-- Seed: permissions for manufacturing_boms
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
VALUES
  ((SELECT id FROM features WHERE `key` = 'manufacturing_boms'), 'read',   'View BOMs'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_boms'), 'write',  'Create / Edit BOMs'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_boms'), 'delete', 'Delete BOMs');

-- Add manufacturing module to all existing company subscriptions
INSERT IGNORE INTO `company_subscription_modules` (`company_id`, `subscription_id`, `module_id`)
SELECT cs.company_id, cs.id, m.id
FROM company_subscriptions cs
JOIN modules m ON m.`key` = 'manufacturing'
WHERE cs.is_current = 1;

-- ============================================================
-- Manufacturing Module — Phase 2: Production Orders
-- ============================================================

CREATE TABLE `manufacturing_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `mo_number` varchar(20) NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `bom_id` bigint unsigned NOT NULL,
  `bom_name` varchar(100) NOT NULL,
  `planned_qty` decimal(15,4) NOT NULL,
  `produced_qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `planned_date` date DEFAULT NULL,
  `status` enum('draft','confirmed','materials_allocated','in_production','completed','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `track_serial_genealogy` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `confirmed_by` bigint unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mo_number` (`company_id`,`mo_number`),
  KEY `idx_company` (`company_id`),
  KEY `idx_company_product` (`company_id`,`product_id`),
  KEY `idx_status` (`company_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `manufacturing_order_material_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `planned_qty` decimal(15,4) NOT NULL,
  `actual_qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `product_uom_id` bigint unsigned DEFAULT NULL,
  `uom_code` varchar(10) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `manufacturing_order_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`manufacturing_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Seed: manufacturing_orders feature
INSERT INTO `features` (`module_id`, `key`, `name`, `description`, `route`, `route_type`, `is_active`, `access_level`, `sort_order`)
VALUES (
  (SELECT id FROM modules WHERE `key` = 'manufacturing'),
  'manufacturing_orders', 'Manufacturing Orders', 'Manage Production Orders', '/manufacturing/orders', 'both', 1, 'public', 2
);

-- Seed: module_feature_map
INSERT INTO `module_feature_map` (`module_id`, `feature_id`)
VALUES (
  (SELECT id FROM modules WHERE `key` = 'manufacturing'),
  (SELECT id FROM features WHERE `key` = 'manufacturing_orders')
);

-- Seed: permissions for manufacturing_orders
INSERT INTO `permissions` (`feature_id`, `action`, `label`)
VALUES
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'read',    'View Manufacturing Orders'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'write',   'Create Manufacturing Orders'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'confirm', 'Confirm Manufacturing Orders'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'cancel',  'Cancel Manufacturing Orders');



-- Manufacturing Orders: add source_location_id for per-location stock reservation
ALTER TABLE `manufacturing_orders`
  ADD COLUMN `source_location_id` BIGINT UNSIGNED NULL AFTER `bom_name`,
  ADD KEY `idx_location` (`source_location_id`);

-- Manufacturing Orders: revised status enum + new columns for allocation_status, destination, origin
ALTER TABLE `manufacturing_orders`
  MODIFY COLUMN `status` ENUM('draft','confirmed','in_production','completed','cancelled') NOT NULL DEFAULT 'draft',
  ADD COLUMN `allocation_status` ENUM('not_allocated','partially_allocated','fully_allocated') NOT NULL DEFAULT 'not_allocated' AFTER `status`,
  ADD COLUMN `destination_location_id` BIGINT UNSIGNED NULL AFTER `source_location_id`,
  ADD COLUMN `origin_type` ENUM('manual','sales_order','mrp_plan') NOT NULL DEFAULT 'manual' AFTER `notes`,
  ADD COLUMN `origin_id` BIGINT UNSIGNED NULL AFTER `origin_type`;

-- Manufacturing Order History: align with sales_order_history structure
-- Step 1: Add title new column
ALTER TABLE `manufacturing_order_history`
  ADD COLUMN `title` VARCHAR(255) NULL AFTER `action`;
-- Step 2: fill any NULL titles before making NOT NULL
UPDATE `manufacturing_order_history` SET `title` = `action` WHERE `title` IS NULL OR `title` = '';
-- Step 3: rename action → log_type, make title NOT NULL, drop notes, add reference + meta columns
ALTER TABLE `manufacturing_order_history`
  CHANGE COLUMN `action` `log_type` VARCHAR(50) NOT NULL,
  MODIFY COLUMN `title` VARCHAR(255) NOT NULL,
  DROP COLUMN `notes`,
  ADD COLUMN `reference_type` VARCHAR(50) DEFAULT NULL AFTER `title`,
  ADD COLUMN `reference_id` BIGINT UNSIGNED DEFAULT NULL AFTER `reference_type`,
  ADD COLUMN `meta` JSON DEFAULT NULL AFTER `reference_id`;

-- Phase 3: Material Allocation tables
CREATE TABLE `manufacturing_order_material_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracks allocated qty for non-serial-tracked components per allocation event
CREATE TABLE `manufacturing_order_material_allocation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `allocation_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `material_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `allocated_qty` decimal(15,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_allocation` (`allocation_id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_material_item` (`material_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `manufacturing_order_material_allocation_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `allocation_item_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `manufacturing_order_output_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_allocation_item` (`allocation_item_id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_output` (`manufacturing_order_output_id`),
  KEY `idx_serial` (`serial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If re-running on existing data: ALTER TABLE `manufacturing_order_material_allocation_serials`
--   DROP COLUMN `allocation_id`, DROP COLUMN `material_item_id`,
--   ADD COLUMN `allocation_item_id` bigint unsigned NOT NULL AFTER `company_id`,
--   ADD KEY `idx_allocation_item` (`allocation_item_id`);

-- Phase 4a: Output Recording
CREATE TABLE `manufacturing_order_outputs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `output_qty` decimal(15,4) NOT NULL,
  `destination_location_id` bigint unsigned NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `manufacturing_order_output_consumptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `output_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `material_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `consumed_qty` decimal(15,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_output` (`output_id`),
  KEY `idx_material_item` (`material_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phase 4b: Serial consumption tracking
ALTER TABLE `manufacturing_order_output_consumptions`
  ADD COLUMN `serial_id` bigint unsigned DEFAULT NULL AFTER `consumed_qty`,
  ADD KEY `idx_serial` (`serial_id`);

-- Phase 4c: Finished goods serial tracking
CREATE TABLE `manufacturing_order_output_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `output_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_output` (`output_id`),
  KEY `idx_serial` (`serial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phase 3 (corrective): seed allocate + produce permissions that were added directly to DB but missed from this file
INSERT IGNORE INTO `permissions` (`feature_id`, `action`, `label`)
VALUES
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'allocate', 'Allocate Materials'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'produce',  'Record Production / Force Complete');

-- Phase 3 (corrective): rename allocate → material_allocation on existing rows
UPDATE `permissions`
SET `action` = 'material_allocation', `label` = 'Allocate Materials'
WHERE `action` = 'allocate'
  AND `feature_id` = (SELECT id FROM features WHERE `key` = 'manufacturing_orders');

-- Phase 5: seed produce + material_return for fresh installs (INSERT IGNORE is safe on existing DBs)
INSERT IGNORE INTO `permissions` (`feature_id`, `action`, `label`)
VALUES
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'material_allocation', 'Allocate Materials'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'produce',             'Record Production / Force Complete'),
  ((SELECT id FROM features WHERE `key` = 'manufacturing_orders'), 'material_return',     'Return Materials to Warehouse');

-- Phase 5: Post-Production Material Return
CREATE TABLE `manufacturing_order_material_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `manufacturing_order_material_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `return_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `material_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `returned_qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_return` (`return_id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_material_item` (`material_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `manufacturing_order_material_return_serials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `return_item_id` bigint unsigned NOT NULL,
  `manufacturing_order_id` bigint unsigned NOT NULL,
  `material_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `serial_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_return_item` (`return_item_id`),
  KEY `idx_order` (`manufacturing_order_id`),
  KEY `idx_serial` (`serial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phase 5: add production_return to inv_stock_movements movement_type ENUM
ALTER TABLE `inv_stock_movements`
  MODIFY COLUMN `movement_type` ENUM('adjust_in','adjust_out','transfer_in','transfer_out','purchase_receipt','sale','return_from_customer','return_to_supplier','consume','produce','production_return','scrap') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL;

-- Phase 6: Migrate inv_serial_history to event-based schema
-- Old schema had: changed_field, old_value, new_value, reason, changed_by + idx_field index
ALTER TABLE `inv_serial_history`
  DROP COLUMN `changed_field`,
  DROP COLUMN `old_value`,
  DROP COLUMN `new_value`,
  DROP COLUMN `reason`,
  CHANGE COLUMN `changed_by` `created_by` bigint unsigned DEFAULT NULL,
  ADD COLUMN `log_type` enum('created','reserved','reservation_released','consumed','produced','dispatched','received','returned_to_stock','location_moved','adjustment_in','adjustment_out','scrapped','status_changed') NOT NULL AFTER `serial_id`,
  ADD COLUMN `title` varchar(255) NOT NULL AFTER `log_type`,
  ADD COLUMN `meta` json DEFAULT NULL AFTER `title`,
  DROP INDEX `idx_field`,
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created` (`created_at`);

-- Phase 6: Migrate inv_lot_history to event-based schema
-- Table was unused (no existing data); drop-and-recreate is safe
DROP TABLE IF EXISTS `inv_lot_history`;
CREATE TABLE `inv_lot_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `lot_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `log_type` enum('created','reserved','reservation_released','consumed','produced','dispatched','received','returned_to_stock','location_moved','adjustment_in','adjustment_out','scrapped','status_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_lot` (`lot_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE purchase_orders ADD COLUMN currency_code VARCHAR(10) NOT NULL DEFAULT 'INR' AFTER vendor_id;
