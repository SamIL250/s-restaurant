-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 26, 2026 at 10:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tacos_v2`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_subscriptions`
-- (See below for the actual view)
--
CREATE TABLE `active_subscriptions` (
`customer_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`subscription_status` enum('active','expired','cancelled','pending')
,`subscription_start_date` date
,`subscription_end_date` date
,`type_name` varchar(100)
,`price` decimal(10,2)
,`meals_per_day` int(11)
,`service_hours` varchar(100)
,`includes_beverages` tinyint(1)
,`next_payment_date` date
,`auto_renewal` tinyint(1)
,`days_remaining` int(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `is_active`, `created_at`, `deleted_at`) VALUES
(1, 'Tacos', 'Various types of tacos', 1, '2025-06-21 13:27:39', NULL),
(2, 'Beverages', 'Drinks and beverages', 1, '2025-06-21 13:27:39', NULL),
(3, 'Appetizers', 'Starters and appetizers', 1, '2025-06-21 13:27:39', NULL),
(4, 'Desserts', 'Sweet treats and desserts', 1, '2025-06-21 13:27:39', NULL),
(5, 'edw', 'Raw ingredients for cooking', 1, '2025-06-21 13:27:39', NULL),
(8, 's', 'ss', 1, '2025-08-28 16:06:48', '2025-08-28 16:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `has_subscription` tinyint(1) DEFAULT 0,
  `subscription_status` enum('active','expired','cancelled','pending') DEFAULT NULL,
  `subscription_start_date` date DEFAULT NULL,
  `subscription_end_date` date DEFAULT NULL,
  `subscription_type_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','card','mobile_money','bank_transfer') DEFAULT NULL,
  `auto_renewal` tinyint(1) DEFAULT 0,
  `last_payment_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `email`, `phone`, `loyalty_points`, `has_subscription`, `subscription_status`, `subscription_start_date`, `subscription_end_date`, `subscription_type_id`, `payment_method`, `auto_renewal`, `last_payment_date`, `next_payment_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Samuel', 'NIZEYIMANA', 'scamil350@gmail.com', '0798874111', 0, 1, 'cancelled', '2025-10-20', '2025-11-19', 2, 'cash', 0, NULL, '2025-11-19', '2025-08-25 14:02:33', '2025-10-20 17:04:08', NULL),
(2, 'Samuel', 'NIZEYIMANA', 'scadwemil350@gmail.com', '0798874111', 0, 1, 'active', '2025-08-25', '2025-09-24', 1, 'cash', 0, '2025-08-25', '2025-09-24', '2025-08-25 14:11:29', '2025-08-26 08:20:42', NULL),
(4, 'SAm', 'sam', 'sam@gmail.com', '0798874111', 0, 1, 'active', '2025-10-20', '2025-12-19', 4, 'cash', 0, '2025-10-20', '2025-12-19', '2025-10-20 16:04:46', '2025-10-20 16:05:03', NULL),
(5, 'Prince', '', 'prince@gmail.com', '0798874111', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-11-18 17:44:18', '2025-11-18 17:44:18', NULL),
(6, 'Jackk', '', 'few@fo.com', '0798874411', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-02-03 00:03:37', '2026-02-03 00:03:37', NULL),
(7, 'Kbahizi', 'Anette', 'anette@gmailcom', '+250 7887652', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-02-03 14:59:44', '2026-02-03 14:59:44', NULL),
(12, 'iuceoc', 'cewc', 'w@wec.com', '+250 7887652', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-02-03 15:56:34', '2026-02-03 15:56:34', NULL),
(13, 'test', '', 'test@gmail.com', '0798874999', 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-05-31 21:00:44', '2026-05-31 21:00:44', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `daily_revenue`
-- (See below for the actual view)
--
CREATE TABLE `daily_revenue` (
`sales_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(32,2)
,`avg_order_value` decimal(14,6)
,`dine_in_orders` decimal(22,0)
,`takeaway_orders` decimal(22,0)
,`delivery_orders` decimal(22,0)
,`online_orders` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales`
--

CREATE TABLE `daily_sales` (
  `sales_date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `total_profit` decimal(10,2) DEFAULT 0.00,
  `dine_in_orders` int(11) DEFAULT 0,
  `takeaway_orders` int(11) DEFAULT 0,
  `delivery_orders` int(11) DEFAULT 0,
  `online_orders` int(11) DEFAULT 0,
  `cash_payments` decimal(10,2) DEFAULT 0.00,
  `card_payments` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `log_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `email_content` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`log_id`, `purchase_order_id`, `recipient_email`, `subject`, `sent_at`, `status`, `error_message`, `email_content`, `created_at`) VALUES
(2, 1, 'ryxeze@mailinator.com', 'Purchase Order PU38921I - Tacos Restaurant', '2025-08-29 10:00:01', 'sent', NULL, '\n    <!DOCTYPE html>\n    <html lang=\"en\">\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n        <title>Purchase Order - PU38921I</title>\n        <style>\n            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }\n            .email-container { background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }\n            .header { background-color: #2c3e50; color: white; padding: 30px; text-align: center; }\n            .header h1 { margin: 0; font-size: 24px; font-weight: 300; }\n            .header .po-number { font-size: 18px; margin-top: 10px; opacity: 0.9; }\n            .content { padding: 30px; }\n            .section { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e9ecef; }\n            .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }\n            .section h2 { color: #2c3e50; font-size: 18px; margin-bottom: 15px; font-weight: 600; }\n            .info-grid { display: table; width: 100%; margin-bottom: 15px; }\n            .info-row { display: table-row; }\n            .info-label { display: table-cell; width: 120px; font-weight: 600; color: #6c757d; padding: 8px 0; }\n            .info-value { display: table-cell; padding: 8px 0; }\n            .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }\n            .items-table th { background-color: #f8f9fa; padding: 12px 8px; text-align: left; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }\n            .items-table td { padding: 12px 8px; border-bottom: 1px solid #e9ecef; }\n            .items-table .quantity { text-align: center; }\n            .items-table .price { text-align: right; }\n            .total-section { background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 20px; }\n            .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; }\n            .total-row.total { font-size: 18px; font-weight: 600; color: #2c3e50; border-top: 2px solid #dee2e50; padding-top: 15px; margin-top: 15px; }\n            .footer { background-color: #f8f9fa; padding: 20px 30px; text-align: center; color: #6c757d; font-size: 14px; }\n            .notes { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-top: 20px; }\n        </style>\n    </head>\n    <body>\n        <div class=\"email-container\">\n            <div class=\"header\">\n                <h1>Purchase Order</h1>\n                <div class=\"po-number\">PU38921I</div>\n            </div>\n            \n            <div class=\"content\">\n                <div class=\"section\">\n                    <h2>Order Information</h2>\n                    <div class=\"info-grid\">\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">PO Number:</div>\n                            <div class=\"info-value\">PU38921I</div>\n                        </div>\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Order Date:</div>\n                            <div class=\"info-value\">August 25, 2025</div>\n                        </div>\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Expected Delivery:</div>\n                            <div class=\"info-value\">August 26, 2025</div>\n                        </div>\n                    </div>\n                </div>\n                \n                <div class=\"section\">\n                    <h2>Supplier Information</h2>\n                    <div class=\"info-grid\">\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Company:</div>\n                            <div class=\"info-value\">Amber Skinner23</div>\n                        </div>\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Contact:</div>\n                            <div class=\"info-value\">Sunt et amet facere23</div>\n                        </div>\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Email:</div>\n                            <div class=\"info-value\">ryxeze@mailinator.com</div>\n                        </div>\n                        <div class=\"info-row\">\n                            <div class=\"info-label\">Phone:</div>\n                            <div class=\"info-value\">+1 (558) 582-569223</div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"section\">\n                    <div class=\"notes\">\n                        <h3>Special Instructions</h3>\n                        <p>Some notes</p>\n                    </div>\n                </div>\n            </div>\n            \n            <div class=\"footer\">\n                <div class=\"company-info\">\n                    <strong>Tacos Restaurant</strong><br>\n                    123 Restaurant Street, City, Country\n                </div>\n                <div class=\"contact-info\">\n                    Phone: +1234567890 | Email: info@tacosrestaurant.com<br>\n                    Website: www.tacosrestaurant.com\n                </div>\n            </div>\n        </div>\n    </body>\n    </html>', '2025-08-29 10:00:01');

-- --------------------------------------------------------

--
-- Stand-in structure for view `expiring_subscriptions`
-- (See below for the actual view)
--
CREATE TABLE `expiring_subscriptions` (
`customer_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`subscription_end_date` date
,`days_until_expiry` int(7)
,`type_name` varchar(100)
,`price` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `unit_of_measure` enum('kg','lbs','liters','pieces','boxes','bottles') NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maximum_stock` decimal(10,2) DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `last_restocked` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `item_name`, `category_id`, `supplier_id`, `unit_of_measure`, `current_stock`, `minimum_stock`, `maximum_stock`, `unit_cost`, `last_restocked`, `expiry_date`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Carrotts', 5, NULL, 'kg', 60.00, 20.00, 50.00, 110.00, NULL, '2025-06-24', 1, '2025-06-21 13:58:53', '2026-02-09 12:57:48', NULL),
(2, 'Haviva Stevens', 3, NULL, 'kg', 79.00, 92.00, 89.00, 61.00, NULL, '2025-06-25', 1, '2025-06-21 14:00:57', '2025-06-21 14:00:57', NULL),
(3, 'Rice', 3, 3, 'kg', 130.00, 20.00, 200.00, 820.00, NULL, '2026-10-20', 1, '2025-06-24 17:07:57', '2025-06-24 17:16:17', NULL),
(4, 'fanta', 2, 2, 'liters', 180.00, 50.00, 200.00, 1200.00, NULL, '2025-11-28', 1, '2025-10-20 16:59:16', '2025-10-20 17:01:29', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_items`
-- (See below for the actual view)
--
CREATE TABLE `low_stock_items` (
`item_id` int(11)
,`item_name` varchar(100)
,`current_stock` decimal(10,2)
,`minimum_stock` decimal(10,2)
,`unit_of_measure` enum('kg','lbs','liters','pieces','boxes','bottles')
,`supplier_name` varchar(100)
,`category_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `cost` decimal(8,2) DEFAULT NULL,
  `preparation_time` int(11) DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `is_vegetarian` tinyint(1) DEFAULT 0,
  `is_vegan` tinyint(1) DEFAULT 0,
  `is_gluten_free` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`menu_item_id`, `item_name`, `description`, `category_id`, `price`, `cost`, `preparation_time`, `calories`, `is_vegetarian`, `is_vegan`, `is_gluten_free`, `is_available`, `image_url`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'Sara Kerr', 'Vero qui veniam nat', 2, 618.00, 32.00, 55, 75, 0, 0, 0, 0, 'https://img.freepik.com/premium-photo/delicious-taco-filled-with-seasoned-beef-fresh-lettuce-tomatoes-cheese-wrapped-crunchy-taco-shell_963414-41527.jpg?semt=ais_hybrid&w=740&q=80', '2025-06-21 14:34:46', '2025-11-18 17:29:28', NULL),
(4, 'Sushi Platter', 'Fresh assortment of premium sushi rolls', 3, 24.99, 12.00, 15, 320, 0, 0, 0, 1, 'https://img.freepik.com/premium-photo/delicious-taco-filled-with-seasoned-beef-fresh-lettuce-tomatoes-cheese-wrapped-crunchy-taco-shell_963414-41527.jpg?semt=ais_hybrid&w=740&q=80', '2025-08-26 16:14:54', '2025-11-18 17:29:43', NULL),
(5, 'Taco Trio', 'Three authentic tacos with your choice of filling', 1, 18.99, 8.00, 10, 450, 0, 0, 0, 1, '', '2025-08-26 16:14:54', '2025-08-26 16:14:54', NULL),
(6, 'Tempura Rolls', 'Crispy tempura shrimp rolls with spicy mayo', 3, 22.99, 11.00, 12, 380, 0, 0, 0, 1, '', '2025-08-26 16:14:54', '2025-08-26 16:14:54', NULL),
(7, 'Vegan Delight', 'Plant-based sushi rolls with fresh vegetables', 3, 19.99, 9.00, 10, 280, 1, 1, 1, 1, '', '2025-08-26 16:14:54', '2025-08-26 16:14:54', NULL),
(8, 'Miso Soup', 'Traditional Japanese miso soup with tofu', 3, 6.99, 2.50, 5, 120, 0, 0, 0, 1, '/restaurant_stock/src/services/menu/uploads/menu_691cadbe53f0d6.80146165_1763487166.webp', '2025-08-26 16:14:54', '2025-11-18 17:32:46', NULL),
(9, 'Matcha Dessert 2', 'Green tea flavored dessert with sweet cream', 4, 8.99, 3.50, 5, 180, 0, 0, 0, 1, '', '2025-08-26 16:14:54', '2026-07-20 13:51:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_type` enum('dine_in','takeaway','delivery','online') NOT NULL,
  `order_status` enum('pending','confirmed','preparing','ready','served','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `payment_method` enum('cash','card','online','mobile') DEFAULT 'cash',
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_fee` decimal(8,2) DEFAULT 0.00,
  `special_instructions` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `estimated_ready_time` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `customer_id`, `table_id`, `user_id`, `order_type`, `order_status`, `payment_status`, `payment_method`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `delivery_address`, `delivery_fee`, `special_instructions`, `order_date`, `estimated_ready_time`, `completed_at`, `deleted_at`) VALUES
(1, 'ORD202511183963', 4, NULL, 1, 'delivery', 'pending', 'pending', 'cash', 8.99, 3.00, 0.00, 11.99, NULL, 0.00, 'ss', '2025-11-18 15:42:23', NULL, NULL, NULL),
(2, 'ORD202511181974', 1, 2, 1, 'dine_in', 'cancelled', 'paid', 'cash', 24.99, 0.00, 0.00, 24.99, NULL, 0.00, 'ddd\n\nCancellation reason: no reason', '2025-11-18 15:43:58', NULL, NULL, NULL),
(3, 'ORD202511182247', NULL, 1, 1, 'delivery', 'completed', 'paid', 'cash', 22.99, 0.00, 0.00, 22.99, 'kigali, rwanda', 0.00, 'no special instra', '2025-11-18 15:51:59', NULL, NULL, NULL),
(4, 'ORD202511186464', 5, NULL, NULL, 'delivery', 'completed', 'paid', 'cash', 56.97, 5.70, 0.00, 67.67, '0', 5.00, 'I need them hot', '2025-11-18 17:44:18', NULL, '2025-11-18 17:47:32', NULL),
(5, 'ORD202602031544', 6, NULL, NULL, 'delivery', 'confirmed', 'pending', 'cash', 74.97, 7.50, 0.00, 87.47, '0', 5.00, '', '2026-02-03 00:03:36', NULL, NULL, NULL),
(6, 'ORD202602033368', 2, NULL, 1, 'delivery', 'preparing', 'pending', 'cash', 24.99, 2.50, 0.00, 2027.49, '0', 2000.00, 'none', '2026-02-03 15:55:46', NULL, NULL, NULL),
(7, 'ORD202602036982', 12, NULL, 1, 'delivery', 'completed', 'paid', 'cash', 113.94, 11.39, 0.00, 2125.33, '0', 2000.00, 'none', '2026-02-03 15:56:34', NULL, NULL, NULL),
(8, 'ORD202605314043', 13, NULL, NULL, 'dine_in', 'pending', 'pending', 'cash', 24.99, 2.50, 0.00, 27.49, NULL, 0.00, 'none', '2026-05-31 21:00:44', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(8,2) NOT NULL,
  `total_price` decimal(8,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `item_status` enum('pending','preparing','ready','served') DEFAULT 'pending',
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`, `special_instructions`, `item_status`, `deleted_at`) VALUES
(1, 1, 9, 1, 8.99, 8.99, NULL, 'pending', NULL),
(2, 2, 4, 1, 24.99, 24.99, NULL, 'pending', NULL),
(3, 3, 6, 1, 22.99, 22.99, NULL, 'pending', NULL),
(4, 4, 4, 2, 24.99, 49.98, NULL, 'pending', NULL),
(5, 4, 8, 1, 6.99, 6.99, NULL, 'pending', NULL),
(6, 5, 4, 3, 24.99, 74.97, NULL, 'pending', NULL),
(7, 6, 4, 1, 24.99, 24.99, NULL, 'pending', NULL),
(8, 7, 8, 2, 6.99, 13.98, NULL, 'pending', NULL),
(9, 7, 4, 4, 24.99, 99.96, NULL, 'pending', NULL),
(10, 8, 4, 1, 24.99, 24.99, NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_logs`
--

CREATE TABLE `order_status_logs` (
  `log_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_logs`
--

INSERT INTO `order_status_logs` (`log_id`, `order_id`, `old_status`, `new_status`, `user_id`, `created_at`) VALUES
(1, 5, 'pending', 'confirmed', 1, '2026-02-03 11:43:17'),
(2, 3, 'pending', 'preparing', 1, '2026-02-03 11:44:00'),
(3, 3, 'preparing', 'completed', 1, '2026-02-03 11:44:30'),
(4, 6, 'pending', 'preparing', 1, '2026-02-10 20:25:15'),
(5, 7, 'pending', 'completed', 1, '2026-02-10 20:50:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_due_subscriptions`
-- (See below for the actual view)
--
CREATE TABLE `payment_due_subscriptions` (
`customer_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`next_payment_date` date
,`days_until_payment` int(7)
,`type_name` varchar(100)
,`price` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `popular_menu_items`
-- (See below for the actual view)
--
CREATE TABLE `popular_menu_items` (
`menu_item_id` int(11)
,`item_name` varchar(100)
,`price` decimal(8,2)
,`times_ordered` bigint(21)
,`total_quantity_sold` decimal(32,0)
,`total_revenue` decimal(30,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `promotion_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(8,2) NOT NULL,
  `minimum_order_amount` decimal(8,2) DEFAULT 0.00,
  `promotion_days` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promotion_id`, `promotion_name`, `description`, `discount_type`, `discount_value`, `minimum_order_amount`, `promotion_days`, `is_active`, `max_uses`, `current_uses`, `created_at`, `deleted_at`) VALUES
(1, 'Burger Promo', 'Thursday promomo', 'fixed_amount', 6000.00, 2.00, '1,5', 0, NULL, 0, '2025-10-20 16:48:59', NULL),
(2, 'Big tacos promo', 'Descriptoin', 'fixed_amount', 11000.00, 3.00, '5,6', 1, NULL, 0, '2026-02-12 08:10:03', NULL),
(3, 'Big mac chees', 'Descriptoin', 'fixed_amount', 12000.00, 4.00, '1,3', 1, NULL, 0, '2026-02-12 09:06:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `purchase_order_id` int(11) NOT NULL,
  `po_number` varchar(20) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `po_status` enum('draft','sent','received','cancelled') DEFAULT 'draft',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`purchase_order_id`, `po_number`, `supplier_id`, `user_id`, `po_status`, `order_date`, `expected_delivery_date`, `actual_delivery_date`, `total_amount`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PU38921I', 2, 1, 'sent', '2025-08-25', '2025-08-26', NULL, NULL, 'Some notes', '2025-08-25 10:59:29', '2025-08-29 10:00:01', NULL),
(2, 'PO000001', 2, 1, 'draft', '2025-08-29', '2025-08-29', NULL, NULL, 'New chairs expected', '2025-08-29 07:31:46', '2025-08-29 07:37:04', NULL),
(3, 'PO000002', 4, 1, 'sent', '2025-08-29', '2025-08-29', NULL, NULL, 'New tables', '2025-08-29 07:32:13', '2025-08-29 08:44:28', NULL),
(4, 'PO000003', 3, 1, 'received', '2025-08-29', '2025-09-04', '2025-10-20', NULL, 'New 100 food plates', '2025-08-29 08:40:41', '2025-10-20 16:36:29', NULL),
(5, 'PO000004', 4, 1, 'sent', '2025-10-20', '2025-10-25', NULL, 82000.00, 'Ntimuzazane mumifuka icitse nanone', '2025-10-20 16:32:49', '2025-10-20 16:33:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `po_item_id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_ordered` decimal(10,2) NOT NULL,
  `quantity_received` decimal(10,2) DEFAULT 0.00,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`po_item_id`, `purchase_order_id`, `item_id`, `quantity_ordered`, `quantity_received`, `unit_cost`, `total_cost`, `deleted_at`) VALUES
(1, 5, 3, 100.00, 0.00, 820.00, 82000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `recipe_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_needed` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`recipe_id`, `menu_item_id`, `item_id`, `quantity_needed`, `unit`, `deleted_at`) VALUES
(3, 3, 1, 5.00, 'piece', NULL),
(7, 4, 1, 30.00, 'kg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `customer_name`, `customer_phone`, `customer_email`, `reservation_date`, `reservation_time`, `number_of_guests`, `table_id`, `status`, `special_requests`, `total_amount`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Samuel NIZEYIMANA', '0798874111', 'scamil350@gmail.com', '2025-08-28', '16:00:00', 3, NULL, 'pending', 'We prefer our dishes hot', 0.00, '2025-08-26 16:41:25', '2025-10-20 16:29:43', NULL),
(3, 'Yuli Harper', '+1 (675) 687-9169', 'xomo@mailinator.com', '2025-08-29', '12:00:00', 9, NULL, 'cancelled', 'Sequi aute sint even', 0.00, '2025-08-28 08:04:50', '2025-08-28 15:30:02', NULL),
(4, 'Digne', '0798873123', 'digne@gmail.com', '2025-08-29', '17:58:00', 5, 2, 'completed', 'No special requests', 0.00, '2025-08-28 14:59:11', '2025-10-20 16:29:34', NULL),
(5, 'ferf', '078936232', 'dwe@fdew.com', '2026-02-03', '18:13:00', 2, NULL, 'pending', 'none', 0.00, '2026-02-03 15:13:45', '2026-02-03 15:13:45', NULL),
(6, 'Sam', '0798874111', 'samniz.350@gmail.com', '2026-05-30', '15:34:00', 3, NULL, 'pending', '', 0.00, '2026-05-30 13:32:50', '2026-05-30 13:32:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_items`
--

CREATE TABLE `reservation_items` (
  `reservation_item_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(8,2) NOT NULL,
  `total_price` decimal(8,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_items`
--

INSERT INTO `reservation_items` (`reservation_item_id`, `reservation_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`, `special_instructions`, `created_at`, `deleted_at`) VALUES
(1, 1, 5, 1, 18.99, 18.99, NULL, '2025-08-26 16:41:25', NULL),
(2, 1, 4, 1, 24.99, 24.99, NULL, '2025-08-26 16:41:25', NULL),
(3, 1, 7, 1, 19.99, 19.99, NULL, '2025-08-26 16:41:25', NULL),
(5, 3, 5, 1, 18.99, 18.99, NULL, '2025-08-28 08:04:50', NULL),
(6, 3, 6, 1, 22.99, 22.99, NULL, '2025-08-28 08:04:50', NULL),
(7, 4, 8, 5, 6.99, 34.95, '', '2025-08-28 14:59:11', NULL),
(8, 4, 4, 3, 24.99, 74.97, '', '2025-08-28 14:59:11', NULL),
(9, 5, 8, 3, 6.99, 20.97, '', '2026-02-03 15:13:45', NULL),
(10, 5, 4, 6, 24.99, 149.94, '', '2026-02-03 15:13:45', NULL),
(11, 5, 6, 5, 22.99, 114.95, '', '2026-02-03 15:13:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`table_id`, `table_number`, `capacity`, `location`, `is_available`, `created_at`, `deleted_at`) VALUES
(1, '2', 32, 'indoor', 1, '2025-08-25 10:57:55', NULL),
(2, '1', 4, 'indoor', 1, '2025-08-26 16:14:54', NULL),
(3, '3', 2, 'outdoor', 1, '2025-08-26 16:14:54', NULL),
(4, '4', 8, 'indoor', 1, '2025-08-26 16:14:54', NULL),
(5, '5', 4, 'outdoor', 1, '2025-08-26 16:14:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','waste') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`movement_id`, `item_id`, `movement_type`, `quantity`, `unit_cost`, `total_cost`, `reason`, `reference_id`, `user_id`, `movement_date`, `deleted_at`) VALUES
(1, 1, 'in', 30.00, 100.00, 3000.00, 'Initial stock', NULL, 1, '2025-06-21 13:58:53', NULL),
(2, 1, 'out', 10.00, 100.00, 1000.00, 'It was used to cook for a wedding', NULL, 1, '2025-06-21 13:59:39', NULL),
(3, 1, 'in', 80.00, 100.00, 8000.00, 'Some reason', NULL, 1, '2025-06-21 14:00:13', NULL),
(4, 2, 'in', 79.00, 61.00, 4819.00, 'Initial stock', NULL, 1, '2025-06-21 14:00:57', NULL),
(5, 3, 'in', 100.00, 820.00, 82000.00, 'Initial stock', NULL, 1, '2025-06-24 17:07:58', NULL),
(6, 3, 'out', 20.00, 820.00, 16400.00, 'Umuceri watetswe', NULL, 1, '2025-06-24 17:12:39', NULL),
(7, 3, 'in', 50.00, 820.00, 41000.00, 'Twaguze umuceri mushya', NULL, 1, '2025-06-24 17:16:17', NULL),
(8, 1, 'out', 50.00, 100.00, 5000.00, 'Cooking', NULL, 1, '2025-08-28 15:38:54', NULL),
(9, 1, 'in', 50.00, 110.00, 5500.00, 'New carrots, and price increased by 110Frw, unit', NULL, 1, '2025-10-20 16:12:07', NULL),
(10, 4, 'in', 180.00, 1200.00, 216000.00, 'Initial stock', NULL, 1, '2025-10-20 16:59:16', NULL),
(11, 1, 'waste', 40.00, 110.00, 4400.00, 'bad quality', NULL, 1, '2026-02-09 12:57:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_alerts`
--

CREATE TABLE `subscription_alerts` (
  `alert_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `alert_type` enum('expiring_soon','expired','payment_due','renewal_reminder') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `alert_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_alerts`
--

INSERT INTO `subscription_alerts` (`alert_id`, `customer_id`, `alert_type`, `message`, `is_read`, `alert_date`, `created_at`, `deleted_at`) VALUES
(1, 1, 'renewal_reminder', 'Your subscription will expire on Nov 19, 2025. Consider renewing to continue enjoying our services.', 0, '2025-11-12', '2025-10-20 15:51:47', NULL),
(2, 4, 'renewal_reminder', 'Your subscription will expire on Dec 19, 2025. Consider renewing to continue enjoying our services.', 0, '2025-12-12', '2025-10-20 16:05:03', NULL),
(3, 1, 'expired', 'Subscription cancelled. Reason: Customer requested cancellation', 0, '2025-10-20', '2025-10-20 17:04:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `payment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subscription_type_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','card','mobile_money','bank_transfer') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_payments`
--

INSERT INTO `subscription_payments` (`payment_id`, `customer_id`, `subscription_type_id`, `amount_paid`, `payment_date`, `payment_method`, `payment_status`, `transaction_reference`, `notes`, `created_at`, `deleted_at`) VALUES
(1, 1, 2, 180.00, '2025-10-20', 'cash', 'completed', 'SUB_20251020175147_1', NULL, '2025-10-20 15:51:47', NULL),
(2, 4, 4, 200.00, '2025-10-20', 'cash', 'completed', 'REN_20251020180503_4', NULL, '2025-10-20 16:05:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_types`
--

CREATE TABLE `subscription_types` (
  `subscription_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `meals_per_day` int(11) DEFAULT 1,
  `service_hours` varchar(100) DEFAULT NULL COMMENT 'e.g., "Lunch only", "Dinner only", "All day"',
  `includes_beverages` tinyint(1) DEFAULT 0,
  `max_meals_per_month` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_types`
--

INSERT INTO `subscription_types` (`subscription_type_id`, `type_name`, `description`, `duration_days`, `price`, `meals_per_day`, `service_hours`, `includes_beverages`, `max_meals_per_month`, `is_active`, `created_at`, `deleted_at`) VALUES
(1, 'Lunch Only - Monthly', 'Daily lunch service for 30 days', 30, 150.00, 1, 'Lunch only (12:00-14:00)', 0, 30, 1, '2025-08-25 11:25:03', NULL),
(2, 'Dinner Only - Monthly', 'Daily dinner service for 30 days', 30, 180.00, 1, 'Dinner only (18:00-21:00)', 0, 30, 1, '2025-08-25 11:25:03', NULL),
(3, 'All Day - Monthly', 'Unlimited meals throughout the day for 30 days', 30, 300.00, 3, 'All day (8:00-22:00)', 1, 90, 1, '2025-08-25 11:25:03', NULL),
(4, 'Lunch + Beverages - Monthly', 'Daily lunch with beverages for 30 days', 30, 200.00, 1, 'Lunch only (12:00-14:00)', 1, 30, 1, '2025-08-25 11:25:03', NULL),
(5, 'Premium - Monthly', 'All day access with premium menu items and beverages', 30, 450.00, 4, 'All day (8:00-22:00)', 1, 120, 1, '2025-08-25 11:25:03', NULL),
(6, 'Weekly Trial', '7-day trial subscription', 7, 50.00, 2, 'Lunch and dinner', 0, 60, 1, '2025-08-25 11:25:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_usage`
--

CREATE TABLE `subscription_usage` (
  `usage_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subscription_type_id` int(11) NOT NULL,
  `usage_date` date NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `items_consumed` text DEFAULT NULL COMMENT 'JSON array of consumed items',
  `total_value` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Amber Skinner23', 'Sunt et amet facere23', 'ryxeze@mailinator.com', '+1 (558) 582-569223', 1, '2025-06-21 14:19:52', '2025-08-25 10:58:47', NULL),
(3, 'George Santos', 'Ex nisi maxime repel', 'samniz.350@gmail.com', '+1 (802) 734-8832', 1, '2025-06-21 14:20:43', '2025-08-29 08:40:10', NULL),
(4, 'Kylee Simmons', 'Samuel NIZEYIMANA', 'scamil350@gmail.com', '0798874111', 1, '2025-08-25 11:17:58', '2025-08-25 11:17:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','cashier','stock_clerk') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `phone`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'admin', 'admin@tacos.com', 'admintacos', 'Admin', 'User', 'admin', '+1234567890', 1, '2025-06-21 13:27:39', '2026-02-03 19:17:39', NULL),
(3, 'cashier', 'cashier@tacos.com', 'admintacos', 'Janee', 'Cashier', 'cashier', '+1234567892', 1, '2025-06-21 13:27:39', '2026-02-04 15:42:15', NULL),
(4, 'stocker', 'stock@tacos.com', '123456', 'Janee', 'Anette', 'stock_clerk', '0798874411', 1, '2026-02-04 14:39:56', '2026-02-04 15:25:33', NULL);

-- --------------------------------------------------------

--
-- Structure for view `active_subscriptions`
--
DROP TABLE IF EXISTS `active_subscriptions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_subscriptions`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`email` AS `email`, `c`.`phone` AS `phone`, `c`.`subscription_status` AS `subscription_status`, `c`.`subscription_start_date` AS `subscription_start_date`, `c`.`subscription_end_date` AS `subscription_end_date`, `st`.`type_name` AS `type_name`, `st`.`price` AS `price`, `st`.`meals_per_day` AS `meals_per_day`, `st`.`service_hours` AS `service_hours`, `st`.`includes_beverages` AS `includes_beverages`, `c`.`next_payment_date` AS `next_payment_date`, `c`.`auto_renewal` AS `auto_renewal`, to_days(`c`.`subscription_end_date`) - to_days(curdate()) AS `days_remaining` FROM (`customers` `c` join `subscription_types` `st` on(`c`.`subscription_type_id` = `st`.`subscription_type_id`)) WHERE `c`.`subscription_status` = 'active' AND `c`.`subscription_end_date` >= curdate() AND `c`.`deleted_at` is null AND `st`.`deleted_at` is null AND `st`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `daily_revenue`
--
DROP TABLE IF EXISTS `daily_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_revenue`  AS SELECT cast(`orders`.`order_date` as date) AS `sales_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_revenue`, avg(`orders`.`total_amount`) AS `avg_order_value`, sum(case when `orders`.`order_type` = 'dine_in' then 1 else 0 end) AS `dine_in_orders`, sum(case when `orders`.`order_type` = 'takeaway' then 1 else 0 end) AS `takeaway_orders`, sum(case when `orders`.`order_type` = 'delivery' then 1 else 0 end) AS `delivery_orders`, sum(case when `orders`.`order_type` = 'online' then 1 else 0 end) AS `online_orders` FROM `orders` WHERE `orders`.`order_status` = 'completed' AND `orders`.`deleted_at` is null GROUP BY cast(`orders`.`order_date` as date) ORDER BY cast(`orders`.`order_date` as date) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `expiring_subscriptions`
--
DROP TABLE IF EXISTS `expiring_subscriptions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `expiring_subscriptions`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`email` AS `email`, `c`.`phone` AS `phone`, `c`.`subscription_end_date` AS `subscription_end_date`, to_days(`c`.`subscription_end_date`) - to_days(curdate()) AS `days_until_expiry`, `st`.`type_name` AS `type_name`, `st`.`price` AS `price` FROM (`customers` `c` join `subscription_types` `st` on(`c`.`subscription_type_id` = `st`.`subscription_type_id`)) WHERE `c`.`subscription_status` = 'active' AND `c`.`subscription_end_date` between curdate() and curdate() + interval 7 day AND `c`.`deleted_at` is null AND `st`.`deleted_at` is null AND `st`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `low_stock_items`
--
DROP TABLE IF EXISTS `low_stock_items`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `low_stock_items`  AS SELECT `i`.`item_id` AS `item_id`, `i`.`item_name` AS `item_name`, `i`.`current_stock` AS `current_stock`, `i`.`minimum_stock` AS `minimum_stock`, `i`.`unit_of_measure` AS `unit_of_measure`, `s`.`supplier_name` AS `supplier_name`, `c`.`category_name` AS `category_name` FROM ((`inventory_items` `i` left join `suppliers` `s` on(`i`.`supplier_id` = `s`.`supplier_id`)) left join `categories` `c` on(`i`.`category_id` = `c`.`category_id`)) WHERE `i`.`current_stock` <= `i`.`minimum_stock` AND `i`.`is_active` = 1 AND `i`.`deleted_at` is null AND (`s`.`deleted_at` is null OR `s`.`supplier_id` is null) AND (`c`.`deleted_at` is null OR `c`.`category_id` is null) ;

-- --------------------------------------------------------

--
-- Structure for view `payment_due_subscriptions`
--
DROP TABLE IF EXISTS `payment_due_subscriptions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_due_subscriptions`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`email` AS `email`, `c`.`phone` AS `phone`, `c`.`next_payment_date` AS `next_payment_date`, to_days(`c`.`next_payment_date`) - to_days(curdate()) AS `days_until_payment`, `st`.`type_name` AS `type_name`, `st`.`price` AS `price` FROM (`customers` `c` join `subscription_types` `st` on(`c`.`subscription_type_id` = `st`.`subscription_type_id`)) WHERE `c`.`subscription_status` = 'active' AND `c`.`next_payment_date` between curdate() and curdate() + interval 7 day AND `c`.`deleted_at` is null AND `st`.`deleted_at` is null AND `st`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `popular_menu_items`
--
DROP TABLE IF EXISTS `popular_menu_items`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `popular_menu_items`  AS SELECT `mi`.`menu_item_id` AS `menu_item_id`, `mi`.`item_name` AS `item_name`, `mi`.`price` AS `price`, count(`oi`.`order_item_id`) AS `times_ordered`, sum(`oi`.`quantity`) AS `total_quantity_sold`, sum(`oi`.`total_price`) AS `total_revenue` FROM ((`menu_items` `mi` left join `order_items` `oi` on(`mi`.`menu_item_id` = `oi`.`menu_item_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`order_id`)) WHERE `o`.`order_status` = 'completed' AND `mi`.`is_available` = 1 AND `mi`.`deleted_at` is null AND `o`.`deleted_at` is null AND `oi`.`deleted_at` is null GROUP BY `mi`.`menu_item_id`, `mi`.`item_name`, `mi`.`price` ORDER BY count(`oi`.`order_item_id`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_categories_deleted_at` (`deleted_at`),
  ADD KEY `idx_categories_deleted` (`deleted_at`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `customers_ibfk_subscription_type` (`subscription_type_id`),
  ADD KEY `idx_customers_subscription_status` (`subscription_status`),
  ADD KEY `idx_customers_subscription_end_date` (`subscription_end_date`),
  ADD KEY `idx_customers_next_payment_date` (`next_payment_date`),
  ADD KEY `idx_customers_deleted_at` (`deleted_at`);

--
-- Indexes for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD PRIMARY KEY (`sales_date`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_purchase_order_id` (`purchase_order_id`),
  ADD KEY `idx_recipient_email` (`recipient_email`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email_logs_composite` (`purchase_order_id`,`status`,`sent_at`),
  ADD KEY `idx_email_logs_date_status` (`sent_at`,`status`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_inventory_stock` (`current_stock`,`minimum_stock`),
  ADD KEY `idx_inventory_items_deleted_at` (`deleted_at`),
  ADD KEY `idx_items_deleted` (`deleted_at`),
  ADD KEY `idx_items_category` (`category_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`menu_item_id`),
  ADD KEY `idx_menu_items_category` (`category_id`),
  ADD KEY `idx_menu_items_available` (`is_available`),
  ADD KEY `idx_menu_items_deleted_at` (`deleted_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_date` (`order_date`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_deleted_at` (`deleted_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`),
  ADD KEY `idx_order_items_deleted_at` (`deleted_at`);

--
-- Indexes for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `idx_promotions_deleted_at` (`deleted_at`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`purchase_order_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_purchase_orders_deleted_at` (`deleted_at`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_purchase_order_items_deleted_at` (`deleted_at`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`recipe_id`),
  ADD UNIQUE KEY `unique_recipe_ingredient` (`menu_item_id`,`item_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_recipe_ingredients_deleted_at` (`deleted_at`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `idx_reservation_date` (`reservation_date`),
  ADD KEY `idx_reservation_status` (`status`),
  ADD KEY `fk_reservation_table` (`table_id`),
  ADD KEY `idx_reservations_deleted_at` (`deleted_at`);

--
-- Indexes for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD PRIMARY KEY (`reservation_item_id`),
  ADD KEY `fk_reservation_item_reservation` (`reservation_id`),
  ADD KEY `fk_reservation_item_menu` (`menu_item_id`),
  ADD KEY `idx_reservation_items_deleted_at` (`deleted_at`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`table_id`),
  ADD UNIQUE KEY `table_number` (`table_number`),
  ADD KEY `idx_restaurant_tables_deleted_at` (`deleted_at`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_stock_movements_date` (`movement_date`),
  ADD KEY `idx_stock_movements_deleted_at` (`deleted_at`),
  ADD KEY `idx_sm_date` (`movement_date`),
  ADD KEY `idx_sm_type` (`movement_type`),
  ADD KEY `idx_sm_item` (`item_id`);

--
-- Indexes for table `subscription_alerts`
--
ALTER TABLE `subscription_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `idx_subscription_alerts_deleted_at` (`deleted_at`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `subscription_type_id` (`subscription_type_id`),
  ADD KEY `payment_date` (`payment_date`),
  ADD KEY `idx_subscription_payments_customer_date` (`customer_id`,`payment_date`),
  ADD KEY `idx_subscription_payments_deleted_at` (`deleted_at`);

--
-- Indexes for table `subscription_types`
--
ALTER TABLE `subscription_types`
  ADD PRIMARY KEY (`subscription_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`),
  ADD KEY `idx_subscription_types_deleted_at` (`deleted_at`);

--
-- Indexes for table `subscription_usage`
--
ALTER TABLE `subscription_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `subscription_type_id` (`subscription_type_id`),
  ADD KEY `usage_date` (`usage_date`),
  ADD KEY `idx_subscription_usage_customer_date` (`customer_id`,`usage_date`),
  ADD KEY `idx_subscription_usage_deleted_at` (`deleted_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD KEY `idx_suppliers_deleted_at` (`deleted_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_deleted_at` (`deleted_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `menu_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `purchase_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `recipe_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservation_items`
--
ALTER TABLE `reservation_items`
  MODIFY `reservation_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `subscription_alerts`
--
ALTER TABLE `subscription_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscription_types`
--
ALTER TABLE `subscription_types`
  MODIFY `subscription_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subscription_usage`
--
ALTER TABLE `subscription_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_subscription_type` FOREIGN KEY (`subscription_type_id`) REFERENCES `subscription_types` (`subscription_type_id`);

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `fk_email_logs_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`table_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_ingredients_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservation_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`table_id`) ON DELETE SET NULL;

--
-- Constraints for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD CONSTRAINT `fk_reservation_item_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservation_item_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subscription_alerts`
--
ALTER TABLE `subscription_alerts`
  ADD CONSTRAINT `subscription_alerts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_payments_ibfk_2` FOREIGN KEY (`subscription_type_id`) REFERENCES `subscription_types` (`subscription_type_id`);

--
-- Constraints for table `subscription_usage`
--
ALTER TABLE `subscription_usage`
  ADD CONSTRAINT `subscription_usage_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_usage_ibfk_2` FOREIGN KEY (`subscription_type_id`) REFERENCES `subscription_types` (`subscription_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
