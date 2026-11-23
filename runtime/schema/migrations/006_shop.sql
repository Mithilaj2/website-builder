-- Online shop (e-commerce templates). Money is stored in paise (1 rupee = 100 paise).
-- The tables exist on every website; the shop pages are switched on by templates that use them.

CREATE TABLE IF NOT EXISTS `{{prefix}}product_categories` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  description VARCHAR(400) NULL,
  image VARCHAR(255) NULL,
  status ENUM('active','hidden') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pc_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}products` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  sku VARCHAR(60) NULL,
  short_description VARCHAR(400) NULL,
  description MEDIUMTEXT NULL,
  price INT UNSIGNED NOT NULL,
  compare_price INT UNSIGNED NULL,
  gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  hsn VARCHAR(12) NULL,
  track_stock TINYINT(1) NOT NULL DEFAULT 1,
  stock INT NOT NULL DEFAULT 0,
  option_name VARCHAR(40) NULL,
  image VARCHAR(255) NULL,
  gallery_json TEXT NULL,
  badge VARCHAR(30) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','draft') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  weight_grams INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_p_slug (slug),
  KEY idx_p_category (category_id, status),
  KEY idx_p_featured (is_featured, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Options of one product (e.g. sizes S/M/L or colours). Price NULL = the product's price.
CREATE TABLE IF NOT EXISTS `{{prefix}}product_variants` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  label VARCHAR(60) NOT NULL,
  sku VARCHAR(60) NULL,
  price INT UNSIGNED NULL,
  stock INT NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_pv_product (product_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}coupons` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL,
  description VARCHAR(160) NULL,
  type ENUM('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent',
  value INT UNSIGNED NOT NULL DEFAULT 0,
  min_order INT UNSIGNED NOT NULL DEFAULT 0,
  max_discount INT UNSIGNED NULL,
  starts_at DATE NULL,
  ends_at DATE NULL,
  usage_limit INT UNSIGNED NULL,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_c_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}orders` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_no VARCHAR(20) NOT NULL,
  status ENUM('pending_payment','placed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment',
  payment_method ENUM('online','cod') NOT NULL,
  payment_status ENUM('unpaid','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
  transaction_id INT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(20) NOT NULL,
  address VARCHAR(300) NOT NULL,
  city VARCHAR(80) NOT NULL,
  state VARCHAR(60) NOT NULL,
  pincode CHAR(6) NOT NULL,
  customer_gstin VARCHAR(15) NULL,
  notes VARCHAR(500) NULL,
  subtotal INT UNSIGNED NOT NULL,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  coupon_code VARCHAR(30) NULL,
  shipping INT UNSIGNED NOT NULL DEFAULT 0,
  cod_fee INT UNSIGNED NOT NULL DEFAULT 0,
  tax INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL,
  prices_include_tax TINYINT(1) NOT NULL DEFAULT 1,
  inter_state TINYINT(1) NOT NULL DEFAULT 0,
  invoice_seq INT UNSIGNED NULL,
  invoice_no VARCHAR(30) NULL,
  invoiced_at DATETIME NULL,
  courier VARCHAR(60) NULL,
  tracking_no VARCHAR(80) NULL,
  tracking_url VARCHAR(255) NULL,
  admin_note VARCHAR(500) NULL,
  stock_reserved TINYINT(1) NOT NULL DEFAULT 0,
  emails_sent TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  paid_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_o_no (order_no),
  UNIQUE KEY uq_o_invoice (invoice_seq),
  KEY idx_o_status (status, created_at),
  KEY idx_o_email (customer_email),
  KEY idx_o_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}order_items` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  variant_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  variant_label VARCHAR(60) NULL,
  sku VARCHAR(60) NULL,
  hsn VARCHAR(12) NULL,
  unit_price INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  taxable INT UNSIGNED NOT NULL,
  tax INT UNSIGNED NOT NULL DEFAULT 0,
  line_total INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_oi_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}order_events` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  event VARCHAR(40) NOT NULL,
  detail VARCHAR(500) NULL,
  actor VARCHAR(120) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_oe_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A payment made for a shop order points to it.
ALTER TABLE `{{prefix}}payment_transactions` ADD COLUMN order_id INT UNSIGNED NULL AFTER note;
