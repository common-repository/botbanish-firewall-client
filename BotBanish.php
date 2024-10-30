<?php
   /*
   Plugin Name: BotBanish Firewall Client
   Plugin URI: https://botbanish.com
   description: A firewall to detect and banish BOT/Users that are possibly attempting to do harm to your system by	performing brute force attacks in an attempt to create an account on the system or probing to find vulerabilities on your site. We Don't Report Abuse - WE STOP IT!!!
   Version: 5.2.01
   Tested up to: 6.5.3
   Author: Randem Systems
   Author URI: https://randemsystems.com
   License: GPL2
   */

/*
	// Data to be placed into wp_config.php if needed

// Delete all data for WooCommerce
//define('WC_DELETE_ALL_DATA', true);

// Debug WordPress
define('WP_DEBUG', true);
define('WP_CACHE', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
*/

	// Changed: 05/30/2024 - Added new checks to BotBanish_ValidateContact().
	// Changed: 05/10/2024 - Fixed data delete issue.
	// Changed: 05/10/2024 - Check contact information from contact forms or outgoing mail.
	// Changed: 05/04/2024 - Uninstall not to delete BotBanish database tables unless chosen to do so.
	// Changed: 03/16/2024 - Added system name to analytics log call.
	// Changed: 03/14/2024 - Change database backup to table based backup.
	// Changed: 07/05/2023 - Added wp-config.php and .htaccess protection option
	// Changed: 06/25/2023 - Added xmlrpc.php protection option
	//						 Changed startup flow
	// Changed: 06/16/2023 - Added filter to check login attempts
	// Changed: 05/19/2023 - Added download of documents at install time that cannot fit into the install
	// Changed: 05/05/2023 - Do not uninstall html info, write log if uninstalling
	// Changed: 02/21/2023 - Added routine to add and update country and country_code fields to database tables
	// Changed: 11/15/2022 - Copied Analytics status and css folder to wp-content/themes/BotBanish folder. Added delete data option.
	// Changed: 11/13/2022 - Remove copying BotBanish to root. Remove BotBanish echo code in wp_head
	// Changed: 09/17/2022 - Fix setting not having defaults
	// Changed: 09/15/2022 - Copy the BotBanish folder to the WordPress root so that Analytics can save data
	// Changed: 08/22/2022
	// Changed: 08/09/2022
	// Changed: 07/03/2022
	// Changed: 06/07/2022
	// Changed: 01/24/2022
	// Changed: 01/16/2022
	// Changed: 01/17/2020

	if (!function_exists( 'add_action'))
		exit;

	add_action( 'wp_head', 'BotBanishClientPageStart', 10);
	add_action( 'publish_post', 'BotBanishClientPublishPost', 10);
	add_action( 'comment_post', 'BotBanishClientCommentPost', 10, 2);
	add_action( 'user_new_form', 'BotBanishClientNewUserAddField', 10);
	add_action( 'wp_login', 'BotBanishClientLogin', 10, 2);
	add_action( 'user_register', 'BotBanishClientRegister', 10);
	add_action( 'edit_user_created_user', 'BotBanishClientAddUser', 10, 2);
	add_action( 'register_post', 'BotBanishClientRegisterPost', 10, 3);
	add_action( 'authenticate', 'BotBanishAuthenticate', 10);

	if (!function_exists( 'add_filter'))
		exit;

	add_filter( 'wp_authenticate_user', 'BotBanish_Authenticate_User', 999999, 2 );
	add_filter('wp_mail','BotBanish_ValidateContact', 10,1);

	register_activation_hook( __FILE__, 'BotBanish_plugin_activation');
	register_deactivation_hook( __FILE__, 'BotBanish_plugin_deactivation');

	$GLOBALS['BotBanishSettings'] = array();
	$GLOBALS['BotBanishText'] = array();
	$GLOBALS['install'] = array();

	define('BOTBANISH', 1);
	define('BOTBANISH_MINIMUM_WP_VERSION', '4.0' );
	define('BOTBANISH_PLUGIN_DIR', str_ireplace('\\', '/', rtrim(plugin_dir_path( __FILE__ ), '/') . '/'));
	define('BOTBANISH_PLUGIN_URL', str_ireplace('\\', '/', rtrim(plugin_dir_url(__FILE__), '/') . '/'));
	define('BOTBANISH_DIR', BOTBANISH_PLUGIN_DIR . '/bot/');

	require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanishVersion.php';

	// This had to be done for the INTECH themes and other themes that use the redux-framework.
	// They create a lot of depreaciation in PHP 8.2 in the
	// error.log file which at some point will overrun the server storage.

	if (defined('WP_DEBUG') && !WP_DEBUG) {

		$filename = ABSPATH . 'error_log';
		if (file_exists($filename))
			unlink($filename);
	}

	BotBanish_Start();

