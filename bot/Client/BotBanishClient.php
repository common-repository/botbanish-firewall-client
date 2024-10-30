<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Client Site Access Protection Script
// Date: 06/01/2024
//
// Function: To monitor and control access to the system
//
// Usage: BotBanishClient($ForceLockout = false, $username = '', $email = '', $remove = false, $lock_ip = '', $lock_add = false)
//
// $ForceLockout	- true / false - Force $username or $email to be locked out in BotBanish
// $username		- Users name to lock out
// $email			- Email to lock out
// $remove			- To remove $username or $email from BotBanish database
// $lock_ip 		- IP to place in or remove from .htaccess file
// $lock_add 		- true = Add IP / false = Remove IP
///////////////////////////////////////////////////////////////////////////////////////////////////////
	
function BotBanishClient($ForceLockout = false, $username = '', $email = '', $remove = false, $lock_ip = '', $lock_add = false) {
	
	global $client_info, $BotBanishText;

	if (defined('BOTBANISH_ACTIVE') && (BOTBANISH_ACTIVE == false))
		return;

	ini_set('memory_limit', '512M');	// Insure we have enough memory for large data

	if (empty($client_info))
		$client_info = $_SERVER;	// Store client information if not already present

	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs.php';	// All systems need this file
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_HTACCESS.php';

	set_error_handler('BotBanishErrorHandler');

	BotBanishCleanUpClientTable(); // Cleanup IP information
	BotBanishHTACCESSLockIP($lock_ip, $ForceLockout, $lock_add);

	// WordPress

	if (defined('ABSPATH'))
		require_once(BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_WordPress.php');

	// SMF

	if (isset($GLOBALS['sourcedir']))
		BotBanishSetLanguage();

	// OpenCart

	if (defined('DIR_APPLICATION'))
		require_once(BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_OpenCart.php');

	// Websites

	if (!defined('DIR_APPLICATION') && !isset($GLOBALS['sourcedir']) && !defined('ABSPATH')) {

		// Only define timezone on a stand-alone website installation

		require_once(BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_Website.php');
		date_default_timezone_set(BOTBANISH_TIMEZONE);
	}

	BotBanishHTMLHoneyPotCreate();	// Create our HoneyPot html file (if it does not exist)

	// Proceed normally

	$ip = BotBanishGetIP();
	$domain_name = BotBanishGetDomainName($ip);

	// Check if we are processing an Error Document Redirect

	$client_info['DOCERROR'] = isset($client_info['REDIRECT_STATUS']) ? $client_info['REDIRECT_STATUS'] : 0;

	if (!empty($client_info['DOCERROR'])) {
		$client_info['DOCERROR_CAUSE'] = isset($client_info['REQUEST_URI']) ? $client_info['REQUEST_URI'] : '';
		$client_info['DOCERROR_CAUSE'] = empty($client_info['DOCERROR_CAUSE']) && isset($client_info['REDIRECT_QUERY_STRING']) ? $client_info['REDIRECT_QUERY_STRING']  : $client_info['DOCERROR_CAUSE'];
	}

	$boturl = '';

	if (in_array($client_info['DOCERROR'], BotBanishSafeUnserialize(BOTBANISH_DOC_ERRORS))) {

		$boturl = isset($client_info['REDIRECT_QUERY_STRING']) ? $client_info['REDIRECT_QUERY_STRING'] : $client_info['QUERY_STRING'];
		$sStr = isset($client_info['REDIRECT_SCRIPT_NAME']) ? $client_info['REDIRECT_SCRIPT_NAME'] : '';
		$useragent = isset($client_info['REDIRECT_HTTP_USER_AGENT']) ? $client_info['REDIRECT_HTTP_USER_AGENT'] : '';

	} else {

		$boturl = $client_info['QUERY_STRING'];
		$sStr = isset($client_info['SCRIPT_NAME']) ? $client_info['SCRIPT_NAME'] : '';
		$useragent = isset($client_info['HTTP_USER_AGENT']) ? $client_info['HTTP_USER_AGENT'] : '';
	}


	$session_ip = isset($_SESSION['BotBanish'][$ip]) ? $_SESSION['BotBanish'][$ip] : $ip;
	$session_first_hit = isset($_SESSION['BotBanish']['ip_first_hit']) ? $_SESSION['BotBanish']['ip_first_hit'] : BotBanishGetCorrectTimeB();
	$session_hit_count = isset($_SESSION['BotBanish']['ip_hit_count']) ? $_SESSION['BotBanish']['ip_hit_count'] : 1;
	
	BotBanishDatabaseLockIP($lock_ip, $ForceLockout, $domain_name, $ip, $boturl, $session_first_hit, $session_hit_count);

	BotBanishDatabaseHTACCESSFlushCache();			// Add info to .htaccess files

	BotBanishMaintainLogs();		// Remove excess log files

	if ($ip === '')
		return false;	// Nothing we can do here!!!	

	if (BotBanishCheckForClientIPBlockOverride($ip, 'bbc_botbanishclient_ip_dnb')) // If we should not block IP, do nothing
		return BotBanishExit(false);

	$name = BotBanishGetDomainOrigin($ip);

	if (!empty($name)) {

		if (BotBanishCheckForClientDomainBlockOverride('bbc_botbanishclient_domain_good', $name)) // If we should not block domain, do nothing
			return BotBanishExit(false);
	}

	// Check for information after index.php?

	$row = array();

	$useragent = BotBanishFixUserAgent($useragent); // Try to convert and remove extraneous information

	// If client wants a spider/bot to be good, then do it
	
	if (BotBanishCheckForClientBadBotOverride($useragent))
		return BotBanishExit(false);

	// If client wants a spider/bot to be bad, then do it

	if (BotBanishCheckForClientGoodBotOverride($useragent)) {

		// If this bot was previously overridden, don't attempt again. Just discard it
		
		if (BotBanishClientIPVerifyLocal($ip, $domain_name))
			BotBanishRedirectExit();
	
		$row['bot_ip'] = $ip;
		$row['user_agent'] = $useragent;
		$row['forcelockout'] = BOTBANISH_BAD_BOT_LOCKOUT;
		BotBanishHTACCESSUpdate($row, 'bot');			// If this was a bad bot lockout, let do it! (SetEnvIfNoCase)
		BotBanishClientIPInsertLocal($row, $ip);		// Update local IP Database

		$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_bot'], $_SERVER['SERVER_NAME']);
		$body = sprintf($BotBanishText['BotBanishClient_mail_body_bot'], $ip, $useragent);
		BotBanishSendMail($subject, $body, $ip);
		BotBanishRedirectExit();
	}

	if (!empty($boturl)) {

		if (strpos($boturl, 'XDEBUG') === 0) {

			$pos = strripos($sStr, '/');

			if ($pos == 0)
				$pos = strripos($sStr, '/');

			if ($pos > 0)
				$boturl = trim(substr($sStr, $pos + 1));	// No index.php? Use the file name or folder name
			else
				$boturl = '';
		}

	} else {

		// If not an index.php request, just check the URL

		if (in_array($client_info['DOCERROR'], BotBanishSafeUnserialize(BOTBANISH_DOC_ERRORS)))
			$boturl = isset($client_info['REDIRECT_URL']) ? $client_info['REDIRECT_URL'] : '';
		else
			$boturl = isset($client_info['REQUEST_URI']) ? $client_info['REQUEST_URI'] : '';

		// Check for an invalid URL to search for

		if (strpos($boturl,'"') !== false)
			$boturl = '';
	}

	$lockitout = BotBanishClientCheckUserRegistrationAttempt();

	// If we are forcing a lockdown ignore all else

	$lockitout = $ForceLockout == true ? BOTBANISH_IP_LOCKOUT : $lockitout;

	if (($boturl != '\\') && ($boturl != '/') && !empty($boturl)) {

		if (BotBanishCheckForClientURLOverride($boturl))  // If we should not check url, do nothing
			return BotBanishExit(false);
	}
	
	// OK, were seeking to track this bugger...

	if (!isset($_SESSION['BotBanish'][$ip]))
		$_SESSION['BotBanish'][$ip] = 1;
	else
		$_SESSION['BotBanish'][$ip]++;	// Track number of hits this session

	// Start of Test Code
	// If we are testing do not allow the hit count tests to work

	if (defined('BOTBANISH_TEST'))
		$_SESSION['BotBanish'][$ip] = 0;

	// End of Test Code

	if (!isset($_SESSION['BotBanish']['ip_first_hit']))
		$_SESSION['BotBanish']['ip_first_hit'] = BotBanishGetCorrectDateTimeB();

	// If a blocked country, log it and get rid of it
	
	if (BotBanishCheckForBlockedCountry($ip)) {
		
		$row['bot_ip'] = $ip;
		$row['user_agent'] = $useragent;
		$row['forcelockout'] = BOTBANISH_COUNTRY_LOCKOUT;
		BotBanishClientRecordInfo($row, $ip, $useragent, $domain_name, true);
		BotBanishRedirectExit();
	}
	
	if ($remove) {
		
		BotBanishClientRemoveIP($session_ip, $domain_name, $session_hit_count, $session_first_hit, $username, $email);
		return BotBanishExit(false);
	}

	// Check the local database for IP
	// If we are checking or setting username / email lockouts, we skip the IP check
	// because we may need to record the username/email combination.

	if (empty($email)) {

		if (BotBanishClientIPVerifyLocal($ip, $domain_name))
			return BotBanishExit(false);
	}

	if (!empty($name)) {

		if (BotBanishCheckForClientDomainBlockOverride('bbc_botbanishclient_domain_bad', $name))
			$row['forcelockout'] = BOTBANISH_BAD_DOMAIN_LOCKOUT; // If found in the table, Force a local lockout!!
	}

	if (!empty($email)) {

		// If username/email combo does not exist in the database the server
		// will try email only. If that appears in the database the username/email
		// combo will be inserted for lockouts.

//		$lockitout = (stripos($email, $username) != false) ? true : $lockitout;
		$row = BotBanishClientEmailVerify($ip, $domain_name, $session_ip, $session_first_hit, $lockitout, $username, $email);

		$lockitout = isset($row['forcelockout']) ? $row['forcelockout'] : 0;
		$lockitout = BotBanishClientRecordInfo($row, $ip, $useragent, $domain_name, $lockitout, $username, $email);

	} else {

		$row = BotBanishClientIPVerify($ip, $domain_name, $session_hit_count, $_SESSION['BotBanish']['ip_first_hit'], $lockitout, $boturl);
	
		if (isset($row) && $row !== false) {

			if (isset($row['fatal_error']))
				return BotBanishExit(false);

			if (empty($row) || !is_array($row))
				return BotBanishExit(false);

			$lockitout = BotBanishClientRecordInfo($row, $ip, $useragent, $domain_name, $lockitout);
		}
	}
	
	BotBanishClient_Notify($row, $ip);	// notify client a message if one is received from server

	// Problem? Process Diagnostics

//	if (isset($row['diag']))
//		BotBanishClient_Diagnostics($row['diag'], $domain_name);

	// On error; log it and exit!!!

	if (isset($row['error'])) {
		
		BotBanishLogError($row['error']);
		return BotBanishExit(false);
	}

	if ($lockitout != 0 || $ForceLockout)
		BotBanish_Fatal_Error('registration_disabled', true);	// Stop this guy in his tracks! You are not welcome here!

	BotBanishExit(false);
}

function BotBanishDatabaseLockIP($lock_ip, $lockitout, $domain_name, $ip, $boturl, $session_first_hit, $session_hit_count) {
	
	$row = BotBanishClientIPVerify($lock_ip, $domain_name, $session_hit_count, $session_first_hit, BOTBANISH_IP_LOCKOUT, $boturl);
}

function BotBanishClientRecordInfo($row, $ip, $useragent, $domain_name, $ForceLockout, $username = '', $email = '')
{
	global $BotBanishText;

	BotBanishBlockedIPRangeCheck($ip);
	BotBanishIPCheckRange($ip, $useragent, $domain_name);
	BotBanishLogTrace($ip);

	// Has this IP been seen before?
	// If not then the server has already taken care of it and we need to update our IP list

	if (isset($row) && is_array($row)) {

		if (!isset($row['hit_count']))	// If no hit_count; no row was returned
			return 0;

		if (!isset($row['domain']))
			$row['domain'] = '';

		if (!isset($row['ip_hit_count']))
			$row['ip_hit_count'] = $_SESSION['BotBanish']['ip_hit_count'];

		$_SESSION['BotBanish'][$ip] = $ip;

		$row['user_agent'] = isset($row['user_agent']) ? $row['user_agent'] : '';
		$row['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) && empty($row['user_agent']) ? $_SERVER['HTTP_USER_AGENT'] : $row['user_agent'];

		$_SESSION['BotBanish']['ip_hit_count'] = intval($row['ip_hit_count']);
		unset($row['ip_hit_count']);
		unset($row['ip_first_hit']);

		// Possible BOT/USER attempting to brute force attack the system?

		if (($row['hit_count'] >= BOTBANISH_MAX_HIT_COUNT) || $ForceLockout) {

			// Kick them off the system (indicate to create a "deny from" ip entry in the .htaccess file)
			// and send an email to the administrator. Keep logging until we say we had had enough
			// When we have had enough we will update the .htaccess file to prevent this IP from accessing the system totally

			// Check our IP list to see if we created an entry in the .htaccess file

			$deny = BotBanishClientLocalSelectDeny($ip);

			// Create entry in .htaccess file if we have not done so already

			// We only do one firewall rule at a time. Either a bot SetEnvIfCase lockout or an IP lockout sometimes BOTH!!!

			switch ($row['forcelockout']) {

				case BOTBANISH_BAD_BOT_LOCKOUT:

					// Check to see if the client wants to override the servers decision for this bad bot

					if (!BotBanishCheckForClientBadBotOverride(trim($row['user_agent']))){

						// Lockout due to a known bad bot

						BotBanishHTACCESSUpdate($row, 'bot');		// If this was a bad bot lockout, let do it! (SetEnvIfNoCase)

						$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_bot'], $_SERVER['SERVER_NAME']);
						$body = sprintf($BotBanishText['BotBanishClient_mail_body_bot'], $ip, $useragent);
					}
					break;

				case BOTBANISH_HITCOUNT_LOCKOUT:

					// Lockout due to excessive repetitive hits by user/bot
					// Don't block user agent if we did not replace it

					BotBanishHTACCESSUpdate($row, 'bot');		// If this was a bad bot lockout, let do it!

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_res'], $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_res'], $ip, $useragent);
					break;


				case BOTBANISH_HIDDEN_LOCKOUT:

				// Lockout due to a honeypot link trap

					BotBanishHTACCESSUpdate($row, 'ip');		// Lock out IP

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_bot'], 'Honeypot Bot - ' . $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_bot'], $ip, $useragent);
					break;

				case BOTBANISH_EMAIL_USERNAME_LOCKOUT:

					// Lockout due to a username & email combo or email or username

					BotBanishHTACCESSUpdate($row, 'ip');

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_email'], 'Invalid Username / Email - ' . $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_email'], $ip, $useragent, $username, $email);
					break;

				case BOTBANISH_UNKNOWN_BOT_LOCKOUT:

					// Lockout due to a unknown bot check

					BotBanishHTACCESSUpdate($row, 'ip');		// Lock out IP

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_unknown_bot'], 'Unknown Bot - ' . $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_unknown_bot'], $ip, $useragent);
					break;

				case BOTBANISH_SPOOF_BOT_LOCKOUT:

					// Lockout due to bot attempting to spoof being a known good bot

					BotBanishHTACCESSUpdate($row, 'ip');	// Add this guy to the firewall deny list...

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_spoof'], $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_spoof'], $ip, $useragent, $row['domain']);
					break;

				case BOTBANISH_BAD_DOMAIN_LOCKOUT:

					// Lockout due to ip coming from a known bad domain

					BotBanishHTACCESSUpdate($row, 'ip');	// Add this guy to the firewall deny list...

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_domain'], $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_domain'], $ip, $useragent, $row['domain']);
					break;

				case BOTBANISH_COUNTRY_LOCKOUT:

					// Regular IP lockout

					BotBanishHTACCESSUpdate($row, 'ip');	// Add this guy to the firewall deny list...

					$geo_data = BotBanishGetGeoData($row['bot_ip']);
					$country_info = BotBanishGetCountryData($geo_data);
					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_country'], $country_info['country'], $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_country'], $ip, $country_info['country']);
					break;
					
				// When in doubt, LOCK IT OUT!!!
				// Client may not have caught up to the servers lockout codes.
				// So we lock it out by the ip and send the generic message

				case BOTBANISH_IP_LOCKOUT:
				case BOTBANISH_INVALID_REQUEST_LOCKOUT:
				case BOTBANISH_EMPTY_USERAGENT_LOCKOUT:
				case BOTBANISH_IP_RANGE_LOCKOUT:
				case BOTBANISH_ABUSE_LOCKOUT:
				case BOTBANISH_URL_LOCKOUT:
				default:

					// Regular IP lockout

					BotBanishHTACCESSUpdate($row, 'ip');	// Add this guy to the firewall deny list...

					$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_ip'], $_SERVER['SERVER_NAME']);
					$body = sprintf($BotBanishText['BotBanishClient_mail_body_ip'], $ip, $useragent);
					break;
			}

			if (isset($row['diag']))
				unset($row['diag']);

			BotBanishClientIPInsertLocal($row, $ip);		// Update local IP Database
			BotBanishLogTraceResponse($row, $ip, $row['forcelockout']);

			// Notify the administrator and give the BOT the bum's rush!!!

			if (isset($subject))
				BotBanishSendMail($subject, $body, $ip);

			return $row['forcelockout'];

		} else {

			// Check to see if the client will turn a good bot into a bad one

			if (BotBanishCheckForClientGoodBotOverride($row['user_agent'])){

				BotBanishHTACCESSUpdate($row, 'bot');		// If this was a bad bot lockout, let do it! (SetEnvIfNoCase)
				BotBanishHTACCESSUpdate($row, 'ip');		// Lock out IP too! (maybe overkill...)

				$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_bot'], $_SERVER['SERVER_NAME']);
				$body = sprintf($BotBanishText['BotBanishClient_mail_body_bot'], $ip, $useragent);

				BotBanishSendMail($subject, $body, $ip);

				return BOTBANISH_BAD_BOT_LOCKOUT;
			}
		}
	}

	return 0;
}

