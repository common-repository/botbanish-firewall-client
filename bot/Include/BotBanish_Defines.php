<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Pre-Defines
// Date: 05/21/2024
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

//================================================================
// Setup IPv4 or IPv6 format for .htaccess
// Apache versions below 2.4.00 cannot use IPv6
//================================================================

	BotBanishDefineGlobals('BOTBANISH_APACHE_VERSION', intval(2400));

//================================================================
// .htaccess file error handling setup
//================================================================

	// If we are protecting the root and the application folder, we need to modify the htaccess file in both locations

	$htaccess_array = array();

	// Protect the OpenCart folder

	if (defined('DIR_APPLICATION'))
		$htaccess_array[] = rtrim(dirname(DIR_APPLICATION, 1), '/') . '/admin/.htaccess';

	// Protect the SMF folder

	if (isset($GLOBALS['boarddir']))
		$htaccess_array[] = realpath($GLOBALS['boarddir']) . '/.htaccess';

	// Protect the WordPress folder

	if (defined('ABSPATH'))
		$htaccess_array[] = realpath(ABSPATH) . '/.htaccess';

	// Protect the root folder

	$htaccess_array[] = realpath($_SERVER['DOCUMENT_ROOT']) . '/.htaccess';

	// Protect the BotBanish folder also

	$htaccess_array[] = realpath(BOTBANISH_DIR) . '/.htaccess';

	// No duplicate folders!
	// Insure proper slash usage!!!

	foreach ($htaccess_array as $key => $value)
		$htaccess_array[$key] = str_ireplace('\\', '/', $value);

	$htaccess_array = array_unique($htaccess_array);

	require_once BOTBANISH_DIR . 'Subs/BotBanish_Subs.php';
	BotBanishDefineGlobals('BOTBANISH_HTACCESS_ARRAY', BotBanishSafeSerialize($htaccess_array));