function BotBanish_Start() {

	BotBanishGetOptions();
	require_once BOTBANISH_PLUGIN_DIR . 'BotBanish_Options.php';

	BotBanishDefineGlobals('BOTBANISH', true);
	BotBanishDefineGlobals('BOTBANISH_SERVER', false);
	BotBanishDefineGlobals('BOTBANISH_SYSTEM', 'WORDPRESS');
	BotBanishLoadSettings();
}

function BotBanishLoadFunctions () {

	require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanishVersion.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanish_Setup.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanish_Defines.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_Subs.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_Subs_DB_Website.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_Subs_HTACCESS.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_Subs_WordPress.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanish_Common.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Client/BotBanishClient.php';
}

function BotBanish_BackupDatabase() {

	//////////////////////////////////////////////////////////////////////////////////////////////
	// Let's backup the database or table before we start; just in case of
	// install failure. If BotBanish installation fails for ANY reason you can take the backed up
	// tables and restore them to their original location to restore WordPress to it's prior state.
	// Now backup the database!!!
	// We must allow user to be able to completely recover from an installation failure
	//////////////////////////////////////////////////////////////////////////////////////////////

	global $table_prefix;

	$tables = array($table_prefix . 'options');
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_AdjustDatabase.php';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_DumpDatabase.php';

	foreach($tables as $table)
		$download_link = BotBanish_DumpSingleTable($table);
}

