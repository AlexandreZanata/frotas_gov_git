-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 27, 2025 at 11:00 PM
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
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Usuário que realizou a ação. NULL se for uma ação do sistema.',
  `action` varchar(50) NOT NULL COMMENT 'Ex: create, update, delete, login_success, login_fail',
  `table_name` varchar(100) DEFAULT NULL COMMENT 'A tabela que foi afetada',
  `record_id` int(11) DEFAULT NULL COMMENT 'O ID do registro que foi afetado',
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `selector` varchar(255) NOT NULL,
  `hashed_validator` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`id`, `selector`, `hashed_validator`, `user_id`, `expires_at`) VALUES
(1, '180aa2acdc124ee93f8f9e8a57ee8af4', 'd0b48351bd4d6812f4a3b579e2efeaeeed7804f2f0cb40d12799474b7542a1c4', 2, '2025-09-25 22:30:22'),
(2, '9b74589c36b83982f563170df2fbc286', 'e5237a868f0db8cc1baf5526c4495cae499ebbc1429444aa6760bda44d59d61c', 2, '2025-09-25 22:30:30'),
(3, 'cf4bb009a9aac87ef0d2f905141fec64', '7304019ddaec9efbb5f1db0617dbd65bd369c7f7fc550618701832c604956753', 2, '2025-09-25 22:31:10'),
(4, '718fe68ce9e65fab627938827619a040', '103e92f4cb18726625b33716f221e10c5224d643cc2b7cfdd6d5002b3103399f', 2, '2025-09-25 22:54:11'),
(5, 'f1ce386bc68e772771462f2883b1acba', '89cf4f67209e4acfeb13d119c47a839da10d76a8980b64e24917c6bf099f35ad', 2, '2025-09-26 13:10:41'),
(6, '93fcbe729f7e7cddc57af65ead4e4334', '52584bfa5507fb48bb87a1e2c09f93e01b19414729256315e4aff9db15eb150e', 2, '2025-09-26 13:29:25'),
(7, '05c5c78a1119af1067e9566de7f8cc9e', 'd34f3ce2c0976135f3bbc0b20c04a6821af1a624acde90382e65045e6c428794', 2, '2025-09-26 13:37:06'),
(8, 'b93501e3e9449970f84e6350ef873d9b', '134d05fa043340841870e862f234940de12e614894fcc9bf2669f1a42ed53927', 2, '2025-09-26 13:38:16'),
(9, '6e186438a66d13c6dd4108f57b49434a', '4e23fa6e564b6fb960e85f104a1dfce3c103b4dcd4f031954cf629dfe71309e6', 2, '2025-09-26 15:22:04'),
(10, '7af825d8923c94a406554a38c5a5d60d', 'e988ee111f46692a65531e825867ff015f6a8baec29e86d4c5d9f4d82469ec1c', 2, '2025-09-26 15:35:21'),
(11, 'd116dbd16a9408ef483cdb9aa7103029', 'ee1a02676586ce02ea37bc0231cad001cfbb671f13ea4ea81b92fc1aba681c09', 2, '2025-09-26 15:35:58'),
(12, 'eae35166de8c5c2af94a3248318728ad', 'e4911f151b06619be2424c052e27c09d5e4aaea7380e5b8396b81864aed1454c', 2, '2025-09-26 16:04:00'),
(13, 'de609919f22ab1e48da7c82010753612', '89ab9852671c024338628eca29a709d26b8cc30613b2efee9000bd3668e0c16b', 2, '2025-09-26 17:51:04'),
(14, 'e385c7869f488af56671118049a1e6cc', '049b27df19bf1eecc8ac2707d8769bc3e8f9426851b45523d86cef134d2e4528', 3, '2025-09-26 21:34:50'),
(15, '1657e49121c68ba5b49e687166644595', 'de8614a684cf14a47273f780350dada140cdcce8fc9302d4e07515a38666a601', 3, '2025-09-26 21:39:40'),
(16, '1e4cd8738b9f2f6daa0b68280cac047b', 'd4d31304c69fed322f9d8522c551ebb716723b4044c006de1298fe48b8d8bfa5', 2, '2025-09-26 21:54:14'),
(17, '246a66d31ad4108670a1f4d7f988fdb9', 'fbf4dc4b9b8f9274db0ce78c042fe14795a2b576bb1c59c9947d9ad9954e302e', 2, '2025-09-26 21:56:00'),
(18, '4c679ca5a8ed0f9405cd8431c496caa6', '90dded496bade8581eec3a706a26ec993cdb0d0db925ad938dbe766f1dc4db23', 2, '2025-09-26 22:02:58'),
(19, '8f715f4f7dd884d4c63bf33122e536b9', '9112134ba414d45144945971c40a665fdb4fef5c9646d86f45f1ae3fc1112a57', 2, '2025-09-26 22:13:35'),
(20, 'e53fce6a628042aca798cd49a0bb8003', '50bcf770c281488b2b1265cd5da2e3b0c71197d0d730cc957bd3f07319d16ac0', 2, '2025-09-26 22:52:43'),
(21, '1c3ba5040394324ec9cd695e92eb7490', '112e3e65d1b9baa29ce9e9c849009b44d71f303959c3bc2df5c6955a89bcd0b5', 2, '2025-09-26 22:53:35'),
(22, 'a20d92ee5e098dd87f52b0e62485152c', '2cb1118fcc3469347c8203de9e0028bfd76533909870a6cd4f8efc004f2d74ba', 3, '2025-09-26 22:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `checklists`
--

CREATE TABLE `checklists` (
  `id` int(11) NOT NULL,
  `run_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checklists`
--

INSERT INTO `checklists` (`id`, `run_id`, `user_id`, `vehicle_id`, `created_at`) VALUES
(15, 50, 3, 2, '2025-08-27 19:35:27'),
(16, 51, 3, 2, '2025-08-27 19:39:14');

-- --------------------------------------------------------

--
-- Table structure for table `checklist_answers`
--

CREATE TABLE `checklist_answers` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `status` enum('ok','attention','problem') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checklist_answers`
--

INSERT INTO `checklist_answers` (`id`, `checklist_id`, `item_id`, `status`, `notes`) VALUES
(113, 15, 1, 'ok', NULL),
(114, 15, 2, 'problem', 'eweew'),
(115, 15, 3, 'attention', NULL),
(116, 15, 4, 'ok', NULL),
(117, 15, 5, 'ok', NULL),
(118, 15, 6, 'ok', NULL),
(119, 15, 7, 'attention', NULL),
(120, 15, 8, 'problem', 'wewewe'),
(121, 16, 1, 'ok', NULL),
(122, 16, 2, 'problem', 'eweew'),
(123, 16, 3, 'attention', NULL),
(124, 16, 4, 'ok', NULL),
(125, 16, 5, 'ok', NULL),
(126, 16, 6, 'ok', NULL),
(127, 16, 7, 'attention', NULL),
(128, 16, 8, 'problem', 'wewewe');

-- --------------------------------------------------------

--
-- Table structure for table `checklist_items`
--

CREATE TABLE `checklist_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checklist_items`
--

INSERT INTO `checklist_items` (`id`, `name`, `description`) VALUES
(1, 'Combustível', NULL),
(2, 'Água', 'Nível do reservatório do radiador'),
(3, 'Óleo', 'Nível do óleo do motor'),
(4, 'Bateria', NULL),
(5, 'Pneus', NULL),
(6, 'Filtro de Ar', NULL),
(7, 'Lâmpadas', 'Faróis, setas, luz de freio'),
(8, 'Sistema Elétrico', NULL);

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

--
-- Dumping data for table `fuelings`
--

INSERT INTO `fuelings` (`id`, `run_id`, `user_id`, `vehicle_id`, `secretariat_id`, `gas_station_id`, `gas_station_name`, `km`, `liters`, `fuel_type_id`, `total_value`, `invoice_path`, `is_manual`, `created_at`) VALUES
(80, 102, 2, 1, 4, NULL, 'Posto Central', 15950, 40.00, 1, 228.00, NULL, 0, '2025-06-10 15:40:00'),
(81, 103, 2, 2, 4, NULL, 'Posto Avenida', 23220, 35.50, 2, 175.72, NULL, 0, '2025-06-15 20:20:00'),
(82, 104, 2, 1, 4, NULL, 'Posto Central', 16185, 38.20, 1, 217.74, NULL, 0, '2025-07-01 16:10:00'),
(83, 105, 2, 3, 4, NULL, 'Posto Trevo', 46650, 45.00, 1, 256.50, NULL, 0, '2025-07-12 19:15:00'),
(84, 106, 2, 2, 4, NULL, 'Posto Avenida', 23610, 33.00, 2, 163.35, NULL, 0, '2025-07-25 16:00:00'),
(85, 107, 2, 4, 4, NULL, 'Posto Diesel Forte', 68050, 55.00, 3, 335.50, NULL, 0, '2025-08-05 17:10:00'),
(86, 108, 2, 1, 4, NULL, 'Posto Central', 16390, 25.00, 1, 142.50, NULL, 0, '2025-08-18 21:45:00'),
(87, 109, 2, 5, 4, NULL, 'Posto Diesel Forte', 9350, 48.80, 3, 297.68, NULL, 0, '2025-08-27 21:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_types`
--

CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_types`
--

INSERT INTO `fuel_types` (`id`, `name`) VALUES
(4, 'Diesel Comum'),
(5, 'Diesel S10'),
(3, 'Etanol'),
(2, 'Gasolina Aditivada'),
(1, 'Gasolina Comum');

-- --------------------------------------------------------

--
-- Table structure for table `gas_stations`
--

CREATE TABLE `gas_stations` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_stations`
--

INSERT INTO `gas_stations` (`id`, `name`, `status`) VALUES
(1, 'Posto Shell Centro', 'active'),
(2, 'Posto Ipiranga Bairro', 'active'),
(3, 'Posto Petrobras Rodovia', 'active');

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

--
-- Dumping data for table `gas_station_fuels`
--

INSERT INTO `gas_station_fuels` (`id`, `gas_station_id`, `fuel_type_id`, `price`, `updated_at`) VALUES
(11, 1, 1, 5.890, '2025-08-27 16:43:06'),
(12, 1, 3, 3.990, '2025-08-27 16:43:06'),
(13, 1, 5, 6.200, '2025-08-27 16:43:06'),
(14, 2, 2, 6.150, '2025-08-27 16:43:06'),
(15, 2, 3, 4.050, '2025-08-27 16:43:06'),
(16, 3, 1, 5.950, '2025-08-27 16:43:06'),
(17, 3, 4, 6.100, '2025-08-27 16:43:06');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Ex: general_manager, sector_manager, mechanic, driver',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'general_manager', 'Gestor Geral - Acesso total ao sistema'),
(2, 'sector_manager', 'Gestor Setorial - Acesso à sua secretaria'),
(3, 'mechanic', 'Mecânico - Acesso ao painel de manutenção'),
(4, 'driver', 'Motorista - Acesso ao diário de bordo');

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

--
-- Dumping data for table `runs`
--

INSERT INTO `runs` (`id`, `vehicle_id`, `driver_id`, `secretariat_id`, `start_km`, `end_km`, `start_time`, `end_time`, `destination`, `stop_point`, `status`) VALUES
(50, 2, 3, NULL, 109, 109, '2025-08-27 15:35:27', '2025-08-27 15:35:42', 'teste', 'teste', 'completed'),
(51, 2, 3, NULL, 109, 109, '2025-08-27 15:39:14', '2025-08-27 15:42:18', 'teste', 'teste', 'completed'),
(102, 1, 2, 4, 15800, 15950, '2025-06-10 09:00:00', '2025-06-10 11:30:00', 'Prefeitura Municipal', 'Protocolo de Ofícios', 'completed'),
(103, 2, 2, 4, 23100, 23220, '2025-06-15 14:00:00', '2025-06-15 16:10:00', 'Secretaria de Obras', 'Vistoria de Equipamentos', 'completed'),
(104, 1, 2, 4, 16100, 16185, '2025-07-01 08:30:00', '2025-07-01 12:00:00', 'Hospital Central', 'Entrega de Suprimentos', 'completed'),
(105, 3, 2, 4, 46500, 46650, '2025-07-12 13:30:00', '2025-07-12 15:00:00', 'Centro Comunitário', 'Reunião com lideranças', 'completed'),
(106, 2, 2, 4, 23500, 23610, '2025-07-25 10:00:00', '2025-07-25 11:45:00', 'Almoxarifado', 'Retirada de material', 'completed'),
(107, 4, 2, 4, 67800, 68050, '2025-08-05 09:15:00', '2025-08-05 13:00:00', 'Inspeção Zona Rural', 'Levantamento de demandas', 'completed'),
(108, 1, 2, 4, 16300, 16390, '2025-08-18 15:00:00', '2025-08-18 17:30:00', 'Garagem Municipal', 'Manutenção preventiva', 'completed'),
(109, 5, 2, 4, 9200, 9350, '2025-08-27 14:20:00', '2025-08-27 16:50:00', 'Defesa Civil', 'Transporte de doações', 'completed');

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

--
-- Dumping data for table `secretariats`
--

INSERT INTO `secretariats` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Administração', '2025-08-26 20:00:39', '2025-08-26 20:00:39'),
(2, 'Educação', '2025-08-26 20:00:39', '2025-08-26 20:00:39'),
(3, 'Saúde', '2025-08-26 20:00:39', '2025-08-26 20:00:39'),
(4, 'Obras e Serviços Públicos', '2025-08-26 20:00:39', '2025-08-26 20:00:39');

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `cpf`, `email`, `password`, `role_id`, `secretariat_id`, `department_id`, `cnh_number`, `cnh_expiry_date`, `profile_photo_path`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin Geral', '11122233344', 'admin@frotas.gov', '$2y$10$WknxPAb.e./JpX8Idgs6SemVv.7g75Lz29kE7J1wJ5VvshXn5B.eK', 1, 1, NULL, NULL, NULL, NULL, NULL, 'active', '2025-08-26 20:02:32', '2025-08-26 20:02:32'),
(2, 'ALEXANDRE ZANATA', '12345678911', 'admin@example.com', '$2y$10$TLVbMeZPafx1qLYqCWGqcOtAhw2NrYfczQcCG5hK872ARZlgEqY1y', 2, 4, NULL, NULL, NULL, NULL, NULL, 'active', '2025-08-26 20:29:49', '2025-08-27 20:53:28'),
(3, 'Luis Ignacio Lula Zanata', '13131313131', 'L@13.com', '$2y$10$9rwKvc5vHXOxvQhBuRkIuehYRgzMgS0beGuy7y9HoknOCZDmTUiqW', 4, 4, NULL, NULL, NULL, NULL, NULL, 'active', '2025-08-27 19:34:17', '2025-08-27 20:58:28');

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
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `plate`, `prefix`, `current_secretariat_id`, `fuel_tank_capacity_liters`, `avg_km_per_liter`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FIAT/STRADA FREEDOM CD', 'RGH7B21', 'V-10', 1, 55.00, 12.50, 'available', '2025-08-27 13:55:47', '2025-08-27 13:55:47'),
(2, 'VW/GOL 1.6', 'PMJ5890', 'A-11', 4, 55.00, 11.20, 'available', '2025-08-27 13:55:47', '2025-08-27 20:11:12'),
(3, 'TOYOTA/HILUX SRV 4X4', 'SAD2A33', 'A-110', 4, 80.00, 9.80, 'in_use', '2025-08-27 13:55:47', '2025-08-27 17:19:20'),
(4, 'CHEVROLET/ONIX 1.0', 'QWE4R56', 'SEC-042', 3, 44.00, 14.10, 'available', '2025-08-27 13:55:47', '2025-08-27 13:55:47'),
(5, 'FORD/RANGER XLS CD', 'JKL9M87', 'VTR-008', 4, 80.00, 10.50, 'blocked', '2025-08-27 13:55:47', '2025-08-27 13:55:47'),
(6, 'RENAULT/SANDERO ZEN', 'XYZ1A23', 'ADM-021', 2, 50.00, 13.50, 'available', '2025-08-27 13:55:47', '2025-08-27 13:55:47'),
(7, 'HYUNDAI/HB20 SENSE', 'BRA2E19', 'SEC-011', 3, 50.00, 13.00, 'in_use', '2025-08-27 13:55:47', '2025-08-27 13:55:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `selector_idx` (`selector`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `checklists`
--
ALTER TABLE `checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `run_id` (`run_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `checklist_answers`
--
ALTER TABLE `checklist_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `checklist_items`
--
ALTER TABLE `checklist_items`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `checklists`
--
ALTER TABLE `checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `checklist_answers`
--
ALTER TABLE `checklist_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `checklist_items`
--
ALTER TABLE `checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuelings`
--
ALTER TABLE `fuelings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gas_stations`
--
ALTER TABLE `gas_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `gas_station_fuels`
--
ALTER TABLE `gas_station_fuels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `secretariats`
--
ALTER TABLE `secretariats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `checklists`
--
ALTER TABLE `checklists`
  ADD CONSTRAINT `checklists_ibfk_1` FOREIGN KEY (`run_id`) REFERENCES `runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklists_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklists_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `checklist_answers`
--
ALTER TABLE `checklist_answers`
  ADD CONSTRAINT `checklist_answers_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklist_answers_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `checklist_items` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`current_secretariat_id`) REFERENCES `secretariats` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;