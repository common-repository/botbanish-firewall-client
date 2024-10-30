<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Website / WordPress / OpenCart Installation Configuration
// Date: 06/01/2024
// Usage: http://userdomain.com/BotBanish/bot_v4.x.xx/Server/Install/install.php
// Usage: http://userdomain.com/BotBanish/bot/Client/Install/install.php
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

// For WordPress Error display

/*
	define('BOTBANISH_DEBUG_TRACE_ALL',1);
	define('BOTBANISH_DEBUG_SQL', 1);
	define('BOTBANISH_DEBUG_LOG_PROGRESS', 1);
	define('BOTBANISH_DEBUG_LOG_INFO', 1);
*/

// For PHP Error display

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

xdebug_break();
*/

//================================================================================

	$BotBanishSettings = array();
	$BotBanishText = array();
	$screenData = array();
	$install = array();
	$BotBanishSQL = '';
	$BotBanishURL = '';
	
	define('BOTBANISH', 1);
	define('BOTBANISH_INSTALL', 1);

	$dirs = explode('/', $_SERVER['SCRIPT_NAME']);
	$str = str_ireplace('\\', '/', dirname(getcwd(), 4) . '/');
	unset($dirs[count($dirs) - 1]);
	unset ($dirs[count($dirs) - 1]);
	unset ($dirs[count($dirs) - 1]);
	$botbanish_dir = $_SERVER['DOCUMENT_ROOT'] . implode('/', $dirs) . '/';

	$config_dir = BotBanishGetConfigLocationA('config.php');

	if (!empty($config_dir)) {

		// Must be OpenCart
		require_once $config_dir . '/config.php';
		$botbanish_dir = rtrim(realpath(DIR_APPLICATION . '../BotBanish/bot/'), '/') . '/';

	} else {

		// Must be WordPress or Website
		$botbanish_dir = (defined('ABSPATH')) ? BOTBANISH_PLUGIN_DIR . 'bot/' : $botbanish_dir;
	}

	$botbanish_dir = str_replace('\\', '/', $botbanish_dir);

	require_once $botbanish_dir . 'Include/BotBanishVersion.php';
	require_once $botbanish_dir . 'Include/BotBanish_Setup.php';
	require_once $botbanish_dir . 'Include/BotBanish_Defines.php';
	require_once $botbanish_dir . 'Subs/BotBanish_Subs.php';

	$title = BOTBANISH_SERVER_TYPE . ' ';
	BotBanishDefineGlobals('BOTBANISH_INCLUDE_DIR', $botbanish_dir . 'Include/');

	if (!empty($config_dir)) {

		// Must be OpenCart
		$title .= $BotBanishText['OpenCart_Install'];

	} else {

		// Must be WordPress or Website
		$title .= (defined('ABSPATH')) ? $BotBanishText['WordPress_Install'] : $BotBanishText['Website_Install'];
	}

	BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WEBSITE');

	BotBanish_ProcessInstall($install, $config_dir);

	$dir = getcwd();
	chdir($dir);

	$screenData = BotBanish_SetupScreens();
	BotBanish_CreateSettings();

	BotBanishDefineGlobals('BOTBANISH_VERSION', 'BotBanish Client ' . BOTBANISH_VERSION_CLIENT . ' (Website)');

	if (BOTBANISH_INSTALL_TYPE == 'Server')

		require_once BOTBANISH_SERVER_DIR . 'Settings_Server.php';
	else
		require_once BOTBANISH_CLIENT_DIR . 'Settings_Client.php';

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_ScreenSubs.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_FormInput.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';

	BotBanishSetLanguage($screenData['BOTBANISH_LANGUAGE_SELECT']);

	if (isset($_POST))
		$screenData = getScreenData($screenData);
	else
		if (isset($install))
			$screenData = array_merge($screenData, $install);

	$BotBanishText['ENABLED'] = 'disabled';

	BotBanish_DoDBTest();

	if (BOTBANISH_INSTALL_TYPE == 'Server')

		DisplayServerScreen();
	else
		DisplayClientScreen($title);

function BotBanish_CreateSettings() {

//============================================================================================
// Create BotBanish_Settings file with new information
//============================================================================================

	if (($_SERVER['REQUEST_METHOD'] !== 'POST') && (!isset($_POST['BOTBANISH_POST_CODE']))) {

		// First creation of BotBanish_Settings.php
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Settings_Update_Website.php';
		BotBanishSettingsCreate($BotBanishSettings);
	}
}

