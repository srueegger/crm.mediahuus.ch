-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Erstellungszeit: 01. Sep 2025 um 14:20
-- Server-Version: 10.11.13-MariaDB-ubu2204-log
-- PHP-Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `crm_mediahuus`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `city` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `branches`
--

INSERT INTO `branches` (`id`, `name`, `street`, `zip`, `city`, `phone`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Mediahuus Clara', 'Hauptstrasse 123', '4058', 'Basel', '+41 61 123 45 67', 'info@mediahuus.ch', '2025-08-31 14:18:23', '2025-08-31 16:20:38'),
(2, 'Mediahuus Reinach', 'Baselstrasse 456', '4153', 'Reinach BL', '+41 61 987 65 43', 'info@mediahuus.ch', '2025-08-31 14:18:23', '2025-08-31 16:20:38');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `doc_type` enum('estimate','purchase','insurance') NOT NULL,
  `doc_number` varchar(20) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `documents`
--

INSERT INTO `documents` (`id`, `doc_type`, `doc_number`, `branch_id`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `created_at`, `updated_at`) VALUES
(1, 'estimate', 'KO-2025-000001', 2, 2, 'Max Müller', '+41793513494', 'kunde@kunde.ch', '2025-08-31 14:53:50', '2025-08-31 14:53:50'),
(2, 'estimate', 'KO-2025-000002', 1, 2, 'Max Mustermann', '0793513494', 'kunde@kunde.ch', '2025-08-31 14:58:36', '2025-08-31 14:58:36'),
(3, 'estimate', 'KO-2025-000003', 1, 2, 'Max müller', '+41793513494', 'samuel@rueegger.me', '2025-08-31 16:18:01', '2025-08-31 16:18:01'),
(4, 'estimate', 'KO-2025-000004', 1, 2, 'Max Muster', '079 351 34 94', 'kunde@kunde.ch', '2025-09-01 01:37:18', '2025-09-01 01:37:18'),
(5, 'estimate', 'KO-2025-000005', 1, 2, 'Samuel Rüegger', '0793513494', 'samuel@rueegger.me', '2025-09-01 01:44:21', '2025-09-01 01:44:21'),
(6, 'estimate', 'KO-2025-000006', 1, 2, 'Samuel Rüegger', '0793513494', 'samuel@rueegger.me', '2025-09-01 11:52:39', '2025-09-01 11:52:39');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `estimates`
--

CREATE TABLE `estimates` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `issue_text` text NOT NULL,
  `price_chf` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `estimates`
--

INSERT INTO `estimates` (`id`, `document_id`, `device_name`, `serial_number`, `issue_text`, `price_chf`) VALUES
(1, 1, '', '', 'Genauer Schaden\r\n\r\nMit Abstände\r\n\r\nUnd so weiter', 400.00),
(2, 2, '', '', 'HEftiger Schaden', 400.00),
(3, 3, 'iPhone 15', 'XYZ6789vc', 'Wasserschaden.\r\nAkku ersetzen\r\nLautsprecher ersetzen', 420.00),
(4, 4, 'Samsung Galaxy S24', 'jdejfchfj844', '<p>Hier kommt eine <u>asuführliche</u> Beschreibung</p><p><br></p><p>Mit <strong>Abständen</strong> und so</p>', 370.00),
(5, 5, 'Samsung Galaxy S24', 'IEHFJ76345', '<p>Wir <u>haben</u> ein Problem</p><p><br></p><p><strong>Und</strong> so</p><p><br></p><ul><li>Liste 1</li><li>Liste 2</li></ul>', 340.00),
(6, 6, 'Samsung galaxy S24', 'DFGJDKFÖHGH56', '<p>Wasserschaden LAutsprecher müssen ersetzt werden</p><p>Kopfhörerbuchse</p><p>Aku muss gewechselt werden</p>', 400.00);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `executed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `executed_at`) VALUES
(1, '001_create_users_table', '2025-08-31 14:18:19'),
(2, '002_create_branches_table', '2025-08-31 14:18:19'),
(3, '003_create_documents_table', '2025-08-31 14:52:33'),
(4, '004_create_estimates_table', '2025-08-31 14:52:33');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@mediahuus.ch', '$2y$10$COtbCXVkqCjiNDOUCt5N7eFgI8Z5gHTVlydxN29y1YaRCRHMVQqqi', 0, '2025-08-31 14:18:23', '2025-08-31 14:46:39'),
(2, 'Samuel Rüegger', 'samuel@rueegger.me', '$2y$10$9EftQQQB5XUaVRkFGsVt/.swQm.5dk7UYrcBcfOPbr84bG9Eaad7q', 1, '2025-08-31 14:46:17', '2025-08-31 14:46:17');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indizes für die Tabelle `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_number` (`doc_number`),
  ADD KEY `idx_doc_type` (`doc_type`),
  ADD KEY `idx_doc_number` (`doc_number`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indizes für die Tabelle `estimates`
--
ALTER TABLE `estimates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_id` (`document_id`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_price` (`price_chf`),
  ADD KEY `idx_device_name` (`device_name`),
  ADD KEY `idx_serial_number` (`serial_number`);

--
-- Indizes für die Tabelle `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration` (`migration`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `estimates`
--
ALTER TABLE `estimates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `estimates`
--
ALTER TABLE `estimates`
  ADD CONSTRAINT `estimates_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
