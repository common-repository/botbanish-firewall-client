<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish define updates for Websites
// Date: 03/18/2024
// Usage:
//
// Function: Create the BotBanish_Settings.php file that will be used upon loading with the
// 				Settings_Client.php or the Settings_Server.php files.
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishSettingsCreate(&$BotBanishSettings) {

	global $BotBanishText;

	require_once BOTBANISH_INCLUDE_DIR . 'BotBanish_PreIncludes.php';
	 
	if (defined('ABSPATH')) {
		
		require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_Settings_Update_WordPress.php';
		BotBanishSettingsCreateWordPress($BotBanishSettings);
		$str = BotBanishSetupWordPress();
		
	} else {
		
		if (defined('SMF')) {
			
			global $boarddir;
			require_once $boarddir . BOTBANISH_LOCATION . 'Subs/BotBanish_Settings_Update_SMF.php';
			BotBanishSettingsCreateSMF($BotBanishSettings);
			$str = BotBanishSetupSMF();
			
		} else {
			
			if (defined('DIR_APPLICATION')) {
				
				$dir = rtrim(realpath(DIR_APPLICATION . '../' . BOTBANISH_LOCATION), '/') . '/';
				require_once  $dir . 'Subs/BotBanish_Settings_Update_OpenCart.php';
				BotBanishSettingsCreateOpenCart($BotBanishSettings);
				$str = BotBanishSetupOpenCart();	
				
			} else {
				
				$str = BotBanishSetupWebsite();
			}
		}
	}

	BotBanishDefineGlobals('BOTBANISH_VERSION_SERVER', BOTBANISH_VERSION_SERVER);
	BotBanishDefineGlobals('BOTBANISH_VERSION_CLIENT', BOTBANISH_VERSION_CLIENT);
	BotBanishDefineGlobals('BOTBANISH_VERSION_NO', BOTBANISH_VERSION_NO);
		
	BotBanishSettingsFileSave($str);
}

function BotBanishSettingsFileSave($str) {

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
	BotBanishWriteFile(BOTBANISH_DIR . 'BotBanish_Settings.php', $str);
}

function BotBanishSetupWebsite() {

	$str = "<?php
		ini_set('memory_limit','256M');

		\$BotBanishSQL = '';
		\$BotBanishURL = '';
		\$BotBanishConn = '';
		\$BotBanishTableColumns = array();

		BotBanishDefineGlobals('BOTBANISH', true);
		BotBanishDefineGlobals('BOTBANISH_SYSTEM', '" . BOTBANISH_SYSTEM . "');
		BotBanishDefineGlobals('BOTBANISH_LANGUAGE_DIR','" . BOTBANISH_DIR . "Language/');

		require_once 'Include/BotBanishVersion.php';
	
		require_once 'Subs/BotBanish_Subs.php';
		require_once 'Subs/BotBanish_Subs_DB_Website.php';
		require_once 'Subs/BotBanish_Subs_HTACCESS.php';
		require_once 'Client/BotBanishClient.php';

		\$BotBanishSettings = BotBanishSettingsTableGetValues();
		\$BotBanishText = \$GLOBALS['BotBanishText'];

		BotBanishDefineGlobals('BOTBANISH_AUTOLOAD_DIR', BOTBANISH_DIR . '/vendor/');
		BotBanishDefineGlobals('BOTBANISH_LOGS_DIR', BOTBANISH_DIR . '/Client/BotBanish_Logs/');
		BotBanishDefineGlobals('BOTBANISH_USER_HOST', BOTBANISH_USER_HOST);

		require_once 'Include/BotBanish_Common.php';
?>";

return $str;
}
?>