function BotBanish_DoDBTest() {
	global $BotBanishText;

//============================================================================================
// Section is executed when "Test DB Conn" is selected to test the database connection.
//============================================================================================

	if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['BOTBANISH_POST_CODE'] === '3')) {

		$Conn = BotBanishDatabaseOpen($_POST['BOTBANISH_DB_SERVERNAME'], $_POST['BOTBANISH_DB_USERNAME'], $_POST['BOTBANISH_DB_PASSWORD'], $_POST['BOTBANISH_DB_NAME'], true);

		if (!is_object($Conn)) {

			$BotBanishText['DB_TEST'] = $BotBanishText['Fail'];
			$BotBanishText['COLOR'] = '#f44336';

		} else {

			$BotBanishText['DB_TEST'] = $BotBanishText['Success'];
			$BotBanishText['COLOR'] = '#4CAF50';
			$BotBanishText['ENABLED'] = ' ';
		}
	}
}

function BotBanish_SetupScreens() {

//============================================================================================
// Check to see if "Language" has been changed.
//============================================================================================

	if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['BOTBANISH_POST_CODE'] === '1')) {

		$screenData = getScreenData($screenData);

	} else {

		if (isset($GLOBALS['sourcedir'])) {

			// SMF
			// Try to match the language that SMF is currently in to see if we support it

			global $language, $db_server, $db_name, $db_port, $db_user, $db_passwd;
			$lang = isset($GLOBALS[$language]) && array_search(BOTBANISH_LANGUAGES, $language) !== false ? strtolower($language) : 'english';

			$screenData = array(
				'BOTBANISH_LANGUAGE_SELECT' => isset($_POST['BOTBANISH_LANGUAGE_SELECT']) ? $_POST['BOTBANISH_LANGUAGE_SELECT'] : $lang,
				'BOTBANISH_DB_SERVERNAME' => $db_server,
				'BOTBANISH_DB_NAME' => $db_name,
				'BOTBANISH_DB_PORT' => $db_port,
				'BOTBANISH_DB_USERNAME' => $db_user,
				'BOTBANISH_DB_PASSWORD' => $db_passwd,
				'BOTBANISH_DB_PREFIX' => 'bbc_',
				'BOTBANISH_SMTP_SERVER' => $modSettings['smtp_host'],
				'BOTBANISH_SMTP_PORT' => $modSettings['smtp_port'],
				'BOTBANISH_SMTP_USERNAME' => $modSettings['smtp_username'],
				'BOTBANISH_SMTP_PASSWORD' => $modSettings['smtp_password'],
				'BOTBANISH_WEBMASTER_EMAIL' => $webmaster_email,
				'BOTBANISH_TIMEZONE' => $modSettings['default_timezone'],
				'BOTBANISH_POST_CODE' => 0,
				'BOTBANISH_SEND_EMAIL_ALERTS' => 0,
				'BOTBANISH_UPDATE_HTML' => 0,
				'BOTBANISH_UPDATE_HTML_FOLDER' => BOTBANISH_ROOT_FOLDER,
				'BOTBANISH_UPDATE_HTACCESS' => 1,
			);

		} else {

			// OpenCart

			if (defined('DIR_APPLICATION')) {

				BotBanishCheckIfInstalled(realpath(DIR_APPLICATION . '../index.php'));

				$screenData = array(
					'BOTBANISH_LANGUAGE_SELECT' => isset($_POST['BOTBANISH_LANGUAGE_SELECT']) ? $_POST['BOTBANISH_LANGUAGE_SELECT'] : 'english',
					'BOTBANISH_DB_SERVERNAME' => DB_HOSTNAME,
					'BOTBANISH_DB_NAME' => DB_DATABASE,
					'BOTBANISH_DB_PORT' => DB_PORT,
					'BOTBANISH_DB_USERNAME' => DB_USERNAME,
					'BOTBANISH_DB_PASSWORD' => DB_PASSWORD,
					'BOTBANISH_DB_PREFIX' => 'bbc_',
					'BOTBANISH_SMTP_SERVER' => '',
					'BOTBANISH_SMTP_PORT' => 25,
					'BOTBANISH_SMTP_USERNAME' => '',
					'BOTBANISH_SMTP_PASSWORD' => '',
					'BOTBANISH_WEBMASTER_EMAIL' => 'support@' . $_SERVER['SERVER_NAME'],
					'BOTBANISH_TIMEZONE' => isset($_SERVER['TZ']) ? $_SERVER['TZ'] : date_default_timezone_get(),
					'BOTBANISH_POST_CODE' => 0,
					'BOTBANISH_SEND_EMAIL_ALERTS' => 1,
					'BOTBANISH_UPDATE_HTML' => 0,
					'BOTBANISH_UPDATE_HTML_FOLDER' => BOTBANISH_ROOT_FOLDER,
					'BOTBANISH_UPDATE_HTACCESS' => 1,
				);

			} else {

				// Website

				$screenData = array(
					'BOTBANISH_LANGUAGE_SELECT' => isset($_POST['BOTBANISH_LANGUAGE_SELECT']) ? $_POST['BOTBANISH_LANGUAGE_SELECT'] : 'english',
					'BOTBANISH_DB_SERVERNAME' => 'localhost',
					'BOTBANISH_DB_NAME' => 'botbanish' . strtolower(BOTBANISH_INSTALL_TYPE) . BOTBANISH_VERSION_DATABASE,
					'BOTBANISH_DB_PORT' => '',
					'BOTBANISH_DB_USERNAME' => '',
					'BOTBANISH_DB_PASSWORD' => '',
					'BOTBANISH_DB_PREFIX' => (BOTBANISH_INSTALL_TYPE == 'Server') ? 'bbs_' : 'bbc_',
					'BOTBANISH_SMTP_SERVER' => '',
					'BOTBANISH_SMTP_PORT' => 25,
					'BOTBANISH_SMTP_USERNAME' => '',
					'BOTBANISH_SMTP_PASSWORD' => '',
					'BOTBANISH_WEBMASTER_EMAIL' => 'support@' . $_SERVER['SERVER_NAME'],
					'BOTBANISH_TIMEZONE' => isset($_SERVER['TZ']) ? $_SERVER['TZ'] : date_default_timezone_get(),
					'BOTBANISH_POST_CODE' => 0,
					'BOTBANISH_SEND_EMAIL_ALERTS' => 1,
					'BOTBANISH_UPDATE_HTML' => 0,
					'BOTBANISH_UPDATE_HTML_FOLDER' => BOTBANISH_ROOT_FOLDER,
					'BOTBANISH_UPDATE_HTACCESS' => 1,
				);
			}
		}
	}

	foreach ($screenData as $key => $value) {

		$value = !empty($value) ? trim($value) : '';

		if (!defined($key))
			BotBanishDefineGlobals($key, $value);
	}

	return $screenData;
}

