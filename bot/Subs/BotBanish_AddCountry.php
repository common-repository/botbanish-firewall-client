<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// Add Country and Country Codes
// Date: 03/06/2024
// Usage: BotBanish_AddCountry.php
//
// all=true - Recalculate ALL `country` and `country_code` columns
// No Parameter (default) - Only update rows with empty `country` columns
//
// Will automatically add `country` and `country_code` columns to table if the columns do not exist
//
// Under normal operations we will restart this script from the url so we can breakup the time running
// so we do not hang while attempting to process all the records.
//
// Special SMF running concerns:
//
// if running under SMF; we need to restart under SMF constraints (redirectexit) so we do not hang
// while this scripts executes. We will be adding hooks to SMF to load and add this script to the
// actions of SMF.
///////////////////////////////////////////////////////////////////////////////////////////////////////

	// If we are called via a POST / GET, perform all operations otherwise we were called by an INCLUDE / REQUIRE call
	// in that case we just load the functions that will be needed so they can be called

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'BotBanish_AddCountry.php') !== false)
		AddCountry();

function BotBanishAddCountry_AddActions(&$actionArray) {

	$actionArray = array_merge(
		array(
			'addcountry' => array('BotBanish_AddCountry.php','AddCountry'),
		),
		$actionArray
	);
}

function AddCountry() {
	
	define('BOTBANISH_LIMIT', 50);

	if (defined('SMF')) {
		
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
		
			$url = str_ireplace(';', '&', $_SERVER['QUERY_STRING']);
			parse_str($url, $actions);
			$records = isset($actions['records']) ? $actions['records'] : 0;
			$overwrite = !isset($actions['overwrite']) || ($actions['overwrite'] == true) ? true : false;
			$func = isset($actions['func']) ? $actions['func'] : '';
			$loc = isset($actions['loc']) ?  $actions['loc'] : '';
		}
		
	} else {
			
		$records = isset($_GET['records']) ? $_GET['records'] : 0;
		$overwrite = !isset($_GET['overwrite']) || ($_GET['overwrite'] == true) ? true : false;
		$func = isset($_GET['func']) ? $_GET['func'] : '';
	}
	
	if (!defined('ABSPATH')) {

		if (defined('BOTBANISH_INCLUDE_DIR')) {
			
			require_once BOTBANISH_INCLUDE_DIR . 'BotBanish_PreIncludes.php';
			$loc = BOTBANISH_INCLUDE_DIR;
			
		} else {
		
			if (!isset($loc) || empty($loc))
				$loc = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);

			$filename = realpath($loc . '../Include/BotBanish_PreIncludes.php');
			require_once $filename;
		}

		$botbanish_system = BotBanishCheckSystem();

		switch ($botbanish_system['install_type']) {

			case 'SERVER':

				$filename = realpath($loc . '../Settings_Server.php');
				require_once $filename;
				break;

			default:

				$filename = realpath($loc . '../Settings_Client.php');
				require_once $filename;
				break;
		}

	} else {

		$botbanish_system = BotBanishCheckSystem();
	}

