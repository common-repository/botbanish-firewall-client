<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Common Settings
// Date: 03/06/2024
// Usage: require_once('BotBanish_Common.php');
//
// Function: Common location for common settings
///////////////////////////////////////////////////////////////////////////////////////////////////////

	if (!defined('BOTBANISH_UNINSTALL')) {

//========================================================================================
// Autoloading Start.
// The use statements CANNOT be in a code block!!! They are pre-compiled.
// So instead we use autoloading of the classes.
//========================================================================================

		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Autoload.php';

		if (isset($GLOBALS['sourcedir'])) {
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_SMF.php';
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_SMF.php';
		}

		if (defined('ABSPATH'))
			require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_WordPress.php';

//================================================================
// Load all classes in the vendor folder
//================================================================

		BotBanish_Autoload(BOTBANISH_AUTOLOAD_DIR);
		
		// Can't run without this one
		
		if (class_exists('PHPSQLParser'))
			$GLOBALS['SQL_Parser'] = new PHPSQLParser;
		
		// Can run without this one. If it does not exist turn off email alerts
		
		if (class_exists('PHPMailer'))
			$GLOBALS['mail'] = new PHPMailer(true);     // Passing `true` enables exceptions
		else
			$GLOBALS['BotBanishSettings']['BOTBANISH_SEND_EMAIL_ALERTS'] = 0;
	}
?>