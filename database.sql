-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 05:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `custom_gifts_full_db`
--
CREATE DATABASE IF NOT EXISTS `custom_gifts_full_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `custom_gifts_full_db`;

-- --------------------------------------------------------

--
-- Table structure for table `order_issues`
--
-- Creation: May 30, 2026 at 10:36 PM
--

DROP TABLE IF EXISTS `order_issues`;
CREATE TABLE `order_issues` (
  `IssueID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `IssueType` varchar(100) NOT NULL,
  `IssueMessage` text NOT NULL,
  `IssueStatus` varchar(50) DEFAULT 'Open',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_issues`
--

INSERT INTO `order_issues` (`IssueID`, `OrderID`, `UserID`, `IssueType`, `IssueMessage`, `IssueStatus`, `CreatedAt`) VALUES
(1, 4, 5, 'Wrong Customization', 'wrong image', 'Open', '2026-05-25 23:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `productcatalog`
--
-- Creation: May 30, 2026 at 10:36 PM
--

DROP TABLE IF EXISTS `productcatalog`;
CREATE TABLE `productcatalog` (
  `CatalogProductID` int(11) NOT NULL,
  `ProductName` varchar(255) NOT NULL,
  `Category` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `ProductImage` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcatalog`
--

INSERT INTO `productcatalog` (`CatalogProductID`, `ProductName`, `Category`, `Description`, `ProductImage`, `CreatedAt`) VALUES
(1, 'Mug', 'Drinkware', '90, 150, and 250 ml', 'mugs.jpg', '2026-05-25 20:35:04'),
(2, 'Thermal Flasks', 'Drinkware', '750 ml, 1.5L, and 3L', 'flask.jpg', '2026-05-25 20:35:04'),
(3, 'Classic Notebook', 'Stationary', '60, 80, 120 pages', 'notebook.jpg', '2026-05-25 20:35:04'),
(4, 'Wired Notebook', 'Stationary', '60, 80, 120 pages', 'wired-notebook.jpg', '2026-05-25 20:35:04'),
(5, 'Set 1', 'Sets', 'Bottle, Notebook and Pen', 'set1.jpg', '2026-05-25 20:35:04'),
(6, 'Set 2', 'Sets', 'Notebook and Mug', 'set2.jpg', '2026-05-25 20:35:04'),
(7, 'Set 3', 'Sets', 'Notebook, Key chain, Pen, 2 Mugs', 'set3.jpg', '2026-05-25 20:35:04'),
(8, 'Set 4', 'Sets', 'Bottle, Mug, Notebook and Pen', 'set4.jpg', '2026-05-25 20:35:04'),
(9, 'Set 5', 'Sets', 'Notebook, Pen, Key chain, Mug, Bottle, Speakers and cardholder', 'set5.jpg', '2026-05-25 20:35:04'),
(10, 'Set 6', 'Sets', 'Notebook, power bank, pen and USB flash drive', 'set6.jpg', '2026-05-25 20:35:04'),
(11, 'Set 7', 'Sets', 'Bottle, USB cable, Pen, Plug and Power bank', 'set7.jpg', '2026-05-25 20:35:04'),
(12, 'Set 8', 'Sets', 'Bottle, Pen, USB flash drive, Speaker and power bank', 'set8.jpg', '2026-05-25 20:35:04'),
(13, 'USB Flash Drive', 'Accessories', 'Plastic, Metal and Leather', 'usb.jpg', '2026-05-25 20:35:04'),
(14, 'Key Chain', 'Accessories', 'Plastic, Metal and Leather', 'keychain.jpg', '2026-05-25 20:35:04'),
(15, 'Power Bank', 'Accessories', '5000, 10000, and 20000 mah', 'powerbank.jpg', '2026-05-25 20:35:04');

-- --------------------------------------------------------

--
-- Table structure for table `productcatalogoption`
--
-- Creation: May 30, 2026 at 10:36 PM
--

