-- Listings: the main items of a template's category (properties, tour packages, rooms, projects…).
-- Their fields ("attributes") are defined by the template manifest and stored as JSON. Money in paise.

CREATE TABLE IF NOT EXISTS `{{prefix}}listing_categories` (
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
  UNIQUE KEY uq_lc_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{prefix}}listings` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  location VARCHAR(120) NULL,
  price BIGINT UNSIGNED NULL,
  price_note VARCHAR(40) NULL,
  short_description VARCHAR(400) NULL,
  description MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  gallery_json TEXT NULL,
  attributes_json TEXT NULL,
  badge VARCHAR(30) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','closed','draft') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_l_slug (slug),
  KEY idx_l_status (status, is_featured),
  KEY idx_l_category (category_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
