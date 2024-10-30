<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Server Database Maintenance Sub-routines
// Date: 03/04/2024
// Usage: BotBanish_Subs_Maintenance.php
//
// 08/15/2023 - Adjusted IP discard from 30 days to 1 day to see if this will adversly impact detection
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

	$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
	$filename = realpath(str_ireplace($self, '../Include/BotBanish_PreIncludes.php', $_SERVER['SCRIPT_FILENAME']));
	require_once $filename;
	
	BotBanishDefineGlobals('BOTBANISH_MAINTENANCE', true);

	ini_set('memory_limit', '512M');	// We need big memory for large projects
	set_time_limit(0);

	$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
	$filename = realpath(str_ireplace($self, '../Settings_Server.php', $_SERVER['SCRIPT_FILENAME']));
	require_once $filename;
	
	BotBanishDefineGlobals('BOTBANISH_INSTALL_TYPE', 'server');
	BotBanishDefineGlobals('BOTBANISH_DB_PREFIX', 'bbc_');
	
	BotBanishServerPerformTableMaintenance(); 	// We only want to do this once per day!
												// probably best for a cron job
	BotBanishCleanOutput();
	echo 'BotBanish Maintenance Done';

function BotBanishServerPerformTableMaintenance()
{
	// Give some time in between major operations for server to recovery and users to get in

	BotBanishExecuteStatements();			// Clean up Database
	BotBanishExtendTime(3);

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		BotBanishRemoveOutdatedInfoHTACCESS();	// Remove old unused IP's
		BotBanishExtendTime(3);
	}
/*
	BotBanishHTACCESSCombineBlockedIP();			// Consolidate blocked ip ranges and sort IP list
	BotBanishExtendTime(3);

	BotBanishHTACCESSSortData('bot'); 		// Keep the Bot list sorted
	BotBanishExtendTime(3);
*/
	BotBanishDatabaseHTACCESSFlushCache();	// Flush the htaccess cache
	BotBanishExtendTime(3);
/*
	$sql = 'TRUNCATE TABLE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_htaccess`';
	BotBanishExecuteSQL($sql);
*/

	if (defined('BOTBANISH_LOG_FILES') && (BOTBANISH_LOG_FILES > 5))
		BotBanishMaintainLogs(BOTBANISH_LOG_FILES);		// Remove excess log files
}

function BotBanishExecuteStatements() {

	$statements = array(

		// Make sure all queried text fields are truncated in spider and domain tables
		'UPDATE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_spiders_bad` SET `spider_name` = trim(`spider_name`), `user_agent_part` = trim(`user_agent_part`)',
		'UPDATE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_spiders_good` SET `spider_name` = trim(`spider_name`), `user_agent_part` = trim(`user_agent_part`)',

		'UPDATE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_domain_bad` SET `domain` = trim(`domain`)',
		'UPDATE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_domain_good` SET `domain` = trim(`domain`)',
	);

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		$statements[] =
			// Record any IP data that we are going to remove from the IP table
			'INSERT INTO `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip_removed` (bot_ip, hit_count, first_hit, last_hit, deny, created, forcelockout, domain_name, user_agent, mined, domain, country, country_code)
				SELECT bot_ip, hit_count, first_hit, last_hit, deny, created, forcelockout, domain_name, user_agent, mined, domain, country, country_code
					FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > ' . BOTBANISH_SERVER_CLEANUP_DAYS;

		$statements[] =
			// Delete any old saved IP information from the IP removed table that is over 120 days old. We do this to conserve database space on the hosting server
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip_removed` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > 120';

		$statements[] =
			// Delete any old saved IP information from the IP rep table that is over 120 days old. We do this to conserve database space on the hosting server
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip_rep` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > 120';

		$statements[] =
			// Delete any old Debug Trace information from the debug table that is over 1 days old. We do this to conserve database space on the hosting server
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_debug_trace` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > 1';

		$statements[] =
			// Delete any old Doc Error information from the document error table that is over 20 days old. We do this to conserve database space on the hosting server
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_doc_errors` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > 20';

		$statements[] =
			// Delete any inactive users for over 3 months (90 days)
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_usage_demo` WHERE DATEDIFF(NOW(), `updated`) > 90';

		$statements[] =
			// Delete any emails where the username is the same as the email (bug corrected in 4.0.07)
			'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_email` WHERE `email` = `username`';
	}

	$statements[] =
		// Delete any old IP information from the IP table that we have not seen in over 24 hours. We do this to preserve the checking integrity
		// so that if an attacking BOT/USER has stopped using the IP we will allow future legitimate users to use it on newer registered systems.
		// All the older systems will have locked the IP out so we can reset our system to be up to date.
		'DELETE FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > ' . BOTBANISH_SERVER_CLEANUP_DAYS;

	foreach ($statements as $sql)
		BotBanishExecuteSQL($sql);
}

function BotBanishRemoveOutdatedInfoHTACCESS()
{
	// Delete any old IP information from the .htaccess file that we have not seen in over 30 days. We do this to preserve the checking integrity
	// so that if an attacking BOT/USER has stopped using the IP we will allow future legitimate users to use it on newer registered systems.
	// All the older systems will have locked the IP out so we can reset our system to be up to date.

	$sql = 'SELECT `bot_ip` FROM `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip_removed` WHERE `processed` = 0';
	$rows = BotBanishExecuteSQL($sql);

	if (is_array($rows) && !empty($rows)) {

		$ips = BotBanishAdjustDatabaseData($rows);
		BotBanishHTACCESSRemoveEntry($ips, 'ip');

		$sql = 'UPDATE `' . BOTBANISH_DB_PREFIX . 'botbanish' . BOTBANISH_INSTALL_TYPE . '_ip_removed` SET `processed` = 1 WHERE `processed` = 0';
		$rows = BotBanishExecuteSQL($sql);
	}
}

function BotBanishAdjustDatabaseData($rows) {

	$ips = array();

	foreach ($rows as $row)
		$ips[] = $row['bot_ip'];

	return $ips;
}
?>