-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Dec 19, 2025 at 04:23 PM
-- Server version: 10.1.48-MariaDB-1~bionic
-- PHP Version: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `getraenkeliste`
--

-- --------------------------------------------------------

--
-- Table structure for table `db_actions_log`
--

CREATE TABLE `db_actions_log` (
  `id` int(11) NOT NULL,
  `action_type` enum('verkauf','bezahlen','neue_person') NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `json_data` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `db_eintrag`
--

CREATE TABLE `db_eintrag` (
  `id` int(11) NOT NULL,
  `person` int(11) NOT NULL,
  `date` date NOT NULL,
  `produkt` int(11) NOT NULL,
  `anzahl` int(11) NOT NULL,
  `bezahlt` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `db_personen`
--

CREATE TABLE `db_personen` (
  `person_id` int(11) NOT NULL,
  `nachname` tinytext NOT NULL,
  `vorname` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `db_produkte_standard`
--

CREATE TABLE `db_produkte_standard` (
  `produkt_id` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `preis` float NOT NULL,
  `bestand` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `db_actions_log`
--
ALTER TABLE `db_actions_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `db_eintrag`
--
ALTER TABLE `db_eintrag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `person` (`person`,`date`,`produkt`,`bezahlt`);

--
-- Indexes for table `db_personen`
--
ALTER TABLE `db_personen`
  ADD UNIQUE KEY `id` (`person_id`);

--
-- Indexes for table `db_produkte_standard`
--
ALTER TABLE `db_produkte_standard`
  ADD PRIMARY KEY (`produkt_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `db_actions_log`
--
ALTER TABLE `db_actions_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `db_eintrag`
--
ALTER TABLE `db_eintrag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `db_personen`
--
ALTER TABLE `db_personen`
  MODIFY `person_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `db_produkte_standard`
--
ALTER TABLE `db_produkte_standard`
  MODIFY `produkt_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
