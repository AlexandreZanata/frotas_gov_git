-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 09, 2025 at 09:29 PM
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

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `created_at`) VALUES
(1, 7, 'update_category_layout', 'vehicle_categories', 3, NULL, '{\"layout_key\":\"moto_1x1\"}', '172.19.2.140', '2025-09-09 18:39:06'),
(2, 7, 'update_tire_layout', 'tire_layouts', 3, NULL, '{\"id\":\"3\",\"name\":\"Caminh\\u00e3o Toco (6 Pneus)\",\"layout_key\":\"truck_4x0\",\"positions\":\"front_left, front_right, rear_left_outer, rear_left_inner, rear_right_outer, rear_right_inner\"}', '172.19.2.140', '2025-09-09 18:39:20'),
(3, 7, 'create_tire_rule', 'tire_lifespan_rules', 3, NULL, '{\"csrf_token\":\"23573d30e101dc675accd920a304d15d59cef46e043431da55d5a4701df5471d\",\"category_id\":\"3\",\"lifespan_km\":\"1399\",\"lifespan_days\":\"4555\"}', '172.19.2.140', '2025-09-09 18:39:55'),
(4, 7, 'create_tire_layout', 'tire_layouts', 4, NULL, '{\"id\":\"\",\"name\":\"eee\",\"layout_key\":\"2x2\",\"positions\":\"front_left, front_right, rear_left, rear_right\"}', '172.19.2.140', '2025-09-09 18:40:19'),
(5, 7, 'update_category_layout', 'vehicle_categories', 3, NULL, '{\"layout_key\":\"2X2\"}', '172.19.2.140', '2025-09-09 18:40:26'),
(6, 7, 'create_tire_layout', 'tire_layouts', 5, NULL, '{\"id\":\"\",\"name\":\"eeeeeeeee\",\"layout_key\":\"ee\",\"positions\":\"front_left, front_right, rear_left, rear_right, rear_left_inner, rear_right_inner, steer\"}', '172.19.2.140', '2025-09-09 18:41:30'),
(7, 7, 'update_category_layout', 'vehicle_categories', 2, NULL, '{\"layout_key\":\"EE\"}', '172.19.2.140', '2025-09-09 18:41:40'),
(8, 7, 'update_category_layout', 'vehicle_categories', 1, NULL, '{\"layout_key\":\"2X2\"}', '172.19.2.140', '2025-09-09 18:41:50'),
(9, 7, 'update_category_layout', 'vehicle_categories', 1, NULL, '{\"layout_key\":\"EE\"}', '172.19.2.140', '2025-09-09 18:41:56'),
(10, 7, 'update_category_layout', 'vehicle_categories', 3, NULL, '{\"layout_key\":\"EE\"}', '172.19.2.140', '2025-09-09 19:19:41');

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
(1, '78e8a9ac48e27a320eb1a755fd884988', 'd0e1933175e27a1b83db6e74ea3869febd7a79b8a668ad04334b234b2097b254', 7, '2025-10-09 19:49:47'),
(2, 'e8e524ab3ef44867d530ab0c9ca8ca20', 'fbc2027879946c1d3f87487e20569b7e359ef15bb621ed2b26607b36381dde23', 7, '2025-10-09 19:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message_type` enum('standard','notification','broadcast') NOT NULL DEFAULT 'standard',
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_message_recipients`
--

CREATE TABLE `chat_message_recipients` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_message_templates`
--

CREATE TABLE `chat_message_templates` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `scope` enum('personal','sector','global') NOT NULL DEFAULT 'personal' COMMENT 'personal (só o criador), sector (gestores da mesma secretaria), global (só admin geral)',
  `styles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`styles`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_participants`
--

CREATE TABLE `chat_participants` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) DEFAULT NULL COMMENT 'Usuário que iniciou a conversa',
  `name` varchar(150) DEFAULT NULL COMMENT 'Nome do grupo (para futuras implementações)',
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Nível de Óleo', 'Verificar o nível do óleo do motor com o veículo em local plano.'),
(2, 'Nível da Água', 'Verificar o nível do líquido de arrefecimento no reservatório.'),
(3, 'Calibragem dos Pneus', 'Verificar e ajustar a pressão dos pneus conforme especificação.'),
(4, 'Luzes e Setas', 'Testar faróis, lanternas, luz de freio e setas.'),
(5, 'Freios', 'Verificar a eficiência do freio de serviço e de estacionamento.');

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

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `secretariat_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Transporte Sanitário', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(2, 1, 'Vigilância em Saúde', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(3, 2, 'Manutenção de Vias', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(4, 2, 'Frota Pesada', '2025-09-09 17:47:15', '2025-09-09 17:47:15');

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
(1, 1, 5, 1, 1, 1, NULL, 5300, 20.00, 1, 117.98, NULL, 0, '2025-09-09 17:47:15'),
(2, 2, 6, 2, 2, 2, NULL, 15200, 40.00, 3, 246.00, NULL, 0, '2025-09-09 17:47:15');

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
(3, 'Diesel S10'),
(2, 'Etanol'),
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
(1, 'Posto Central', 'active'),
(2, 'Posto Petrovia', 'active');

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
(1, 1, 1, 5.899, '2025-09-09 17:47:15'),
(2, 1, 2, 3.999, '2025-09-09 17:47:15'),
(3, 1, 3, 6.099, '2025-09-09 17:47:15'),
(4, 2, 1, 5.950, '2025-09-09 17:47:15'),
(5, 2, 3, 6.150, '2025-09-09 17:47:15');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `secretariat_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `manager_id` int(11) DEFAULT NULL COMMENT 'ID do gestor que processou',
  `manager_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oil_change_logs`
