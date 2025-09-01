-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 29, 2025 at 06:22 PM
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
-- Database: `frotas_gov`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `secretariat_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuelings`
--

CREATE TABLE `fuelings` (
  `id` int(11) NOT NULL,
  `run_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `secretariat_id` int(11) DEFAULT NULL,
  `gas_station_id` int(11) DEFAULT NULL,
  `gas_station_name` varchar(150) DEFAULT NULL COMMENT 'Para abastecimento manual',
  `km` int(10) UNSIGNED NOT NULL,
  `liters` decimal(10,2) NOT NULL,
  `fuel_type_id` int(11) DEFAULT NULL,
  `total_value` decimal(10,2) DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_types`
--

CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gas_stations`
--

CREATE TABLE `gas_stations` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gas_station_fuels`
--

CREATE TABLE `gas_station_fuels` (
  `id` int(11) NOT NULL,
  `gas_station_id` int(11) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `price` decimal(10,3) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Ex: general_manager, sector_manager, mechanic, driver',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `runs`
--

CREATE TABLE `runs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `secretariat_id` int(11) DEFAULT NULL,
  `start_km` int(10) UNSIGNED DEFAULT NULL,
  `end_km` int(10) UNSIGNED DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `stop_point` varchar(255) DEFAULT NULL,
  `status` enum('in_progress','completed') NOT NULL DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secretariats`
--

CREATE TABLE `secretariats` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `cpf` varchar(17) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Armazena o hash da senha (Bcrypt)',
  `role_id` int(11) NOT NULL,
  `secretariat_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `cnh_number` varchar(20) DEFAULT NULL,
  `cnh_expiry_date` date DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Ex: FORD/RANGER XL CD4',
  `plate` varchar(10) NOT NULL COMMENT 'Placa do veículo',
  `prefix` varchar(20) NOT NULL COMMENT 'Prefixo ou abreviação, ex: V-123',
  `current_secretariat_id` int(11) NOT NULL,
  `fuel_tank_capacity_liters` decimal(5,2) DEFAULT NULL,
  `avg_km_per_liter` decimal(5,2) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','blocked') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `secretariat_id` (`secretariat_id`);

--
-- Indexes for table `fuelings`
--
ALTER TABLE `fuelings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `run_id` (`run_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `gas_station_id` (`gas_station_id`),
  ADD KEY `fuelings_ibfk_5` (`fuel_type_id`),
  ADD KEY `fuelings_ibfk_6` (`secretariat_id`);

--
-- Indexes for table `fuel_types`
--
ALTER TABLE `fuel_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gas_stations`
--
ALTER TABLE `gas_stations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gas_station_fuels`
--
ALTER TABLE `gas_station_fuels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gas_station_id` (`gas_station_id`),
  ADD KEY `gas_station_fuels_ibfk_2` (`fuel_type_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `runs`
--
ALTER TABLE `runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `runs_ibfk_3` (`secretariat_id`);

--
-- Indexes for table `secretariats`
--
ALTER TABLE `secretariats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `secretariat_id` (`secretariat_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate` (`plate`),
  ADD UNIQUE KEY `prefix` (`prefix`),
  ADD KEY `current_secretariat_id` (`current_secretariat_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuelings`
--
ALTER TABLE `fuelings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gas_stations`
--
ALTER TABLE `gas_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gas_station_fuels`
--
ALTER TABLE `gas_station_fuels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `secretariats`
--
ALTER TABLE `secretariats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fuelings`
--
ALTER TABLE `fuelings`
  ADD CONSTRAINT `fuelings_ibfk_1` FOREIGN KEY (`run_id`) REFERENCES `runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fuelings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fuelings_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fuelings_ibfk_4` FOREIGN KEY (`gas_station_id`) REFERENCES `gas_stations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fuelings_ibfk_5` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fuelings_ibfk_6` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gas_station_fuels`
--
ALTER TABLE `gas_station_fuels`
  ADD CONSTRAINT `gas_station_fuels_ibfk_1` FOREIGN KEY (`gas_station_id`) REFERENCES `gas_stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gas_station_fuels_ibfk_2` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `runs`
--
ALTER TABLE `runs`
  ADD CONSTRAINT `runs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `runs_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `runs_ibfk_3` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DEALETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`current_secretariat_id`) REFERENCES `secretariats` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
