<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Website Page Logging Script
// Date: 03/23/2024
//
// Server Usage: https://botbanish.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishAnalyticsLog.php?pagename=YOURPAGENAME&user_id=xxxxx
//
// Local Usage: http://YOURDOMAIN.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishAnalyticsLog.php?pagename=YOURPAGENAME
//
// Function: Log website visitors
// NOTE: The user_id parameter is only used for analytics stored on the server.
///////////////////////////////////////////////////////////////////////////////////////////////////////

	if (isset($_GET['pagename'])) {

		$pagename = isset($_GET['pagename']) ? $_GET['pagename'] : '';
		$userid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

	} else {

		$pagename = isset($_POST['pagename']) ? $_POST['pagename'] : '';
		$userid = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	}

	// If there is no page name, we can do nothing here!
	
	if (empty($pagename))
		return;
	
	if (defined('BOTBANISH_SERVERSIDE_ANALYTICS') && BOTBANISH_SERVERSIDE_ANALYTICS)
		header('location: ' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . '/BotBanish/bot/Analytics/BotBanishAnalytics.php?pagename=' . $pagename);
	
	if (defined('ABSPATH')) {

		$filename = BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
	}

	require_once $filename;

	$botbanish_system = BotBanishCheckSystem();
	$system = $botbanish_system['system'];;
	
	define('BOTBANISH_ANALYTICS', 1);
	$url = '';

	switch ($system) {

		case 'WORDPRESS':

			// Load WordPress core functions. This will also load our routines
			// WordPress will handle errors

			$dir = BotBanishGetConfigLocation('wp-load.php');

			if (!empty($dir)) {

				require_once($dir . 'wp-load.php');
				if (!defined('BOTBANISH_TIMEZONE')) define('BOTBANISH_TIMEZONE', BotBanish_getWpTimezone());
			}
			$url = $_SERVER['REQUEST_URI'];
			break;

		case 'OPENCART':
		case 'SMF':

			$url = $_SERVER['REQUEST_URI'];
			break;

		case 'WEBSITE':
		default:

			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';
			require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');
			break;
	}

	if (!defined('BOTBANISH_ANALYTICS_WEBSITE') || BOTBANISH_ANALYTICS_WEBSITE == false)
		return;

	// Make sure user is allowed to access system
	// only do this if the page name is not a contact page otherwise record the access
	// We do this for if BotBanish is blocking a user, the user can contact the webmaster
	
	if (stripos($pagename, 'contact') === false) {
		
		require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';
		BotBanishClient();
	}

	// If OpenCart or SMF there will be no html page name, only urls. Best if used with PrettyUrls MOD

	if (empty($url)) {

		$pos = stripos($pagename, '?');

		if ($pos !== false)
			$pagename = trim(substr($pagename, 0, $pos));
		
		$pagename = trim(str_ireplace(BOTBANISH_WEBROOT_URL, '', $pagename));

		if (empty($pagename))
			$pagename = BOTBANISH_WEBROOT_URL;

		// If this is a bad page name, don't log it
//		if (BotBanishCheckForBadPagename($pagename))
//			return;
	}

	// Log the page the user is viewing
	BotBanishLogWebsitePage($pagename, $userid, $system);

function BotBanishCheckForBadPagename($pagename) {

	$found = false;
	$len = strlen($pagename);
	$pos = strrpos($pagename, '.');

	// If the dot is 3 or 4 characters from the end, ignore the file name

	if ($pos !== false) {

		if ($pos >= ($len - 4))
			$found = true;
	}

	return $found;
}
?>