--

CREATE TABLE `oil_change_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Usuário que registrou a troca',
  `secretariat_id` int(11) NOT NULL,
  `oil_product_id` int(11) NOT NULL,
  `liters_used` decimal(5,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `current_km` int(10) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oil_products`
--

CREATE TABLE `oil_products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL COMMENT 'Ex: Óleo 5W-30 Sintético',
  `brand` varchar(100) DEFAULT NULL COMMENT 'Ex: Castrol, Mobil',
  `stock_liters` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_per_liter` decimal(10,2) NOT NULL,
  `secretariat_id` int(11) DEFAULT NULL COMMENT 'Se NULL, é um produto global (visível para todos)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `oil_products`
--

INSERT INTO `oil_products` (`id`, `name`, `brand`, `stock_liters`, `cost_per_liter`, `secretariat_id`, `created_at`, `updated_at`) VALUES
(1, 'Óleo 5W30 Sintético', 'ACDelco', 100.00, 45.50, NULL, '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(2, 'Óleo 15W40 Mineral', 'Mobil', 150.00, 32.00, 2, '2025-09-09 17:47:15', '2025-09-09 17:47:15');

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
(1, 'general_admin', 'Administrador Geral com acesso total ao sistema.'),
(2, 'sector_manager', 'Gestor de Setor, gerencia veículos e usuários de sua secretaria.'),
(3, 'mechanic', 'Mecânico, responsável pela manutenção e checklists.'),
(4, 'driver', 'Motorista, pode registrar diário de bordo e corridas.');

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
(1, 1, 5, 1, 5250, 5400, '2025-09-08 08:00:00', '2025-09-08 11:30:00', 'Hospital Regional', 'Pátio da Secretaria de Saúde', 'completed'),
(2, 2, 6, 2, 15150, 15300, '2025-09-08 09:15:00', '2025-09-08 16:45:00', 'Vistoria de Ponte Rio Claro', 'Garagem de Obras', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_messages`
--

CREATE TABLE `scheduled_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `recipient_type` enum('user','secretariat','role','all') NOT NULL,
  `recipient_ids` text DEFAULT NULL COMMENT 'IDs separados por vírgula (ex: 1,2,3)',
  `send_at` datetime NOT NULL,
  `status` enum('pending','sent','error') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
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

--
-- Dumping data for table `secretariats`
--

INSERT INTO `secretariats` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Secretaria de Saúde', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(2, 'Secretaria de Obras e Infraestrutura', '2025-09-09 17:47:15', '2025-09-09 17:47:15');

-- --------------------------------------------------------

--
-- Table structure for table `tires`
--

CREATE TABLE `tires` (
  `id` int(11) NOT NULL,
  `dot` varchar(50) NOT NULL COMMENT 'Código DOT único do pneu',
  `brand` varchar(100) NOT NULL COMMENT 'Marca do pneu',
  `model` varchar(100) NOT NULL COMMENT 'Modelo do pneu',
  `purchase_date` date DEFAULT NULL COMMENT 'Data da compra',
  `status` enum('in_stock','in_use','recapping','discarded') NOT NULL DEFAULT 'in_stock',
  `lifespan_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Vida útil restante em %',
  `secretariat_id` int(11) NOT NULL COMMENT 'Secretaria proprietária do pneu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tires`
--

INSERT INTO `tires` (`id`, `dot`, `brand`, `model`, `purchase_date`, `status`, `lifespan_percentage`, `secretariat_id`, `created_at`) VALUES
(1, 'DOT1111', 'Michelin', 'Primacy 4', NULL, 'in_stock', 100, 1, '2025-09-09 17:47:15'),
(2, 'DOT2222', 'Pirelli', 'Cinturato P7', NULL, 'in_stock', 100, 1, '2025-09-09 17:47:15'),
(3, 'DOT3333', 'Goodyear', 'Wrangler', NULL, 'in_stock', 100, 2, '2025-09-09 17:47:15'),
(4, 'DOT4444', 'Goodyear', 'Wrangler', NULL, 'in_stock', 100, 2, '2025-09-09 17:47:15'),
(5, '33', '33', '33', '2025-09-09', 'in_stock', 100, 2, '2025-09-09 18:32:57');

-- --------------------------------------------------------

--
-- Table structure for table `tire_events`
--

CREATE TABLE `tire_events` (
  `id` int(11) NOT NULL,
  `tire_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_type` enum('installation','rotation','swap_in','swap_out','recapping_sent','recapping_returned','discarded') NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tire_layouts`
--

CREATE TABLE `tire_layouts` (
  `id` int(11) NOT NULL,
  `layout_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Ex: Carro Passeio (4 Pneus)',
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON com posições e CSS' CHECK (json_valid(`config_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tire_layouts`
--

INSERT INTO `tire_layouts` (`id`, `layout_key`, `name`, `config_json`, `created_at`) VALUES
(4, '2X2', 'eee', '{\"positions\":[\"front_left\",\"front_right\",\"rear_left\",\"rear_right\"]}', '2025-09-09 18:40:19'),
(5, 'EE', 'eeeeeeeee', '{\"positions\":[\"front_left\",\"front_right\",\"rear_left\",\"rear_right\",\"rear_left_inner\",\"rear_right_inner\",\"steer\"]}', '2025-09-09 18:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `tire_lifespan_rules`
--

CREATE TABLE `tire_lifespan_rules` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `lifespan_km` int(10) UNSIGNED NOT NULL,
  `lifespan_days` int(10) UNSIGNED NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tire_lifespan_rules`
--

INSERT INTO `tire_lifespan_rules` (`id`, `category_id`, `lifespan_km`, `lifespan_days`, `updated_at`) VALUES
(1, 3, 1399, 4555, '2025-09-09 18:39:55');

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
  `cnh_photo_path` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `cpf`, `email`, `password`, `role_id`, `secretariat_id`, `department_id`, `cnh_number`, `cnh_expiry_date`, `profile_photo_path`, `cnh_photo_path`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin Geral', '11122233344', 'admin@frotas.gov', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(2, 'Gestor da Saúde', '22233344455', 'gestor.saude@frotas.gov', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 2, 1, 1, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(3, 'Gestor de Obras', '33344455566', 'gestor.obras@frotas.gov', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 2, 2, 3, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(4, 'Mecânico Chefe', '44455566677', 'mecanico@frotas.gov', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 3, 2, 4, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(5, 'João da Silva (Motorista Saúde)', '55566677788', 'joao.silva@email.com', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 4, 1, 1, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(6, 'Maria Oliveira (Motorista Obras)', '66677788899', 'maria.oliveira@email.com', '$2y$10$wKq4n3v.d2vY.mE.fG.hO.A5B6C7D8E9F0G1H2I3J4K5', 4, 2, 4, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:47:15', '2025-09-09 17:47:15'),
(7, 'Alexan Zanat', '11111111111', '1@1.com', '$2y$10$.uabkVvZ.V/4gqW1lhCpdeOcndzVYVdB9nWT7VrYNFyLWLdPaurvW', 1, 2, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-09-09 17:49:16', '2025-09-09 17:50:25');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Ex: FORD/RANGER XL CD4',
  `plate` varchar(10) NOT NULL COMMENT 'Placa do veículo',
  `prefix` varchar(20) NOT NULL COMMENT 'Prefixo ou abreviação, ex: V-123',
  `category_id` int(11) DEFAULT 1,
  `current_secretariat_id` int(11) NOT NULL,
  `fuel_tank_capacity_liters` decimal(5,2) DEFAULT NULL,
  `avg_km_per_liter` decimal(5,2) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','blocked') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_oil_change_km` int(10) UNSIGNED DEFAULT NULL COMMENT 'KM da última troca de óleo',
  `last_oil_change_date` date DEFAULT NULL COMMENT 'Data da última troca de óleo',
  `next_oil_change_km` int(10) UNSIGNED DEFAULT NULL COMMENT 'KM previsto para a próxima troca',
  `next_oil_change_date` date DEFAULT NULL COMMENT 'Data prevista para a próxima troca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `plate`, `prefix`, `category_id`, `current_secretariat_id`, `fuel_tank_capacity_liters`, `avg_km_per_liter`, `status`, `created_at`, `updated_at`, `last_oil_change_km`, `last_oil_change_date`, `next_oil_change_km`, `next_oil_change_date`) VALUES
(1, 'Fiat Cronos 1.3', 'BRA1A23', 'SAUDE-01', 1, 1, 48.00, 13.50, 'available', '2025-09-09 17:47:15', '2025-09-09 17:47:15', 5200, '2025-08-10', NULL, NULL),
(2, 'VW Amarok V6', 'BRA2B34', 'OBRAS-01', 2, 2, 80.00, 8.50, 'available', '2025-09-09 17:47:15', '2025-09-09 17:47:15', 15100, '2025-07-20', NULL, NULL),
(3, 'Renault Master Ambulância', 'BRA3C45', 'SAUDE-AMB', 1, 1, 100.00, 9.00, 'available', '2025-09-09 17:47:15', '2025-09-09 17:47:15', 22300, '2025-09-01', NULL, NULL),
(4, 'Honda NXR 160 Bros', 'BRA4D56', 'SAUDE-MOTO', 3, 1, 12.00, 35.00, 'available', '2025-09-09 17:47:15', '2025-09-09 17:47:15', 1100, '2025-08-15', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_categories`
--

CREATE TABLE `vehicle_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `layout_key` varchar(50) NOT NULL DEFAULT 'car_2x2' COMMENT 'Chave que identifica o diagrama de pneus (ex: car_2x2, truck_4x2)',
  `oil_change_km` int(11) NOT NULL DEFAULT 10000 COMMENT 'KM padrão para a troca de óleo',
  `oil_change_days` int(11) NOT NULL DEFAULT 180 COMMENT 'Dias padrão para a troca de óleo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_categories`
--

INSERT INTO `vehicle_categories` (`id`, `name`, `layout_key`, `oil_change_km`, `oil_change_days`) VALUES
(1, 'Veículo Leve', 'EE', 10000, 180),
(2, 'Veículo Pesado', 'EE', 15000, 270),
(3, 'Motocicleta', 'EE', 5000, 120);

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_tires`
--

CREATE TABLE `vehicle_tires` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `tire_id` int(11) NOT NULL,
  `position` varchar(50) NOT NULL COMMENT 'Ex: front_left, rear_right_inner',
  `installed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_transfers`
--

CREATE TABLE `vehicle_transfers` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `origin_secretariat_id` int(11) NOT NULL,
  `destination_secretariat_id` int(11) NOT NULL,
  `transfer_type` enum('permanent','temporary') NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','returned') NOT NULL DEFAULT 'pending',
  `approver_id` int(11) DEFAULT NULL,
  `request_notes` text DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `chat_message_recipients`
--
ALTER TABLE `chat_message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_recipient_unique` (`message_id`,`recipient_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `chat_message_templates`
--
ALTER TABLE `chat_message_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_user_unique` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`),
  ADD KEY `secretariat_id` (`secretariat_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `oil_change_logs`
--
ALTER TABLE `oil_change_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `secretariat_id` (`secretariat_id`),
  ADD KEY `oil_product_id` (`oil_product_id`);

--
-- Indexes for table `oil_products`
--
ALTER TABLE `oil_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `secretariat_id` (`secretariat_id`);

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
-- Indexes for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `send_at` (`send_at`,`status`);

--
-- Indexes for table `secretariats`
--
ALTER TABLE `secretariats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tires`
--
ALTER TABLE `tires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dot` (`dot`),
  ADD KEY `secretariat_id` (`secretariat_id`);

--
-- Indexes for table `tire_events`
--
ALTER TABLE `tire_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tire_id` (`tire_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tire_layouts`
--
ALTER TABLE `tire_layouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `layout_key` (`layout_key`);

--
-- Indexes for table `tire_lifespan_rules`
--
ALTER TABLE `tire_lifespan_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_id` (`category_id`);

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
-- Indexes for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `vehicle_tires`
--
ALTER TABLE `vehicle_tires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_position` (`vehicle_id`,`position`),
  ADD KEY `tire_id` (`tire_id`);

--
-- Indexes for table `vehicle_transfers`
--
ALTER TABLE `vehicle_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `origin_secretariat_id` (`origin_secretariat_id`),
  ADD KEY `destination_secretariat_id` (`destination_secretariat_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_message_recipients`
--
ALTER TABLE `chat_message_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_message_templates`
--
ALTER TABLE `chat_message_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_participants`
--
ALTER TABLE `chat_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checklists`
--
ALTER TABLE `checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checklist_answers`
--
ALTER TABLE `checklist_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checklist_items`
--
ALTER TABLE `checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fuelings`
--
ALTER TABLE `fuelings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gas_stations`
--
ALTER TABLE `gas_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gas_station_fuels`
--
ALTER TABLE `gas_station_fuels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oil_change_logs`
--
ALTER TABLE `oil_change_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oil_products`
--
ALTER TABLE `oil_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `runs`
--
ALTER TABLE `runs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `secretariats`
--
ALTER TABLE `secretariats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tires`
--
ALTER TABLE `tires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tire_events`
--
ALTER TABLE `tire_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tire_layouts`
--
ALTER TABLE `tire_layouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tire_lifespan_rules`
--
ALTER TABLE `tire_lifespan_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_tires`
--
ALTER TABLE `vehicle_tires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_transfers`
--
ALTER TABLE `vehicle_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_message_recipients`
--
ALTER TABLE `chat_message_recipients`
  ADD CONSTRAINT `chat_message_recipients_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_message_recipients_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_message_templates`
--
ALTER TABLE `chat_message_templates`
  ADD CONSTRAINT `chat_message_templates_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD CONSTRAINT `chat_participants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD CONSTRAINT `chat_rooms_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `oil_change_logs`
--
ALTER TABLE `oil_change_logs`
  ADD CONSTRAINT `oil_change_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oil_change_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oil_change_logs_ibfk_3` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oil_change_logs_ibfk_4` FOREIGN KEY (`oil_product_id`) REFERENCES `oil_products` (`id`);

--
-- Constraints for table `oil_products`
--
ALTER TABLE `oil_products`
  ADD CONSTRAINT `oil_products_ibfk_1` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `runs`
--
ALTER TABLE `runs`
  ADD CONSTRAINT `runs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `runs_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `runs_ibfk_3` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  ADD CONSTRAINT `scheduled_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tires`
--
ALTER TABLE `tires`
  ADD CONSTRAINT `tires_ibfk_1` FOREIGN KEY (`secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tire_events`
--
ALTER TABLE `tire_events`
  ADD CONSTRAINT `tire_events_ibfk_1` FOREIGN KEY (`tire_id`) REFERENCES `tires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tire_events_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tire_lifespan_rules`
--
ALTER TABLE `tire_lifespan_rules`
  ADD CONSTRAINT `fk_rule_to_category` FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `vehicle_tires`
--
ALTER TABLE `vehicle_tires`
  ADD CONSTRAINT `vehicle_tires_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_tires_ibfk_2` FOREIGN KEY (`tire_id`) REFERENCES `tires` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_transfers`
--
ALTER TABLE `vehicle_transfers`
  ADD CONSTRAINT `vehicle_transfers_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_transfers_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_transfers_ibfk_3` FOREIGN KEY (`origin_secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_transfers_ibfk_4` FOREIGN KEY (`destination_secretariat_id`) REFERENCES `secretariats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_transfers_ibfk_5` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
