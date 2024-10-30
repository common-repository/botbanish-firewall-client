<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish File Download Protection Script
// Date: 05/12/2024
//
// Server Usage: https://botbanish.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishDownload.php?filename=YOURFILENAME&user_id=xxxxx
//
// Local Usage: http://YOURDOMAIN.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishDownload.php?filename=YOURFILENAME
//
// Function: To detect bad players and deny them from downloading your files
// NOTE: The user_id parameter is only used for analytics stored on the server.
///////////////////////////////////////////////////////////////////////////////////////////////////////

	if (isset($GLOBALS['wpdb'])) {
		
		$dirs = explode('/', $_SERVER['REQUEST_URI']);
		unset ($dirs[count($dirs) - 1]);
		unset ($dirs[count($dirs) - 1]);
		$path = implode('/', $dirs);
		$filename = getcwd() . '/wp-content/plugins/' . $path . '/Settings_Client.php';
		require_once $filename;
		
	} else {
	
		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
		require_once $filename;
	}
	
	$botbanish_system = BotBanishCheckSystem();		// Will not return if system not found
	
	switch ($botbanish_system['system']) {
		
		case 'WORDPRESS':
		
			// Load WordPress core functions. This will also load our routines
			// WordPress will handle errors

			$dir = BotBanishGetConfigLocation('wp-load.php');
			
			if (!empty($dir)) {
				
				require_once($dir . 'wp-load.php');
				if (!defined('BOTBANISH_TIMEZONE')) define('BOTBANISH_TIMEZONE', BotBanish_getWpTimezone());
			}
			break;
		
		default:
		
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';
			break;
	}

	
	//===================================================
	// All download files should reside in this location
	// Example /data/yourdomain.com
	//===================================================

	$path = '/data/' . BOTBANISH_USER_HOST . '/';
//	$file_location = $_SERVER['DOCUMENT_ROOT'] . $path;
//	if (!file_exists($file_location)) @mkdir ($file_location, 0755, true);
	
	$file_name = isset($_GET['filename']) ? trim($_GET['filename']) : '';
	$userid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
	$system = isset($_GET['system']) ? $_GET['system'] : '';

	// if a system is present, download from their system
	
	$path = !empty($system) ? '/data/' . $system . '/' : $path;
	$file_location = $_SERVER['DOCUMENT_ROOT'] . $path;
	if (!file_exists($file_location)) @mkdir ($file_location, 0755, true);
	
	// Can't download a file with no name...

	if (empty($file_name))
		return;

	// Don't log thumbnails
	
	if (stripos($file_name, '_thumb') !== false)
		exit;
	
	BotBanishCheckForHacker($file_name);
	
	// Will not return if a spider/bot. Spiders and bots don't need downloads anyway...
	BotBanishCheckForSpiders();

	// Check for the existance of the file asked to download
	
	if (!file_exists($file_location . $file_name))
		BotBanishRedirect();

	// Ok, they passed all the test; Allow the download

	BotBanishLogDownload($file_name, $system);

	clearstatcache();	// Needs this to get proper filesize after a recent write operation
	$size = filesize($file_location . $file_name);
	$filename = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $path . $file_name;

	BotBanishCleanOutput();
	ob_start();
	
	header('Pragma: ');
	header('Content-Encoding: none');//	header('Content-type: text/html; charset=ISO-8859-1');
	header('Content-type: application/octet-stream; charset=ISO-8859-1');
	header("Content-Transfer-Encoding: Binary");
	header('Content-Length: '. $size);
	header('Cache-Control: no-cache');
	header("Location: " . $filename);

function BotBanishRedirect() {

	header('Location: https://google.com');
	exit;
}

function BotBanishCheckForHacker($file_name) {

	//==================================================================
	// Change this line to control which downloads extensions will work
	//==================================================================

	$download_types = array(
		'.c', '.css', '.doc', '.docx', '.gz', '.htm', '.html', '.java',	'.js', '.log', '.pdf', '.php',
		'.rar', '.rtf', '.sql', '.tar', '.txt', '.xml', '.zip', '.bk', '.exe');
		
	$lockout = false;
	$len = strlen($file_name);

	// Check this download for extraneous information added to the download file name
	// If there is data after the file name; it is an attempt to hack us!!!

	// Obvious hacker if these things are contained after or in the filename

	$str = str_ireplace(array('?', '/', '!', '@', '#', '%', '^', '&', '*', ',', '"', '\'', '\\', '|') , '', $file_name);

	if ($str !== $file_name)
		$lockout = true;

	if (!$lockout) {

		$found = false;

		foreach ($download_types as $type) {

			// If file type is not at the end of the file name (or there is no file type), it could be hacking

			if (substr($file_name, $len - strlen($type), strlen($type)) === $type) {
				
				$found = true;
				break;
			}
		}
		
		if (!$found)
			BotBanishRedirect();
	}
}
?>