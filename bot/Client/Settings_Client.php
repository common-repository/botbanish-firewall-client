<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Client Settings
// Date: 05/28/2024
// Usage: require_once('Settings_Client.php');
//
// Function: Settings
///////////////////////////////////////////////////////////////////////////////////////////////////////

//================================================================================
// For Client Debugging
//================================================================================
//	define('BOTBANISH_DEBUG_SQL', 1);
//	define('BOTBANISH_DEBUG_LOG_PROGRESS', 1);
//	define('BOTBANISH_DEBUG_LOG_INFO', 1);
//	define('BOTBANISH_DEBUG_TRACE_ALL', 1);
//	define('BOTBANISH_USE_TEST_SERVER', 1);
//	define('BOTBANISH_TEST', 1);

//================================================================================

	// See if a session is active, if not then start one
	
	if (!defined('SMF')) {
		
		if (session_status() !== PHP_SESSION_ACTIVE)  {
			
			if (!headers_sent()) {
				
				session_start();
				$_SESSION['BotBanish']['Active'] = true;
			}
		}
	}
	
	// If Sucurri Website Security is active we need to force the real IP

	if (isset($_SERVER['HTTP_X_SUCURI_CLIENTIP']))
		$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_SUCURI_CLIENTIP'];

	$request_types = array('GET', 'POST');

	$diag = 0;
	$ip_hit_count = 0;
	$ip_first_hit = 0;
	$client_info = array();
	$user_id = '';
	$BotBanishText = array();
	$BotBanishSettings = array();
	$debugtracerow = array();
	$country_info = array();
	
	// If country_info was not set correctly due to error, reset it!!
	
	if (isset($_SESSION['BotBanish']['geo_data']))
		unset($_SESSION['BotBanish']['geo_data']);

	if (!defined('BOTBANISH_VERSION_CLIENT'))
		require_once __DIR__ . '/Include/BotBanishVersion.php';
	
	// The below section is auto generated at install

	//***BOTBANISH SETTINGS***

	// The above section is auto generated at install
	
	
	// Once we find the active BotBanish server the first time we keep it throught the current session
	// Otherwise we may overload the server with calls to the BotBanish server via repetative cron calls
	
	if (!isset($_SESSION['BotBanish']['BOTBANISH_WEBSERVER'])) {

		if (defined('BOTBANISH_CONFIGURATION') && (BOTBANISH_CONFIGURATION == false))
			set_error_handler('BotBanishErrorHandler');

		date_default_timezone_set(BOTBANISH_TIMEZONE);
		error_reporting(E_ALL);

		if ((!defined('BOTBANISH_CONFIGURATION') || (!BOTBANISH_CONFIGURATION))
				|| (!defined('BOTBANISH_WEBSERVER')))
			BotBanishFindServer();

		if (defined('BOTBANISH_CONFIGURATION') && (BOTBANISH_CONFIGURATION == false))
			restore_error_handler();
		
	} else {
		
		BotBanishDefineGlobals('BOTBANISH_WEBSERVER', $_SESSION['BotBanish']['BOTBANISH_WEBSERVER']);
		BotBanishDefineGlobals('BOTBANISH_SERVER_URL', $_SESSION['BotBanish']['BOTBANISH_SERVER_URL']);
	}
		
	if (!defined('BOTBANISH_ACTIVE'))
		BotBanishDefineGlobals('BOTBANISH_ACTIVE', 1);

function BotBanishFindServer() {

	if (!defined('BOTBANISH_TEST') || (!BOTBANISH_TEST)) {

		$servers = array();
		$test_servers = array();
		$local_servers = array();
		$hosted_servers = array();

		// Available BotBanish Servers. Check servers to find first available one
		// HTTP clients can only communicate with HTTP Servers
		// HTTPS clients can communicate with both

		if (defined('BOTBANISH_USE_TEST_SERVER') && (BOTBANISH_USE_TEST_SERVER)) {

			$test_servers = array(
				'https://botbanish-test.com',
				);

		} else {

			$debug_server = array('::1', '127.0.0.1');

			if (isset($_SERVER['SERVER_ADDR']) && (in_array($_SERVER['SERVER_ADDR'], $debug_server)))
				$local_servers = array('http://localhost');

			$hosted_servers = array(
					'https://botbanishserver.com',
					'https://botbanishserver.org',
					'https://botbanishserver.us',
					);
		}

		$servers = array_merge($test_servers, $local_servers, $hosted_servers);

		foreach ($servers as $server) {

			$url = $server . '/BotBanish/bot_v' . BOTBANISH_VERSION_SERVER  . '/Server/BotBanishServer.php';

			$response = BotBanishSendPostData($url, '',false, false, false);

			if (stripos($response, 'BotBanish Server Running') !== false) {

				BotBanishDefineGlobals('BOTBANISH_WEBSERVER', $server);
				break;
			}
		}
	}

	$active = !defined('BOTBANISH_WEBSERVER') ? false : true;

	if ($active) {
		
		BotBanishDefineGlobals('BOTBANISH_SERVER_URL', $url);
		$_SESSION['BotBanish']['BOTBANISH_WEBSERVER'] = BOTBANISH_WEBSERVER;
		$_SESSION['BotBanish']['BOTBANISH_SERVER_URL'] = $url;
		
	} else {
		
		unset($_SESSION['BotBanish']);
	}
	
	// If we cant find a server then turn BotBanish checking off!!!

	BotBanishDefineGlobals('BOTBANISH_ACTIVE', $active);
}
?>