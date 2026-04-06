#Track all DB changes here


# ============================================================
# SALES MODULE — Phase 1A
# Date: 2026-03-11
# ============================================================


# ------------------------------------------------------------
# customer_groups
# Segments customers for pricing, reporting, and discounts.
# ------------------------------------------------------------
CREATE TABLE `customer_groups` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`  INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`  INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME DEFAULT NULL,
  `updated_at`  DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cg_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# customers
# Core customer record. Mirrors vendors table conventions.
# ------------------------------------------------------------
CREATE TABLE `customers` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`       INT UNSIGNED NOT NULL,
  `customer_code`    VARCHAR(50) DEFAULT NULL,
  `customer_group_id` INT UNSIGNED DEFAULT NULL,
  `customer_type`    ENUM('company','individual') NOT NULL DEFAULT 'company',
  `legal_name`       VARCHAR(255) NOT NULL,
  `display_name`     VARCHAR(255) NOT NULL,
  `email`            VARCHAR(255) DEFAULT NULL,
  `phone`            VARCHAR(50) DEFAULT NULL,
  `website`          VARCHAR(255) DEFAULT NULL,
  `pan`              VARCHAR(20) DEFAULT NULL,
  `gstin`            VARCHAR(20) DEFAULT NULL,
  `currency_code`    VARCHAR(10) NOT NULL DEFAULT 'INR',
  `payment_term_id`  INT UNSIGNED DEFAULT NULL,
  `credit_limit`     DECIMAL(15,4) DEFAULT NULL,
  `price_list_id`    INT UNSIGNED DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`       INT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME DEFAULT NULL,
  `updated_at`       DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customers_company`      (`company_id`),
  KEY `idx_customers_group`        (`customer_group_id`),
  KEY `idx_customers_price_list`   (`price_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# 2026-03-12: Add salutation, first_name, last_name to customers
#             Rename legal_name to company_name
# ------------------------------------------------------------
ALTER TABLE `customers`
    ADD COLUMN `salutation`  VARCHAR(20)  NULL AFTER `customer_type`,
    ADD COLUMN `first_name`  VARCHAR(100) NULL AFTER `salutation`,
    ADD COLUMN `last_name`   VARCHAR(100) NULL AFTER `first_name`;

ALTER TABLE `customers` RENAME COLUMN `legal_name` TO `company_name`;


# ------------------------------------------------------------
# customer_addresses
# Multiple addresses per customer (billing, shipping, etc.)
# ------------------------------------------------------------
CREATE TABLE `customer_addresses` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `customer_id`   INT UNSIGNED NOT NULL,
  `address_type`  ENUM('billing','shipping','other') NOT NULL DEFAULT 'billing',
  `label`         VARCHAR(100) DEFAULT NULL,
  `attention`     VARCHAR(255) DEFAULT NULL,
  `phone`         VARCHAR(50) DEFAULT NULL,
  `address_line1` VARCHAR(255) DEFAULT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city`          VARCHAR(100) DEFAULT NULL,
  `state`         VARCHAR(100) DEFAULT NULL,
  `postal_code`   VARCHAR(20) DEFAULT NULL,
  `country`       VARCHAR(10) NOT NULL DEFAULT 'IN',
  `is_default`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME DEFAULT NULL,
  `updated_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ca_company_customer` (`company_id`, `customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# customer_contacts