function BotBanish_ProcessInstall(&$install, $path) {
	global $BotBanishText;

//============================================================================================
// Section is executed when "Install" is selected. Will not return!
//============================================================================================

	if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['BOTBANISH_POST_CODE'] === '2')) {

		foreach ($_POST as $key => $value) {

			if (($key == 'BOTBANISH_DB_PREFIX') && !empty($value)) {

				if (substr($value, strlen($value) - 1) !== '_') {

					$value .= '_';
					$_POST[$key] = $value;
				}
			}
		}

		require_once BOTBANISH_SUBS_DIR . 'BotBanishInstall.php';

		if (!empty($path))
			BotBanish_OpenCartInstall($path, BOTBANISH_DIR);

		include_once BOTBANISH_INCLUDE_DIR . 'BotBanish_ScreenComplete.php';
		CompleteScreen($install['Complete'], $BotBanishText[BOTBANISH_INSTALL_TYPE . '_Install']);

		if (BOTBANISH_INSTALL_TYPE == 'Client') {

			// Write the code to place into HTML files to track user visits

			$str = '// ' . $BotBanishText['BOTBANISH_WEBSITE_CODE'] . "\n\n" . stripslashes(BOTBANISH_ANALYTICS_HTML) . "\n\n";

			BotBanishWriteFile(BOTBANISH_DOCS_DIR . 'BotBanish_Website_Code.txt', $str);
		}

		exit();
	}
}