//	define('BOTBANISH_DEBUG_SQL', 1);
//	define('BOTBANISH_DEBUG_TRACE_ALL',1);

	$GLOBALS['output'] = '';
			
	@ini_set('display_errors', E_ALL);
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '512M');	// Insure we have enough memory for large data

	require_once(BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php');
	require_once(BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php');

	if (isset($func) && ($func == 'UpdateVisitors'))
		
		UpdateVisitors($records, $overwrite, $botbanish_system);
	else

		UpdateIP($records, $overwrite, $botbanish_system);
}

function UpdateIP($records, $overwrite, $botbanish_system) {
	
	BotBanishDisplayIt('System Type: ' . $botbanish_system['system'] . ' - ' . $_SERVER['SERVER_NAME'] . '</br></br>');
	BotBanishDisplayIt('Start - ' . date('m-d-Y h:i:s') . '</br></br>');

	BotBanishUpdateIP($records);

	BotBanishDisplayIt('Finish - ' . date('m-d-Y h:i:s') . '</br>');

	$GLOBALS['output'] = str_ireplace('</br>', PHP_EOL, $GLOBALS['output']);
	BotBanishWriteFile($_SERVER['DOCUMENT_ROOT'] . '/AddCountry.log', $GLOBALS['output'], $overwrite);
	
	if (defined('SMF'))
		redirectexit('action=addcountry;area=addcountry;func=UpdateIP;overwrite=false;records=' . $records . ';loc=' . urlencode(BOTBANISH_SUBS_DIR));
	else
		header('Location: ' . $_SERVER['PHP_SELF'] . '?func=UpdateIP&overwrite=false&records=' . $records);				
}

function UpdateVisitors($records, $overwrite, $botbanish_system) {
	
	BotBanishDisplayIt('System Type: ' . $botbanish_system['system'] . ' - ' . $_SERVER['SERVER_NAME'] . '</br></br>');
	BotBanishDisplayIt('Start - ' . date('m-d-Y h:i:s') . '</br></br>');

	BotBanishUpdateVisitors($records);

	BotBanishDisplayIt('Finish - ' . date('m-d-Y h:i:s') . '</br>');

	$GLOBALS['output'] = str_ireplace('</br>', PHP_EOL, $GLOBALS['output']);
	BotBanishWriteFile($_SERVER['DOCUMENT_ROOT'] . '/AddVisitors.log', $GLOBALS['output'], $overwrite);
	
	if (defined('SMF'))
		redirectexit('action=addcountry;area=addcountry;func=UpdateVisitors;overwrite=true;records=' . $records . ';loc=' . urlencode(BOTBANISH_SUBS_DIR));
	else
		header('Location: ' . $_SERVER['PHP_SELF'] . '?func=UpdateVisitors&overwrite=false&records=' . $records);				
}

function BotBanishUpdateIP(&$records) {

	BotBanishDisplayIt('Start IP Update</br>');

	if (BOTBANISH_SERVER)
		$tables = array('bbs_botbanishserver_ip', 'bbc_botbanishclient_ip');
	else
		$tables = array('bbc_botbanishclient_ip');

	foreach ($tables as $table) {

		$tablename = $table;

		$sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '" AND TABLE_NAME = "' . $tablename . '"';
		$rows = BotBanishExecuteSQL($sql, true);

		if (is_array($rows) && !empty($rows)) {

			BotBanishDisplayIt('</br>Processing Table - ' . $tablename . '</br>');

			// Add new columns if they are not present

			BotBanishAddCountryFieldsIP($tablename);

			if (isset($_GET['all']) && $_GET['all'] == 'true') {

				$sql = 'SELECT `ip_id`, `bot_ip` FROM `' . $tablename . '`
						WHERE 1
						ORDER BY `ip_id`
						LIMIT ' . BOTBANISH_LIMIT;
			} else {

					$sql = 'SELECT `ip_id`, `bot_ip` FROM `' . $tablename . '`
						WHERE `country` = ""
						ORDER BY `ip_id`
						LIMIT ' . BOTBANISH_LIMIT;
			}

			$rows = BotBanishExecuteSQL($sql, true);

			if (is_array($rows) && !empty($rows)) {

				$count = 0;
				BotBanishDisplayIt('IP Records to Process: ' . count($rows) . '</br>');

				foreach ($rows as $row) {

					$count++;
					$geo_data = BotBanishGetGeoData($row['bot_ip']);
					$country_info = BotBanishGetCountryData($geo_data);
					
					$sql = 'UPDATE ' . $tablename . '
								SET `country` = "' . $country_info['country'] . '",
								`country_code` = "' . $country_info['country_code'] . '", 
								`geo_info` = \'' . BotBanishSafeSerialize($geo_data) . '\'
									WHERE `ip_id` = ' . $row['ip_id'];

					BotBanishExecuteSQL($sql);
				}

				$records += $count;
				BotBanishDisplayIt('Records Processed: ' . $records . '</br>');

			} else {

				BotBanishDisplayIt('No Data to Process - ' . $tablename . '</br>');
				
				if (defined('SMF')) {
				
//					$context['sub_template'] = 'addCountry';	// Keeps SMF 2.1 RC1 from throwing error "Unable to load the 'main' template"
					// Setup for BotBanish_AddCountry.php to be called by index.php
					
					add_integration_function('integrate_pre_include', BOTBANISH_SUBS_DIR . 'BotBanish_AddCountry.php', true);
					add_integration_function('integrate_actions', 'BotBanishAddCountry_AddActions', true);
					redirectexit('action=addcountry;area=addcountry;func=UpdateVisitors;overwrite=true;records=' . $records . ';loc=' . urlencode(BOTBANISH_SUBS_DIR));
					
				} else {
					
					header('Location: ' . $_SERVER['PHP_SELF'] . '?func=UpdateVisitors&overwrite=true');
					exit;
				}
			}
		}
	}

	// Disengage BotBanish_AddCountry.php
	
	if (defined('SMF')) {
		
		remove_integration_function('integrate_actions', 'BotBanishAddCountry_AddActions');
		remove_integration_function('integrate_pre_include', '$boarddir/BotBanish/bot/Subs/BotBanish_AddCountry.php');
	}

	BotBanishDisplayIt('</br>End IP Update</br></br></br>');
}

function BotBanishUpdateVisitors(&$records) {

	BotBanishDisplayIt('Start Visitor Update</br>');

	$tables = array('bbc_botbanishclient_website_visits', 'bbc_botbanishclient_website_downloads');

	foreach ($tables as $table) {

		$tablename = $table;

		$sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '" AND TABLE_NAME = "' . $tablename . '"';
		$rows = BotBanishExecuteSQL($sql, true);

		if (is_array($rows) && !empty($rows)) {

			BotBanishDisplayIt('</br>Processing Table - ' . $tablename . '</br>');

			// Add new columns if they are not present

			BotBanishAddCountryFieldsVisitors($tablename);

			if (isset($_GET['all']) && $_GET['all'] == 'true') {

				$sql = 'SELECT `id_no`, `ip_addr` FROM `' . $tablename . '`
						WHERE 1 
						ORDER BY `id_no`
						LIMIT ' . BOTBANISH_LIMIT;
			} else {

				$sql = 'SELECT `id_no`, `ip_addr` FROM `' . $tablename . '`
					WHERE `country` = ""
					ORDER BY `id_no`
					LIMIT ' . BOTBANISH_LIMIT;
			}

			$rows = BotBanishExecuteSQL($sql, true);

			if (is_array($rows) && !empty($rows)) {

				$count = 0;
				BotBanishDisplayIt('Visitor Records to Process: ' . count($rows) . ' - Records Processed: ' . $records . '</br>');

				foreach ($rows as $row) {

					$count++;
					$geo_data = BotBanishGetGeoData($row['ip_addr']);
					$country_info = BotBanishGetCountryData($geo_data);
					
					$sql = 'UPDATE ' . $tablename . '
								SET `country` = "' . $country_info['country'] . '", 
								`country_code` = "' . $country_info['country_code'] . '",
								`geo_info` = \'' . BotBanishSafeSerialize($geo_data) . '\'
								WHERE `id_no` = ' . $row['id_no'];

					BotBanishExecuteSQL($sql);
				}

				$records += $count;
				BotBanishDisplayIt('Records Processed: ' . $records . '</br>');
				
			} else {

				BotBanishDisplayIt('No Data to Process - ' . $tablename . '</br>');
//				exit;
			}
		}
	}

	BotBanishDisplayIt('</br>End Visitor Update</br></br></br>');
}

function BotBanishAddCountryFieldsVisitors($tablename) {

	$rows = BotBanishCheckForCountryField($tablename);

	if (!is_array($rows) || empty($rows)) {

		BotBanishDisplayIt('Adding `country` and `country_code` Fields to ' . $tablename . '</br>');
		
		$sql = 'ALTER TABLE `' . $tablename . '`
				ADD `country` VARCHAR(50) NOT NULL DEFAULT "" AFTER `user_id`,
				ADD `country_code` VARCHAR(10) NOT NULL DEFAULT "" AFTER `country`,
				ADD `geo_info` VARCHAR(2048) NOT NULL DEFAULT "" AFTER `country_code`';
				
		BotBanishExecuteSQL($sql, true);
	}
}

function BotBanishAddCountryFieldsIP($tablename) {

	$rows = BotBanishCheckForCountryField($tablename);

	if (!is_array($rows) || empty($rows)) {

		BotBanishDisplayIt('Adding `country` and `country_code` Fields to ' . $tablename . '</br>');
		
		$sql = 'ALTER TABLE `' . $tablename .  '` 
			ADD `country` VARCHAR(50) NOT NULL DEFAULT "" AFTER `domain`, 
			ADD `country_code` VARCHAR(10) NOT NULL DEFAULT "" AFTER `country`,
			ADD `geo_info` VARCHAR(2048) NOT NULL DEFAULT "" AFTER `country_code`';
			
		BotBanishExecuteSQL($sql, true);
	}
}

function BotBanishCheckForCountryField($tablename) {

	$sql = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '" AND TABLE_NAME ="' . $tablename . '" AND COLUMN_NAME = "country"';
	$rows = BotBanishExecuteSQL($sql, true);
	return $rows;
}

function BotBanishDisplayIt($txt) {

	$GLOBALS['output'] .= $txt;
}
?>