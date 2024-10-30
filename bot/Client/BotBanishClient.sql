-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 27, 2023 at 11:04 PM
-- Server version: 5.7.43
-- PHP Version: 8.1.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `botbanish_BotBanishServer_4.1`
--

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_debug_trace`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_debug_trace`;
CREATE TABLE `bbc_botbanishclient_debug_trace` (
  `bot_id` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `hit_count` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_hit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `domain_name` varchar(50) NOT NULL DEFAULT '',
  `client_info` text NOT NULL,
  `server_info` text NOT NULL,
  `botbanish_info` text NOT NULL,
  `datarow` text NOT NULL,
  `response` text NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_doc_errors`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_doc_errors`;
CREATE TABLE `bbc_botbanishclient_doc_errors` (
  `bot_id` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `hit_count` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_hit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `domain_name` varchar(50) NOT NULL DEFAULT '',
  `error_doc` int(10) UNSIGNED NOT NULL,
  `error_cause` varchar(1024) NOT NULL,
  `client_info` text NOT NULL,
  `server_info` text NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_domain_bad`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_domain_bad`;
CREATE TABLE `bbc_botbanishclient_domain_bad` (
  `id_domain` int(10) UNSIGNED NOT NULL,
  `domain` varchar(50) NOT NULL DEFAULT '',
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_domain_good`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_domain_good`;
CREATE TABLE `bbc_botbanishclient_domain_good` (
  `id_domain` int(10) UNSIGNED NOT NULL,
  `domain` varchar(50) NOT NULL DEFAULT '',
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_htaccess`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_htaccess`;
CREATE TABLE `bbc_botbanishclient_htaccess` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(10) NOT NULL,
  `text` varchar(255) NOT NULL,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_ip`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_ip`;
CREATE TABLE `bbc_botbanishclient_ip` (
  `ip_id` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `hit_count` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `first_hit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_hit` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `deny` int(1) UNSIGNED NOT NULL DEFAULT '0',
  `created` int(1) UNSIGNED NOT NULL DEFAULT '0',
  `forcelockout` int(1) UNSIGNED NOT NULL DEFAULT '0',
  `domain_name` varchar(50) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `mined` int(1) UNSIGNED NOT NULL DEFAULT '0',
  `domain` varchar(50) NOT NULL DEFAULT '',
  `country` varchar(50) NOT NULL DEFAULT '',
  `country_code` varchar(10) NOT NULL DEFAULT '',
  `geo_info` varchar(2048) NOT NULL DEFAULT '',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_ip_dnb`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_ip_dnb`;
CREATE TABLE `bbc_botbanishclient_ip_dnb` (
  `ip_id` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_settings`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_settings`;
CREATE TABLE `bbc_botbanishclient_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL DEFAULT '',
  `value` varchar(8192) NOT NULL,
  `type` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_spiders_bad`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_spiders_bad`;
CREATE TABLE `bbc_botbanishclient_spiders_bad` (
  `id_spider` int(10) UNSIGNED NOT NULL,
  `spider_name` varchar(50) NOT NULL DEFAULT '',
  `user_agent_part` varchar(255) NOT NULL DEFAULT '',
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_spiders_good`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_spiders_good`;
CREATE TABLE `bbc_botbanishclient_spiders_good` (
  `id_spider` int(10) UNSIGNED NOT NULL,
  `spider_name` varchar(50) NOT NULL DEFAULT '',
  `user_agent_part` varchar(255) NOT NULL DEFAULT '',
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_url_dnc`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_url_dnc`;
CREATE TABLE `bbc_botbanishclient_url_dnc` (
  `url_id` int(10) UNSIGNED NOT NULL,
  `url_part` varchar(255) NOT NULL,
  `system` varchar(30) NOT NULL,
  `active` int(1) UNSIGNED NOT NULL DEFAULT '1',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bbc_botbanishclient_website_blocks`
--

DROP TABLE IF EXISTS `bbc_botbanishclient_website_blocks`;
CREATE TABLE `bbc_botbanishclient_website_blocks` (
  `id_no` int(10) UNSIGNED NOT NULL,
  `bot_ip` varchar(39) NOT NULL,
  `subject` varchar(100) NOT NULL,
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
  `http_user_agent` varchar(255) DEFAULT NULL,
  `http_referer` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `rpt_date` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `country` varchar(50) NOT NULL DEFAULT '',
  `country_code` varchar(10) NOT NULL DEFAULT '',
  `geo_info` varchar(2048) NOT NULL DEFAULT '',
  `system` varchar(20) NOT NULL DEFAULT '',
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
  `http_user_agent` varchar(255) DEFAULT NULL,
  `http_referer` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `rpt_date` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `country` varchar(50) NOT NULL DEFAULT '',
  `country_code` varchar(10) NOT NULL DEFAULT '',
  `geo_info` varchar(2048) NOT NULL DEFAULT '',
  `system` varchar(20) NOT NULL DEFAULT '',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bbc_botbanishclient_debug_trace`
--
ALTER TABLE `bbc_botbanishclient_debug_trace`
  ADD PRIMARY KEY (`bot_id`),
  ADD KEY `IP` (`bot_ip`),
  ADD KEY `Domain` (`domain_name`);

--
-- Indexes for table `bbc_botbanishclient_doc_errors`
--
ALTER TABLE `bbc_botbanishclient_doc_errors`
  ADD PRIMARY KEY (`bot_id`),
  ADD KEY `bot_ip` (`bot_ip`,`error_doc`,`error_cause`(20)) USING BTREE;

--
-- Indexes for table `bbc_botbanishclient_domain_bad`
--
ALTER TABLE `bbc_botbanishclient_domain_bad`
  ADD PRIMARY KEY (`id_domain`),
  ADD UNIQUE KEY `domain` (`domain`);

--
-- Indexes for table `bbc_botbanishclient_domain_good`
--
ALTER TABLE `bbc_botbanishclient_domain_good`
  ADD PRIMARY KEY (`id_domain`),
  ADD UNIQUE KEY `domain` (`domain`);

--
-- Indexes for table `bbc_botbanishclient_htaccess`
--
ALTER TABLE `bbc_botbanishclient_htaccess`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `updated` (`updated`);

--
-- Indexes for table `bbc_botbanishclient_ip`
--
ALTER TABLE `bbc_botbanishclient_ip`
  ADD PRIMARY KEY (`ip_id`),
  ADD UNIQUE KEY `bot_ip` (`bot_ip`),
  ADD KEY `first_hit` (`first_hit`),
  ADD KEY `last_hit` (`last_hit`);

--
-- Indexes for table `bbc_botbanishclient_ip_dnb`
--
ALTER TABLE `bbc_botbanishclient_ip_dnb`
  ADD PRIMARY KEY (`ip_id`),
  ADD UNIQUE KEY `ip` (`bot_ip`) USING BTREE;

--
-- Indexes for table `bbc_botbanishclient_settings`
--
ALTER TABLE `bbc_botbanishclient_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `bbc_botbanishclient_spiders_bad`
--
ALTER TABLE `bbc_botbanishclient_spiders_bad`
  ADD PRIMARY KEY (`id_spider`),
  ADD UNIQUE KEY `user_agent` (`user_agent_part`);

--
-- Indexes for table `bbc_botbanishclient_spiders_good`
--
ALTER TABLE `bbc_botbanishclient_spiders_good`
  ADD PRIMARY KEY (`id_spider`),
  ADD UNIQUE KEY `user_agent` (`user_agent_part`);

--
-- Indexes for table `bbc_botbanishclient_url_dnc`
--
ALTER TABLE `bbc_botbanishclient_url_dnc`
  ADD PRIMARY KEY (`url_id`),
  ADD UNIQUE KEY `url_part` (`url_part`);

--
-- Indexes for table `bbc_botbanishclient_website_blocks`
--
ALTER TABLE `bbc_botbanishclient_website_blocks`
  ADD PRIMARY KEY (`id_no`),
  ADD UNIQUE KEY `bot_ip` (`bot_ip`,`subject`),
  ADD UNIQUE KEY `uniquehit` (`bot_ip`,`subject`,`date`);

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
-- AUTO_INCREMENT for table `bbc_botbanishclient_debug_trace`
--
ALTER TABLE `bbc_botbanishclient_debug_trace`
  MODIFY `bot_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_doc_errors`
--
ALTER TABLE `bbc_botbanishclient_doc_errors`
  MODIFY `bot_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_domain_bad`
--
ALTER TABLE `bbc_botbanishclient_domain_bad`
  MODIFY `id_domain` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_domain_good`
--
ALTER TABLE `bbc_botbanishclient_domain_good`
  MODIFY `id_domain` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_htaccess`
--
ALTER TABLE `bbc_botbanishclient_htaccess`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_ip`
--
ALTER TABLE `bbc_botbanishclient_ip`
  MODIFY `ip_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_ip_dnb`
--
ALTER TABLE `bbc_botbanishclient_ip_dnb`
  MODIFY `ip_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_settings`
--
ALTER TABLE `bbc_botbanishclient_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_spiders_bad`
--
ALTER TABLE `bbc_botbanishclient_spiders_bad`
  MODIFY `id_spider` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_spiders_good`
--
ALTER TABLE `bbc_botbanishclient_spiders_good`
  MODIFY `id_spider` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbc_botbanishclient_url_dnc`
--
ALTER TABLE `bbc_botbanishclient_url_dnc`
  MODIFY `url_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
