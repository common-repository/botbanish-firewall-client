<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish define updates for WORDPRESS
// Date: 03/16/2024
// Usage:
//
// Function:
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishSettingsCreateWordPress(&$BotBanishSettings) {

	global $wpdb;
	
	$user_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	$user_host = (empty($user_host) && isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : $user_host;
	$user_host = ltrim(str_ireplace('\\', '/', $user_host), '/');
	
	$config = array(
		'BOTBANISH_TIMEZONE' => 'Pacific/Honolulu',
//		'BOTBANISH_DB_PREFIX' => 'bbc_',
		'BOTBANISH_DB_NAME' => $wpdb->dbname,
		'BOTBANISH_DB_USERNAME' => $wpdb->dbuser,
		'BOTBANISH_DB_PASSWORD' => $wpdb->dbpassword,
		'BOTBANISH_DB_SERVERNAME' => $wpdb->dbhost,
		'BOTBANISH_HOST' => $wpdb->dbhost,
		'BOTBANISH_DB_PORT' => '',
		'BOTBANISH_UPDATE_HTML' => 0,
		'BOTBANISH_SYSTEM' => 'WORDPRESS',
		'BOTBANISH_USER_HOST' => $user_host,
		'BOTBANISH_SMTP_SERVER' => '',
		'BOTBANISH_SMTP_PORT' => '',
		'BOTBANISH_SMTP_USERNAME' => '',
		'BOTBANISH_SMTP_PASSWORD' => '',
		'BOTBANISH_WEBMASTER_EMAIL' => get_bloginfo('admin_email'),
	);

	foreach ($config as $key => $value)
		BotBanishDefineGlobals($key, $value);
}

function BotBanishSetupWordPress() {

	$str = "<?php
		ini_set('memory_limit','256M');

		\$BotBanishSQL = '';
		\$BotBanishURL = '';
		\$BotBanishConn = '';
		\$BotBanishTableColumns = array();

		BotBanishDefineGlobals('BOTBANISH', true);
		BotBanishDefineGlobals('BOTBANISH_CLIENT', true);
		BotBanishDefineGlobals('BOTBANISH_SERVER', false);
//		BotBanishDefineGlobals('BOTBANISH_DB_PREFIX', 'bbc_');
		BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WORDPRESS');
		BotBanishDefineGlobals('BOTBANISH_DIR', '" . BOTBANISH_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_SUBS_DIR', '" . BOTBANISH_SUBS_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_BACKUPS_DIR', '" . BOTBANISH_BACKUPS_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_INCLUDE_DIR', '" . BOTBANISH_INCLUDE_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_DATA_DIR', '" . BOTBANISH_DATA_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_CLIENT_DIR', '" . BOTBANISH_CLIENT_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_AUTOLOAD_DIR', '" . BOTBANISH_AUTOLOAD_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_DEBUG_LOG_PROGRESS', 0);
		BotBanishDefineGlobals('BOTBANISH_LOGS_DIR', '" . BOTBANISH_LOGS_DIR . "');
		BotBanishDefineGlobals('BOTBANISH_USER_HOST', '" . BOTBANISH_USER_HOST . "');
		BotBanishDefineGlobals('BOTBANISH_DB_NAME', '" . BOTBANISH_DB_NAME . "');

		require_once BOTBANISH_INCLUDE_DIR . 'BotBanishVersion.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_WordPress.php';
		require_once BOTBANISH_INCLUDE_DIR . 'BotBanish_Common.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_HTACCESS.php';
		require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';

		\$BotBanishSettings = BotBanishSettingsTableGetValues();
		\$BotBanishText = \$GLOBALS['BotBanishText'];
		\$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);
?>";

return $str;
}
?>