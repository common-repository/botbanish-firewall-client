-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 04, 2023 at 08:23 AM
-- Server version: 5.7.36
-- PHP Version: 8.1.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `botbanishserver_4`
--

-- --------------------------------------------------------

--
-- Table structure for table `botbanish_country_code`
--

DROP TABLE IF EXISTS `botbanish_country_code`;
CREATE TABLE `botbanish_country_code` (
  `country_code` varchar(4) NOT NULL,
  `country` varchar(50) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `botbanish_language`
--

DROP TABLE IF EXISTS `botbanish_language`;
CREATE TABLE `botbanish_language` (
  `lang_id` int(10) NOT NULL,
  `language` varchar(30) CHARACTER SET utf8 NOT NULL,
  `lang_code` varchar(10) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `botbanish_language_text`
--

DROP TABLE IF EXISTS `botbanish_language_text`;
CREATE TABLE `botbanish_language_text` (
  `lang_id` int(10) NOT NULL,
  `lang_key` varchar(50) CHARACTER SET utf8 NOT NULL,
  `lang_text` varchar(1024) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `botbanish_timezones`
--

DROP TABLE IF EXISTS `botbanish_timezones`;
CREATE TABLE `botbanish_timezones` (
  `tz_id` int NOT NULL,
  `gmt` varchar(20) NOT NULL,
  `area` varchar(100) NOT NULL,
  `timezone` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `botbanish_timezones`
--
ALTER TABLE `botbanish_timezones`
  ADD UNIQUE KEY (`tz_id`);

--
-- Indexes for table `botbanish_country_code`
--
ALTER TABLE `botbanish_country_code`
  ADD UNIQUE KEY (`country_code`);

--
-- Indexes for table `botbanish_language`
--
ALTER TABLE `botbanish_language`
  ADD UNIQUE KEY (`lang_id`);

--
-- Indexes for table `botbanish_language_text`
--
ALTER TABLE `botbanish_language_text`
  ADD UNIQUE KEY `lang` (`lang_id`, `lang_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `botbanish_timezones`
--
ALTER TABLE `botbanish_timezones`
  MODIFY `tz_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `botbanish_language`
--
ALTER TABLE `botbanish_language`
  MODIFY `lang_id` int(10) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