//================================================================

	$curdir = str_ireplace('\\', '/', getcwd());
	chdir($_SERVER['DOCUMENT_ROOT']);
	$root = str_ireplace('\\', '/', getcwd());
	chdir($curdir);

	if (defined('ABSPATH')) {

		$repl = get_option( 'siteurl' );
		$rootfolder = str_replace('\\', '/', rtrim(plugin_dir_path( __FILE__ ), '/') . '/') ;

	} else {

		$repl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];

		if (isset($boarddir))
			$rootfolder = isset($boarddir) ? $boarddir . '/' : '/';
		else
			$rootfolder = str_replace('\\', '/', realpath(getcwd() . '../../../../../')) . '/';
	}

	$find = $root;

	$botbanish_install_url = $repl;
	$botbanish_install_dir = $root . '/';

	$char = '*';
	$start = str_repeat($char . ' ', 5);
	$end = str_repeat(' ' . $char, 5);
	$section = '# %s%s%s';


	BotBanishDefineGlobals('BOTBANISH_PAGE_HEADER', '
		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Expires" content="0" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.0/jspdf.umd.min.js"></script>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="stylesheet" href="' . BOTBANISH_INSTALL_URL . 'css/stylesheet.css">
		');

	BotBanishDefineGlobals('BOTBANISH_CENTER_HEAD','
		<div>
			<table width="100%" align="center">
				<tr>
					<td width="25%"></td>
					<td width="50%">');

	BotBanishDefineGlobals('BOTBANISH_CENTER_FOOTER','
					</td>
					<td width="25%"></td>
				</tr>
			</table>
		</div>');

	BotBanishDefineGlobals('BOTBANISH_ROOT_FOLDER', $rootfolder);
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_DATABASE', 1);
	BotBanishDefineGlobals('BOTBANISH_HTACCESS_RECORD_IP', 0);
	BotBanishDefineGlobals('BOTBANISH_HTACCESS_RECORD_BOT', 1);
	BotBanishDefineGlobals('BOTBANISH_LOG_FILES', 30);

	BotBanishDefineGlobals('BOTBANISH_TAG_IP_START', '# * * * * * BotBanish IP Start * * * * *');
	BotBanishDefineGlobals('BOTBANISH_TAG_IP_END', '# * * * * * BotBanish IP End * * * * *');
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_START', '# * * * * * BotBanish BOT Start * * * * *');
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_END', '# * * * * * BotBanish BOT End * * * * *');

	BotBanishDefineGlobals('BOTBANISH_CODE_START', '# * * * * * BotBanish CODE Start * * * * *');
	BotBanishDefineGlobals('BOTBANISH_CODE_END', '# * * * * * BotBanish CODE End * * * * *');
	BotBanishDefineGlobals('BOTBANISH_RULE_START', '# * * * * * BotBanish RULES Start * * * * *');
	BotBanishDefineGlobals('BOTBANISH_RULE_END', '# * * * * * BotBanish RULES End * * * * *');

	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_REWRITE_START', sprintf($section, $start, 'BotBanish BOT Start (REWRITECOND)', $end));
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_REWRITE_END', sprintf($section, $start, 'BotBanish BOT End (REWRITECOND)', $end));
	BotBanishDefineGlobals('BOTBANISH_HTACCESS_HEADER', PHP_EOL . PHP_EOL . '# Deny bandwidth, Spam, data and login attacks (BotBanish - ' . BOTBANISH_USER_HOST . ')' . PHP_EOL . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REPLACE', '//	* * * * * BotBanish * * * * *');

	// .htaccess section headers and footers

	BotBanishDefineGlobals('BOTBANISH_IP_HEADER_START', PHP_EOL . '<Limit GET POST>' . PHP_EOL . 'order allow,deny' . PHP_EOL . 'allow from all' . PHP_EOL . '</Limit>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_BOT_HEADER_END', PHP_EOL . '<Limit GET POST HEAD>' . PHP_EOL . 'Order Allow,Deny' . PHP_EOL . 'Allow from all' . PHP_EOL . 'Deny from env=bad_bot' . PHP_EOL . '</Limit>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REQUIREALL_START_ORG', PHP_EOL . '<RequireAll>' . PHP_EOL . 'Require all granted' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REQUIREALL_END_ORG', PHP_EOL . '</RequireAll>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REQUIREALL_START', PHP_EOL . '<Files *>' . PHP_EOL . '<RequireAll>' . PHP_EOL . 'Require all granted' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REQUIREALL_END', PHP_EOL . '</RequireAll>' . PHP_EOL . '</Files>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_EMPTY_REQUIRE', PHP_EOL . '<RequireAll>' . PHP_EOL . 'Require all granted' . PHP_EOL . '</RequireAll>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_EMPTY_FILES', PHP_EOL . '<Files *>' . PHP_EOL . '</Files>' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REWRITECOND_START', PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT}' . PHP_EOL);
	BotBanishDefineGlobals('BOTBANISH_REWRITECOND_END', PHP_EOL . 'RewriteRule .* - [G,L,E=bad_bot:Yes]' . PHP_EOL);

	// BotBanish .htaccess tags

//	BotBanishDefineGlobals('BOTBANISH_BOT_2_4', PHP_EOL . 'RewriteCond %{HTTP_USER_AGENT} ');
	BotBanishDefineGlobals('BOTBANISH_BOT_REWRITECOND_2_4', 'RewriteCond %%{HTTP_USER_AGENT} ');
	BotBanishDefineGlobals('BOTBANISH_TAG_IP_START_2_4', BOTBANISH_TAG_IP_START);
	BotBanishDefineGlobals('BOTBANISH_TAG_IP_END_2_4', BOTBANISH_TAG_IP_END);
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_START_2_4', BOTBANISH_TAG_BOT_START);
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_END_2_4', BOTBANISH_TAG_BOT_END);
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_REWRITECOND_START_2_4', sprintf($section, $start, 'BotBanish BOT Start (REWRITECOND)', $end));
	BotBanishDefineGlobals('BOTBANISH_TAG_BOT_REWRITECOND_END_2_4', sprintf($section, $start, 'BotBanish BOT End (REWRITECOND)', $end));
	BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WEBSITE');
	BotBanishDefineGlobals('BOTBANISH_HONEYPOT_URL', BOTBANISH_INSTALL_URL . 'Subs/BotBanish_RegisterAccount.php');

	BotBanishDefineGlobals('BOTBANISH_LANGUAGEPATH', 'Language/');

	$dataarray = array(
		'BOTBANISH' => 1,
		'BOTBANISH_SIGNATURE_POST_LIMIT' => 10,
		'BOTBANISH_HACKER' => 'Hacker',
		'BOTBANISH_USER_ID' => 0,
		'BOTBANISH_ANALYTICS_WEBSITE' => 1,
		'BOTBANISH_ANALYTICS_DOWNLOADS' => 1,
		'BOTBANISH_SERVERSIDE_ANALYTICS' => 0,		// Let server log the website and download hits (1 = Server / 0 = Local)
		'BOTBANISH_SEND_EMAIL_ALERTS' => 1,
		'BOTBANISH_CHECK_UPDATES' => 1,
		'BOTBANISH_DOC_ERRORS_MONITOR' => 1,
		'BOTBANISH_MAX_HIT_COUNT' => 30,
		'BOTBANISH_MAX_IP_RANGE_COUNT' => 25,
		'BOTBANISH_HTACCESS_FLUSH_COUNT' => 20,
		'BOTBANISH_HTACCESS_FLUSH_TIME' => 20,
		'BOTBANISH_HTACCESS_CACHING' => 1,
//		'BOTBANISH_APIKEY' => 'Free',
		'BOTBANISH_DEFINE'=> 1,
		'BOTBANISH_VALUE' => 2,
		'BOTBANISH_STRING' => 3,
		'BOTBANISH_DEBUG_FORCELOCKOUT' => 0,
		'BOTBANISH_REQUEST_METHOD' => 'POST',
		'BOTBANISH_IP_LOCKOUT' => 1,
		'BOTBANISH_BAD_BOT_LOCKOUT' => 2,
		'BOTBANISH_HITCOUNT_LOCKOUT' => 3,
		'BOTBANISH_HIDDEN_LOCKOUT' => 4,
		'BOTBANISH_EMAIL_USERNAME_LOCKOUT' => 5,
		'BOTBANISH_ABUSE_LOCKOUT' => 6,
		'BOTBANISH_URL_LOCKOUT' => 7,
		'BOTBANISH_UNKNOWN_BOT_LOCKOUT' => 8,
		'BOTBANISH_SPOOF_BOT_LOCKOUT' => 9,
		'BOTBANISH_BAD_DOMAIN_LOCKOUT' => 10,
		'BOTBANISH_INVALID_REQUEST_LOCKOUT' => 11,
		'BOTBANISH_EMPTY_USERAGENT_LOCKOUT' => 12,
		'BOTBANISH_IP_RANGE_LOCKOUT' => 13,
		'BOTBANISH_COUNTRY_LOCKOUT' => 14,
		'BOTBANISH_DOC_ERRORS' =>  serialize(array(400, 401, 404, 405, 406)), // 403 is reserved for bot blocking
		'BOTBANISH_DOC_ERRORS_TERMINATE' =>  serialize(array(400, 401)),
		'BOTBANISH_UA_EXCEPTIONS' =>  serialize(array('http://', 'https://', '@', '?', '#', '^', '&', '~', '$')),
		'BOTBANISH_HTACCESS_LOCK' => 'lock',
		'BOTBANISH_HTACCESS_UNLOCK' => 'unlock',
		'BOTBANISH_LOCALAPPURL' => BOTBANISH_PROTOCOL . '://' . BOTBANISH_USER_HOST . BOTBANISH_LOCATION,
		'BOTBANISH_HONEYPOT_FILE_HTML' => 'BotBanish_RegisterAccount.html',
		'BOTBANISH_UPDATE_HTML_FOLDER' => $rootfolder,
		'BOTBANISH_UPDATE_HTML' => 0,
		'BOTBANISH_UPDATE_HTML_ACTIVE' => 0,
		'BOTBANISH_REFERER_DATA' => 1,
		'BOTBANISH_PAGE_DATA' => 1,
		'BOTBANISH_XMLRPC_ACTIVE' => 1,
		'BOTBANISH_WPCONFIG_ACTIVE' => 1,
		'BOTBANISH_HTACCESS_ACTIVE' => 1,

		'BOTBANISH_XMLRPC_DATA' => addslashes('
# Block WordPress xmlrpc.php requests

<Files xmlrpc.php>
order deny,allow
deny from all
</Files>'),

		'BOTBANISH_HTACCESS_DATA' => addslashes('
# Block .htaccess access

<Files .htaccess>
order deny,allow
deny from all
</Files>'),

		'BOTBANISH_WPCONFIG_DATA' => addslashes('
# Block WordPress wp-config.php access

<Files wp-config.php>
order deny,allow
deny from all
</Files>'),

		'BOTBANISH_HEADER_HTML' => addslashes(
		'<div align="center">
			<table width="60%" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td align="center"><img src="' . BOTBANISH_IMAGES_URL . 'BotBanish/banner.png" width="100%" height="100"></td>
				</tr>
			</table>
		</div>'),

		// Setup our arrays to manipulate the .htaccess file for the proper version
		// New style - Apache 2.4 and greater

		'HTACCESS_ENTRY_IP_2_4' => serialize(array(
			'tag_start' => BOTBANISH_TAG_IP_START_2_4,
			'tag_end' => BOTBANISH_TAG_IP_END_2_4,
			'start' => BOTBANISH_REQUIREALL_START,
			'end' 	=> BOTBANISH_REQUIREALL_END,
			'str'	=> 'Require not ip %s' . PHP_EOL,
			'type' => 'ip',
			'id' => 1
		)),

		'HTACCESS_ENTRY_BOT_2_4' => serialize(array(
			'tag_start' => BOTBANISH_TAG_BOT_REWRITECOND_START_2_4,
			'tag_end' => BOTBANISH_TAG_BOT_REWRITECOND_END_2_4,
			'start'	=> BOTBANISH_REWRITECOND_START,
			'end'	=> BOTBANISH_REWRITECOND_END,
			'str'	=> BOTBANISH_BOT_REWRITECOND_2_4 . '"%s" [OR,NC]' . PHP_EOL,
			'type' => 'bot',
			'id' => 2
		)),

		'BOTBANISH_CODE' =>
		BOTBANISH_CODE_START . '

	# Apache 2.4

	SetEnv BOTBANISH_TYPE 2.4.00

	' . 'SetEnv BOTBANISH_SYSTEM ' . BOTBANISH_SYSTEM . PHP_EOL .
		BOTBANISH_CODE_END		,

		// Bot catcher. Only bots and some toolbars will trigger this link and then we own ya!

		'BOTBANISH_HIDDEN_HONEYPOT_LINK' => addslashes('<div name="divBotBanish" style="height: 0px;width: 0px;overflow:hidden;"><a href="' . BOTBANISH_HONEYPOT_URL . '">Register</a></div>'),
		// Make sure all our folders etc... are defined

		'BOTBANISH_SCRIPT_DIR' => BOTBANISH_APPPATH_DIR . 'Analytics/',
		'BOTBANISH_CHECK_UPDATES' => 1,

		'BOTBANISH_HONEYPOT_HTML' =>
		'<!doctype html>'. PHP_EOL .
		'<html lang="en">'. PHP_EOL .
		'<head>'. PHP_EOL .
		'<META http-equiv="refresh" content="1;URL=%s">'. PHP_EOL .
		'<title>Register Account</title>'. PHP_EOL .
		'</head>'. PHP_EOL .
		'<body>'. PHP_EOL .
		'</body>'. PHP_EOL .
		'</html>'. PHP_EOL,
);

	BotBanishDefineGlobals('BOTBANISH_LANGUAGES', array('english' => 'english', 'french' => 'french', 'german' => 'german', 'italian' => 'italian', 'portuguese' => 'portuguese', 'spanish' => 'spanish', 'swedish' => 'swedish'));

	// Try to match the language that SMF is currently in to see if we support it

	if (defined('SMF')) {
		
		global $language;
		$str = explode('_', $language);
		$lang = $str[0];
		
	} else {
		
		$lang = 'english';
	}
	
	$lang = isset($language) && defined('SMF') && array_search($lang, BOTBANISH_LANGUAGES) !== false ? strtolower($lang) : 'english';
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_SELECT', $lang);
	
	foreach ($dataarray as $key => $value)
		BotBanishDefineGlobals($key, $value);

//================================================================
// Capture & Tracking Section
//================================================================

	// Webpage and download tracking. Gets placed on each page in the header section.

	BotBanishDefineGlobals('BOTBANISH_ANALYTICS_HTML', addslashes('
		<div id="BotBanishObj">

			<iframe id="botbanishanalytics" style="height: 0px;width: 0px;overflow:hidden;" frameborder="0" scrolling="auto" allowtransparency="true" src=""></iframe>

			<script type="text/javascript">

				document.getElementById("botbanishanalytics").allowTransparency = "true";
				document.getElementById("botbanishanalytics").src = "' . BOTBANISH_INSTALL_URL . 'Analytics/BotBanishAnalyticsLog.php?user_id=0&pagename="+document.location.href;

			</script>
		</div>
		'));

//================================================================
// Make all folders that we will need
//================================================================

	if (!is_dir(BOTBANISH_LOGS_DIR))
		@mkdir(BOTBANISH_LOGS_DIR, 0755, true);

	if (!is_dir(BOTBANISH_LOGS_DIR . '/html'))
		@mkdir(BOTBANISH_LOGS_DIR . '/html', 0755, true);

	// Files that BotBanish will log when downloaded; should be placed in this folder

	$download_data = $_SERVER['DOCUMENT_ROOT'] . '/data/' . str_ireplace('www.', '', BOTBANISH_USER_HOST);

	if (!is_dir($download_data))
		@mkdir($download_data, 0755, true);

//================================================================
// Language Section
//================================================================

	BotBanishInitLanguage();

//================================================================
// Time Zone Section
//================================================================

	if (defined('SMF')) {

		global $modSettings;
		BotBanishDefineGlobals('BOTBANISH_TIMEZONE', $modSettings['default_timezone']);

	} else {

		BotBanishDefineGlobals('BOTBANISH_TIMEZONE', isset($_SERVER['TZ']) ? $_SERVER['TZ'] : date_default_timezone_get());
	}

function BotBanishInitLanguage() {

	global $BotBanishSettings;
	$language = defined('BOTBANISH_LANGUAGE_SELECT') ? $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'] : 'english';

	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_SELECT', $language);

	$filename = 'BotBanishLanguage.' . $language . '.php';

	$txt = array();
	include BOTBANISH_LANGUAGE_DIR . $filename;

	if (!empty($txt))
		$GLOBALS['BotBanishText'] = $txt;

	return $language;
}
?>