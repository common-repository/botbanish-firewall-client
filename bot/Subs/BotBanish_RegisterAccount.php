<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Registration Protection
// Date: 05/24/2024
// Usage: BotBanish_RegisterAccount.php
//		Use with hidden field from inside HTML code on Registration Pages where real users cannot
//		possibly enter text in the hidden field.
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

	if (defined('ABSPATH')) {

		$filename = BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
	}

	require_once $filename;

	require_once (BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');

	$ip = BotBanishGetIP();

	// Attempt to make sure that the domain_name contains something

	$domain_name = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	$domain_name = empty($domain_name) && isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $domain_name;
	$domain_name = !empty($domain_name) ? str_replace('www.', '', $domain_name) : $ip;

	// End domain_name Attempt

	$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

	$row = array();
	$row['action'] = 'verify';
	$row['type'] = 'spy';
	$row['bot_ip'] = $ip;
	$row['apikey'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'free';
	$row['domain'] = $domain_name;
	$row['boturl'] = $_SERVER['REQUEST_URI'];
	$row['version'] = BOTBANISH_VERSION;
	$row['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$row['forcelockout'] = BOTBANISH_HIDDEN_LOCKOUT;

	$data = BotBanishURLFormatData($row);

	// Nothing should get returned
	BotBanishProcessRequest($data, BOTBANISH_REQUEST_METHOD);

	$row = array();
	$row['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$row['forcelockout'] = BOTBANISH_HIDDEN_LOCKOUT;

	// Lockout due to a honeypot link trap

	$date = BotBanishGetCorrectDateTimeB();
	$row['hit_count'] = 98;
	$row['first_hit'] = $date;
	$row['last_hit'] = $date;

	$row['deny'] = 1;
	$row['bot_ip'] = $ip;
	BotBanishHTACCESSUpdate($row, 'ip');		// Lock out IP too! (maybe overkill...)

	$row['domain_name'] = $domain_name;
	BotBanishClientIPInsertLocal($row, $ip);	// Update local IP Database

	// Notify the administrator

	$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_bot'], 'Honeypot Bot - ' .$domain_name);
	$body = sprintf($BotBanishText['BotBanishClient_mail_body_bot'], $ip, $useragent);

	BotBanishSendMail($subject, $body);

	// Give the BOT the bum's rush!!!

	header('Location: https://www.google.com/');   // Send this bot elsewhere!!!
	BotBanishExit(true);
?>