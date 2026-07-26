-- Customer account system: passwords, favorites
-- Run once against tacos_v2

ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `account_status` ENUM('guest','active') NOT NULL DEFAULT 'guest' AFTER `password_hash`;

CREATE TABLE IF NOT EXISTS `customer_favorites` (
  `favorite_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `uniq_customer_menu_item` (`customer_id`,`menu_item_id`),
  KEY `idx_customer_favorites_customer` (`customer_id`),
  CONSTRAINT `fk_customer_favorites_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_customer_favorites_menu_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