function BotBanishClientIPVerifyLocal($ip, $domain_name)
{
	$row = BotBanishClientLocalSelectHitCount($ip);

	if (empty($row))
		return false;

	if (is_array($row)){

		if (BotBanishCheckForClientIPBlockOverride($ip, 'bbc_botbanishclient_ip_dnb'))
			return false;

		BotBanish_Fatal_Error('registration_disabled', true);	// If this ip is in our database then get rid of them!
	}

	return true;
}

function BotBanishClientIPVerify($ip, $domain_name, $ip_hit_count, $ip_first_hit, $lockitout, $boturl)
{
	$row = array();
	$row['action'] = 'verify';
	$row['type'] = 'ip';
	$row['apikey'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'Free';
	$row['bot_ip'] = $ip;
	$row['contact'] = BOTBANISH_WEBMASTER_EMAIL;
	$row['domain'] = $domain_name;
	$row['ip_hit_count'] = $ip_hit_count;
	$row['ip_first_hit'] = $ip_first_hit;
	$row['forcelockout'] = $lockitout;
	$row['version'] = BOTBANISH_VERSION;
	$row['version_no'] = BOTBANISH_VERSION_NO;
	$row['boturl'] = $boturl;

	BotBanishAddClientData($row);

	$data = BotBanishURLFormatData($row);

	$row = BotBanishProcessRequest($data, BOTBANISH_REQUEST_METHOD);
	return $row;
}

function BotBanishClientEmailVerify($ip, $domain_name, $ip_hit_count, $ip_first_hit, $ForceLockout, $username, $email) {

	//-----------------------------------------------------------------
	// This area is for email processing of the BOT/USER
	//-----------------------------------------------------------------

	$row = array();
	$row['action'] = 'verify';
	$row['type'] = 'email';
	$row['apikey'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'free';
	$row['bot_ip'] = $ip;
	$row['domain_name'] = $domain_name;
	$row['ip_hit_count'] = $ip_hit_count;
	$row['ip_first_hit'] = $ip_first_hit;
	$row['forcelockout'] = $ForceLockout != 0 ? BOTBANISH_EMAIL_USERNAME_LOCKOUT : 0;
	$row['username'] = $username;
	$row['email'] = $email;
	$row['version'] = BOTBANISH_VERSION;
	$row['version_no'] = BOTBANISH_VERSION_NO;

	BotBanishAddClientData($row);

	$data = BotBanishURLFormatData($row);

	$row = BotBanishProcessRequest($data, BOTBANISH_REQUEST_METHOD);
	return $row;
}

function BotBanishClientCheckUserRegistrationAttempt() {

	// Check if we are on the registration page and the user has clicked 'Submit'

	if (isset($GLOBALS['sourcedir'])){

		// NOTE: Should we check for redirects here?

		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

			if (isset($_POST['regSubmit']) && ($_POST['regSubmit'] === 'Register')){

				// This is our hidden field on the registration page that only bots can see and fill out

				if (isset($_POST['name'])){

					if (!empty($_POST['name'])){
						return BOTBANISH_HIDDEN_LOCKOUT;	// We got a bot!!! Let's stop it!
					}
				}
			}
		}
	}

	return 0;
}

function BotBanishClient_Diagnostics($diag_code, $domain_name) {

	switch ($diag_code){

		case 1:

			BotBanishCollectData($domain_name);
			break;

		default:
			break;
	}
}

function BotBanishClientRemoveIP($ip, $domain_name, $ip_hit_count, $ip_first_hit, $username, $email) {

	$row = array();
	$row['action'] = 'verify';
	$row['type'] = 'logon';
	$row['apikey'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'free';
	$row['bot_ip'] = $ip;
	$row['contact'] = BOTBANISH_WEBMASTER_EMAIL;
	$row['domain'] = $domain_name;
	$row['ip_hit_count'] = $ip_hit_count;
	$row['ip_first_hit'] = $ip_first_hit;
	$row['forcelockout'] = 0;
	$row['version'] = BOTBANISH_VERSION;
	$row['version_no'] = BOTBANISH_VERSION_NO;

	BotBanishAddClientData($row);

	$data = BotBanishURLFormatData($row);

	$row = BotBanishProcessRequest($data, BOTBANISH_REQUEST_METHOD);
	return $row;
}

function BotBanishClient_Notify($row, $ip) {

	if (isset($row['notify']) && !empty($row['notify'])) {

		// Send message from server to user

		global $BotBanishText;

		$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_server'], $_SERVER['SERVER_NAME']);
		$body = sprintf($BotBanishText['BotBanishClient_mail_body_server'], $row['notify']);
		BotBanishSendMail($subject, $body, $ip);
	}
}

function BotBanishCleanUpClientTable() {
	global $BotBanishSettings;
	
	if (defined('DIR_APPLICATION'))
		BotBanishCleanCustomerTable();
	
	// This should be run once a day to cleanup the IP table. IP lockouts are for 1 day only. This is because shared IP users can get locked out
	// by some othe use with the same IP. No other way to achieve IP lockout that is fair. If the IP locout needs to be permanent it will need
	// to be done in the .htaccess file.

	if (!defined('BOTBANISH_CLIENT_CLEANUP_RUN') || BOTBANISH_CLIENT_CLEANUP_RUN == 0)
		return;
	
	if (!defined('BOTBANISH_CLIENT_CLEANUP') || BOTBANISH_CLIENT_CLEANUP == 0)
		return;
	
	$datediff = BotBanishGetCorrectDateTimeB() - strtotime(BOTBANISH_CLIENT_CLEANUP);
	$days = round($datediff / (60 * 60 * 24));
	if ($days < BOTBANISH_CLIENT_CLEANUP_DAYS) return;
	
	$statements = array();
	
	$statements[] =
		// Delete any old IP information from the IP table that we have not seen in over 24 hours. We do this to preserve the checking integrity
		// so that if an attacking BOT/USER has stopped using the IP we will allow future legitimate users to use it on newer registered systems.
		// All the older systems will have locked the IP out so we can reset our system to be up to date.
		
		'DELETE FROM `' . 'bbc_botbanishclient_ip` WHERE DATEDIFF(NOW(), FROM_UNIXTIME(`last_hit`)) > ' . BOTBANISH_CLIENT_CLEANUP_DAYS;

	if (defined('ABSPATH'))
		$GLOBALS['override'] = true;
	
	foreach ($statements as $sql)
		BotBanishExecuteSQL($sql);
		
	if (defined('ABSPATH'))
		$GLOBALS['override'] = false;
	
	BotBanishCleanupLogs();
	$BotBanishSettings['BOTBANISH_CLIENT_CLEANUP_RUN'] = true;
	$BotBanishSettings['BOTBANISH_CLIENT_CLEANUP'] = BotBanishGetCorrectDateTime();
}

function BotBanishCleanCustomerTable() {
	
	// We need to delete customer accounts in OpenCart that have been created using bogus information
	
	$table = DB_PREFIX . 'customer';
	$sql = 'SELECT `customer_id`, LENGTH(`lastname`) - LENGTH(`firstname`) AS newlen FROM `' . $table . '` WHERE  LOCATE(`firstname`, `lastname`) = 1';
	$rows = BotBanishExecuteSQL($sql);
	
	if (is_array($rows) && !empty($rows)) {
		
		foreach ($rows as $row) {
			
			if ($row['newlen'] == 2) {
				
				$sql = 'DELETE FROM `' . $table . '` WHERE `customer_id` = ' . $row['customer_id'];
				BotBanishExecuteSQL($sql);
			}
		}
	}
}

function BotBanishCleanupLogs() {
	
	// This will clean up log files that are out of date
	
	$files = glob(str_replace(array('\\', '//'), '/', BOTBANISH_LOGS_DIR) . '*.log');
	$date = BotBanishGetCorrectDateTimeB();
	
	foreach ($files as $file) {
		
		if (is_dir($file))
			continue;
		
		$filename_parts = explode('_', $file);
		$part = $filename_parts[count($filename_parts)-1];
		$file_date = trim(str_ireplace('.log', '', $part));
		
		$datediff = $date - strtotime($file_date);
		$days = round($datediff / (60 * 60 * 24));
		
		if ($days > BOTBANISH_CLIENT_CLEANUP_DAYS)
			unlink($file);
		else
			break;
	}
}
?>