function DisplayServerScreen() {

//============================================================================
// Show the server side screen for install data collection
//============================================================================

	global $BotBanishText, $screenData, $BotBanishSettings;

echo '
<!DOCTYPE html>
<html>
<head>
<title>' . $BotBanishText["BotBanish_Configuration"] . '</title>' .
BOTBANISH_PAGE_HEADER .
'</head>';

	$heading = BOTBANISH_SERVER_TYPE . ' ' . $BotBanishText['Configuration'];
	$title = $BotBanishText[BOTBANISH_INSTALL_TYPE . '_Install'];
	include BOTBANISH_TEMPLATES_DIR . 'BotBanish_Banner.php';

	echo '
	<div>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr width="100%">
				<td width="100%" class="face_padding_cell">
					<form name="theForm" id="theForm" action="" method="post" enctype="multipart/form-data">
					<input type="hidden" id="BOTBANISH" name="BOTBANISH" value="' . BOTBANISH . '">
					<input type="hidden" id="BOTBANISH_CONFIGURATION" name="BOTBANISH_CONFIGURATION" value="' . BOTBANISH_CONFIGURATION . '">
					<input type="hidden" id="BOTBANISH_POST_CODE" name="BOTBANISH_POST_CODE" value="' . $screenData['BOTBANISH_POST_CODE'] . '">

						<table width="40%" align="center" style="background-color: #0099cc;" border="10" style="border-collapse:collapse">
							<tr>
								<td align="right" width="20%">' . $BotBanishText['BOTBANISH_LANGUAGE_SELECT'] . ': </td><td align "left">' . language_select_droplist('BOTBANISH_LANGUAGE_SELECT', 'onSubmit1(theForm, BOTBANISH_POST_CODE)', $screenData['BOTBANISH_LANGUAGE_SELECT'], 30) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Webmaster_Email'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_WEBMASTER_EMAIL', 50, '', $screenData['BOTBANISH_WEBMASTER_EMAIL']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Database'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_NAME', 50, '', $screenData['BOTBANISH_DB_NAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Table_Prefix'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_PREFIX', 50, '', $screenData['BOTBANISH_DB_PREFIX']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Server'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_SERVERNAME', 50, '', $screenData['BOTBANISH_DB_SERVERNAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Port'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_PORT', 6, '', $screenData['BOTBANISH_DB_PORT']) . ' </td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Username'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_USERNAME', 50, '', $screenData['BOTBANISH_DB_USERNAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Password'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_DB_PASSWORD', 30, '', $screenData['BOTBANISH_DB_PASSWORD'], false, true) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Timezone'] . ': </td><td align "left">' . timezone_select_droplist('BOTBANISH_TIMEZONE', '', $screenData['BOTBANISH_TIMEZONE'], 50) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Server'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_SMTP_SERVER', 50, '', $screenData['BOTBANISH_SMTP_SERVER']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Username'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_SMTP_USERNAME', 50, '', $screenData['BOTBANISH_SMTP_USERNAME']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Password'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_SMTP_PASSWORD', 30, '', $screenData['BOTBANISH_SMTP_PASSWORD'], false, true) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Port'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_SMTP_PORT', 6, '', $screenData['BOTBANISH_SMTP_PORT']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Email_Alerts'] . ': </td><td align "left">' . yes_no_select_droplist('BOTBANISH_SEND_EMAIL_ALERTS', '', $screenData['BOTBANISH_SEND_EMAIL_ALERTS'] == false ? 'false' : 'true') . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_Htaccess'] . ': </td><td align "left">' . yes_no_select_droplist('BOTBANISH_UPDATE_HTACCESS', '', $screenData['BOTBANISH_UPDATE_HTACCESS'] == false ? 'false' : 'true') . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_HTML'] . ': </td><td align "left">' . yes_no_select_droplist('BOTBANISH_UPDATE_HTML', '', $screenData['BOTBANISH_UPDATE_HTML']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_Folder'] . ': </td><td align "left">' . DisplayTextInputField('BOTBANISH_UPDATE_HTML_FOLDER', 50, '', $screenData['BOTBANISH_UPDATE_HTML_FOLDER']) . '</td>
							</tr>
						</table>
					</form>
				</td>
			</tr>
		</table>';

	$url = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['SERVER_NAME'] . BOTBANISH_LOCATION . 'Docs/BotBanish_Readme_' . strtoupper(BOTBANISH_INSTALL_TYPE) . ' - ' . ucwords($screenData['BOTBANISH_LANGUAGE_SELECT']) . '.html';
	$url_check = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['SERVER_NAME'] . '/' . BOTBANISH_LOCATION . 'Subs/BotBanish_PreInstall.php';

	echo '
	</div>
	<div width="40%" align="center">
		<table width="30%">
			<tr>
				<td class=botbanish_button align="right">
					 <button type="button" id="button" ' . $BotBanishText['ENABLED'] . ' onclick="return validateForm_BotBanishInstall(theForm, BOTBANISH_POST_CODE)">' . $BotBanishText['Install'] . '</button>
				</td>';

				if ($screenData['BOTBANISH_POST_CODE'] == 3) {

					echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" style="background-color: ' . $BotBanishText['COLOR'] . '">Database Test: ' . $BotBanishText['DB_TEST'] . '</button>
				</td>';

				} else {

					echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return windowpopup(&quot;' . $url . '&quot;, 545, 433)">' . $BotBanishText['Documentation'] . '</button>
				</td>';

				}

				echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return validate_Connection(theForm, BOTBANISH_POST_CODE)">' . $BotBanishText['Test_Database_Connection'] . '</button>
				</td>';

				echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return windowpopup(&quot;' . $url_check . '&quot;, 545, 433)">' . $BotBanishText['PreInstall'] . '</button>
				</td>
			</tr>
		</table>
	</div>';

	if (isset($screenData['Complete']) && !empty($screenData['Complete'])) {

		echo '
			<script>
				alert("'.$screenData['Complete'].'");
				logout();
			</script>';
	}

	require BOTBANISH_INCLUDE_DIR . 'BotBanish.js';

