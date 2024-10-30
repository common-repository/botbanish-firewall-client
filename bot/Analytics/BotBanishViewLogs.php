<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish View Logs
// Date: 05/27/2024
//
// On Server (Not currently used)
// Usage: https://botbanish.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishAnalytics.php?user_id=xxxxx
//
// Local
// Usage: https://YOURDOMAIN/BotBanish/bot/Analytics/BotBanishAnalytics.php
//
// Function: View website and file download analytics
// NOTE: The user_id parameter is only used for analytics stored on the server.
/////////////////////////////////////////////////////////////////////////////////////////////////////////

//	define('BOTBANISH_DEBUG_SQL',1);
//	define('BOTBANISH_DEBUG_LOG_INFO',1);

	if (!defined('BA_REFRESH')) define('BA_REFRESH', 10*60);		// Minutes

	if (defined('ABSPATH')) {

		$settings = BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';

		if (isset($_GET['file']))
			$filename = $_GET['file'];

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$settings = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
	}

	require_once $settings;

	$botbanish_system = BotBanishCheckSystem();

	if (!defined('BOTBANISH_ANALYTICS')) define('BOTBANISH_ANALYTICS', true);

	@ini_set('display_errors', E_ALL);

	if (!defined('BA_USER_ID')) define('BA_USER_ID', !empty($_GET['user_id']) ? intval($_GET['user_id']) : 0);

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

			require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';
			break;
	}

	// Make sure user is allowed to access system

	require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';
	BotBanishClient();

	if (!defined('BOTBANISH_ORGANIZATION')) define('BOTBANISH_ORGANIZATION', strtoupper($_SERVER['SERVER_NAME']));
	$GLOBALS['this_date'] = date('Y-m-d', strtotime(BotBanishGetCorrectTime(BOTBANISH_TIMEZONE)));
	$GLOBALS['current_time'] = BotBanishGetCorrectTime(BOTBANISH_TIMEZONE);

	// url to script

	if (defined('ABSPATH')) {

		define('PHPURL', get_site_url() . '/wp-admin/admin.php?page=botbanishfirewall-analytics');
		define('GIFURL', get_site_url() . '/wp-content/themes/BotBanish/status/stat/gif');
		define('CSSURL', BOTBANISH_INSTALL_URL . '/bot/Analytics/css/');

	} else {
	
		define('PHPURL', BOTBANISH_INSTALL_URL . 'Analytics/BotBanishAnalytics.php');
		define('GIFURL', BOTBANISH_INSTALL_URL . 'Analytics/status/stat/gif');
		define('CSSURL', BOTBANISH_INSTALL_URL . 'Analytics/css/');
	}

	$files_AccessActivity = glob(BOTBANISH_LOGS_DIR . '*_AccessActivity_*.log');
	$files_Error = glob(BOTBANISH_LOGS_DIR . '*_Activity_*.log');
	$files_Info = glob(BOTBANISH_LOGS_DIR . '*_Info_*.log');
	$files_SQL = glob(BOTBANISH_LOGS_DIR . '*_SQLStatements_*.log');


	if (isset($filename)) {

		Show_Log($filename);

	} else {

		HTML_Logs_Header();
		HTML_Logs($files_AccessActivity, 'AccessActivity');
		HTML_Logs($files_Error, 'Activity');
		HTML_Logs($files_Info, 'Info');
		HTML_Logs($files_SQL, 'SQLStatements');
		HTML_Logs_Footer();
	}

function Show_Log($filename) {

	$filedata = file_get_contents(BOTBANISH_PLUGIN_DIR . 'bot/Client/BotBanish_Logs/' . $filename);
	$filedata = str_replace(array(PHP_EOL, "\n", "\r"), '<br>', $filedata);
	echo $filedata;
}

function HTML_Logs_Header() {

	global $BotBanishText;

	$time_in_seconds = BA_REFRESH*60;
	$scripturl = isset($_SESSION['BotBanish']['scripturl']) ? $_SESSION['BotBanish']['scripturl'] : BOTBANISH_INSTALL_URL;

	echo '
	<!DOCTYPE html>
	<html>
	<head>
	<title>' . BOTBANISH_ORGANIZATION . ' - '.$BotBanishText['BotBanishWebsiteAnalytics'].'</title>
	<META http-equiv="refresh" Content="'.$time_in_seconds.'">
    <META http-equiv="Cache-Control : max-age=86400">
	<link rel="stylesheet" href="'.CSSURL.'BotBanishAnalytics.css">
	</head>

	<body>
	<center>
	<table id="heading">
		<tr>
			<td><tt>' . BOTBANISH_ORGANIZATION . '</tt><td>';

	if (!defined('ABSPATH')) {

		if ($scripturl === BOTBANISH_INSTALL_URL)
			echo '<td><tt><button type="button" onclick="parent.open(\'' . $scripturl . 'Analytics/BotBanishAnalytics.php\')">' . $BotBanishText['View Visits'] . '</button></tt></td>';
	}

	echo '</tr>
		<tr>
			<td><tt>' . $BotBanishText['BotBanishWebsite'] . ' ' . $BotBanishText['View Logs'] . '</tt></td>
		</tr>
		<tr>
			<td><tt>' . BOTBANISH_VERSION . '</tt></td>';

	echo '
		</tr>
			<table>
			   <td width="30%" align="right"><b>'.$BotBanishText['Time'] . ': ' . $GLOBALS['current_time'] . '</b></td>
			</table>
		<tr>
	</table>';
}

function HTML_Logs($rows, $type) {
//*************************************************************
//	Display Logs Names
//*************************************************************

	CollapseLogsStart($type, count($rows));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="62%">&nbsp;</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		$rows = array_reverse($rows);

		foreach ($rows as $row) {

			$file = str_replace(BOTBANISH_LOGS_DIR, '', $row);
			
			if (defined('ABSPATH')) {

				$link = PHPURL . '&file=' . $file;
				$target = '';

			} else {

//				$link = PHPURL . 'Templates/BotBanishLogs.template.php?file=' . $row ;
				$link = BOTBANISH_INSTALL_URL . 'Templates/BotBanishLogs.template.php?file=' . $row ;
				$target = ' " target="_blank"';
			}

			echo '    <tr>';
			echo '      <td width=62%><a href="' . $link . $target . '">' . $file . '</a></td>';
			echo '    </tr>';
		}
	}

	echo '  </table>  <hr width="100%" size=1>  <br>';

	CollapseLogsEnd();
}

function HTML_Logs_Footer() {
//*************************************************************
//	Page Footer
//*************************************************************

	echo '
		<script>
			var coll = document.getElementsByClassName("collapsible");
			var i;

			for (i = 0; i < coll.length; i++) {
			  coll[i].addEventListener("click", function() {
				this.classList.toggle("active");
				var content = this.nextElementSibling;
				if (content.style.display === "block") {
				  content.style.display = "none";
				} else {
				  content.style.display = "block";
				}
			  });
			}
		</script>';

     echo '</div></center></body></html>';
}

function CollapseLogsStart($text, $count = 0) {

	$str = ($count > 0) ? '(' . $count . ')' : '';

	echo '
		<div class="collapsible">' . $text . $str . '</div>
		<div class="content" id="'.$text.'">';
}

function CollapseLogsEnd() {

	echo ' </div>';
}
?>