-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2025 at 12:20 AM
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
-- Database: `unikart_db`
--
CREATE Database IF NOT EXISTS `unikart_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `unikart_db`;
-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(3, 'admin1', 'jose@gmail.com', '$2y$10$MTa1qbhPlgTwJExb0dsqcuvuUuTC7OXDP3CZaKdmfmPp5fvj/8yXm', 'Ssemakula Joseph', 'admin', 1, NULL, '2025-11-13 21:19:15', '2025-11-13 21:19:15'),
(2, 'admin2', 'admin2@unikart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Balaam Mujuni', 'admin', 1, '2025-11-14 00:19:58', '2025-11-11 21:22:00', '2025-11-13 21:19:58');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `product_id`, `quantity`, `price`, `attributes`, `session_id`, `is_active`, `added_at`, `updated_at`) VALUES
(5, 1, 4, 2, 75000.00, NULL, NULL, 1, '2025-11-13 09:45:36', '2025-11-13 20:32:31'),
(4, 1, 3, 3, 1200000.00, NULL, NULL, 1, '2025-11-13 06:49:13', '2025-11-13 20:32:47'),
(6, 1, 9, 1, 25000.00, NULL, NULL, 1, '2025-11-13 15:33:34', '2025-11-13 15:33:34'),
(7, 5, 3, 1, 1200000.00, NULL, NULL, 1, '2025-11-13 21:59:40', '2025-11-13 21:59:40'),
(8, 5, 4, 1, 75000.00, NULL, NULL, 1, '2025-11-13 22:17:00', '2025-11-13 22:17:00');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE IF NOT EXISTS `deliveries` (
  `delivery_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `partner_id` int DEFAULT NULL,
  `delivery_status` enum('pending','on_the_way','delivered') DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  PRIMARY KEY (`delivery_id`),
  KEY `order_id` (`order_id`),
  KEY `partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_partner`
--

DROP TABLE IF EXISTS `delivery_partner`;
CREATE TABLE IF NOT EXISTS `delivery_partner` (
  `partner_id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `status` enum('available','busy','inactive') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `delivery_method` enum('delivery','pickup') NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time DEFAULT NULL,
  `pickup_station` varchar(100) DEFAULT NULL,
  `delivery_address` varchar(255) DEFAULT NULL,
  `payment_method` enum('mobile_money','cash_on_delivery') NOT NULL,
  `mobile_money_provider` enum('mtn momo','airtel money') DEFAULT NULL,
  `mobile_money_number` varchar(15) DEFAULT NULL,
  `order_status` enum('pending','confirmed','processing','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `account_id`, `total_amount`, `delivery_method`, `delivery_date`, `delivery_time`, `pickup_station`, `delivery_address`, `payment_method`, `mobile_money_provider`, `mobile_money_number`, `order_status`, `created_at`) VALUES
(3, 1, 1200000.00, 'pickup', '2025-11-14', '14:00:00', 'Kihumuro campus (Joel&#39;s place)', 'Pickup Station: Kihumuro campus (Joel&#39;s place)', 'mobile_money', 'mtn momo', '0709247760', 'pending', '2025-11-13 09:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
CREATE TABLE IF NOT EXISTS `order_item` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hot_deal` tinyint(1) DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`product_id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `supplier_id`, `category`, `name`, `description`, `price`, `stock`, `image_url`, `created_at`, `hot_deal`, `featured`) VALUES
(3, 1, 'computing', 'laptop(HP pavillion)', 'strong machine i7 256SSD', 1200000.00, 200, 'HP Pavilion 15.jpeg', '2025-11-11 21:47:56', 0, 0),
(4, 1, 'clothing', 'shoe(for male)', 'quality and durable shoes', 75000.00, 40, 'menb5.webp', '2025-11-11 23:28:52', 0, 0),
(6, 1, 'clothing', 'Women Straight Fit-Rise Stretchable Jeans', 'no fade black jeans ,clean look , length: regular', 35000.00, 100, 'lj3.webp', '2025-11-12 17:14:04', 0, 0),
(7, 1, 'groceries', 'Numa Karo', '5kgs of Numa karo ..&#13;&#10;This is a blend of millet and cassava sourced from naturally and carefully grown plantations by our local farmers to give the finest nutritious and quality flour.&#13;&#10;&#13;&#10;It is full of a tasty aroma and not to mention, packed with nutrients that help with blood sugar management and enhance the immune function in the body.', 21000.00, 100, 'numakaro.jpg', '2025-11-13 13:36:18', 0, 0),
(8, 1, 'groceries', 'Supreme Wheat(2kg)', '2kg fortified wheat flour Premium Home baking flour fortified vitamin &#38; minerals', 7500.00, 100, 'supremewheat.jpeg', '2025-11-13 13:46:44', 0, 0),
(9, 1, 'smartphones', 'beats', 'durable', 25000.00, 50, 'beats.png', '2025-11-13 15:33:14', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `university_email` varchar(100) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `UQ_student_contact` (`contact`),
  UNIQUE KEY `UQ_student_email` (`university_email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `full_name`, `university_email`, `contact`, `address`, `faculty`, `year_of_study`, `created_at`, `password`) VALUES
(1, 'lemar wan', 'wan@must.ac.ug', '0709247760', 'kihumuro', 'faculty of computing', 0, '2025-11-11 05:40:11', '$2y$10$GY7ZFeeJllXnSM9/ex1vKep5KBHfuc1Q.I5be8VonDGJTb26tB0mK'),
(5, 'Namara Mark', 'namara@must.ac.ug', '0709247728', 'mile 4', 'faculty of education', 3, '2025-11-13 21:57:33', '$2y$10$om54H4A3Agb7N2xg6Eg1peO3KPdNeIcRTrJnp33bQdtF6StOX.gtq');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
CREATE TABLE IF NOT EXISTS `supplier` (
  `supplier_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

DROP TABLE IF EXISTS `user_account`;
CREATE TABLE IF NOT EXISTS `user_account` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `university_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_status` enum('active','inactive','suspended','locked') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `university_email` (`university_email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`account_id`, `university_email`, `created_at`, `account_status`, `email_verified`, `last_login`, `login_attempts`) VALUES
(1, 'wan@must.ac.ug', '2025-11-11 05:40:11', 'active', 0, '2025-11-13 19:14:15', 0),
(2, 'namara@must.ac.ug', '2025-11-13 21:57:33', 'active', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `wishlist_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_added_at` (`added_at`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlist_id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(6, 1, 6, 1, '2025-11-13 20:31:44'),
(7, 1, 7, 1, '2025-11-13 20:31:58'),
(8, 1, 8, 1, '2025-11-13 20:32:08'),
(12, 5, 8, 1, '2025-11-13 22:45:05'),
(11, 5, 7, 1, '2025-11-13 22:44:59');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `delivery_partner` (`partner_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `user_account` (`account_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
