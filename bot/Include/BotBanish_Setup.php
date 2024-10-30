<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Setup
// Date: 04/30/2024
// Usage:
//
// Function:
//		Setup globals and create needed folders
///////////////////////////////////////////////////////////////////////////////////////////////////////

	$path =  str_ireplace('\\', '/', $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME']);
	$dirs = explode('/', $_SERVER['SCRIPT_NAME']);
	$dirs[count($dirs) - 1] = '';
	$curdir = str_ireplace('\\', '/', $_SERVER['DOCUMENT_ROOT'] . implode('/', $dirs));
	$app_location = '';

	if (defined('ABSPATH') || defined('SMF') || defined('DIR_APPLICATION')) {

		if (defined('ABSPATH')) {

			global $wpdb;
			BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WORDPRESS');
			$str = str_ireplace('\\', '/', realpath(BOTBANISH_PLUGIN_DIR));
			$botbanish_location =  '/bot/';
			$app_location = ABSPATH;

		} else {

			if (defined('SMF')) {

				BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'SMF');
				$str = str_ireplace('\\', '/', realpath($boarddir));
				$app_location = rtrim(str_ireplace('//', '/', trim(implode('/', $dirs))), '/');
				$botbanish_location = '/BotBanish/bot/';
				
			} else {

				BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'OPENCART');
				$str = str_ireplace('\\', '/', realpath(DIR_APPLICATION . '../'));
				$botbanish_location =  '/BotBanish/bot/';
				$app_location = str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $str);
			}
		}
		
		BotBanishDefineGlobals('BOTBANISH_INSTALL_TYPE', 'Client');
		BotBanishDefineGlobals('BOTBANISH_INSTALL_FOLDER', 'bot');

	} else {

		// Website install

		// Website installed locations on the server can be in different folders, so we must find which folder it is installed in.
		// On the client is is always the same

		BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WEBSITE');
		BotBanishDefineGlobals('BOTBANISH_INSTALL_TYPE', $dirs[count($dirs) - 3]);
		BotBanishDefineGlobals('BOTBANISH_INSTALL_FOLDER', $dirs[count($dirs) - 4]);
		
		$str = str_ireplace('\\', '/', dirname(getcwd(), 4) . '/');
		$botbanish_location = '/' . $dirs[count($dirs) - 5] . '/' . $dirs[count($dirs) - 4] . '/';
		$app_location = '/' . $dirs[count($dirs) - 6];
	}
		
	switch (BOTBANISH_INSTALL_TYPE) {

		case 'Server':

			BotBanishDefineGlobals('BOTBANISH_SERVER', 1);
			BotBanishDefineGlobals('BOTBANISH_CLIENT', 0);
			BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'SERVER');
			BotBanishDefineGlobals('BOTBANISH_SERVER_CLEANUP', date("Y-m-d H:i:s"));
			BotBanishDefineGlobals('BOTBANISH_SERVER_CLEANUP_RUN', 1);
			BotBanishDefineGlobals('BOTBANISH_SERVER_CLEANUP_DAYS', 30);
			break;

		case 'Client':

			BotBanishDefineGlobals('BOTBANISH_SERVER', 0);
			BotBanishDefineGlobals('BOTBANISH_CLIENT', 1);
			break;

		default:
			exit('Not in correct folder to run BotBanish install');
			break;
	}
	
	BotBanishDefineGlobals('BOTBANISH_CLIENT_CLEANUP', date("Y-m-d H:i:s"));
	BotBanishDefineGlobals('BOTBANISH_CLIENT_CLEANUP_RUN', 1);
	BotBanishDefineGlobals('BOTBANISH_CLIENT_CLEANUP_DAYS', 30);

	BotBanishDefineGlobals('BOTBANISH', 1);
	BotBanishDefineGlobals('BOTBANISH_CONFIGURATION', 1);
	
	$server = BotBanish_GetServerType();

	BotBanishDefineGlobals('BOTBANISH_SERVER_TYPE', $server);
	
	// End Server Type Check
	
	date_default_timezone_set('Pacific/Honolulu');

	if (!isset($botbanish_folder)) {
		
		$botbanish_location = str_ireplace('\\', '/', $botbanish_location);
		$botbanish_folder = rtrim($str . $botbanish_location, '/') . '/';
	}

	$image_folder = realpath($botbanish_folder . '../') . '/';
	$image_folder = str_ireplace('\\', '/', $image_folder);
	
	$protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
	$protocol = (isset($_SERVER['HTTP_X_FORWARDED_TYPE']) && ($_SERVER['HTTP_X_FORWARDED_TYPE'] == 'ssl')) ? 'https' : $protocol;
	$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : $protocol;

	BotBanishDefineGlobals('BOTBANISH_PROTOCOL', $protocol);

	$user_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	$user_host = (empty($user_host) && isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : $user_host;
	$user_host = ltrim(str_ireplace('\\', '/', $user_host), '/');

	BotBanishDefineGlobals('BOTBANISH_USER_HOST', $user_host);

//	BotBanishDefineGlobals('BOTBANISH_USE_TEST_SERVER', $user_host == 'botbanish-test.com' ? 1 : 0);
	BotBanishDefineGlobals('BOTBANISH_ON_SERVER', (($user_host == 'botbanish.com') || ($user_host == 'botbanish-test.com')) ? 1 : 0);
	BotBanishDefineGlobals('BOTBANISH_DEBUG_LOG_PROGRESS', 0);

	BotBanishDefineGlobals('BOTBANISH_SUBS_DIR', $botbanish_folder . 'Subs/');
	BotBanishDefineGlobals('BOTBANISH_BACKUPS_DIR', $botbanish_folder . 'Backups/');
	BotBanishDefineGlobals('BOTBANISH_INCLUDE_DIR', $botbanish_folder . 'Include/');
	BotBanishDefineGlobals('BOTBANISH_DATA_DIR', $botbanish_folder . '/Data/');
	BotBanishDefineGlobals('BOTBANISH_IMAGES_DIR', $botbanish_folder . 'Images/' . BOTBANISH_INSTALL_TYPE . '/');
	BotBanishDefineGlobals('BOTBANISH_SERVER_DIR', $botbanish_folder . 'Server/');
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_DIR', $botbanish_folder . 'Language/');
	BotBanishDefineGlobals('BOTBANISH_DOCS_DIR', $botbanish_folder . 'Docs/');
	BotBanishDefineGlobals('BOTBANISH_CLASS_DIR', $botbanish_folder . 'Class/');
	BotBanishDefineGlobals('BOTBANISH_TEMPLATES_DIR', $botbanish_folder . 'Templates/');
	BotBanishDefineGlobals('BOTBANISH_ANALYTICS_DIR', $botbanish_folder . 'Analytics/');
	BotBanishDefineGlobals('BOTBANISH_CSS_DIR', $botbanish_folder . 'css/');
	BotBanishDefineGlobals('BOTBANISH_CLIENT_DIR', $botbanish_folder . 'Client/');
	BotBanishDefineGlobals('BOTBANISH_AUTOLOAD_DIR', $botbanish_folder . 'vendor/');
	BotBanishDefineGlobals('BOTBANISH_DIR', $botbanish_folder);
	
	require_once BOTBANISH_INCLUDE_DIR . 'BotBanishVersion.php';
	$botbanish_version = 'BotBanish ' . BOTBANISH_INSTALL_TYPE . ' ' . BOTBANISH_VERSION_CLIENT;

	BotBanishDefineGlobals('BOTBANISH_WEB_SERVER', 'BotBanishServer.php');
	BotBanishDefineGlobals('BOTBANISH_LOCATION', $botbanish_location);
	BotBanishDefineGlobals('BOTBANISH_WEBROOTPATH_DIR', $_SERVER['DOCUMENT_ROOT']);
	BotBanishDefineGlobals('BOTBANISH_APPPATH_DIR', $botbanish_folder);

	if (defined('ABSPATH')) {

		BotBanishDefineGlobals('BOTBANISH_INSTALL_URL', BOTBANISH_PLUGIN_URL);
		BotBanishDefineGlobals('BOTBANISH_WEBROOT_URL', BOTBANISH_INSTALL_URL);

	} else {

		BotBanishDefineGlobals('BOTBANISH_INSTALL_URL', BOTBANISH_PROTOCOL . '://' . BOTBANISH_USER_HOST . $app_location . BOTBANISH_LOCATION);
		BotBanishDefineGlobals('BOTBANISH_WEBROOT_URL', BOTBANISH_INSTALL_URL);
	}

	BotBanishDefineGlobals('BOTBANISH_IMAGES_URL', BOTBANISH_PROTOCOL . '://' . BOTBANISH_USER_HOST . $app_location . $botbanish_location . 'Images/' . BOTBANISH_INSTALL_TYPE . '/');
	BotBanishDefineGlobals('BOTBANISH_INSTALL_DIR', $botbanish_folder);
	BotBanishDefineGlobals('BOTBANISH_LOCATION_ROOT', $app_location . BOTBANISH_LOCATION);
	BotBanishDefineGlobals('BOTBANISH_DELETE_DATA', 0);

//================================================================
// Make all folders that we will need
//================================================================

	switch(BOTBANISH_INSTALL_TYPE) {

		case 'Server':

			BotBanishDefineGlobals('BOTBANISH_LOGS_DIR', BOTBANISH_SERVER_DIR . 'BotBanish_Logs/');
			BotBanishDefineGlobals('BOTBANISH_INSTALL_DIR', BOTBANISH_SERVER_DIR);
			break;

		case 'Client':

			BotBanishDefineGlobals('BOTBANISH_LOGS_DIR', BOTBANISH_CLIENT_DIR . 'BotBanish_Logs/');
			BotBanishDefineGlobals('BOTBANISH_INSTALL_DIR', BOTBANISH_CLIENT_DIR);
			break;
	}

	if (!is_dir(BOTBANISH_LOGS_DIR))
		@mkdir(BOTBANISH_LOGS_DIR, 0755, true);

	if (!is_dir(BOTBANISH_LOGS_DIR . 'html'))
		@mkdir(BOTBANISH_LOGS_DIR . 'html', 0755, true);

	// Files that BotBanish will log when downloaded; should be placed in this folder

	$download_data = BOTBANISH_APPPATH_DIR . 'data/' . str_ireplace('www.', '', BOTBANISH_USER_HOST);

	if (!is_dir($download_data))
		@mkdir($download_data, 0755, true);
	
function BotBanish_GetServerType() {

	$url = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['SERVER_NAME'];

	// Let see what kind of server we are running on

	$headers = BotBanishGetHeaders($url);
	if ($headers === false)
		return 'Unknown';
	
	// Search for a known server type

	$servers = array('Apache', 'NGINX', 'Lighttpd', 'LiteSpeed');
		
	$found = false;
	$servertype = 'Unknown';
	
	foreach ($servers as $server) {
		
		foreach ($headers as $header) {
			
			if (stripos($header, $server) !== false) {
				$servertype = $server;
				$found = true;
				break;
			}
		}
		
		if ($found)
			break;
	}
	
	return $servertype;
}
?>