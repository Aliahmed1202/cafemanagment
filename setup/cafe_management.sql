-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 25, 2026 at 05:01 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `description` text,
  `description_ar` text,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `name_ar`, `description`, `description_ar`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Coffee', 'قهوة', 'Various types of coffee beverages', 'أنواع مختلفة من مشروبات القهوة', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(2, 'Tea', 'شاي', 'Different tea varieties', 'أنواع مختلفة من الشاي', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(3, 'Juices', 'عصائر', 'Fresh fruit juices', 'عصائر فواكه طازجة', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(4, 'Sandwiches', 'سندويشات', 'Fresh sandwiches and wraps', 'سندويشات و wraps طازجة', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(5, 'Desserts', 'حلويات', 'Sweet treats and desserts', 'الحلويات والمعجنات', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(6, 'Snacks', 'وجبات خفيفة', 'Light snacks and appetizers', 'وجبات خفيفة ومقبلات', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `loyalty_points` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_customers_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `loyalty_points`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'john@email.com', '966500000101', 'Riyadh, Saudi Arabia', 150, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(2, 'Sarah Johnson', 'sarah@email.com', '966500000102', 'Jeddah, Saudi Arabia', 320, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(3, 'Ahmed Al-Rashid', 'ahmed@email.com', '966500000103', 'Dammam, Saudi Arabia', 280, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(4, 'Fatima Hassan', 'fatima@email.com', '966500000104', 'Mecca, Saudi Arabia', 450, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(5, 'Mohammed Ali', 'mohammed@email.com', '966500000105', 'Medina, Saudi Arabia', 200, '2026-03-06 01:17:29', '2026-03-06 01:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `daily_closing_inventory`
--

DROP TABLE IF EXISTS `daily_closing_inventory`;
CREATE TABLE IF NOT EXISTS `daily_closing_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `closing_date` date NOT NULL,
  `inventory_id` int NOT NULL,
  `opening_stock` decimal(10,2) NOT NULL,
  `closing_stock` decimal(10,2) NOT NULL,
  `consumed_quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_inventory` (`closing_date`,`inventory_id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `daily_closing_inventory`
--