# Named contacts at a customer company.
# ------------------------------------------------------------
CREATE TABLE `customer_contacts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`  INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `first_name`  VARCHAR(100) NOT NULL,
  `last_name`   VARCHAR(100) DEFAULT NULL,
  `title`       VARCHAR(50) DEFAULT NULL,
  `email`       VARCHAR(255) DEFAULT NULL,
  `phone`       VARCHAR(50) DEFAULT NULL,
  `is_primary`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_by`  INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME DEFAULT NULL,
  `updated_at`  DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cc_company_customer` (`company_id`, `customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# price_lists
# Named price lists assigned to customers or customer groups.
# ------------------------------------------------------------
CREATE TABLE `price_lists` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `name`          VARCHAR(100) NOT NULL,
  `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `description`   TEXT DEFAULT NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME DEFAULT NULL,
  `updated_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pl_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# price_list_items
# Per-product pricing overrides within a price list.
# ------------------------------------------------------------
CREATE TABLE `price_list_items` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `price_list_id` INT UNSIGNED NOT NULL,
  `product_id`    INT UNSIGNED NOT NULL,
  `unit_price`    DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `min_qty`       DECIMAL(15,4) NOT NULL DEFAULT 1.0000,
  `created_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME DEFAULT NULL,
  `updated_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pli_list_product` (`price_list_id`, `product_id`),
  KEY `idx_pli_company_list` (`company_id`, `price_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_orders
# Header record for a customer sales order.
# ------------------------------------------------------------
CREATE TABLE `sales_orders` (
  `id`                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`                INT UNSIGNED NOT NULL,
  `so_number`                 VARCHAR(50) NOT NULL,
  `customer_id`               INT UNSIGNED NOT NULL,
  `customer_po_number`        VARCHAR(100) DEFAULT NULL,
  `salesperson_id`            INT UNSIGNED DEFAULT NULL,
  `price_list_id`             INT UNSIGNED DEFAULT NULL,
  `location_id`               INT UNSIGNED NOT NULL,
  `order_date`                DATE NOT NULL,
  `valid_until`               DATE DEFAULT NULL,
  `expected_delivery_date`    DATE DEFAULT NULL,
  `payment_term_id`           INT UNSIGNED DEFAULT NULL,
  `status`                    ENUM('draft','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'draft',
  `billing_address_snapshot`  TEXT DEFAULT NULL COMMENT 'JSON snapshot of billing address at time of order',
  `shipping_address_snapshot` TEXT DEFAULT NULL COMMENT 'JSON snapshot of shipping address at time of order',
  `subtotal`                  DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `discount_amount`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `tax_amount`                DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `total_amount`              DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `notes`                     TEXT DEFAULT NULL,
  `internal_notes`            TEXT DEFAULT NULL,
  `created_by`                INT UNSIGNED DEFAULT NULL,
  `created_at`                DATETIME DEFAULT NULL,
  `updated_at`                DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_so_number` (`company_id`, `so_number`),
  KEY `idx_so_company`    (`company_id`),
  KEY `idx_so_customer`   (`customer_id`),
  KEY `idx_so_status`     (`status`),
  KEY `idx_so_order_date` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_order_items
# Line items for a sales order.
# ------------------------------------------------------------
CREATE TABLE `sales_order_items` (
  `id`                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id`            INT UNSIGNED NOT NULL,
  `product_id`                INT UNSIGNED NOT NULL,
  `description`               TEXT DEFAULT NULL,
  `ordered_qty`               DECIMAL(15,4) NOT NULL DEFAULT 1.0000,
  `delivered_qty`             DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `product_uom_id`            INT UNSIGNED DEFAULT NULL,
  `uom_code`                  VARCHAR(20) DEFAULT NULL,
  `unit_price`                DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `discount_percent`          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `tax_amount`                DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `tax_info`                  JSON DEFAULT NULL,
  `line_total`                DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `line_status`               ENUM('pending','partial','fulfilled') NOT NULL DEFAULT 'pending',
  `created_by`                INT UNSIGNED DEFAULT NULL,
  `created_at`                DATETIME DEFAULT NULL,
  `updated_at`                DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_order`   (`sales_order_id`),
  KEY `idx_soi_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_order_history
# Audit log for sales order events.
# ------------------------------------------------------------
CREATE TABLE `sales_order_history` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `sales_order_id` INT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `title`         VARCHAR(255) DEFAULT NULL,
  `meta`          JSON DEFAULT NULL,
  `created_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soh_order`   (`sales_order_id`),
  KEY `idx_soh_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_deliveries
# Dispatch note / delivery note header. One SO → many DNs.
# ------------------------------------------------------------
CREATE TABLE `sales_deliveries` (
  `id`                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`                INT UNSIGNED NOT NULL,
  `dn_number`                 VARCHAR(50) NOT NULL,
  `sales_order_id`            INT UNSIGNED NOT NULL,
  `customer_id`               INT UNSIGNED NOT NULL,
  `location_id`               INT UNSIGNED NOT NULL,
  `back_order_of`             INT UNSIGNED DEFAULT NULL COMMENT 'FK to sales_deliveries.id if this is a back-order',
  `status`                    ENUM('draft','dispatched','delivered','cancelled') NOT NULL DEFAULT 'draft',
  `dispatch_date`             DATE DEFAULT NULL,
  `delivery_date`             DATE DEFAULT NULL,
  `carrier`                   VARCHAR(100) DEFAULT NULL,
  `tracking_number`           VARCHAR(100) DEFAULT NULL,
  `shipping_address_snapshot` TEXT DEFAULT NULL COMMENT 'JSON snapshot of shipping address',
  `notes`                     TEXT DEFAULT NULL,
  `created_by`                INT UNSIGNED DEFAULT NULL,
  `created_at`                DATETIME DEFAULT NULL,
  `updated_at`                DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dn_number`     (`company_id`, `dn_number`),
  KEY `idx_sd_company`          (`company_id`),
  KEY `idx_sd_sales_order`      (`sales_order_id`),
  KEY `idx_sd_customer`         (`customer_id`),
  KEY `idx_sd_status`           (`status`),
  KEY `idx_sd_back_order_of`    (`back_order_of`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_delivery_items
# Line items for a delivery note. Refs SO line items.
# ------------------------------------------------------------
CREATE TABLE `sales_delivery_items` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_delivery_id`   INT UNSIGNED NOT NULL,
  `sales_order_item_id` INT UNSIGNED NOT NULL,
  `product_id`          INT UNSIGNED NOT NULL,
  `description`         TEXT DEFAULT NULL,
  `dispatched_qty`      DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `uom_code`            VARCHAR(20) DEFAULT NULL,
  `created_by`          INT UNSIGNED DEFAULT NULL,
  `created_at`          DATETIME DEFAULT NULL,
  `updated_at`          DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdi_delivery`      (`sales_delivery_id`),
  KEY `idx_sdi_so_item`       (`sales_order_item_id`),
  KEY `idx_sdi_product`       (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_delivery_item_serials
# Serial numbers dispatched on a delivery line.
# ------------------------------------------------------------
CREATE TABLE `sales_delivery_item_serials` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`             INT UNSIGNED NOT NULL,
  `sales_delivery_id`      INT UNSIGNED NOT NULL,
  `sales_delivery_item_id` INT UNSIGNED NOT NULL,
  `serial_id`              INT UNSIGNED NOT NULL,
  `serial_number`          VARCHAR(100) NOT NULL,
  `created_at`             DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdis_delivery` (`sales_delivery_id`),
  KEY `idx_sdis_item`     (`sales_delivery_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_delivery_item_lots
# Lot numbers dispatched on a delivery line.
# ------------------------------------------------------------
CREATE TABLE `sales_delivery_item_lots` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`             INT UNSIGNED NOT NULL,
  `sales_delivery_id`      INT UNSIGNED NOT NULL,
  `sales_delivery_item_id` INT UNSIGNED NOT NULL,
  `lot_number`             VARCHAR(100) NOT NULL,
  `qty`                    DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `created_at`             DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdil_delivery` (`sales_delivery_id`),
  KEY `idx_sdil_item`     (`sales_delivery_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# sales_delivery_history
# Audit log for delivery note events.
# ------------------------------------------------------------
CREATE TABLE `sales_delivery_history` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`        INT UNSIGNED NOT NULL,
  `sales_delivery_id` INT UNSIGNED NOT NULL,
  `activity_type`     VARCHAR(50) NOT NULL,
  `title`             VARCHAR(255) DEFAULT NULL,
  `meta`              JSON DEFAULT NULL,
  `created_by`        INT UNSIGNED DEFAULT NULL,
  `created_at`        DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sdh_delivery` (`sales_delivery_id`),
  KEY `idx_sdh_company`  (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ============================================================
# SEQUENCES — Sales Module
# Handled in Service_Sequence::lockAndFetchPattern() and ::sequenceExists().
#   sales_orders     → pattern = 'SO', padding = 6  → SO000001
#   sales_deliveries → pattern = 'DN', padding = 6  → DN000001
# ============================================================


# ------------------------------------------------------------
# 2026-03-12: Discount redesign for sales_order_items
#   - Drop discount_percent (replaced by discount_info JSON)
#   - Add discount_info JSON (stores type + raw value for display/re-editing)
#   - discount_amount column remains (stores calculated ₹ deduction)
# ------------------------------------------------------------
ALTER TABLE `sales_order_items`
  DROP COLUMN `discount_percent`,
  ADD COLUMN `discount_info` JSON DEFAULT NULL AFTER `discount_amount`;


# ------------------------------------------------------------
# 2026-03-12: Order-level discount metadata for sales_orders
#   - discount_amount column already exists (stores calculated ₹ deduction)
#   - Add discount_info JSON (stores type + raw value)
# ------------------------------------------------------------
ALTER TABLE `sales_orders`
  ADD COLUMN `discount_info` JSON DEFAULT NULL AFTER `discount_amount`;


# ------------------------------------------------------------
# 2026-03-12: Add payment_terms snapshot column to sales_orders
#   - payment_term_id (INT FK) — used for validation & reference
#   - payment_terms (VARCHAR) — snapshot of term name at time of order
# ------------------------------------------------------------
ALTER TABLE `sales_orders` ADD COLUMN `payment_terms` VARCHAR(100) DEFAULT NULL AFTER `payment_term_id`;


# ------------------------------------------------------------
# 2026-03-13: Rename customer_po_number to reference in sales_orders
#   - More generic: covers internal refs, customer PO#, contract #, etc.
# ------------------------------------------------------------
ALTER TABLE `sales_orders` RENAME COLUMN `customer_po_number` TO `reference`;


# ------------------------------------------------------------
# 2026-03-13: Rename available_qty to on_hand_qty in inv_product_stock
#   - Clarifies semantics: on_hand_qty = total physical stock
#   - available to sell = on_hand_qty - reserved_qty (computed)
#   - reserved_qty already exists; now properly used for SO confirmations
# ------------------------------------------------------------
ALTER TABLE `inv_product_stock`
  RENAME COLUMN `available_qty` TO `on_hand_qty`;


# ------------------------------------------------------------
# 2026-03-22: Extend sales_orders status ENUM for delivery tracking
#   - partially_dispatched: some DNs dispatched, not all items covered
#   - dispatched:           all items dispatched (not yet confirmed received)
#   - partially_delivered:  some items confirmed received by customer
#   - delivered:            all items confirmed received by customer
# ------------------------------------------------------------
ALTER TABLE `sales_orders`
  MODIFY COLUMN `status` ENUM(
    'draft',
    'confirmed',
    'cancelled',
    'partially_dispatched',
    'dispatched',
    'partially_delivered',
    'delivered'
  ) NOT NULL DEFAULT 'draft';


# ------------------------------------------------------------
# 2026-03-22: DN (Delivery Note) sequence — one row per company
#   Pattern: DN + 6-digit padding → DN000001
#   Apply after running: INSERT below for each existing company
# ------------------------------------------------------------
# Run per company (replace <company_id> with actual id):
# INSERT INTO `sequences` (company_id, entity, pattern, padding, last_number, created_at)
#   VALUES (<company_id>, 'sales_deliveries', 'DN', 6, 0, NOW())
#   ON DUPLICATE KEY UPDATE entity = entity;
#
# Or bulk insert for all existing companies:
INSERT INTO `sequences` (company_id, entity, pattern, padding, last_number, created_at)
  SELECT id, 'sales_deliveries', 'DN', 6, 0, NOW() FROM companies
  ON DUPLICATE KEY UPDATE entity = entity;


# ============================================================
# CRM MODULE
# Date: 2026-04-06
# ============================================================


# ------------------------------------------------------------
# crm_stages
# Configurable pipeline stages per company.
# Default stages seeded on first CRM access.
# ------------------------------------------------------------
CREATE TABLE `crm_stages` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`   BIGINT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `probability`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_won`       TINYINT(1) NOT NULL DEFAULT 0,
  `is_lost`      TINYINT(1) NOT NULL DEFAULT 0,
  `color`        VARCHAR(20) DEFAULT NULL,
  `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME DEFAULT NULL,
  `updated_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_sort` (`company_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# crm_leads
# Unified lead/opportunity entity. Moves through pipeline stages.
# Converts to customer when qualified.
# ------------------------------------------------------------
CREATE TABLE `crm_leads` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`       BIGINT UNSIGNED NOT NULL,
  `lead_code`        VARCHAR(50) NOT NULL,

  -- Pipeline
  `stage_id`         BIGINT UNSIGNED DEFAULT NULL,
  `status`           ENUM('active','won','lost') NOT NULL DEFAULT 'active',
  `probability`      TINYINT UNSIGNED NOT NULL DEFAULT 10,

  -- Contact Info
  `salutation`       VARCHAR(20) DEFAULT NULL,
  `first_name`       VARCHAR(100) NOT NULL,
  `last_name`        VARCHAR(100) DEFAULT NULL,
  `company_name`     VARCHAR(255) DEFAULT NULL,
  `display_name`     VARCHAR(255) NOT NULL,
  `job_title`        VARCHAR(100) DEFAULT NULL,
  `email`            VARCHAR(255) DEFAULT NULL,
  `phone`            VARCHAR(50) DEFAULT NULL,
  `website`          VARCHAR(255) DEFAULT NULL,

  -- Address
  `address_line1`    VARCHAR(255) DEFAULT NULL,
  `address_line2`    VARCHAR(255) DEFAULT NULL,
  `city`             VARCHAR(100) DEFAULT NULL,
  `state`            VARCHAR(100) DEFAULT NULL,
  `postal_code`      VARCHAR(20) DEFAULT NULL,
  `country`          VARCHAR(10) DEFAULT 'IN',

  -- Deal Info
  `expected_revenue` DECIMAL(15,2) DEFAULT NULL,
  `expected_close_date` DATE DEFAULT NULL,
  `source`           VARCHAR(100) DEFAULT NULL,
  `priority`         ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `tags`             JSON DEFAULT NULL,

  -- Assignment
  `assigned_to`      BIGINT UNSIGNED DEFAULT NULL,

  -- Customer Link
  `customer_id`      BIGINT UNSIGNED DEFAULT NULL,
  `converted_at`     DATETIME DEFAULT NULL,

  -- Closure
  `lost_reason`      VARCHAR(255) DEFAULT NULL,
  `closed_at`        DATETIME DEFAULT NULL,

  -- Notes
  `notes`            TEXT DEFAULT NULL,

  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`       BIGINT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME DEFAULT NULL,
  `updated_at`       DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lead_code` (`company_id`, `lead_code`),

  KEY `idx_stage` (`company_id`, `stage_id`),
  KEY `idx_status` (`company_id`, `status`),
  KEY `idx_assigned` (`company_id`, `assigned_to`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# crm_lead_history
# Chatter / audit log for leads.
# ------------------------------------------------------------
CREATE TABLE `crm_lead_history` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`   BIGINT UNSIGNED NOT NULL,
  `lead_id`      BIGINT UNSIGNED NOT NULL,
  `log_type`     ENUM(
                    'created',
                    'note',
                    'stage_change',
                    'activity_done',
                    'conversion',
                    'system'
                  ) NOT NULL,
  `title`        VARCHAR(255) DEFAULT NULL,
  `meta`         JSON DEFAULT NULL,
  `created_by`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# activities
# Generic activity table — polymorphic, reusable across modules.
# ------------------------------------------------------------
CREATE TABLE `activities` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`      BIGINT UNSIGNED NOT NULL,

  `related_type`    ENUM('lead','customer','sales_order') NOT NULL,
  `related_id`      BIGINT UNSIGNED NOT NULL,

  `type`            ENUM('call','email','meeting','todo') NOT NULL,
  `summary`         VARCHAR(255) NOT NULL,

  `due_date`        DATE NOT NULL,
  `due_time`        TIME DEFAULT NULL,

  `assigned_to`     BIGINT UNSIGNED DEFAULT NULL,

  `note`            TEXT DEFAULT NULL,
  `is_done`         TINYINT(1) NOT NULL DEFAULT 0,
  `done_at`         DATETIME DEFAULT NULL,
  `outcome`         TEXT DEFAULT NULL,

  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME DEFAULT NULL,
  `updated_at`      DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),

  KEY `idx_related` (`related_type`, `related_id`),
  KEY `idx_assigned_due` (`company_id`, `assigned_to`, `is_done`, `due_date`),
  KEY `idx_company_due` (`company_id`, `due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


# ------------------------------------------------------------
# CRM Lead sequence — one row per company
#   Pattern: LD + 6-digit padding → LD000001
# ------------------------------------------------------------
INSERT INTO `sequences` (company_id, entity, pattern, padding, last_number, created_at)
  SELECT id, 'crm_leads', 'LD', 6, 0, NOW() FROM companies
  ON DUPLICATE KEY UPDATE entity = entity;
