-- Online payments ("Pay online" page). Amounts are stored in paise (1 rupee = 100 paise).
CREATE TABLE IF NOT EXISTS `{{prefix}}payment_transactions` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference VARCHAR(30) NOT NULL,
  gateway VARCHAR(20) NOT NULL,
  mode ENUM('test','live') NOT NULL DEFAULT 'test',
  purpose VARCHAR(190) NOT NULL,
  amount INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'INR',
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(20) NOT NULL,
  note VARCHAR(500) NULL,
  status ENUM('created','pending','paid','failed','cancelled') NOT NULL DEFAULT 'created',
  gateway_order_id VARCHAR(100) NULL,
  gateway_payment_id VARCHAR(100) NULL,
  method VARCHAR(40) NULL,
  message VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  emails_sent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  paid_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pt_reference (reference),
  KEY idx_pt_gateway_order (gateway, gateway_order_id),
  KEY idx_pt_status (status, created_at),
  KEY idx_pt_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every message received from a gateway (returns and webhooks), for support and audit.
CREATE TABLE IF NOT EXISTS `{{prefix}}payment_events` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaction_id INT UNSIGNED NULL,
  gateway VARCHAR(20) NOT NULL,
  source ENUM('return','webhook','recheck','start') NOT NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  result VARCHAR(20) NULL,
  detail VARCHAR(500) NULL,
  payload MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pe_tx (transaction_id),
  KEY idx_pe_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