DROP TABLE IF EXISTS `productcatalogoption`;
CREATE TABLE `productcatalogoption` (
  `OptionID` int(11) NOT NULL,
  `CatalogProductID` int(11) NOT NULL,
  `OptionName` varchar(100) NOT NULL,
  `Price` decimal(10,2) NOT NULL CHECK (`Price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcatalogoption`
--

INSERT INTO `productcatalogoption` (`OptionID`, `CatalogProductID`, `OptionName`, `Price`) VALUES
(1, 1, '90 ml', 60.00),
(2, 1, '150 ml', 80.00),
(3, 1, '250 ml', 100.00),
(4, 2, '750 ml', 70.00),
(5, 2, '1.5 L', 90.00),
(6, 2, '3 L', 150.00),
(7, 3, '60 pages', 60.00),
(8, 3, '80 pages', 80.00),
(9, 3, '120 pages', 100.00),
(10, 4, '60 pages', 70.00),
(11, 4, '80 pages', 90.00),
(12, 4, '120 pages', 110.00),
(13, 5, 'Lowest Quality', 200.00),
(14, 5, 'Medium Quality', 270.00),
(15, 5, 'Highest Quality', 350.00),
(16, 6, 'Lowest Quality', 150.00),
(17, 6, 'Medium Quality', 190.00),
(18, 6, 'Highest Quality', 230.00),
(19, 7, 'Lowest Quality', 300.00),
(20, 7, 'Medium Quality', 500.00),
(21, 7, 'Highest Quality', 700.00),
(22, 8, 'Lowest Quality', 250.00),
(23, 8, 'Medium Quality', 300.00),
(24, 8, 'Highest Quality', 400.00),
(25, 9, 'Lowest Quality', 450.00),
(26, 9, 'Medium Quality', 650.00),
(27, 9, 'Highest Quality', 900.00),
(28, 10, 'Lowest Quality', 230.00),
(29, 10, 'Medium Quality', 310.00),
(30, 10, 'Highest Quality', 400.00),
(31, 11, 'Lowest Quality', 230.00),
(32, 11, 'Medium Quality', 340.00),
(33, 11, 'Highest Quality', 410.00),
(34, 12, 'Lowest Quality', 300.00),
(35, 12, 'Medium Quality', 450.00),
(36, 12, 'Highest Quality', 600.00),
(37, 13, 'Plastic', 150.00),
(38, 13, 'Metal', 300.00),
(39, 13, 'Leather', 350.00),
(40, 14, 'Plastic', 60.00),
(41, 14, 'Metal', 80.00),
(42, 14, 'Leather', 100.00),
(43, 15, '5000 mah', 400.00),
(44, 15, '10000 mah', 700.00),
(45, 15, '20000 mah', 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_option_offers`
--
-- Creation: May 31, 2026 at 02:23 AM
-- Last update: May 31, 2026 at 02:23 AM
--

DROP TABLE IF EXISTS `supplier_option_offers`;
CREATE TABLE `supplier_option_offers` (
  `OfferID` int(11) NOT NULL,
  `OptionID` int(11) NOT NULL,
  `SupplierProfileID` int(11) NOT NULL,
  `ExtraCost` decimal(10,2) DEFAULT 0.00,
  `ProductionTime` varchar(50) DEFAULT '5-7 days',
  `OfferLabel` varchar(80) DEFAULT 'Standard Offer',
  `OfferDescription` varchar(255) DEFAULT NULL,
  `IsAvailable` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_option_offers`
--

INSERT INTO `supplier_option_offers` (`OfferID`, `OptionID`, `SupplierProfileID`, `ExtraCost`, `ProductionTime`, `OfferLabel`, `OfferDescription`, `IsAvailable`, `CreatedAt`) VALUES
(1, 1, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(2, 1, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(3, 1, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(4, 1, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(5, 1, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(6, 1, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(7, 1, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(8, 1, 19, 100.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(9, 2, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(10, 2, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(11, 2, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(12, 2, 9, 110.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(13, 2, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(14, 2, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(15, 2, 15, 110.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(16, 2, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(17, 3, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(18, 3, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(19, 3, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(20, 3, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(21, 3, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(22, 3, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(23, 3, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(24, 3, 19, 120.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(25, 4, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(26, 4, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(27, 4, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(28, 4, 9, 130.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(29, 4, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(30, 4, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(31, 4, 15, 130.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(32, 4, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(33, 5, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(34, 5, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(35, 5, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(36, 5, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(37, 5, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(38, 5, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(39, 5, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(40, 5, 19, 90.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(41, 6, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(42, 6, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(43, 6, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(44, 6, 9, 100.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(45, 6, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(46, 6, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(47, 6, 15, 100.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(48, 6, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(49, 7, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(50, 7, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(51, 7, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(52, 7, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(53, 7, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(54, 7, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(55, 7, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(56, 7, 19, 110.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(57, 8, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(58, 8, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(59, 8, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(60, 8, 9, 120.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(61, 8, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(62, 8, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(63, 8, 15, 120.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(64, 8, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(65, 9, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(66, 9, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(67, 9, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(68, 9, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(69, 9, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(70, 9, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(71, 9, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(72, 9, 19, 130.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(73, 10, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(74, 10, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(75, 10, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(76, 10, 9, 90.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(77, 10, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(78, 10, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(79, 10, 15, 90.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(80, 10, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(81, 11, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(82, 11, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(83, 11, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(84, 11, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(85, 11, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(86, 11, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(87, 11, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(88, 11, 19, 100.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(89, 12, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(90, 12, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(91, 12, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(92, 12, 9, 110.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(93, 12, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(94, 12, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(95, 12, 15, 110.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(96, 12, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(97, 13, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(98, 13, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(99, 13, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(100, 13, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(101, 13, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(102, 13, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(103, 13, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(104, 13, 19, 120.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(105, 14, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(106, 14, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(107, 14, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(108, 14, 9, 130.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(109, 14, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(110, 14, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(111, 14, 15, 130.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(112, 14, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(113, 15, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(114, 15, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(115, 15, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(116, 15, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(117, 15, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(118, 15, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(119, 15, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(120, 15, 19, 90.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(121, 16, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(122, 16, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(123, 16, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(124, 16, 9, 100.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(125, 16, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(126, 16, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(127, 16, 15, 100.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(128, 16, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(129, 17, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(130, 17, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(131, 17, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(132, 17, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(133, 17, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(134, 17, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(135, 17, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(136, 17, 19, 110.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(137, 18, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(138, 18, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(139, 18, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(140, 18, 9, 120.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(141, 18, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(142, 18, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(143, 18, 15, 120.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(144, 18, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(145, 19, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(146, 19, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(147, 19, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(148, 19, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(149, 19, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(150, 19, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(151, 19, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(152, 19, 19, 130.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(153, 20, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(154, 20, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(155, 20, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(156, 20, 9, 90.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(157, 20, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(158, 20, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(159, 20, 15, 90.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(160, 20, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(161, 21, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(162, 21, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(163, 21, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(164, 21, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(165, 21, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(166, 21, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(167, 21, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(168, 21, 19, 100.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(169, 22, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(170, 22, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(171, 22, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(172, 22, 9, 110.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(173, 22, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(174, 22, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(175, 22, 15, 110.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(176, 22, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(177, 23, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(178, 23, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(179, 23, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(180, 23, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(181, 23, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(182, 23, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(183, 23, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(184, 23, 19, 120.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(185, 24, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(186, 24, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(187, 24, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(188, 24, 9, 130.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(189, 24, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(190, 24, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(191, 24, 15, 130.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(192, 24, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(193, 25, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(194, 25, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(195, 25, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(196, 25, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(197, 25, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(198, 25, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(199, 25, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(200, 25, 19, 90.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(201, 26, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(202, 26, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(203, 26, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(204, 26, 9, 100.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(205, 26, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(206, 26, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(207, 26, 15, 100.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(208, 26, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(209, 27, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(210, 27, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(211, 27, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(212, 27, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(213, 27, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(214, 27, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(215, 27, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(216, 27, 19, 110.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(217, 28, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(218, 28, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(219, 28, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(220, 28, 9, 120.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(221, 28, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(222, 28, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(223, 28, 15, 120.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(224, 28, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(225, 29, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(226, 29, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(227, 29, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(228, 29, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(229, 29, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(230, 29, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(231, 29, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(232, 29, 19, 130.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(233, 30, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(234, 30, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(235, 30, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(236, 30, 9, 90.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(237, 30, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(238, 30, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(239, 30, 15, 90.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(240, 30, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(241, 31, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(242, 31, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(243, 31, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(244, 31, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(245, 31, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(246, 31, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(247, 31, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(248, 31, 19, 100.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(249, 32, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(250, 32, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(251, 32, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(252, 32, 9, 110.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(253, 32, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(254, 32, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(255, 32, 15, 110.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(256, 32, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(257, 33, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(258, 33, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(259, 33, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(260, 33, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(261, 33, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(262, 33, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(263, 33, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(264, 33, 19, 120.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(265, 34, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(266, 34, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(267, 34, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(268, 34, 9, 130.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(269, 34, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(270, 34, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(271, 34, 15, 130.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(272, 34, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(273, 35, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(274, 35, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(275, 35, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(276, 35, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(277, 35, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(278, 35, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(279, 35, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(280, 35, 19, 90.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(281, 36, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(282, 36, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(283, 36, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(284, 36, 9, 100.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(285, 36, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(286, 36, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(287, 36, 15, 100.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(288, 36, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(289, 37, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(290, 37, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(291, 37, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(292, 37, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(293, 37, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(294, 37, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(295, 37, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(296, 37, 19, 110.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(297, 38, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(298, 38, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(299, 38, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(300, 38, 9, 120.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(301, 38, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(302, 38, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(303, 38, 15, 120.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(304, 38, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(305, 39, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(306, 39, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(307, 39, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(308, 39, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(309, 39, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(310, 39, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(311, 39, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(312, 39, 19, 130.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(313, 40, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(314, 40, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(315, 40, 7, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(316, 40, 9, 90.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(317, 40, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(318, 40, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(319, 40, 15, 90.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(320, 40, 17, 50.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(321, 41, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(322, 41, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(323, 41, 10, 20.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(324, 41, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(325, 41, 13, 20.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(326, 41, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(327, 41, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(328, 41, 19, 100.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(329, 42, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(330, 42, 5, 35.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(331, 42, 7, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(332, 42, 9, 110.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(333, 42, 12, 35.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(334, 42, 14, 85.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(335, 42, 15, 110.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(336, 42, 17, 40.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(337, 43, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(338, 43, 8, 95.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(339, 43, 10, 15.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(340, 43, 11, 95.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(341, 43, 13, 15.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(342, 43, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(343, 43, 18, 40.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(344, 43, 19, 120.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(345, 44, 4, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(346, 44, 5, 25.00, '4-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(347, 44, 7, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(348, 44, 9, 130.00, '6-8 days', 'Eco Packaging', 'Includes eco-friendly packaging and better presentation.', 1, '2026-05-31 02:23:14'),
(349, 44, 12, 25.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(350, 44, 14, 65.00, '3-6 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(351, 44, 15, 130.00, '6-8 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14'),
(352, 44, 17, 60.00, '3-5 days', 'Fast Delivery', 'Faster production option suitable for urgent orders.', 1, '2026-05-31 02:23:14'),
(353, 45, 6, 0.00, '5-8 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(354, 45, 8, 75.00, '5-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(355, 45, 10, 10.00, '4-6 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(356, 45, 11, 75.00, '4-7 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(357, 45, 13, 10.00, '5-7 days', 'Standard Offer', 'Reliable standard supplier for customized products.', 1, '2026-05-31 02:23:14'),
(358, 45, 16, 0.00, '5-7 days', 'Best Price', 'Affordable supplier with no extra supplier cost.', 1, '2026-05-31 02:23:14'),
(359, 45, 18, 30.00, '5-8 days', 'Bulk Ready', 'Suitable for bulk and business orders.', 1, '2026-05-31 02:23:14'),
(360, 45, 19, 90.00, '7-10 days', 'Top Rated', 'Highly rated supplier with premium finishing quality.', 1, '2026-05-31 02:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_profiles`
--
-- Creation: May 30, 2026 at 10:58 PM
-- Last update: May 31, 2026 at 02:23 AM
--

DROP TABLE IF EXISTS `supplier_profiles`;
CREATE TABLE `supplier_profiles` (
  `SupplierProfileID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `SupplierName` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Phone` varchar(30) DEFAULT NULL,
  `Specialty` varchar(255) NOT NULL,
  `PriceLevel` varchar(50) DEFAULT 'Medium',
  `ProductionTime` varchar(100) DEFAULT '7 to 10 days',
  `Rating` decimal(2,1) DEFAULT 4.5,
  `IsVerified` tinyint(4) DEFAULT 1,
  `IsBulkReady` tinyint(4) DEFAULT 1,
  `IsEcoPackaging` tinyint(4) DEFAULT 0,
  `Status` varchar(50) DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_profiles`
--

INSERT INTO `supplier_profiles` (`SupplierProfileID`, `UserID`, `SupplierName`, `Email`, `Phone`, `Specialty`, `PriceLevel`, `ProductionTime`, `Rating`, `IsVerified`, `IsBulkReady`, `IsEcoPackaging`, `Status`, `CreatedAt`) VALUES
(1, 2, 'Premium Printing Supplier', 'printing@taggy.com', '01033334444', 'Mugs, T-Shirts, Notebooks', 'Medium', '5 to 7 days', 4.8, 1, 1, 0, 'Active', '2026-05-25 23:28:57'),
(2, NULL, 'Corporate Gift Supplier', 'corporate@taggy.com', '01044445555', 'Gift Sets, Power Banks, Pens', 'High', '7 to 12 days', 4.9, 1, 1, 1, 'Active', '2026-05-25 23:28:57'),
(3, NULL, 'Fast Promo Supplier', 'fastpromo@taggy.com', '01055556666', 'Keychains, Tote Bags, Caps', 'Low', '3 to 5 days', 4.4, 1, 0, 0, 'Active', '2026-05-25 23:28:57'),
(4, 13, 'Cairo Print House', 'supplier1@taggy.com', '01000000001', 'General customized gifts and printing', 'Low', '5-7 days', 3.9, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(5, 14, 'Nile Branding Solutions', 'supplier2@taggy.com', '01000000002', 'Branding, packaging, and giveaways', 'Medium', '4-6 days', 4.1, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(6, 15, 'Delta Gifts Factory', 'supplier3@taggy.com', '01000000003', 'Customized gifts and bulk orders', 'Low', '5-8 days', 4.0, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(7, 16, 'Alex Promo Studio', 'supplier4@taggy.com', '01000000004', 'Promotional products and printed items', 'Medium', '3-5 days', 4.4, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(8, 17, 'Giza Corporate Supplies', 'supplier5@taggy.com', '01000000005', 'Corporate gifts and business orders', 'High', '5-7 days', 4.6, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(9, 18, 'Maadi Print Lab', 'supplier6@taggy.com', '01000000006', 'High quality customization and finishing', 'High', '6-8 days', 4.8, 1, 1, 1, 'Active', '2026-05-31 02:23:14'),
(10, 19, 'Nasr City Gifts Hub', 'supplier7@taggy.com', '01000000007', 'Affordable customized products', 'Low', '4-6 days', 4.0, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(11, 20, 'Heliopolis Branding Co.', 'supplier8@taggy.com', '01000000008', 'Premium branding and gift customization', 'High', '4-7 days', 4.7, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(12, 21, 'Mansoura Promo Works', 'supplier9@taggy.com', '01000000009', 'Printed promotional products', 'Medium', '4-6 days', 4.3, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(13, 22, 'Tanta Custom Gifts', 'supplier10@taggy.com', '01000000010', 'Customized gifts for individuals and businesses', 'Low', '5-7 days', 4.1, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(14, 23, 'October Branding Center', 'supplier11@taggy.com', '01000000011', 'Corporate branding and bulk orders', 'Medium', '3-6 days', 4.6, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(15, 24, 'Zamalek Creative Print', 'supplier12@taggy.com', '01000000012', 'Premium design finishing and packaging', 'High', '6-8 days', 4.9, 1, 1, 1, 'Active', '2026-05-31 02:23:14'),
(16, 25, 'Shoubra Pack & Print', 'supplier13@taggy.com', '01000000013', 'Budget printing and packaging', 'Low', '5-7 days', 3.9, 1, 0, 0, 'Active', '2026-05-31 02:23:14'),
(17, 26, 'Smart Promo Egypt', 'supplier14@taggy.com', '01000000014', 'Fast promotional products and giveaways', 'Medium', '3-5 days', 4.4, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(18, 27, 'Nile Delta Supplies', 'supplier15@taggy.com', '01000000015', 'Stationery, giveaways, and business supplies', 'Medium', '5-8 days', 4.2, 1, 1, 0, 'Active', '2026-05-31 02:23:14'),
(19, 28, 'Premium Mark Egypt', 'supplier16@taggy.com', '01000000016', 'Premium corporate gifts and luxury packaging', 'High', '7-10 days', 5.0, 1, 1, 1, 'Active', '2026-05-31 02:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: May 31, 2026 at 12:48 AM
-- Last update: May 31, 2026 at 02:23 AM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('customer','admin','supplier','delivery') NOT NULL DEFAULT 'customer',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `AccountType` varchar(30) DEFAULT 'individual',
  `CompanyName` varchar(255) DEFAULT NULL,
  `BusinessType` varchar(100) DEFAULT NULL,
  `OrderVolume` varchar(50) DEFAULT NULL,
  `SubscriptionPlan` varchar(50) DEFAULT 'none',
  `SubscriptionStatus` varchar(30) DEFAULT 'inactive',
  `SubscriptionPrice` decimal(10,2) DEFAULT 0.00,
  `SubscriptionPaymentMethod` varchar(100) DEFAULT NULL,
  `SubscriptionPaymentReference` varchar(150) DEFAULT NULL,
  `SubscriptionStartDate` date DEFAULT NULL,
  `SubscriptionEndDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FullName`, `Email`, `Password`, `Role`, `CreatedAt`, `AccountType`, `CompanyName`, `BusinessType`, `OrderVolume`, `SubscriptionPlan`, `SubscriptionStatus`, `SubscriptionPrice`, `SubscriptionPaymentMethod`, `SubscriptionPaymentReference`, `SubscriptionStartDate`, `SubscriptionEndDate`) VALUES
(1, 'Taggy Admin', 'admin@taggy.com', '$2y$12$erAfbcaE553eo/QH13Gpzuj61neqYko2/dKmfrB9CJUrIz6WaUEPK', 'admin', '2026-05-25 20:35:03', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(2, 'Supplier User', 'supplier@taggy.com', '$2y$12$Ha5Pn.eeqq5d2zJvX2uSBeO5agwomuTgl163MJ3AMLROpSpVM/o2S', 'supplier', '2026-05-25 20:35:03', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(3, 'Delivery User', 'delivery@taggy.com', '$2y$12$Ha5Pn.eeqq5d2zJvX2uSBeO5agwomuTgl163MJ3AMLROpSpVM/o2S', 'delivery', '2026-05-25 20:35:03', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(4, 'Customer User', 'customer@taggy.com', '$2y$12$Ha5Pn.eeqq5d2zJvX2uSBeO5agwomuTgl163MJ3AMLROpSpVM/o2S', 'customer', '2026-05-25 20:35:03', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(5, 'Marwa Ahmed', 'Marwaahmed51@gmail.com', '$2y$10$a.dPKQ8nArsjMF7CEP7pBeFEdTI6lhm0YbF7VNzOA/wDxqzBJfljC', 'customer', '2026-05-25 21:54:09', 'individual', '', '', '', 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(6, 'Elserag comapny', 'Elseragcompany21@gmail.com', '$2y$10$3iAqbVj3M3PBiSeHvxb7DuDlLSRvJW.VlXFKrf3UaWIDPKKH35ToC', 'customer', '2026-05-25 22:02:11', 'business', 'Elserag comapny', 'cars spare parts', '51-100', 'premium', 'active', 0.00, NULL, NULL, '2026-05-31', '2026-06-30'),
(7, 'Admin hana', 'Hanaadmin@taggy.com', '$2y$12$Ha5Pn.eeqq5d2zJvX2uSBeO5agwomuTgl163MJ3AMLROpSpVM/o2S', 'admin', '2026-05-25 23:07:27', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(8, 'Hossam Aglan', 'Hossamaglan34@gmail.com', '$2y$10$SbbJZRW6agZ9DPymiT/voO422QvtLAumN8eDSkfJunxJRi/yS0Wim', 'customer', '2026-05-26 18:42:02', 'individual', '', '', '', 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(9, 'farah khaled', 'faroohakhaled@gmail.com', '$2y$10$vcszHbaPTFWVnMP1G2f3fuhjXgS0ctesu.qUsIs2ncl/HuY97PjrS', 'customer', '2026-05-30 23:11:48', 'business', 'farooha', 'events', '101-500', 'growth', 'active', 0.00, NULL, NULL, '2026-05-31', '2026-06-30'),
(10, 'Small Delivery Company', 'small.delivery@taggy.com', '$2y$10$XNe6iN1e4li.OQED3xep1uN5eXQ1ItM5n4YfODMOI6kD4oB1MIlD2', 'delivery', '2026-05-31 00:35:18', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(11, 'Medium Delivery Company', 'medium.delivery@taggy.com', '$2y$10$XNe6iN1e4li.OQED3xep1uN5eXQ1ItM5n4YfODMOI6kD4oB1MIlD2', 'delivery', '2026-05-31 00:35:18', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(12, 'Bulk Delivery Company', 'bulk.delivery@taggy.com', '$2y$10$XNe6iN1e4li.OQED3xep1uN5eXQ1ItM5n4YfODMOI6kD4oB1MIlD2', 'delivery', '2026-05-31 00:35:18', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(13, 'Cairo Print House', 'supplier1@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(14, 'Nile Branding Solutions', 'supplier2@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(15, 'Delta Gifts Factory', 'supplier3@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(16, 'Alex Promo Studio', 'supplier4@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(17, 'Giza Corporate Supplies', 'supplier5@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(18, 'Maadi Print Lab', 'supplier6@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(19, 'Nasr City Gifts Hub', 'supplier7@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(20, 'Heliopolis Branding Co.', 'supplier8@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(21, 'Mansoura Promo Works', 'supplier9@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(22, 'Tanta Custom Gifts', 'supplier10@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(23, 'October Branding Center', 'supplier11@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(24, 'Zamalek Creative Print', 'supplier12@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(25, 'Shoubra Pack & Print', 'supplier13@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(26, 'Smart Promo Egypt', 'supplier14@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(27, 'Nile Delta Supplies', 'supplier15@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL),
(28, 'Premium Mark Egypt', 'supplier16@taggy.com', '$2y$12$lj5/T/80ONHj.YVIu6tFA.OnIP/heXc0Tix0wJuqJsQu6v2XKHC4K', 'supplier', '2026-05-31 02:23:14', 'individual', NULL, NULL, NULL, 'none', 'inactive', 0.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `website_orders`
--
-- Creation: May 30, 2026 at 10:58 PM
-- Last update: May 31, 2026 at 01:19 AM
--

DROP TABLE IF EXISTS `website_orders`;
CREATE TABLE `website_orders` (
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `SupplierProfileID` int(11) DEFAULT NULL,
  `DeliveryUserID` int(11) DEFAULT NULL,
  `TrackingNumber` varchar(100) DEFAULT NULL,
  `CustomerName` varchar(255) DEFAULT NULL,
  `Phone` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `DeliveryType` varchar(100) DEFAULT NULL,
  `PaymentMethod` varchar(100) DEFAULT NULL,
  `PaymentStatus` varchar(50) DEFAULT 'Pending',
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `OrderStatus` varchar(100) DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ProductsTotal` decimal(10,2) DEFAULT 0.00,
  `DeliveryFees` decimal(10,2) DEFAULT 0.00,
  `DiscountPercent` decimal(5,2) DEFAULT 0.00,
  `DiscountAmount` decimal(10,2) DEFAULT 0.00,
  `SubscriptionPlan` varchar(100) DEFAULT 'No Business Offer',
  `SupplierProductionTime` varchar(100) DEFAULT '5-7 days',
  `FinalProductionTime` varchar(100) DEFAULT '5-7 days',
  `EstimatedArrival` varchar(100) DEFAULT '6-9 days'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_orders`
--

INSERT INTO `website_orders` (`OrderID`, `UserID`, `SupplierProfileID`, `DeliveryUserID`, `TrackingNumber`, `CustomerName`, `Phone`, `Email`, `Address`, `DeliveryType`, `PaymentMethod`, `PaymentStatus`, `TotalAmount`, `OrderStatus`, `CreatedAt`, `ProductsTotal`, `DeliveryFees`, `DiscountPercent`, `DiscountAmount`, `SubscriptionPlan`, `SupplierProductionTime`, `FinalProductionTime`, `EstimatedArrival`) VALUES
(1, 4, 1, 3, 'TAGGY-00001', 'Customer User', '01000000000', 'customer@taggy.com', 'Cairo', 'Standard Delivery - 30 days - 200 EGP', 'Cash on delivery', 'Pending', 500.00, 'Pending', '2026-05-25 20:35:04', 0.00, 0.00, 0.00, 0.00, 'No Business Offer', '5-7 days', '5-7 days', '6-9 days'),
(2, 4, 1, 3, 'TAGGY-00002', 'Customer User', '01000000000', 'customer@taggy.com', 'Cairo', 'Fast Delivery - 10 to 15 days - 500 EGP', 'Vodafone Cash', 'Pending', 900.00, 'Processing', '2026-05-25 20:35:04', 0.00, 0.00, 0.00, 0.00, 'No Business Offer', '5-7 days', '5-7 days', '6-9 days'),
(3, 4, 1, 3, 'TAGGY-00003', 'Customer User', '01000000000', 'customer@taggy.com', 'Cairo', 'Standard Delivery - 30 days - 200 EGP', 'Pay with card', 'Pending', 700.00, 'Ready For Shipping', '2026-05-25 20:35:04', 0.00, 0.00, 0.00, 0.00, 'No Business Offer', '5-7 days', '5-7 days', '6-9 days'),
(4, 5, 1, 3, 'TAGGY-00004', 'Marwa ahmed', '01064040206', 'Marwaahmed51@gmail.com', 'Governorate: Alexandria, City: mamooura, Street: street 33 mamoura, Building: 22, Floor: 3, Apartment: 6', '3 to 5 days - 200 EGP', 'Cash on delivery', 'Pending', 650.00, 'Delivered', '2026-05-25 21:59:12', 450.00, 200.00, 0.00, 0.00, 'No Business Offer', '5-7 days', '5-7 days', '6-9 days'),
(5, 6, 1, 3, 'TAGGY-00005', 'Elserag company', '01019894724', 'Elseragcompany21@gmail.com', 'Governorate: Cairo, City: herafyeen, Street: 31 herafyeen street, Building: 78, Floor: 1, Apartment: 75', '12 to 18 days - 350 EGP', 'Vodafone Cash', 'Pending', 8865.00, 'In Production', '2026-05-25 22:05:01', 9500.00, 350.00, 10.00, 985.00, 'Growth Bulk Orders Plan', '5-7 days', '5-7 days', '6-9 days'),
(6, 8, 1, 3, 'TAGGY-00006', 'Hossam Aglan', '01006888918', NULL, 'Apartment 32, Building 1, wesal compound, shrouk, Cairo', NULL, 'Cash on delivery', 'Pending', 340.00, 'Pending', '2026-05-26 18:50:04', 240.00, 100.00, 0.00, 0.00, 'No Business Offer', '5-7 days', '5-7 days', '6-9 days'),
(7, 9, 2, 3, NULL, 'farah khaled', '01012925987', 'faroohakhaled@gmail.com', 'Apartment 3, Building 78, wesal  views compound, shrouk, Cairo', 'Standard Delivery', 'Cash on delivery', 'Pending', 2240.00, 'Pending', '2026-05-30 23:15:31', 2090.00, 150.00, 0.00, 0.00, 'No Business Offer', '7-12 days', '7-12 days', '8-14 days'),
(8, 9, 3, 11, 'TAGGY-00008', 'farah khaled', '01023898656', 'faroohakhaled@gmail.com', 'Apartment 3, Building 4, street30, 5th setellment, Cairo', 'Standard Delivery', 'Cash on delivery', 'Pending', 1830.00, 'Processing', '2026-05-31 00:36:33', 1680.00, 150.00, 0.00, 0.00, 'No Business Offer', '3-5 days', '5-7 days', '6-9 days'),
(9, 9, 2, 11, 'TAGGY-00009', 'farah khaled', '01518945158', 'faroohakhaled@gmail.com', 'Apartment 32, Building 78, street30, 5th setellment, Cairo', 'Standard Delivery', 'Cash on delivery', 'Pending', 4740.00, 'Processing', '2026-05-31 01:19:13', 5100.00, 150.00, 10.00, 510.00, 'growth', '7-12 days', '7-12 days', '8-14 days');

-- --------------------------------------------------------

--
-- Table structure for table `website_order_items`
--
-- Creation: May 30, 2026 at 10:36 PM
-- Last update: May 31, 2026 at 01:19 AM
--

DROP TABLE IF EXISTS `website_order_items`;
CREATE TABLE `website_order_items` (
  `ItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductName` varchar(255) DEFAULT NULL,
  `OptionName` varchar(255) DEFAULT NULL,
  `Quantity` int(11) DEFAULT 1,
  `UnitPrice` decimal(10,2) DEFAULT 0.00,
  `TotalPrice` decimal(10,2) DEFAULT 0.00,
  `ProductImage` varchar(255) DEFAULT NULL,
  `SupplierName` varchar(255) DEFAULT NULL,
  `DesignText` text DEFAULT NULL,
  `DesignColor` varchar(100) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_order_items`
--

INSERT INTO `website_order_items` (`ItemID`, `OrderID`, `ProductName`, `OptionName`, `Quantity`, `UnitPrice`, `TotalPrice`, `ProductImage`, `SupplierName`, `DesignText`, `DesignColor`, `Notes`) VALUES
(1, 1, 'Mug', '150 ml', 2, 80.00, 160.00, 'mugs.jpg', 'Premium Prints Egypt', 'Taggy Logo', '#2563eb', ''),
(2, 1, 'Key Chain', 'Metal', 3, 80.00, 240.00, 'keychain.jpg', 'Corporate Gifts Hub', 'Company Name', '#071426', ''),
(3, 2, 'Power Bank', '10000 mah', 1, 700.00, 700.00, 'powerbank.jpg', 'Smart Gifts Factory', 'Tech Gift', '#071426', ''),
(4, 3, 'Classic Notebook', '120 pages', 5, 100.00, 500.00, 'notebook.jpg', 'Notebook Pro Supplier', 'Team 2026', '#2563eb', ''),
(5, 4, 'Thermal Flasks', '750 ml', 1, 70.00, 70.00, 'flask.jpg', 'Premium Prints Egypt', 'Company Gift', '#047857', ''),
(6, 5, 'Set 1', 'Medium Quality', 1, 270.00, 270.00, 'set1.jpg', 'Corporate Gifts Hub', 'Corporate Box', '#b45309', ''),
(7, 6, 'Classic Notebook', '120 pages | Supplier: Premium Printing Supplier | Design: hossam', 2, 120.00, 240.00, 'notebook.jpg', 'Premium Printing Supplier', 'hossam', '#071426', ''),
(8, 7, 'Thermal Flasks', '3 L | Supplier: Corporate Gift Supplier | Design: Farooha 2026', 11, 190.00, 2090.00, 'flask.jpg', 'Corporate Gift Supplier', 'Farooha 2026', '#b45309', 'no notes'),
(9, 8, 'Thermal Flasks', '750 ml | Supplier: Fast Promo Supplier | Design: fofo 2026', 24, 70.00, 1680.00, 'flask.jpg', 'Fast Promo Supplier', 'fofo 2026', '#dc2626', ''),
(10, 9, 'Mug', '90 ml | Supplier: Corporate Gift Supplier | Design: fofa', 51, 100.00, 5100.00, 'mugs.jpg', 'Corporate Gift Supplier', 'fofa', '#b45309', '');

-- --------------------------------------------------------

--
-- Table structure for table `website_reviews`
--
-- Creation: May 30, 2026 at 10:36 PM
--

DROP TABLE IF EXISTS `website_reviews`;
CREATE TABLE `website_reviews` (
  `ReviewID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CustomerName` varchar(255) NOT NULL,
  `Rating` int(11) NOT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_reviews`
--

INSERT INTO `website_reviews` (`ReviewID`, `OrderID`, `UserID`, `CustomerName`, `Rating`, `Comment`, `CreatedAt`) VALUES
(1, 4, 5, 'Marwa Ahmed', 5, 'wrong image', '2026-05-25 23:17:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `order_issues`
--
ALTER TABLE `order_issues`
  ADD PRIMARY KEY (`IssueID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `productcatalog`
--
ALTER TABLE `productcatalog`
  ADD PRIMARY KEY (`CatalogProductID`);

--
-- Indexes for table `productcatalogoption`
--
ALTER TABLE `productcatalogoption`
  ADD PRIMARY KEY (`OptionID`),
  ADD KEY `CatalogProductID` (`CatalogProductID`);

--
-- Indexes for table `supplier_option_offers`
--
ALTER TABLE `supplier_option_offers`
  ADD PRIMARY KEY (`OfferID`),
  ADD UNIQUE KEY `unique_option_supplier` (`OptionID`,`SupplierProfileID`);

--
-- Indexes for table `supplier_profiles`
--
ALTER TABLE `supplier_profiles`
  ADD PRIMARY KEY (`SupplierProfileID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `website_orders`
--
ALTER TABLE `website_orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `website_order_items`
--
ALTER TABLE `website_order_items`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `website_reviews`
--
ALTER TABLE `website_reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD UNIQUE KEY `OrderID` (`OrderID`),
  ADD KEY `UserID` (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `order_issues`
--
ALTER TABLE `order_issues`
  MODIFY `IssueID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `productcatalog`
--
ALTER TABLE `productcatalog`
  MODIFY `CatalogProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `productcatalogoption`
--
ALTER TABLE `productcatalogoption`
  MODIFY `OptionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `supplier_option_offers`
--
ALTER TABLE `supplier_option_offers`
  MODIFY `OfferID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=512;

--
-- AUTO_INCREMENT for table `supplier_profiles`
--
ALTER TABLE `supplier_profiles`
  MODIFY `SupplierProfileID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `website_orders`
--
ALTER TABLE `website_orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `website_order_items`
--
ALTER TABLE `website_order_items`
  MODIFY `ItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `website_reviews`
--
ALTER TABLE `website_reviews`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_issues`
--
ALTER TABLE `order_issues`
  ADD CONSTRAINT `order_issues_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `website_orders` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_issues_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `productcatalogoption`
--
ALTER TABLE `productcatalogoption`
  ADD CONSTRAINT `productcatalogoption_ibfk_1` FOREIGN KEY (`CatalogProductID`) REFERENCES `productcatalog` (`CatalogProductID`) ON DELETE CASCADE;

--
-- Constraints for table `website_orders`
--
ALTER TABLE `website_orders`
  ADD CONSTRAINT `website_orders_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `website_order_items`
--
ALTER TABLE `website_order_items`
  ADD CONSTRAINT `website_order_items_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `website_orders` (`OrderID`) ON DELETE CASCADE;

--
-- Constraints for table `website_reviews`
--
ALTER TABLE `website_reviews`
  ADD CONSTRAINT `website_reviews_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `website_orders` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `website_reviews_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