function BotBanishGetOptions() {

	$options = BotBanish_InitializeDefaults();

	BotBanishDefineGlobals('BOTBANISH_ACTIVE', isset($options['_botbanishtext_botbanish_active']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_APIKEY', isset($options['_botbanishtext_botbanish_apikey']) ? $options['_botbanishtext_botbanish_apikey'] : 'Free');
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_SELECT', isset($options['_botbanishtext_botbanish_language_select']) ? $options['_botbanishtext_botbanish_language_select']: 'english');
	BotBanishDefineGlobals('BOTBANISH_DELETE_DATA', isset($options['_botbanishtext_botbanish_delete_data']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_SEND_EMAIL_ALERTS', isset($options['_botbanishtext_botbanish_send_email_alerts']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_DOC_ERRORS_MONITOR', isset($options['_botbanishtext_botbanish_doc_errors_monitor']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_ANALYTICS_WEBSITE', isset($options['_botbanishtext_botbanish_analytics_website']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_ANALYTICS_DOWNLOADS', isset($options['_botbanishtext_botbanish_analytics_downloads']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_CHECK_UPDATES', isset($options['_botbanishtext_botbanish_check_updates']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_XMLRPC_ACTIVE', isset($options['_botbanishtext_botbanish_xmlrpc']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_WPCONFIG_ACTIVE', isset($options['_botbanishtext_botbanish_wpconfig']) ? true: false);
	BotBanishDefineGlobals('BOTBANISH_HTACCESS_ACTIVE', isset($options['_botbanishtext_botbanish_htaccess']) ? true : false);
	BotBanishDefineGlobals('BOTBANISH_MAX_HIT_COUNT', isset($options['_botbanishtext_botbanish_max_hit_count']) ? $options['_botbanishtext_botbanish_max_hit_count'] : '30');
	BotBanishDefineGlobals('BOTBANISH_MAX_IP_RANGE_COUNT', isset($options['_botbanishtext_botbanish_max_ip_range_count']) ? $options['_botbanishtext_botbanish_max_ip_range_count'] : 25);
}

function BotBanish_InitializeDefaults() {

	// Check to see if the BotBanish options exist, if not then create them

	$botbanishfirewall_client_option_name = get_option('botbanishfirewall_client_option_name');

	if (empty($botbanishfirewall_client_option_name)) {

		require_once BOTBANISH_PLUGIN_DIR . 'bot/Include/BotBanish_PreIncludes.php';

		$botbanishfirewall_client_option_name = array(
			'_botbanishtext_botbanish_active' => true,
			'_botbanishtext_botbanish_apikey' => 'Free',
			'_botbanishtext_botbanish_language_select' => 'english',
			'_botbanishtext_botbanish_delete_data' => false,
			'_botbanishtext_botbanish_send_email_alerts' => true,
			'_botbanishtext_botbanish_doc_errors_monitor' => true,
			'_botbanishtext_botbanish_analytics_website' => true,
			'_botbanishtext_botbanish_analytics_downloads' => true,
			'_botbanishtext_botbanish_check_updates' => true,
			'_botbanishtext_botbanish_xmlrpc' => true,
			'_botbanishtext_botbanish_wpconfig' => true,
			'_botbanishtext_botbanish_htaccess' => true,
			'_botbanishtext_botbanish_max_hit_count' => 30,
			'_botbanishtext_botbanish_max_ip_range_count' => 25,
		);

		add_option('botbanishfirewall_client_option_name', $botbanishfirewall_client_option_name, null ,'yes');
	}

	return $botbanishfirewall_client_option_name;
}

function BotBanish_plugin_activation() {
				
	BotBanishLoadFunctions();
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanishInstall.php';
	BotBanishRecursiveCopy(BOTBANISH_PLUGIN_DIR . 'bot/Analytics/status', ABSPATH . 'wp-content/themes/BotBanish/status');
	BotBanishRecursiveCopy(BOTBANISH_PLUGIN_DIR . 'bot/Analytics/css', ABSPATH . 'wp-content/themes/BotBanish/css');
	BotBanish_Start();
	BotBanish_BackupDatabase();
	define('BOTBANISH_INSTALL', 1);
	BotBanishClient();
}

function BotBanish_plugin_deactivation() {

	global $table_prefix, $BotBanishSettings;

	// Remove all our custom actions

	remove_action( 'wp_head', 'BotBanishClientPageStart');
	remove_action( 'publish_post', 'BotBanishClientPublishPost');
	remove_action( 'comment_post', 'BotBanishClientCommentPost');
	remove_action( 'user_new_form', 'BotBanishClientNewUserAddField');
	remove_action( 'wp_login', 'BotBanishClientLogin');
	remove_action( 'user_register', 'BotBanishClientRegister');
	remove_action( 'edit_user_created_user', 'BotBanishClientAddUser');
	remove_action( 'register_post', 'BotBanishClientRegisterPost');
	remove_action( 'authenticate', 'BotBanishAuthenticate');

	remove_filter( 'wp_authenticate_user', 'BotBanish_Authenticate_User');

	BotBanishLoadSettings();

	$deletedata = isset($options['_botbanishtext_botbanish_delete_data']) ? $options['_botbanishtext_botbanish_delete_data']: false;

	if ($deletedata) {

		$sql = 'DELETE FROM ' . $table_prefix . 'options WHERE `option_name` = "botbanishfirewall_client_option_name"';
		BotBanishExecuteSQL($sql);

		BotBanishRemoveDirectory(ABSPATH . 'BotBanish/');
		BotBanishRemoveDirectory(ABSPATH . 'wp-content/themes/BotBanish/');

		$options = BotBanish_InitializeDefaults();
	}

	BotBanishCleanup($deletedata, 'CLIENT', false);
}

function BotBanishLoadSettings() {

	BotBanishGetOptions();
	if (file_exists(BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php'))
		require_once BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';
}

function BotBanishClientPageStart() {

	BotBanishLoadSettings();
	BotBanishClientAnalytics();
	BotBanishUserCheck();
}

function BotBanishUserCheck() {

	$user_obj = wp_get_current_user();

	if ((isset($user_obj->ID)) || ($user_obj->ID == 0))
		BotBanishClient();
}

function BotBanishClientAnalytics() {

	$pagename = get_the_title();

	// If there is no page name, we can do nothing here!

	if (empty($pagename))
		return;

	$_GET['pagename'] = $pagename;
	$_GET['system'] = 'WORDPRESS"';
	require_once BOTBANISH_PLUGIN_DIR . 'bot/Analytics/BotBanishAnalyticsLog.php';
}

function BotBanishClientPublishPost($post_id) {

	// When the user clicks the publish button; we check him out

	$user_obj = wp_get_current_user();

	if (isset($user_obj->user_login) && isset($user_obj->user_email))
		BotBanishClient(false, $user_obj->user_login, $user_obj->user_email, false);
}

function BotBanishClientCommentPost($comment_ID, $comment_approved) {

	// if someone is attempting to submit a comment, check them out
	// There is no user signed in so we get it from the comment form post

	if ($_POST['author'] && $_POST['email'])
		BotBanishClient(false, $_POST['author'], $_POST['email'], false);
}

function BotBanishClientNewUserAddField()
{
	// Place our hidden field in the registration form

	?>
		user_name" name="user_name" value="" />
	<?php
}

function BotBanishClientLogin($username, $user_obj) {

	// Check this user after they log on

	if (isset($user_obj->user_login) && isset($user_obj->user_email))
		BotBanishClient(false, $user_obj->user_login, $user_obj->user_email, false);

	// If we get here the user checks out; so remove the potential block
	// Once logged in remove the ip from being blocked

	if (isset($user_obj->roles) && is_array($user_obj->roles))
		BotBanishClient(false, '', '', true);
}

function BotBanishClientRegister($user_id) {

	// Check for our special registration field

	 if (isset($_POST['botname']) && !empty($_POST['botname']))
		BotBanishClient(true, '', '', false);
}

function BotBanishClientAddUser($user_id, $notify){

	// if this is a bot get rid of it

	if (isset($_POST['user_name']) && !empty($_POST['user_name'])){

		$username = '';
		$email = '';

		if (isset($_POST['user_login']))
			$username = $_POST['user_login'];

		if (isset($_POST['email']))
			$email = $_POST['email'];

		// Lockout this user

		BotBanish_StoreWPUser($user_id);

		BotBanishClient(BOTBANISH_HIDDEN_LOCKOUT, $username, $email, true);
		return;
	}

	// Check the new user and delete if an abuser

	BotBanish_StoreWPUser($user_id);

	BotBanishClient(false, $_POST['user_login'], $_POST['email'], false);	// Will not return if a bad user
}

function BotBanishClientRegisterPost($sanitized_user_login, $user_email, $errors) {

	// Check the posting user

	BotBanishClient(false, $sanitized_user_login, $user_email, false);
}

function BotBanishAuthenticate($user_obj) {

	// Before user logs into WordPress

	BotBanishLoadSettings();

	if (isset($user_obj->user_login)){

		BotBanishClient(false, $user_obj->user_login, $user_obj->user_email, false);	// Check for a bad user logon attempt
		return $user_obj;

	}else{

		if (isset($user_obj->errors['invalid_username']) || !empty($username)){
			BotBanishClient(false, $username, $username, false);	// Check for a bad username / email ($username can be username or email address)
			return $user_obj;
		}
	}

	BotBanishClient(false, '', '', false);	// let's log this login attempt
	return $user_obj;
}

function BotBanish_GetWPUser(){

	$user_id = isset($_SESSION['user_delete']) ? $_SESSION['user_delete'] : 0;

	if (isset($_SESSION['user_delete']))
		unset($_SESSION['user_delete']);

	return $user_id;
}

function BotBanish_StoreWPUser($user_id){

	$_SESSION['user_delete'] = $user_id;
}

function BotBanish_Authenticate_User( $user, $password ) {

	// Before user logs into WordPress

	BotBanishLoadSettings();
	BotBanishClient(false, '', '', false);	// let's log this login attempt
	return $user;
}

function BotBanish_ValidateContact($args) {

    //$to = $args['to'];
    $subject = $args['subject'];
    $message = $args['message'];
    //$args['headers']
    //$args['attachments']

	$eliminate = array(PHP_EOL, "\n", chr(10), chr(13), '.', ' ');
	$forbidden = array('https://', 'http://', '@', 'bit.ly', 'rb.gy');
		
	// Contact Pages
	
	if (stripos($subject, 'Contact from') === false)
		return $args;

	BotBanishLoadFunctions();

	$str = str_ireplace('</b><br />', '=>', $message);
	$str = str_ireplace(array(PHP_EOL, "\n\n", "Your "), '~', $str);
	$str = strip_tags($str);
	$str = str_ireplace('</li>', "\n", $str);
	$str = trim($str);
	$arr = explode('~', $str);
	$arr_new = array();
	
	foreach ($arr as $key => $value) {

		$tmp_array = explode('=>', $value);
		$name = isset($tmp_array[0]) ? strtolower(trim($tmp_array[0])) : '';
		$text = isset($tmp_array[1]) ? trim($tmp_array[1]) : '';
		
		if (!empty($name)) {
			
			$arr_new[$name] = $text;
		
			if ($name == 'message') {
				
				$arr_new[$name] = implode("\n", $arr);
				break;
			}
		}
		
		$arr[$key] = '';
	}
	
	$sub = str_ireplace($eliminate, '', $arr_new['subject']);
	$msg = str_ireplace($eliminate, '', $arr_new['message']);
	
	if ($sub == $msg) {
		
		BotBanishLogError('Subject and Message are the same:' . PHP_EOL . $arr_new['subject'] . PHP_EOL . PHP_EOL);
		$args = BotBanishRejectMessage($args);
	}
	
//	BotBanishLogError(PHP_EOL . PHP_EOL . 'WordPress $arr_new["message"]: ' . var_export($arr_new['message'], true) . PHP_EOL . PHP_EOL);

	if (!empty($arr_new['message'])) {

		$mag = str_ireplace($eliminate, '', $arr_new['message']);
		$len = strlen($msg);
		$newlen = strlen(str_ireplace($forbidden, '', $msg));
		
		if ($len != $newlen) {
			
			BotBanishLogError('Message has forbidden items:' . PHP_EOL . trim($arr_new['message'], chr(10)) . PHP_EOL . PHP_EOL);
			$args = BotBanishRejectMessage($args);
		}
	}

    return $args;
  }
  
  function BotBanishRejectMessage($args){
	  
	$args['to'] = '';
	$args['subject'] = '';
	$args['message'] = '';
	$args['headers'] = '';
	$args['attachments'] = '';
	
	$ip = BotBanishGetIP();
	$boturl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$boturl = empty($boturl) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $boturl;
	$domain_name = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	BotBanishClientIPVerify($ip, $domain_name, 99, strtotime('now'), true, $boturl);
	wp_redirect('https://goggle.com');
	exit();
	
	return $args;
  }
?>