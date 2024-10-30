-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 02, 2023 at 06:35 AM
-- Server version: 5.7.41
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `research_911ResearchArchives`
--

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_website_blocks`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_website_blocks`;
CREATE TABLE `bbc_botbanishclient_website_blocks` (
  `id_no` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `subject` varchar(100) CHARACTER SET utf8 NOT NULL,
  `blocks` int(10) NOT NULL,
  `date` date NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_website_downloads`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_website_downloads`;
CREATE TABLE `bbc_botbanishclient_website_downloads` (
  `id_no` int(10) UNSIGNED NOT NULL,
  `filename` varchar(100) NOT NULL,
  `ip_addr` varchar(39) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `http_user_agent` varchar(255) NOT NULL,
  `http_referer` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `rpt_date` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `country` varchar(50) NOT NULL DEFAULT '',
  `country_code` varchar(10) NOT NULL DEFAULT '',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_website_visits`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_website_visits`;
CREATE TABLE `bbc_botbanishclient_website_visits` (
  `id_no` int(10) UNSIGNED NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `ip_addr` varchar(39) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `http_user_agent` varchar(255) NOT NULL,
  `http_referer` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `rpt_date` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `country` varchar(50) NOT NULL DEFAULT '',
  `country_code` varchar(10) NOT NULL DEFAULT '',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bbc_botbanishclient_website_blocks`
--
ALTER TABLE `bbc_botbanishclient_website_blocks`
  ADD PRIMARY KEY (`id_no`),
  ADD UNIQUE KEY `uniquehit` (`subject`,`date`);

--
-- Indexes for table `bbc_botbanishclient_website_downloads`
--
ALTER TABLE `bbc_botbanishclient_website_downloads`
  ADD PRIMARY KEY (`id_no`),
  ADD UNIQUE KEY `uniquehit` (`user_id`,`filename`,`ip_addr`,`date`);

--
-- Indexes for table `bbc_botbanishclient_website_visits`
--
ALTER TABLE `bbc_botbanishclient_website_visits`
  ADD PRIMARY KEY (`id_no`),
  ADD UNIQUE KEY `uniquehit` (`user_id`,`page_name`,`ip_addr`,`date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_website_blocks`
--
ALTER TABLE `bbc_botbanishclient_website_blocks`
  MODIFY `id_no` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_website_downloads`
--
ALTER TABLE `bbc_botbanishclient_website_downloads`
  MODIFY `id_no` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_website_visits`
--
ALTER TABLE `bbc_botbanishclient_website_visits`
  MODIFY `id_no` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