echo '

	</body>
</html>';
}

function DisplayClientScreen($title) {

//============================================================================
// Show the client side screen for install data collection
//============================================================================

	global $BotBanishSettings, $BotBanishText, $screenData;

echo '
<!DOCTYPE html>
<html>
<head>
<title>' . $BotBanishText["BotBanish_Configuration"] . '</title>' .
BOTBANISH_PAGE_HEADER .
'</head>';

	$heading = $BotBanishText['Configuration'];
	include BOTBANISH_TEMPLATES_DIR . 'BotBanish_Banner.php';

	echo '
	<div>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr width="100%">
				<td width="100%" class="face_padding_cell">
					<form name="theForm" id="theForm" action="" method="post" enctype="multipart/form-data">
					<input type="hidden" id="BOTBANISH_CONFIGURATION" name="BOTBANISH_CONFIGURATION" value="' . BOTBANISH_CONFIGURATION .'">
	  				<input type="hidden" id="BOTBANISH_DB_PREFIX" name="BOTBANISH_DB_PREFIX" value="' . $screenData['BOTBANISH_DB_PREFIX'] . '">
					<input type="hidden" id="BOTBANISH_POST_CODE" name="BOTBANISH_POST_CODE" value="' . $screenData['BOTBANISH_POST_CODE'] . '">
					<input type="hidden" id="BOTBANISH" name="BOTBANISH" value="' . BOTBANISH . '">
					<input type="hidden" id="BOTBANISH_SYSTEM" name="BOTBANISH_SYSTEM" value="' . BOTBANISH_SYSTEM . '">
					<input type="hidden" id="BOTBANISH_CLIENT" name="BOTBANISH_CLIENT" value="' . BOTBANISH_CLIENT . '">
					<input type="hidden" id="BOTBANISH_SERVER" name="BOTBANISH_SERVER" value="' . BOTBANISH_SERVER . '">
					<input type="hidden" id="BOTBANISH_SUBS_DIR" name="BOTBANISH_SUBS_DIR" value="' . BOTBANISH_SUBS_DIR . '">
					<input type="hidden" id="BOTBANISH_INCLUDE_DIR" name="BOTBANISH_INCLUDE_DIR" value="' . BOTBANISH_INCLUDE_DIR . '">
					<input type="hidden" id="BOTBANISH_DATA_DIR" name="BOTBANISH_DATA_DIR" value="' . BOTBANISH_DATA_DIR . '">
					<input type="hidden" id="BOTBANISH_IMAGES_DIR" name="BOTBANISH_IMAGES_DIR" value="' . BOTBANISH_IMAGES_DIR . '">
					<input type="hidden" id="BOTBANISH_SERVER_DIR" name="BOTBANISH_SERVER_DIR" value="' . BOTBANISH_SERVER_DIR . '">
					<input type="hidden" id="BOTBANISH_DOCS_DIR" name="BOTBANISH_DOCS_DIR" value="' . BOTBANISH_DOCS_DIR . '">
					<input type="hidden" id="BOTBANISH_CLASS_DIR" name="BOTBANISH_CLASS_DIR" value="' . BOTBANISH_CLASS_DIR . '">
					<input type="hidden" id="BOTBANISH_LOGS_DIR" name="BOTBANISH_LOGS_DIR" value="' . BOTBANISH_LOGS_DIR . '">
					<input type="hidden" id="BOTBANISH_LANGUAGE_DIR" name="BOTBANISH_LANGUAGE_DIR" value="' . BOTBANISH_LANGUAGE_DIR . '">
					<input type="hidden" id="BOTBANISH_TEMPLATES_DIR" name="BOTBANISH_TEMPLATES_DIR" value="' . BOTBANISH_TEMPLATES_DIR . '">
					<input type="hidden" id="BOTBANISH_ANALYTICS_DIR" name="BOTBANISH_ANALYTICS_DIR" value="' . BOTBANISH_ANALYTICS_DIR . '">
					<input type="hidden" id="BOTBANISH_CSS_DIR" name="BOTBANISH_CSS_DIR" value="' . BOTBANISH_CSS_DIR . '">
					<input type="hidden" id="BOTBANISH_CLIENT_DIR" name="BOTBANISH_CLIENT_DIR" value="' . BOTBANISH_CLIENT_DIR . '">
					<input type="hidden" id="BOTBANISH_DIR" name="BOTBANISH_DIR" value="' . BOTBANISH_DIR . '">
					<input type="hidden" id="BOTBANISH_APPPATH_DIR" name="BOTBANISH_APPPATH_DIR" value="' . BOTBANISH_APPPATH_DIR . '">
					<input type="hidden" id="BOTBANISH_VERSION" name="BOTBANISH_VERSION" value="' . BOTBANISH_VERSION . '">
					<input type="hidden" id="BOTBANISH_VERSION_NO" name="BOTBANISH_VERSION_NO" value="' . BOTBANISH_VERSION_NO . '">
					<input type="hidden" id="BOTBANISH_WEB_SERVER" name="BOTBANISH_WEB_SERVER" value="' . BOTBANISH_WEB_SERVER . '">
					<input type="hidden" id="BOTBANISH_LOCATION" name="BOTBANISH_LOCATION" value="' . BOTBANISH_LOCATION . '">
					<input type="hidden" id="BOTBANISH_WEBROOTPATH_DIR" name="BOTBANISH_WEBROOTPATH_DIR" value="' . BOTBANISH_WEBROOTPATH_DIR . '">
					<input type="hidden" id="BOTBANISH_PROTOCOL" name="BOTBANISH_PROTOCOL" value="' . BOTBANISH_PROTOCOL . '">
					<input type="hidden" id="BOTBANISH_INSTALL_URL" name="BOTBANISH_INSTALL_URL" value="' . BOTBANISH_INSTALL_URL . '">
					<input type="hidden" id="BOTBANISH_INSTALL_DIR" name="BOTBANISH_INSTALL_DIR" value="' . BOTBANISH_INSTALL_DIR . '">
					<input type="hidden" id="BOTBANISH_INSTALL_TYPE" name="BOTBANISH_INSTALL_TYPE" value="' . BOTBANISH_INSTALL_TYPE . '">
					<input type="hidden" id="BOTBANISH_DB_PORT" name="BOTBANISH_DB_PORT" value="' . BOTBANISH_DB_PORT . '">

						<table align="center" width="40%" style="background-color: #0099cc;" border="10" style="border-collapse:collapse">
							<tr>
								<td align="right" width="20%">' . $BotBanishText['BOTBANISH_LANGUAGE_SELECT'] . ': </td><td>' . language_select_droplist('BOTBANISH_LANGUAGE_SELECT', 'onSubmit1(theForm, BOTBANISH_POST_CODE)', $screenData['BOTBANISH_LANGUAGE_SELECT'], 30) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Webmaster_Email'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_WEBMASTER_EMAIL', 50, '', $screenData['BOTBANISH_WEBMASTER_EMAIL']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Database'] . ' </td><td>' . DisplayTextInputField('BOTBANISH_DB_NAME', 50, '', $screenData['BOTBANISH_DB_NAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Table_Prefix'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_DB_PREFIX', 50, '', $screenData['BOTBANISH_DB_PREFIX']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Server'] . ' </td><td>' . DisplayTextInputField('BOTBANISH_DB_SERVERNAME', 50, '', $screenData['BOTBANISH_DB_SERVERNAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Port'] . ' </td><td>' . DisplayTextInputField('BOTBANISH_DB_PORT', 6, '', $screenData['BOTBANISH_DB_PORT']) . ' </td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Username'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_DB_USERNAME', 50, '', $screenData['BOTBANISH_DB_USERNAME']) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Password'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_DB_PASSWORD', 30, '', $screenData['BOTBANISH_DB_PASSWORD'], false, true) . ' *</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Timezone'] . ': </td><td>' . timezone_select_droplist('BOTBANISH_TIMEZONE', '', $screenData['BOTBANISH_TIMEZONE'], 50) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Server'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_SMTP_SERVER', 50, '', $screenData['BOTBANISH_SMTP_SERVER']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Username'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_SMTP_USERNAME', 50, '', $screenData['BOTBANISH_SMTP_USERNAME']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Password'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_SMTP_PASSWORD', 30, '', $screenData['BOTBANISH_SMTP_PASSWORD'], false, true) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['SMTP_Port'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_SMTP_PORT', 6, '', $screenData['BOTBANISH_SMTP_PORT']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Email_Alerts'] . ': </td><td>' . yes_no_select_droplist('BOTBANISH_SEND_EMAIL_ALERTS', '', $screenData['BOTBANISH_SEND_EMAIL_ALERTS']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_Htaccess'] . ': </td><td>' . yes_no_select_droplist('BOTBANISH_UPDATE_HTACCESS', '', $screenData['BOTBANISH_UPDATE_HTACCESS']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_HTML'] . ': </td><td>' . yes_no_select_droplist('BOTBANISH_UPDATE_HTML', '', $screenData['BOTBANISH_UPDATE_HTML']) . '</td>
							</tr>
							<tr>
								<td align="right">' . $BotBanishText['Update_Folder'] . ': </td><td>' . DisplayTextInputField('BOTBANISH_UPDATE_HTML_FOLDER', 50, '', $screenData['BOTBANISH_UPDATE_HTML_FOLDER']) . '</td>
							</tr>
						</table>
					</form>
				</td>
			</tr>
		</table>';

	$url = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['SERVER_NAME'] . BOTBANISH_LOCATION . 'Docs/BotBanish_Readme_' . strtoupper(BOTBANISH_INSTALL_TYPE) . ' - ' . ucwords($screenData['BOTBANISH_LANGUAGE_SELECT']) . '.html';
	$url_check = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['SERVER_NAME'] . '/' . BOTBANISH_LOCATION . 'Subs/BotBanish_PreInstall.php';

	echo '
	</div>
	<div width="40%" align="center">
		<table width="30%">
			<tr>
				<td class=botbanish_button align="right">
					 <button type="button" id="button" ' . $BotBanishText['ENABLED'] . ' onclick="return validateForm_BotBanishInstall(theForm, BOTBANISH_POST_CODE)">' . $BotBanishText['Install'] . '</button>
				</td>';

				if ($screenData['BOTBANISH_POST_CODE'] == 3) {

					echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" style="background-color: ' . $BotBanishText['COLOR'] . '">Database Test: ' . $BotBanishText['DB_TEST'] . '</button>
				</td>';

				} else {

					echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return windowpopup(&quot;' . $url . '&quot;, 545, 433)">' . $BotBanishText['Documentation'] . '</button>
				</td>';

				}

				echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return validate_Connection(theForm, BOTBANISH_POST_CODE)">' . $BotBanishText['Test_Database_Connection'] . '</button>
				</td>';

				echo '
				<td class=botbanish_button align="right">
					 <button type="button" id="button" onclick="return windowpopup(&quot;' . $url_check . '&quot;, 545, 433)">' . $BotBanishText['PreInstall'] . '</button>
				</td>';
		echo '
			</tr>
		</table>
	</div>';

	if (isset($screenData['Complete']) && !empty($screenData['Complete'])) {

		echo '
			<script>
				alert("'.$screenData['Complete'].'");
				logout();
			</script>';
	}

	require BOTBANISH_INCLUDE_DIR . 'BotBanish.js';

echo '

	</body>
</html>';
}

function getScreenData($screenData) {

	foreach ($_POST as $key => $value)
		$screenData[$key] = $value;

	return $screenData;
}

Function BotBanish_OpenCartInstall($config_dir, $botbanish_dir) {

	// Insert code into index.php files (vqmod will not make these changes)
	$version = BotBanishFindOpenCartVersion();

	switch ($version){

		case '3.x':

			BotBanishProcess3x($botbanish_dir);
			break;

		case '2.x':

			BotBanishProcess2x($botbanish_dir);
			break;

		default:

			// We cannot process any other versions
			die('We can only support OpenCart version 2x and 3x');
			break;
	}

	// Start VQMOD replacements
	// If the folder does not exist this may be a OCMOD installation. Can only have one BotBanish MOD type installed

	if (is_dir($config_dir . '/vqmod/xml/'))
		copy($config_dir. '/BotBanish/vqmod/xml/BotBanish Firewall Client.xml', $config_dir . '/vqmod/xml/BotBanish Firewall Client.xml');
}

function BotBanishFindOpenCartVersion() {

	$filename = DIR_SYSTEM . '/config/catalog.php';
	$version = '2.x';

	if (file_exists($filename)){

		$data = file_get_contents($filename);

		if (stripos($data, 'template_engine') !== false)
			$version = '3.x';
	}

	return $version;
}

function BotBanishProcess2x($botbanish_dir) {

	// Process the root folder

	$items = array();

	$items[] = array(
		'finddata' => "require_once('config.php');",
		'moddata' => PHP_EOL . "	require_once('" . $botbanish_dir . "Settings_Client.php');" . PHP_EOL,
		'location' => 'after'
	);

	$rep_text = PHP_EOL . "// Only check for registration abuse if a guest so we don't lock valid users out" . PHP_EOL . PHP_EOL .
	"if (!\$customer->isLogged()) {" . PHP_EOL . PHP_EOL .
			"	require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');" . PHP_EOL .
			"	BotBanishClient();" . PHP_EOL .
	"}" . PHP_EOL . PHP_EOL;

	$items[] = array(
		'finddata' => "\$controller->dispatch(\$action, new Action('error/not_found'));",
		'moddata' => $rep_text,
		'location' => 'before'
	);

	$items[] = array(
		'finddata' => "echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';",
		'moddata' => 'continue;',
		'location' => 'replace'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_display')) {",
		'moddata' => "if (1 == 2) {
			",
		'location' => 'before'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_log')) {",
		'moddata' => "}
		",
		'location' => 'before'
	);

	BotBanishModifyFile(DIR_APPLICATION . '../' . 'index.php', $items);

	// Now the Admin folder. There is a slight change in the code to add.

	$items = array();

	$items[] = array(
		'finddata' => "require_once('config.php');",
		'moddata' => PHP_EOL . "	require_once('" . $botbanish_dir . "Settings_Client.php');" . PHP_EOL,
		'location' => 'after'
	);

	$rep_text = PHP_EOL . "// Only check for registration abuse if a guest so we don't lock valid users out" . PHP_EOL . PHP_EOL .
			"	require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');" . PHP_EOL .
			"	BotBanishClient();" . PHP_EOL;

	$items[] = array(
		'finddata' => "\$controller->dispatch(\$action, new Action('error/not_found'));",
		'moddata' => $rep_text,
		'location' => 'before'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_display')) {",
		'moddata' => "if (1 == 2) {
			",
		'location' => 'before'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_log')) {",
		'moddata' => "}
		",
		'location' => 'before'
	);

	BotBanishModifyFile(realpath(DIR_APPLICATION . '../' . 'admin/index.php'), $items);
}

function BotBanishProcess3x($botbanish_dir) {

	// Modify index.php files (root & admin folders)

	$items = array();

	$items[] = array(
		'finddata' => "require_once('config.php');",
		'moddata' => PHP_EOL . "	require_once('" . $botbanish_dir . "Settings_Client.php');" . PHP_EOL,
		'location' => 'after'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_display')) {",
		'moddata' => "if (1 == 2) {
			",
		'location' => 'before'
	);

	$items[] = array(
		'finddata' => "if (\$config->get('config_error_log')) {",
		'moddata' => "}
		",
		'location' => 'before'
	);

	BotBanishModifyFile(realpath(DIR_APPLICATION . '../' . 'index.php'), $items);
	BotBanishModifyFile(realpath(DIR_APPLICATION . '../' . 'admin/index.php'), $items);

	// Modify framework.php

	$rep_text = PHP_EOL . "// Only check for registration abuse if a guest so we don't lock valid users out" . PHP_EOL . PHP_EOL .
		"	require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');" . PHP_EOL .
		"	BotBanishClient();" . PHP_EOL . PHP_EOL;

	$items = array();

	$items[] = array(
		'finddata' => "\$route->dispatch(new Action(\$config->get('action_router')), new Action(\$config->get('action_error')));",
		'moddata' => $rep_text,
		'location' => 'before'
	);

	BotBanishModifyFile(DIR_SYSTEM . 'framework.php', $items);
}

function BotBanishCheckIfInstalled($filename) {

	global $BotBanishText;

	$data = BotBanishReadFile($filename);

	if (stripos($data, "Settings_Client.php") !== false)
		die ($BotBanishText['BotBanishClient_Installed']);
}

function BotBanishGetConfigLocationA($config = 'config.php') {

	$dir = rtrim(realpath(getcwd()), '/') . '/';
	$dir = str_replace('\\', '/', $dir);
	$found = false;

	$folders = explode('/', $dir);
	$x = count($folders);

	for ($i = $x - 1; $i > -1; $i--) {

		$folder = implode('/', $folders);

		if (file_exists($folder . '/' . $config)) {
			$found = true;
			break;
		}

		unset($folders[count($folders) - 1]);
	}

	if (!$found)
		$folder = '';

	return $folder;
}
?>