INSERT INTO `daily_closing_inventory` (`id`, `closing_date`, `inventory_id`, `opening_stock`, `closing_stock`, `consumed_quantity`, `unit_cost`, `total_cost`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-03-05', 1, 55.00, 50.00, 5.00, 80.00, 400.00, 'Normal daily consumption', 1, '2026-03-06 01:17:29'),
(2, '2026-03-05', 2, 25.00, 20.00, 5.00, 6.00, 30.00, 'Normal daily consumption', 1, '2026-03-06 01:17:29'),
(3, '2026-03-05', 3, 105.00, 100.00, 5.00, 4.00, 20.00, 'Normal daily consumption', 1, '2026-03-06 01:17:29'),
(4, '2026-03-05', 4, 35.00, 30.00, 5.00, 2.00, 10.00, 'Normal daily consumption', 1, '2026-03-06 01:17:29'),
(5, '2026-03-05', 5, 45.00, 40.00, 5.00, 35.00, 175.00, 'Normal daily consumption', 1, '2026-03-06 01:17:29'),
(6, '2026-04-07', 4, 70.00, 50.00, 20.00, 2.00, 40.00, '0', 2, '2026-04-07 23:34:52'),
(7, '2026-04-08', 6, 44.00, 30.00, 14.00, 35.00, 490.00, '', 2, '2026-04-08 15:28:24'),
(8, '2026-04-20', 6, 50.00, 20.00, 0.00, 0.00, 0.00, '', 1, '2026-04-20 20:26:37'),
(9, '2026-04-21', 6, 20.00, 10.00, 10.00, 35.00, 350.00, '0', 1, '2026-04-21 15:37:24'),
(10, '2026-04-21', 4, 70.00, 50.00, 20.00, 2.00, 40.00, '0', 1, '2026-04-21 19:07:24');

-- --------------------------------------------------------

--
-- Stand-in structure for view `daily_closing_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `daily_closing_summary`;
CREATE TABLE IF NOT EXISTS `daily_closing_summary` (
`closing_date` date
,`item_name` varchar(100)
,`item_name_ar` varchar(100)
,`unit` varchar(20)
,`opening_stock` decimal(10,2)
,`closing_stock` decimal(10,2)
,`consumed_quantity` decimal(10,2)
,`unit_cost` decimal(10,2)
,`total_cost` decimal(10,2)
,`notes` text
,`created_by` varchar(50)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `item_name_ar` varchar(100) NOT NULL,
  `description` text,
  `current_stock` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `minimum_stock` decimal(10,2) DEFAULT '0.00',
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `item_name_ar`, `description`, `current_stock`, `unit`, `minimum_stock`, `unit_cost`, `supplier`, `last_restocked`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Coffee Beans', 'حبوب القهوة', 'Premium Arabica coffee beans', 50.00, 'kg', 10.00, 80.00, 'Coffee Supplier Co.', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(2, 'Milk', 'حليب', 'Fresh whole milk', 20.00, 'liters', 5.00, 6.00, 'Dairy Farm', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(3, 'Sugar', 'سكر', 'White granulated sugar', 100.00, 'kg', 20.00, 4.00, 'Food Supplier', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(4, 'Bread', 'خبز', 'Fresh sandwich bread', 50.00, 'pieces', 10.00, 2.00, 'Bakery Co.', '2026-04-08', 'active', '2026-03-06 01:17:29', '2026-04-21 19:07:24'),
(5, 'Chicken Breast', 'صدر دجاج', 'Fresh chicken breast', 30.00, 'kg', 8.00, 25.00, 'Meat Supplier', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(6, 'Cheese', 'جبنة', 'Cheddar cheese slices', 50.00, 'kg', 10.00, 35.00, 'Dairy Farm', '2026-04-24', 'active', '2026-03-06 01:17:29', '2026-04-24 22:33:07'),
(8, 'Oranges', 'برتقال', 'Fresh oranges for juice', 80.00, 'kg', 20.00, 5.00, 'Fruit Market', NULL, 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29');

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_status`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `inventory_status`;
CREATE TABLE IF NOT EXISTS `inventory_status` (
`item_name` varchar(100)
,`item_name_ar` varchar(100)
,`current_stock` decimal(10,2)
,`unit` varchar(20)
,`minimum_stock` decimal(10,2)
,`stock_status` varchar(12)
,`supplier_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('success','failed') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `description` text,
  `description_ar` text,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `ingredients` text,
  `allergens` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `preparation_time` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_items_category` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `name_ar`, `description`, `description_ar`, `price`, `cost_price`, `image_url`, `ingredients`, `allergens`, `status`, `preparation_time`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test Update', 'إسبريسو', 'Strong black coffee', 'قهوة سوداء قوية', 8.00, 2.50, NULL, NULL, NULL, 'active', 3, '2026-03-06 01:17:29', '2026-04-08 16:54:00'),
(2, 1, 'Cappuccino', 'كابتشينو', 'Espresso with steamed milk foam', 'إسبريسو مع حليب مبخور ورغوة', 12.00, 3.50, NULL, NULL, NULL, 'active', 5, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(3, 1, 'Latte', 'لاتيه', 'Espresso with steamed milk', 'إسبريسو مع حليب مبخور', 14.00, 4.00, NULL, NULL, NULL, 'active', 5, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(4, 1, 'Turkish Coffee', 'قهوة تركية', 'Traditional Turkish coffee', 'قهوة تركية تقليدية', 10.00, 3.00, NULL, NULL, NULL, 'active', 7, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(5, 2, 'Green Tea', 'شاي أخضر', 'Fresh green tea leaves', 'أوراق شاي أخضر طازجة', 6.00, 1.50, NULL, NULL, NULL, 'active', 4, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(6, 2, 'Mint Tea', 'شاي بالنعناع', 'Tea with fresh mint leaves', 'شاي مع أوراق نعناع طازجة', 7.00, 1.80, NULL, NULL, NULL, 'active', 4, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(7, 3, 'Orange Juice', 'عصير برتقال', 'Fresh squeezed orange juice', 'عصير برتقال طازج', 15.00, 5.00, NULL, NULL, NULL, 'active', 3, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(8, 3, 'Apple Juice', 'عصير تفاح', 'Fresh apple juice', 'عصير تفاح طازج', 14.00, 4.50, NULL, '', '', 'active', 3, '2026-03-06 01:17:29', '2026-03-06 20:01:05'),
(9, 4, 'Club Sandwich', 'ساندويتش كلوب', 'Triple layer sandwich with chicken', 'ساندويتش ثلاثي الطبقات بالدجاج', 25.00, 12.00, NULL, NULL, NULL, 'active', 10, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(10, 4, 'Cheese Sandwich', 'ساندويتش جبنة', 'Grilled cheese sandwich', 'ساندويتش جبنة مشوية', 18.00, 8.00, NULL, NULL, NULL, 'active', 8, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(11, 5, 'Chocolate Cake', 'كيك شوكولاتة', 'Rich chocolate cake', 'كيك شوكولاتة غني', 20.00, 8.00, NULL, NULL, NULL, 'active', 2, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(13, 6, 'French Fries', 'بطاطس مقلية', 'Crispy golden fries', 'بطاطس مقلية مقرمشة ذهبية', 12.00, 4.00, NULL, NULL, NULL, 'active', 6, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(14, 6, 'Onion Rings', 'حلقات بصل', 'Crispy battered onion rings', 'حلقات بصل مقلية مقرمشة', 14.00, 5.00, NULL, NULL, NULL, 'active', 7, '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(15, 1, 'Fresh Orange Juice', 'عصير برتقال طازج', 'Freshly squeezed orange juice', 'عصير برتقال طازج', 4.50, 2.00, NULL, 'Fresh oranges', 'Citrus', 'active', 5, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(16, 1, 'Lemonade', 'الليمونادة', 'Fresh lemonade with mint', 'الليمونادة الطازجة مع النعناع', 3.50, 1.50, NULL, 'Fresh lemons, mint, sugar', 'Citrus', 'active', 5, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(17, 1, 'Iced Tea', 'الشاي المثلج', 'Refreshing iced tea', 'شاي منعش مثلج', 3.00, 1.00, NULL, 'Tea bags, ice, lemon', 'Caffeine', 'active', 3, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(18, 2, 'Espresso', 'الإسبريسو', 'Strong Italian espresso', 'إسبريسو إيطالي قوي', 2.50, 0.80, NULL, 'Coffee beans', 'Caffeine', 'active', 2, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(19, 2, 'Cappuccino', 'الكابتشينو', 'Espresso with steamed milk foam', 'إسبريسو مع حليب مبخر ورغوة', 4.00, 1.50, NULL, 'Coffee beans, milk', 'Dairy, caffeine', 'active', 4, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(20, 2, 'Latte', 'اللاتيه', 'Smooth espresso with steamed milk', 'إسبريسو ناعم مع الحليب المبخر', 4.50, 1.80, NULL, 'Coffee beans, milk', 'Dairy, caffeine', 'active', 4, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(21, 2, 'Mocha', 'الموكا', 'Espresso with chocolate and milk', 'إسبريسو بالشوكولاتة والحليب', 5.00, 2.20, NULL, 'Coffee beans, chocolate, milk', 'Dairy, caffeine, chocolate', 'active', 5, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(22, 3, 'Green Tea', 'الشاي الأخضر', 'Organic green tea', 'شاي أخضر عضوي', 3.00, 1.20, NULL, 'Green tea leaves', 'Caffeine', 'active', 3, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(23, 3, 'Mint Tea', 'شاي النعناع', 'Refreshing mint tea', 'شاي النعناع المنعش', 3.50, 1.30, NULL, 'Mint leaves, tea', 'Caffeine', 'active', 3, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(24, 3, 'Chamomile Tea', 'شاي البابونج', 'Relaxing chamomile tea', 'شاي البابونج المريح', 3.50, 1.40, NULL, 'Chamomile flowers', 'None', 'active', 3, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(25, 4, 'Croissant', 'الكرواسان', 'Buttery French croissant', 'كرواسان فرنسي زبدة', 3.00, 1.20, NULL, 'Flour, butter, yeast', 'Gluten, dairy', 'active', 8, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(26, 4, 'Chocolate Muffin', 'مافين الشوكولاتة', 'Rich chocolate muffin', 'مافين الشوكولاتة الغني', 3.50, 1.50, NULL, 'Chocolate, flour, eggs', 'Gluten, dairy, eggs', 'active', 12, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(27, 4, 'Danish Pastry', 'معجنات الدنماركية', 'Fruit-filled Danish pastry', 'معجنات دنماركية بالفواكه', 4.00, 1.80, NULL, 'Flour, butter, fruits', 'Gluten, dairy', 'active', 15, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(28, 5, 'Club Sandwich', 'ساندويتش النادي', 'Triple-decker club sandwich', 'ساندويتش النادي المكون من ثلاث طبقات', 8.50, 4.00, NULL, 'Bread, turkey, bacon, lettuce, tomato', 'Gluten, meat', 'active', 12, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(29, 5, 'Grilled Cheese', 'ساندويتش الجبن المشوي', 'Classic grilled cheese sandwich', 'ساندويتش الجبن المشوي الكلاسيكي', 6.00, 2.50, NULL, 'Bread, cheese, butter', 'Gluten, dairy', 'active', 8, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(31, 6, 'Chocolate Cake', 'كيكة الشوكولاتة', 'Decadent chocolate cake', 'كيكة الشوكولاتة الفاخرة', 5.50, 2.50, NULL, 'Chocolate, flour, eggs, sugar', 'Gluten, dairy, eggs, chocolate', 'active', 5, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(32, 6, 'Tiramisu', 'التياراميسو', 'Classic Italian tiramisu', 'تياراميسو إيطالي كلاسيكي', 6.00, 3.00, NULL, 'Mascarpone, coffee, ladyfingers', 'Dairy, caffeine, eggs', 'active', 5, '2026-03-06 01:17:30', '2026-03-06 01:17:30'),
(33, 6, 'Ice Cream Scoop', 'كرة الآيس كريم', 'Premium ice cream scoop', 'كرة آيس كريم ممتازة', 3.50, 1.50, NULL, 'Milk, cream, sugar, flavors', 'Dairy', 'active', 2, '2026-03-06 01:17:30', '2026-03-06 01:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int DEFAULT NULL,
  `table_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','mobile','credit') DEFAULT 'cash',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `status` enum('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  KEY `table_id` (`table_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `table_id`, `user_id`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(25, 'ORD20260425308', 0, 1, NULL, 0.00, 0.00, 0.00, 0.00, 'cash', '', '', '', '2026-04-25 13:53:56', '2026-04-25 13:53:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text,
  `status` enum('ordered','preparing','ready','served','cancelled') DEFAULT 'ordered',
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `idx_order_items_order_id` (`order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`, `special_instructions`, `status`) VALUES
(1, 2, 29, 1, 6.00, 6.00, '', 'ordered'),
(2, 2, 11, 1, 20.00, 20.00, '', 'ordered'),
(3, 3, 22, 1, 3.00, 3.00, '', 'ordered'),
(4, 3, 12, 1, 15.00, 15.00, '', 'ordered'),
(5, 3, 28, 1, 8.50, 8.50, '', 'ordered'),
(7, 9, 11, 1, 20.00, 20.00, '', 'ordered'),
(8, 13, 4, 1, 10.00, 10.00, '', 'ordered'),
(20, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(19, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(18, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(17, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(16, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(15, 14, 30, 1, 7.00, 7.00, '', 'ordered'),
(21, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(22, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(23, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(24, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(25, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(26, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(27, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(28, 15, 11, 1, 20.00, 20.00, '', 'ordered'),
(29, 21, 11, 1, 20.00, 20.00, '', 'ordered');

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `order_summary`;
CREATE TABLE IF NOT EXISTS `order_summary` (
`id` int
,`order_number` varchar(20)
,`customer_name` varchar(100)
,`table_number` varchar(10)
,`created_by` varchar(100)
,`total_amount` decimal(10,2)
,`status` enum('pending','preparing','ready','completed','cancelled')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_logs`
--

DROP TABLE IF EXISTS `password_reset_logs`;
CREATE TABLE IF NOT EXISTS `password_reset_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `request_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('requested','completed','failed') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_request_time` (`request_time`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `type` enum('income','outcome') NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `amount`, `description`, `type`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 800.00, 'Catering service payment', 'income', 1, '2026-04-08 22:20:10', '2026-04-08 22:20:10'),
(3, 500.00, 'Monthly coffee beans purchase', 'outcome', 2, '2026-04-08 22:20:10', '2026-04-08 22:20:10'),
(4, 300.00, 'Utility bills payment', 'outcome', 2, '2026-04-08 22:20:10', '2026-04-08 22:20:10'),
(5, 200.00, 'Cleaning supplies', 'outcome', 3, '2026-04-08 22:20:10', '2026-04-08 22:20:10'),
(7, 150.00, 'Milk and dairy products', 'outcome', 3, '2026-04-08 22:20:10', '2026-04-08 22:20:10'),
(8, 450.00, 'Private event booking', 'income', 1, '2026-04-08 22:20:10', '2026-04-08 22:20:10');

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `payment_summary`;
CREATE TABLE IF NOT EXISTS `payment_summary` (
`id` int
,`amount` decimal(10,2)
,`description` text
,`type` enum('income','outcome')
,`created_at` timestamp
,`created_by_name` varchar(100)
,`created_by_username` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Coffee Supplier Co.', 'Mr. Ahmed', 'ahmed@coffeesupplier.com', '966511111111', 'Riyadh, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(2, 'Dairy Farm', 'Ms. Fatima', 'fatima@dairyfarm.com', '966522222222', 'Jeddah, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(3, 'Food Supplier', 'Mr. Mohammed', 'mohammed@foodsupplier.com', '966533333333', 'Dammam, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(4, 'Bakery Co.', 'Ms. Sarah', 'sarah@bakery.com', '966544444444', 'Mecca, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(5, 'Meat Supplier', 'Mr. John', 'john@meatsupplier.com', '966555555555', 'Medina, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(6, 'Vegetable Supplier', 'Ms. Maryam', 'maryam@vegetables.com', '966566666666', 'Riyadh, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29'),
(7, 'Fruit Market', 'Mr. Ali', 'ali@fruitmarket.com', '966577777777', 'Jeddah, Saudi Arabia', 'active', '2026-03-06 01:17:29', '2026-03-06 01:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

DROP TABLE IF EXISTS `tables`;
CREATE TABLE IF NOT EXISTS `tables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_number` varchar(10) NOT NULL,
  `capacity` int NOT NULL,
  `status` enum('available','occupied','reserved','cleaning') DEFAULT 'available',
  `location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_number` (`table_number`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `capacity`, `status`, `location`, `created_at`, `updated_at`) VALUES
(1, 'T1', 4, '', 'Indoor', '2026-03-06 01:17:29', '2026-04-25 13:35:56'),
(2, 'T2', 4, '', 'Indoor', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(3, 'T3', 2, '', 'Indoor', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(4, 'T4', 6, '', 'Indoor', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(5, 'T5', 4, '', 'Outdoor', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(6, 'T6', 4, '', 'Outdoor', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(7, 'T7', 8, '', 'Private Room', '2026-03-06 01:17:29', '2026-04-23 19:12:53'),
(10, 'T11', 11, '', 'Indoor', '2026-04-23 18:47:53', '2026-04-25 13:23:36'),
(11, 'T22', 2, '', 'Indoor', '2026-04-25 13:37:15', '2026-04-25 13:37:15'),
(13, 'T12', 2, '', 'Outdoor', '2026-04-25 13:38:07', '2026-04-25 13:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('owner','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expiry` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`, `reset_token`, `reset_expiry`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin123', 'Administrator', 'admin@cafe.com', '01141500302', 'owner', 'active', NULL, NULL, NULL, '2026-03-06 01:17:29', '2026-04-08 16:43:20'),
(3, 'cashier1', 'cashier123', 'Fatima Ali', 'cashier1@cafe.com', '966500000003', 'staff', 'active', NULL, NULL, NULL, '2026-03-06 01:17:29', '2026-04-08 16:38:58'),
(4, 'waiter1', 'waiter123', 'Mohammed Salem', 'waiter1@cafe.com', '966500000004', 'staff', 'active', NULL, NULL, NULL, '2026-03-06 01:17:29', '2026-04-08 16:38:58');

-- --------------------------------------------------------

--
-- Structure for view `daily_closing_summary`
--
DROP TABLE IF EXISTS `daily_closing_summary`;

DROP VIEW IF EXISTS `daily_closing_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_closing_summary`  AS SELECT `dci`.`closing_date` AS `closing_date`, `i`.`item_name` AS `item_name`, `i`.`item_name_ar` AS `item_name_ar`, `i`.`unit` AS `unit`, `dci`.`opening_stock` AS `opening_stock`, `dci`.`closing_stock` AS `closing_stock`, `dci`.`consumed_quantity` AS `consumed_quantity`, `dci`.`unit_cost` AS `unit_cost`, `dci`.`total_cost` AS `total_cost`, `dci`.`notes` AS `notes`, `u`.`username` AS `created_by`, `dci`.`created_at` AS `created_at` FROM ((`daily_closing_inventory` `dci` join `inventory` `i` on((`dci`.`inventory_id` = `i`.`id`))) join `users` `u` on((`dci`.`created_by` = `u`.`id`))) ORDER BY `dci`.`closing_date` DESC, `i`.`item_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `inventory_status`
--
DROP TABLE IF EXISTS `inventory_status`;

DROP VIEW IF EXISTS `inventory_status`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_status`  AS SELECT `i`.`item_name` AS `item_name`, `i`.`item_name_ar` AS `item_name_ar`, `i`.`current_stock` AS `current_stock`, `i`.`unit` AS `unit`, `i`.`minimum_stock` AS `minimum_stock`, (case when (`i`.`current_stock` <= `i`.`minimum_stock`) then 'Low Stock' when (`i`.`current_stock` <= (`i`.`minimum_stock` * 1.5)) then 'Medium Stock' else 'Good Stock' end) AS `stock_status`, `s`.`name` AS `supplier_name` FROM (`inventory` `i` left join `suppliers` `s` on((`i`.`supplier` = `s`.`name`))) WHERE (`i`.`status` = 'active') ;

-- --------------------------------------------------------

--
-- Structure for view `order_summary`
--
DROP TABLE IF EXISTS `order_summary`;

DROP VIEW IF EXISTS `order_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`order_number` AS `order_number`, `c`.`name` AS `customer_name`, `t`.`table_number` AS `table_number`, `u`.`full_name` AS `created_by`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`created_at` AS `created_at` FROM (((`orders` `o` left join `customers` `c` on((`o`.`customer_id` = `c`.`id`))) left join `tables` `t` on((`o`.`table_id` = `t`.`id`))) left join `users` `u` on((`o`.`user_id` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `payment_summary`
--
DROP TABLE IF EXISTS `payment_summary`;

DROP VIEW IF EXISTS `payment_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_summary`  AS SELECT `p`.`id` AS `id`, `p`.`amount` AS `amount`, `p`.`description` AS `description`, `p`.`type` AS `type`, `p`.`created_at` AS `created_at`, `u`.`full_name` AS `created_by_name`, `u`.`username` AS `created_by_username` FROM (`payments` `p` left join `users` `u` on((`p`.`created_by` = `u`.`id`))) ORDER BY `p`.`created_at` DESC ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
