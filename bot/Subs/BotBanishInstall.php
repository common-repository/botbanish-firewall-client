<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Server Install
// Date: 05/31/2024
// Usage:
//
// Function:
//		Installing the Client/Server Tables
//		Create the BOT tables that we will need
//		We also import the IPs from the local .htaccess file to help avoid duplication
///////////////////////////////////////////////////////////////////////////////////////////////////////

	// Only run pre-install test if on OpenCart or SMF. Website installs have their own selection screen
	
	if (defined('DIR_APPLICATION'))
		BotBanish_PreInstall_Test();
	
	BotBanishInstallStart();

function BotBanishInstallStart() {
	
	global $BotBanishSettings, $install, $txt;
	
	// Destroy all BotBanish session variables if they are present for a new install
	
	if (isset($_SESSION['BotBanish']))
		unset($_SESSION['BotBanish']);

	// Turn on all debugging
	// This for testing ONLY!!!
	// Turn off for production!!!
	
	BotBanishDefineGlobals('BOTBANISH_DEBUG_TRACE_ALL', 0); 
	BotBanishDefineGlobals('BOTBANISH_DEBUG_SQL', 0);
	BotBanishDefineGlobals('BOTBANISH_DEBUG_LOG_PROGRESS', 0);
	BotBanishDefineGlobals('BOTBANISH_DEBUG_LOG_INFO', 0);
			
	// Setup for install
	
	$screenData = array();
	$installsettings = !empty($_POST) ? $_POST : $GLOBALS['BotBanishSettings'];

	foreach ($installsettings as $key => $value) {

		if (!empty($key)) {

			if (!defined($key))
				BotBanishDefineGlobals($key, $value);
				$BotBanishSettings[$key] = $value;

			$screenData[$key] = $value;
		}
	}
	
	// Download the language documents for SMF and place them in the BotBanish Docs folder
	
	require_once BOTBANISH_INCLUDE_DIR . 'BotBanish_Common.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanishTransferFile.php';

	// Create the client in all cases

	$BotBanishSettingsFile1 = BOTBANISH_CLIENT_DIR . 'Settings_Client.php';
	$BotBanishSettingsFile2 =  BOTBANISH_APPPATH_DIR . 'Settings_Client.php';

	BotBanishSetSettingsInstall($BotBanishSettingsFile1, $BotBanishSettingsFile2, $screenData, 'Client');

	if (BOTBANISH_SERVER) {

		$BotBanishSettingsFile1 = BOTBANISH_SERVER_DIR . 'Settings_' . BOTBANISH_INSTALL_TYPE . '.php';
		$BotBanishSettingsFile2 =  BOTBANISH_APPPATH_DIR . 'Settings_' . BOTBANISH_INSTALL_TYPE . '.php';
		BotBanishSetSettingsInstall($BotBanishSettingsFile1, $BotBanishSettingsFile2, $screenData, 'Server');
	}

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_DumpDatabase.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_AdjustDatabase.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_HTACCESS.php';

	// Backup some tables, if needed

	if (defined('SMF')) {
		
		$type = defined('SMF') ? 'SMF' : '';
		$type = empty($type) && defined('ABSPATH') ? 'WORDPRESS' : $type;
		$tables = array();
		
		switch ($type) {
			
			case 'SMF':
			
				global $db_prefix;
				$tables = array($db_prefix . 'settings', $db_prefix . 'log_packages');
				break;

			case 'WORDPRESS':
			
				global $table_prefix;
				$tables = array($table_prefix . 'options');
				break;
		}
		
		foreach ($tables as $table)
			BotBanish_DumpSingleTable($table);
	}

	// Create BotBanish database tables
	
	$records = BotBanish_CreateDatabase();
	
	// We need to save our setting and merge them after the load of $BotBanisgSettingsFile2
	// for a new $BotBanishSettings will be created and wipe out all the settings

	$savedSettings = $BotBanishSettings;
	require_once $BotBanishSettingsFile2;
	$BotBanishSettings = array_merge($BotBanishSettings, $savedSettings);
	BotBanishSetLanguage();

	//----------------------------------------------------------------------
	// Backup .htaccess files, create honeypot, index.php and favicon files
	//----------------------------------------------------------------------

	date_default_timezone_set(BOTBANISH_TIMEZONE);
	$icons = array('android-chrome-192x192.png', 'android-chrome-512x512.png', 'apple-touch-icon.png', 'favicon-16x16.png', 'favicon.ico', 'favicon-32x32.png');
	$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	if (defined('BOTBANISH_SERVER') && BOTBANISH_SERVER)
		BotBanishRecursiveCopy(BOTBANISH_IMAGES_DIR, realpath(BOTBANISH_LOCATION_ROOT . '../Images/'));
	
	foreach ($htaccess_array as $htaccessFile) {
		
		// Backup .htaccess file is it exists
		
		if (file_exists($htaccessFile))
			copy($htaccessFile, $htaccessFile . BotBanishGetTimeExtension());
		
		$htaccessFolder = str_ireplace('.htaccess', '', $htaccessFile);
		BotBanishHTMLHoneyPotCreate($htaccessFolder);
		
		// if no a favicon.ico file; give them one (Critically needed to prevent doc errors)

		if (!file_exists($htaccessFolder . 'favicon.ico')) {

			foreach ($icons as $icon)
			copy(BOTBANISH_IMAGES_DIR . 'icons/' . $icon, $htaccessFolder . $icon);
		}
/*
		// Give them our index.php version also so that they may implement it if they like
		
		$str = 'BotBanishRootIndex.php';

		BotBanishIndexUpdate(BOTBANISH_INCLUDE_DIR . 'BotBanishRootIndex.php', $htaccessFolder . $str);
*/
	}
	
	// Add our code to the .htaccess files to handle error processing etc.

	BotBanishHTACCESSMaintenance('clean');
	BotBanishHTACCESSMaintenance('add');

	$BotBanishSettings['BOTBANISH_CONFIGURATION'] = 0;  // Turn off install configuration indicator

	ksort($BotBanishSettings);
	BotBanishSettingsTablePutValues($BotBanishSettings);
	BotBanishHTACCESSImport();

	// Update HTML files if needed

	$records['updated'] = 0;
	$records['filecount'] = 0;

	if ($BotBanishSettings['BOTBANISH_UPDATE_HTML']) {
		
		$records_html = BotBanishModifyHTMLFiles();
		$records = array_merge($records, $records_html);
	}

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_AddCountry.php';

	$type = BOTBANISH_SERVER == 1 ? 'BotBanishServer_InstallComplete' : 'BotBanishClient_InstallComplete';
	$complete = '<br>' . BOTBANISH_USER_HOST . ':  ' . sprintf($GLOBALS['BotBanishText'][$type], $records['ip'], $records['bot'], $records['filecount'], $records['updated']);

	if ($records['filecount'] > 0)
		$complete .= '<br><br>' . BotBanishGrabArray($records['files']) . '<br><br>';

	$install['Complete'] = str_replace(array("\n", "\r"), array('<br>', ''), $complete);
	
	if (defined('SMF')) {

		// Insure that we haven't somehow messed up Pretty Urls by adding BotBanish to it.
		// Run PrettyUrls maintenance task to correct!

		BotBanishUpdatePrettyURLs();
	
		$str = str_replace(array("\n", "\r"), array('<br>', ''), $complete);
		$txt['package_installed_done'] = stripslashes(BOTBANISH_HEADER_HTML) . $txt['package_installed_done'] . '<br>' . $str;
		$txt['package_installed_done'] .= file_get_contents(BOTBANISH_DOCS_DIR . 'BotBanish_Readme_' . BOTBANISH_SYSTEM . ' - ' . ucwords(BOTBANISH_LANGUAGE_SELECT) . '.html');
		$txt['extracting'] = 'BotBanish';
	}
}

 function BotBanishSetSettingsInstall($source, $dest, $settings, $installType) {

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Settings_Update_Website.php';

	if (defined('ABSPATH')) {

		global $wpdb;
				
		$serverInfo = array(
			'BOTBANISH_DB_PORT' => '',		
			'BOTBANISH_TIMEZONE' => isset($_SERVER['TZ']) ? $_SERVER['TZ'] : date_default_timezone_get(),
			'BOTBANISH_DB_PREFIX' => 'bbc_',
			'BOTBANISH_DB_NAME' => $wpdb->dbname,
			'BOTBANISH_DB_USERNAME' => $wpdb->dbuser,
			'BOTBANISH_DB_PASSWORD' => $wpdb->dbpassword,
			'BOTBANISH_DB_SERVERNAME' => $wpdb->dbhost,
			'BOTBANISH_HOST' => $wpdb->dbhost,
			'BOTBANISH' => 1,
			'BOTBANISH_SERVER' => 0,
			'BOTBANISH_CLIENT' => 1,
			'BOTBANISH_SYSTEM' => 'WORDPRESS',
		);

	} else {

		// Default to the client

		$serverInfo = array(
			'BOTBANISH' => 1,
			'BOTBANISH_SERVER' => 0,
			'BOTBANISH_CLIENT' => 1,
			'BOTBANISH_TIMEZONE' => BOTBANISH_TIMEZONE,
			'BOTBANISH_SYSTEM' => BOTBANISH_SYSTEM,
			'BOTBANISH_USER_HOST' => BOTBANISH_USER_HOST,
		);

		if ($installType == 'Server') {

			$serverInfo = array_merge($serverInfo, array(
				'BOTBANISH_DB_SERVERNAME_PRIMARY' => BOTBANISH_DB_SERVERNAME,
				'BOTBANISH_DB_PORT_PRIMARY' => BOTBANISH_DB_PORT,
				'BOTBANISH_DB_USERNAME_PRIMARY' => BOTBANISH_DB_USERNAME,
				'BOTBANISH_DB_PASSWORD_PRIMARY' => BOTBANISH_DB_PASSWORD,
				'BOTBANISH_DB_NAME_PRIMARY' => BOTBANISH_DB_NAME,
				'BOTBANISH_HOST_PRIMARY' => BOTBANISH_DB_SERVERNAME,
				'BOTBANISH_DB_PREFIX_PRIMARY' => BOTBANISH_DB_PREFIX,
				'BOTBANISH_SERVER' => 1,
				'BOTBANISH_CLIENT' => 0,
			));
			
		}

		$serverInfo = array_merge($serverInfo, array(
			'BOTBANISH_DB_SERVERNAME' => BOTBANISH_DB_SERVERNAME,
			'BOTBANISH_DB_PORT' => BOTBANISH_DB_PORT,
			'BOTBANISH_DB_USERNAME' => BOTBANISH_DB_USERNAME,
			'BOTBANISH_DB_PASSWORD' => BOTBANISH_DB_PASSWORD,
			'BOTBANISH_DB_NAME' => BOTBANISH_DB_NAME,
			'BOTBANISH_HOST' => BOTBANISH_DB_SERVERNAME,
			'BOTBANISH_DB_PREFIX' => '',
//			'BOTBANISH_DB_PREFIX' => BOTBANISH_DB_PREFIX,
		));
		
		foreach ($serverInfo as $key => $value)
			$settings[$key] = $value;
	}

	$str = PHP_EOL;

	$str_require = PHP_EOL .
		"\tBotBanishDefineGlobals('BOTBANISH_DIR', str_replace('bot_v" . BOTBANISH_VERSION_CLIENT . "', BOTBANISH_CURRENT_FOLDER, '" . BOTBANISH_DIR . "'));" . PHP_EOL .
		"\tBotBanishDefineGlobals('BOTBANISH_LOGS_DIR', BOTBANISH_DIR . '" . $installType . "/BotBanish_Logs/');" . PHP_EOL;

	$str_include = PHP_EOL . "\trequire_once 'Include/BotBanish_PreIncludes.php';";

	$str_require .= PHP_EOL . "\trequire_once 'BotBanish_Settings.php';" . PHP_EOL . PHP_EOL;

	BotBanishSettingsCreate($settings);

	foreach ($serverInfo as $key => $value) {

		$value = is_numeric($value) ? $value : "'" . addslashes($value) . "'";
		$str .= "\t" . "BotBanishDefineGlobals('" . $key . "'," . $value . ");" . PHP_EOL;
	}

	$str =
		"\t" . $str_include . PHP_EOL . PHP_EOL .
		$str .
		"\t" . $str_require . PHP_EOL . PHP_EOL;

	// Let's update the database setting in the passed settings file

	$data = file_get_contents($source);
	$data = str_ireplace('//***BOTBANISH SETTINGS***', $str, $data);
	BotBanishWriteFile($dest, $data);
}

 function BotBanishIndexUpdate($source, $dest) {

	// Let's update the database setting in the passed settings file

	$data = file_get_contents($source);
	$str = "	require_once '" . BOTBANISH_DIR . "Settings_Client.php';";
	$data = str_ireplace(BOTBANISH_REPLACE, $str, $data);
	BotBanishWriteFile($dest, $data);
}

function BotBanish_PreInstall_Test() {

	$action = isset($_GET['install_action']) ? $_GET['install_action'] : '';

	if (!isset($_POST['BOTBANISH_POST_CODE'])) {

		switch ($action) {

			case 'quit':
				exit;
				break;

			case 'continue':
				break;

			case '':

				$filename = realpath('../../Subs/BotBanish_PreInstall.php');
				include $filename;

				$scripturl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . str_ireplace($_SERVER['DOCUMENT_ROOT'], '' , $_SERVER['SCRIPT_FILENAME']);

				echo '
				<button onclick="window.location.href=\'' . $scripturl . '?install_action=continue\'' . '">Continue</button>
				<button onclick="window.location.href=\'' . $scripturl . '?install_action=quit\'' . '">Quit</button>';
				exit;
				break;
				
			default:
				break;
		}
	}
}
?>