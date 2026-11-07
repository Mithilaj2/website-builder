-- Navigation menus. A location with no rows is "automatic" (built from pages marked show-in-menu / show-in-footer).
CREATE TABLE IF NOT EXISTS `{{prefix}}menu_items` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location ENUM('header','footer') NOT NULL,
  label VARCHAR(60) NOT NULL,
  page_id INT UNSIGNED NULL,
  url VARCHAR(500) NULL,
  new_tab TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_menu_location (location, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
