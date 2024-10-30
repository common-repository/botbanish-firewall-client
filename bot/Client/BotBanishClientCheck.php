<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 4.1.06
// Randem Systems: https://randemsystems.com/support/
// BotBanish Return Client Information to the server.
// Date: 02/08/2024
// Usage: http://botbanish.com/scripts/BotBanishDownload.php?filename=YOURFILENAME
//
// Function: To return selected client side information to the server for debugging purposes.
///////////////////////////////////////////////////////////////////////////////////////////////////////

//	include_once '../Settings_Client.php';
	$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
	$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
	require_once $filename;

	$botbanish_system = BotBanishCheckSystem();

	// Turn off magic quotes runtime and enable error reporting.

//	@set_magic_quotes_runtime(0);
	error_reporting(E_ALL);

	switch ($botbanish_system['system']) {

		case 'WORDPRESS':

			// Load WordPress core functions. This will also load our routines
			// WordPress will handle errors

			$dir = BotBanishGetConfigLocation('wp-load.php');
			require_once($dir . 'wp-load.php');
			define('BOTBANISH_TIMEZONE', BotBanish_getWpTimezone());
			break;

		default:

			require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');
			break;
	}

	// Remove xdebug limitations
	ini_set("xdebug.var_display_max_children", -1);
	ini_set("xdebug.var_display_max_data", -1);
	ini_set("xdebug.var_display_max_depth", -1);

	ini_set('memory_limit','256M');

	if (ini_get('session.save_handler') == 'user')
		ini_set('session.save_handler', 'files');

	$row = BotBanishGetPassedData();

	// If we don't have the information we need then exit!

	if (!isset($row['domain']) && !isset($row['APIKEY']))
		return;

	$url = BOTBANISH_WEBSERVER . '/BotBanish/' . BOTBANISH_CURRENT_LOCATION . '/Server/BotBanishServer_LogSettings.php';

	$domain_name = $row['domain'];
	$APIKEY = $row['APIKEY'];

	// Get all available information for debugging

	$botbanish_debug = array(
		'Settings' => $botbanish_settings = get_defined_vars(),
		'Constants' => $botbanish_constants = get_defined_constants(),
		'Functions' => $botbanish_functions = get_defined_functions()
	);

	$header = urlencode(BOTBANISH_VERSION . '_' . $domain_name);
	$count = 1;

	foreach($botbanish_debug as $key => $value){

		$row = array();
		$row['header'] = $header;
		$row['position'] = $count;
		$row[$key] =  BotBanishGrabDump($value);

		$value = null;
		$data = BotBanishURLFormatData($row);
		$BotBanishURL = $data;
		BotBanishSendPostData($url, $data);
		$count++;
		$data = null;
	}

	// Get .htaccess from the root

	$htaccess_data = trim(file_get_contents(BOTBANISH_HTACCESS_NAME));
	BotBanishPrepareData($htaccess_data, $count);

	// Get all error logs from the logs folder
	// Only send the latest log

	$files = glob(BOTBANISH_APPPATH_DIR . 'logs/*.log');

	if (is_array($files) && count($files) > 0){

		sort($files);
		$logfile = $files[count($files) - 1];

		$info = trim(file_get_contents($logfile));
		BotBanishPrepareData($info, $count);
	}

	// Reset the diagnostic code on the server

	$row = array();
	$row['header'] = $header;
	$row['position'] = $count;
	$row['APIKEY'] = $APIKEY;

	$data = BotBanishURLFormatData($row);
	$BotBanishURL = $data;

	BotBanishSendPostData($url, $data);

function BotBanishPrepareData($data, &$count) {

	$lines = explode(PHP_EOL, $data);
	$max_len = 2048;
	$buf_len = 0;
	$buffer = '';
	$sent = false;

	foreach ($lines as $line) {

		$buffer .= $line;
		$buf_len = $buf_len + strlen($line);
		$sent = false;

		if ($buf_len > $max_len) {

			BotBanishSendData($buffer, $count);
			$buffer = null;
			$buf_len = 0;
			$sent = true;
			$count++;
		}
	}

	if (!$sent) {

		BotBanishSendData($buffer, $count);
		$buffer = null;
		$sent = false;
		$count++;
	}
}

function BotBanishSendData ($buffer, $count) {

	$row = array();
	$row['header'] = $header;
	$row['position'] = $count;
	$row['htaccess'] =  BotBanishGrabDump($buffer);

	$data = BotBanishURLFormatData($row);
	$BotBanishURL = $data;

	BotBanishSendPostData($url, $data);
	$data = null;
	$row = null;
}
?>