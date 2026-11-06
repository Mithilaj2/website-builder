-- Channel Softech Website Builder — builder (master) schema
-- Compatible with MySQL 5.7+ and MariaDB 10.3+. JSON is stored as LONGTEXT for portability.
-- Site tables (prefix sNNNNNN_) are NOT defined here; they come from runtime module schemas.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS wb_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','admin','designer','viewer') NOT NULL DEFAULT 'viewer',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_attempts_ip_time (ip, attempted_at),
  KEY idx_attempts_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value LONGTEXT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(60) NOT NULL,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  code VARCHAR(80) NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  preview_image VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
  current_version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
  features LONGTEXT NULL,
  folder_path VARCHAR(255) NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_templates_code (code),
  KEY idx_templates_category (category_id),
  CONSTRAINT fk_templates_category FOREIGN KEY (category_id) REFERENCES wb_categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_template_versions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  template_id INT UNSIGNED NOT NULL,
  version VARCHAR(20) NOT NULL,
  manifest_hash CHAR(64) NOT NULL,
  changelog TEXT NULL,
  archive_path VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_tv_template (template_id),
  CONSTRAINT fk_tv_template FOREIGN KEY (template_id) REFERENCES wb_templates (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_template_screens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  template_id INT UNSIGNED NOT NULL,
  image VARCHAR(255) NOT NULL,
  caption VARCHAR(190) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ts_template (template_id),
  CONSTRAINT fk_ts_template FOREIGN KEY (template_id) REFERENCES wb_templates (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_clients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_name VARCHAR(190) NOT NULL,
  contact_person VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  notes TEXT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_clients_name (business_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_websites (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  site_code CHAR(6) NULL,
  client_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  template_id INT UNSIGNED NULL,
  template_version VARCHAR(20) NULL,
  runtime_version VARCHAR(20) NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  domain VARCHAR(190) NULL,
  description TEXT NULL,
  table_prefix VARCHAR(20) NULL,
  folder_path VARCHAR(255) NULL,
  status ENUM('draft','config_pending','generating','generated','testing','ready','deployed','archived','failed') NOT NULL DEFAULT 'draft',
  wizard_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
  features LONGTEXT NULL,
  payment_gateway VARCHAR(30) NULL,
  payment_mode ENUM('test','live') NULL,
  hosting_info TEXT NULL,
  deployed_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_websites_code (site_code),
  KEY idx_websites_client (client_id),
  KEY idx_websites_status (status),
  CONSTRAINT fk_websites_client FOREIGN KEY (client_id) REFERENCES wb_clients (id) ON DELETE RESTRICT,
  CONSTRAINT fk_websites_category FOREIGN KEY (category_id) REFERENCES wb_categories (id) ON DELETE RESTRICT,
  CONSTRAINT fk_websites_template FOREIGN KEY (template_id) REFERENCES wb_templates (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_generation_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  website_id INT UNSIGNED NOT NULL,
  type ENUM('generate','regenerate','export') NOT NULL,
  status ENUM('running','success','failed','cancelled') NOT NULL DEFAULT 'running',
  current_step VARCHAR(40) NULL,
  error_message TEXT NULL,
  triggered_by INT UNSIGNED NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_runs_website (website_id),
  CONSTRAINT fk_runs_website FOREIGN KEY (website_id) REFERENCES wb_websites (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_generation_steps (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id INT UNSIGNED NOT NULL,
  step_key VARCHAR(40) NOT NULL,
  status ENUM('pending','running','success','failed','skipped') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  log TEXT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_steps_run (run_id),
  CONSTRAINT fk_steps_run FOREIGN KEY (run_id) REFERENCES wb_generation_runs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_exports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  website_id INT UNSIGNED NOT NULL,
  run_id INT UNSIGNED NULL,
  zip_path VARCHAR(255) NULL,
  sql_path VARCHAR(255) NULL,
  size_bytes BIGINT UNSIGNED NULL,
  checksum CHAR(64) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_exports_website (website_id),
  CONSTRAINT fk_exports_website FOREIGN KEY (website_id) REFERENCES wb_websites (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wb_activity_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40) NULL,
  entity_id INT UNSIGNED NULL,
  details TEXT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_activity_entity (entity_type, entity_id),
  KEY idx_activity_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
