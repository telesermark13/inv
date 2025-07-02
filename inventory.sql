-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 05:31 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `delivered_items`
--

CREATE TABLE `delivered_items` (
  `id` int(11) NOT NULL,
  `delivery_number` int(11) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `ordered` int(11) NOT NULL,
  `delivered` int(11) NOT NULL,
  `outstanding` int(11) NOT NULL,
  `unit` varchar(10) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_taxed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_nontaxed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_nontaxed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_items`
--

CREATE TABLE `delivery_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_delivered` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_receipts`
--

CREATE TABLE `delivery_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `client` varchar(255) DEFAULT NULL,
  `project` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `delivery_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_number` int(11) NOT NULL,
  `prepared_by` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `total_quantity` int(11) DEFAULT NULL,
  `outstanding` int(11) DEFAULT 0,
  `comments` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `movement_type` enum('in','out') DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `reference_type` enum('request','delivery','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `master_item_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `category` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_taxed` decimal(10,2) NOT NULL,
  `price_nontaxed` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_stock_level` int(11) NOT NULL DEFAULT 10,
  `price` decimal(10,2) DEFAULT 0.00,
  `taxable` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `master_items`
--

CREATE TABLE `master_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `price_taxed` decimal(12,2) DEFAULT NULL,
  `price_nontaxed` decimal(12,2) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `materials_requests`
--

CREATE TABLE `materials_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_amount_nontaxed` decimal(10,2) DEFAULT 0.00,
  `total_tax_amount` decimal(10,2) DEFAULT 0.00,
  `grand_total_amount` decimal(10,2) DEFAULT 0.00,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','denied','completed') DEFAULT 'pending',
  `purchase_order_id` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `materials_request_items`
--

CREATE TABLE `materials_request_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `master_item_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `master_item_type` enum('current','old') DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `taxable` tinyint(1) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `old_stocks`
--

CREATE TABLE `old_stocks` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00,
  `taxable` tinyint(1) DEFAULT 0,
  `price_taxed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_nontaxed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_level` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_details`
--

CREATE TABLE `purchase_details` (
  `id` int(11) NOT NULL,
  `material_request_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `status` enum('pending_arrival','partially_received','fulfilled','cancelled') NOT NULL DEFAULT 'pending_arrival',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `status` enum('pending','pending_po_approval','approved_to_order','ordered','purchased','partially_received','fully_received','cancelled') DEFAULT 'pending_po_approval',
  `status_full` enum('pending_arrival','partially_received','fulfilled','cancelled') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sales_invoice_no` varchar(100) DEFAULT NULL,
  `po_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_type` enum('current','old') NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `master_item_id` int(11) DEFAULT NULL,
  `description_at_purchase` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `taxable` tinyint(1) NOT NULL DEFAULT 0,
  `quantity_received` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `returned_items`
--

CREATE TABLE `returned_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'Foreign Key to items.id',
  `name_at_return` varchar(255) NOT NULL COMMENT 'Name of item when returned',
  `sku_at_return` varchar(100) DEFAULT NULL COMMENT 'SKU of item when returned',
  `description_at_return` text DEFAULT NULL COMMENT 'Description of item when returned',
  `unit_at_return` varchar(50) DEFAULT NULL COMMENT 'Unit of item when returned',
  `unit_price_at_return` decimal(10,2) DEFAULT NULL COMMENT 'Unit price of item when returned',
  `price_taxed_at_return` decimal(10,2) DEFAULT NULL COMMENT 'Taxed price of item when returned',
  `price_nontaxed_at_return` decimal(10,2) DEFAULT NULL COMMENT 'Non-taxed price of item when returned',
  `quantity_returned` int(11) NOT NULL COMMENT 'Quantity of units returned',
  `reason` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `return_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','client') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(2, 'admin', 'telesermark13@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delivered_items`
--
ALTER TABLE `delivered_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_delivery_number` (`delivery_number`);

--
-- Indexes for table `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_di_receipt_id` (`receipt_id`),
  ADD KEY `fk_di_item_id` (`item_id`);

--
-- Indexes for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_number` (`delivery_number`),
  ADD UNIQUE KEY `delivery_number_2` (`delivery_number`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD UNIQUE KEY `uq_receipt_number` (`receipt_number`),
  ADD KEY `idx_dr_date` (`date`),
  ADD KEY `idx_dr_client` (`client`),
  ADD KEY `fk_dr_prepared_by` (`prepared_by`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sku` (`sku`),
  ADD KEY `idx_items_name` (`name`),
  ADD KEY `idx_items_sku` (`sku`),
  ADD KEY `fk_items_master_item` (`master_item_id`);

--
-- Indexes for table `master_items`
--
ALTER TABLE `master_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `materials_requests`
--
ALTER TABLE `materials_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `materials_request_items`
--
ALTER TABLE `materials_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_master_item` (`master_item_id`,`master_item_type`);

--
-- Indexes for table `purchase_details`
--
ALTER TABLE `purchase_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_material_request_id` (`material_request_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_pd_suppliers` (`supplier_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_po_status` (`status`),
  ADD KEY `idx_po_order_date` (`order_date`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_poi_items_new` (`item_id`),
  ADD KEY `fk_poi_order_id` (`order_id`),
  ADD KEY `fk_poi_master_item_id` (`master_item_id`);

--
-- Indexes for table `returned_items`
--
ALTER TABLE `returned_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `returned_by` (`returned_by`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `fk_ri_items` (`item_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_supplier_name` (`name`),
  ADD UNIQUE KEY `uq_supplier_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delivered_items`
--
ALTER TABLE `delivered_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_items`
--
ALTER TABLE `delivery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_items`
--
ALTER TABLE `master_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_requests`
--
ALTER TABLE `materials_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_request_items`
--
ALTER TABLE `materials_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_details`
--
ALTER TABLE `purchase_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returned_items`
--
ALTER TABLE `returned_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivered_items`
--
ALTER TABLE `delivered_items`
  ADD CONSTRAINT `fk_delivery_number` FOREIGN KEY (`delivery_number`) REFERENCES `delivery_receipts` (`delivery_number`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_delivery_receipt` FOREIGN KEY (`delivery_number`) REFERENCES `delivery_receipts` (`delivery_number`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD CONSTRAINT `delivery_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `delivery_receipts` (`id`),
  ADD CONSTRAINT `delivery_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `fk_di_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_di_receipt_id` FOREIGN KEY (`receipt_id`) REFERENCES `delivery_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD CONSTRAINT `fk_dr_prepared_by` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prepared_by` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `inventory_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_master_item` FOREIGN KEY (`master_item_id`) REFERENCES `master_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `materials_requests`
--
ALTER TABLE `materials_requests`
  ADD CONSTRAINT `materials_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `materials_requests_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `materials_requests_ibfk_4` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `materials_requests_ibfk_5` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `materials_request_items`
--
ALTER TABLE `materials_request_items`
  ADD CONSTRAINT `materials_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `materials_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_details`
--
ALTER TABLE `purchase_details`
  ADD CONSTRAINT `fk_pd_mr` FOREIGN KEY (`material_request_id`) REFERENCES `materials_requests` (`id`),
  ADD CONSTRAINT `fk_pd_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_details_ibfk_1` FOREIGN KEY (`material_request_id`) REFERENCES `materials_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `purchase_details_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `purchase_details_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `materials_requests` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_poi_items_new` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_poi_master_items` FOREIGN KEY (`master_item_id`) REFERENCES `master_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_poi_order_id` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_poi_orders` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `purchase_orders` (`id`);

--
-- Constraints for table `returned_items`
--
ALTER TABLE `returned_items`
  ADD CONSTRAINT `fk_returned_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ri_items` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `returned_items_ibfk_1` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `returned_items_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
