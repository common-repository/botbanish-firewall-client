<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Common Subroutines
// Date: 06/01/2024
// Usage:
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishSendNotification($row, $entry) {

	global $BotBanishText;

	if (isset($GLOBALS['sourcedir']))
		loadLanguage('BotBanish/BotBanishLanguage');

	if (BOTBANISH_SEND_EMAIL_ALERTS && BOTBANISH_CLIENT) {

		$subject = sprintf($BotBanishText['BotBanishClient_mail_subject_verify_bot'], $_SERVER['SERVER_NAME']);
		$body = sprintf($BotBanishText['BotBanishClient_mail_body_verify_bot'], $entry, $row['bot_ip'], $row['user_agent']);
		BotBanishSendMail($subject, $body);
	}
}

function BotBanishSendPostData($url, $data, $clearbefore = false, $clearafter = false, $report_errors = true) {

	global $sourcedir, $BotBanishText;

	if ($clearbefore)
		BotBanishFlushOutputBuffer();

	if (function_exists('curl_init'))	{

		//open connection

		$dir = dirname(__FILE__);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'BotBanish');

		//execute post

		$returned_data = curl_exec($ch);

	} else {

		if (isset($GLOBALS['sourcedir'])){

			require_once($sourcedir . '/Subs-Package.php');
			$returned_data = fetch_web_data($url . '?' . $data);

		}else{

			$returned_data = file_get_contents($url . '?' . $data);
		}
	}

	if (($report_errors) || BOTBANISH_DEBUG_TRACE_ALL){

		if ($returned_data === false) {

			$datasent = !empty($data) ? $data : 'none';

			if (!empty($ch)) {

				$datasent .= PHP_EOL . 'Curl Error: ' . curl_error($ch) . PHP_EOL . 'Curl Error Code: ' . curl_errno($ch);
				BotBanishLogError(sprintf($BotBanishText['BotBanishClient_CollectionFailed'], $url, $datasent));

			} else {

				$datasent = PHP_EOL . 'Regular Error: ' . PHP_EOL . $datasent;
				BotBanishLogError(sprintf($BotBanishText['BotBanishClient_CollectionFailed'], $url, $datasent));
			}
		}
	}

	if ($clearafter)
		BotBanishFlushOutputBuffer();

	return $returned_data;
}

function BotBanishSendPostDataHTTP($url, $data, $clearbefore = false, $clearafter = false, $report_errors = true) {

	global $sourcedir, $BotBanishText;

	if ($clearbefore)
		BotBanishFlushOutputBuffer();

	$data = array('key1' => 'value1', 'key2' => 'value2');

	$options = array(
	  'http' => array(
		'header'  => "Content-type: application/x-www-form-urlencoded",
		'method'  => 'POST',
		'content' => http_build_query($data)
	  )
	);
	$context  = stream_context_create($options);
	$returned_data = file_get_contents($url, false, $context);

	if ($clearafter)
		BotBanishFlushOutputBuffer();
	
	return $returned_data;
}

function BotBanishRetrieveURLData($url) {

	global $sourcedir;

	BotBanishFlushOutputBuffer();

	if (function_exists('curl_init'))	{
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
	    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	    curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'BotBanish');
		curl_setopt($ch, CURLOPT_TIMEOUT, 400);

		$returned_data = curl_exec($ch);
		curl_close($ch);

	} else {

		if (isset($GLOBALS['sourcedir'])){
			require_once($sourcedir . '/Subs-Package.php');
			$returned_data = fetch_web_data($url);
		}else{
			$returned_data = file_get_contents($url);
		}
	}

	BotBanishFlushOutputBuffer();
	return $returned_data;
}

function BotBanishProcessRequest($data, $send_type = 'GET') {

	global $BotBanishText;

	switch ($send_type){

		case 'POST':
			$returned_data = BotBanishSendPostData(BOTBANISH_SERVER_URL, $data);
			break;

		case 'GET':
		default:
			$returned_data = BotBanishSendURLData(BOTBANISH_SERVER_URL, $data);
			break;
	}

	$rows = BotBanishExtractData($returned_data);
	BotBanishCheckForReturnedErrors($returned_data, $rows, BOTBANISH_SERVER_URL);

	// If client license has expired alert client and exit
/*
	if (isset($rows['expired'])) {

		echo '<script>
		alert(' . $BotBanishText['demo_expired'] . ');
		</script>';
		BotBanishExit(true);
	}
*/
	return $rows;
}

function BotBanishCheckForReturnedErrors($returned_data, $rows, $url) {

	global $BotBanishText;

	// Check for notices and error messages from Server
	// Check for PHP Server Issues

	$err_array = array('not found', '( ! )');

	foreach ($err_array as $element)
	{
		if (stripos($returned_data, $element) !== false) {
			
			$str = $BotBanishText['botbanish_url'] . ': ' . BOTBANISH_SERVER_URL . PHP_EOL . PHP_EOL . $BotBanishText['botbanish_fatal_error'] . ': ' . BOTBANISH_VERSION;
			BotBanishLogError($str . PHP_EOL . $returned_data);
			$row = array();
			$row['fatal_error'] = $str . PHP_EOL . $returned_data;
			return BotBanishReturnData($row);
		}
	}

	// Check for BotBanish Server Issues

	$desc = array();
	$msgtypes = array('error', 'msg', 'fatal');

	if (is_array($rows)) {

		foreach ($rows as $key => $value) {

			if (in_array($key, $msgtypes)) {
				$desc[$key] = $value;
				unset($rows[$key]);
			}
		}
	}

	if (isset($desc['error']) && !empty($desc['error'])) {

		$error = sprintf($BotBanishText['botbanish_infobaseError'], BOTBANISH_VERSION . PHP_EOL . $desc['error'] . PHP_EOL . PHP_EOL . 'URL = ' . BOTBANISH_SERVER_URL);
		BotBanishLogError($error);

		$row = array();
		$row['fatal error'] = $error;
		return BotBanishReturnData($row);	// Show the error in the browser
	}
}

function BotBanishExtractData($returned_data) {

	if (!isset($returned_data) || empty($returned_data))
		return false;

	if (($returned_data !== false) && stripos($returned_data, '(!)') === false){

		if ($pos = stripos($returned_data, '|') != false) {

			if ($pos = stripos($returned_data, '<br />') !== false) {

				// Multi-dimensional array (at least two rows MUST exist!)

				$rows = array();
				$lines = explode("<br />", $returned_data);

				foreach ($lines as $line){

					$pos = stripos($line, '|');

					if ($pos > 0){
						$data = '&' . str_replace('|', '&', $line);
						parse_str($data, $rows[]);
					}
				}

			} else {

				// Single dimensional array

				$data = '&' . str_replace('|', '&', $returned_data);
				parse_str($data, $rows);
			}

			return $rows;
		}
	}
}

function BotBanishSendURLData($url, $data, $clearbefore = false, $clearafter = false) {

	global $sourcedir, $BotBanishURL;

	$query = $url . '?' . $data;
	$BotBanishURL = $query;

	if ($clearbefore)
		BotBanishFlushOutputBuffer();

	if (function_exists('curl_init')) {

		$ch = curl_init($query);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$returned_data = curl_exec($ch);
		curl_close($ch);

	} else {

		if (isset($GLOBALS['sourcedir'])) {

			require_once($sourcedir . '/Subs-Package.php');
			$returned_data = fetch_web_data($query);

		} else {

			$returned_data = file_get_contents($query);
		}
	}

	if ($clearafter)
		BotBanishFlushOutputBuffer();

	return $returned_data;
}

function BotBanishFindArrayInStringRaw($string, $cmd_array, $caseSensitive = true) {

	if (empty($string))
		return false;

	$i = 0;

	foreach ($cmd_array as $cmd){

		$pos = $caseSensitive ? strpos($string, $cmd) : stripos($string, $cmd);

		if ($pos !== false)
			break;

		$i++;
	}

	if ($pos === false)
		return false;

	return $cmd;
}

function BotBanishFindArrayInString($string, $cmd_array, $caseSensitive = true) {

	if (empty($string))
		return false;

	$str = $string;

	$stripedString = $caseSensitive ? str_replace($cmd_array, '', $str) : str_ireplace($cmd_array, '', $str);
	return strlen($stripedString) !== strlen($string);
}

function BotBanishArrayCheck($array_type) {

	global $BotBanishText;

	$error_array = array(
		'error' =>  '',
		'valid' => 1
	);

	if (!isset($array_type)){

		$error_array['error'] .= $BotBanishText['BotBanish_NotDefined'];
		$error_array['valid'] = 0;
	}

	if (empty($array_type)){

		$error_array['error'] .= $BotBanishText['BotBanish_NoValue'];
		$error_array['valid'] = 0;
	}

	if (!is_array($array_type)){

		$error_array['error'] .= $BotBanishText['BotBanish_NotArray'];
		$error_array['valid'] = 0;
	}

	if (!$error_array['valid'])
		$error_array['error'] = BotBanishDebugBackTrace();

	if (!empty($error_array['error']))
		return BotBanishReturnData($error_array['error']);

	return $error_array;
}

function BotBanishHTMLHoneyPotCreate() {

	$dirs = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	foreach ($dirs as $dir) {

		$dir = str_ireplace('.htaccess', '', $dir);

		if (file_exists($dir . BOTBANISH_HONEYPOT_FILE_HTML) && (filesize($dir . BOTBANISH_HONEYPOT_FILE_HTML) >= strlen($dir . BOTBANISH_HONEYPOT_HTML)))
			return;

		BotBanishWriteFile($dir . BOTBANISH_HONEYPOT_FILE_HTML, sprintf($dir . BOTBANISH_HONEYPOT_HTML, BOTBANISH_HONEYPOT_URL));
	}
}

function BotBanish_Fatal_Error($error_index, $quit = true) {

	if (!$quit){

		if (isset($GLOBALS['sourcedir'])){

			// SMF

			loadLanguage('Errors', 'BotBanish/BotBanishLanguage', true, true);		// Change SMF error messages to our selected language
			fatal_lang_error($error_index, 'BotBanish');	// Stop this guy in his tracks! You are not welcome here!
		}

		if (defined('ABSPATH')){

			// WordPress

			$user_id = BotBanish_GetWPUser();

			if (!empty($user_id)){
				require_once( BOTBANISH_INSTALL_DIR . 'wp-admin/includes/user.php');
				wp_delete_user($user_id);
			}
		}
	}

	BotBanishExit(false);
	BotBanishRedirectExit();
	exit;
}

function BotBanishGetIP() {

	if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	else
		return '';

	if (!BotBanishCheckIfValidIP($ip))
		return ''; // Not a valid IP address

	$debug_server = array('::1', '127.0.0.1');

	if (in_array($ip, $debug_server)){
		$ip = getHostByName(getHostName());	// This only happens in the debugging environment
	}else{
		if (($ip == '') || (strlen($ip) < 8))
			$ip = getHostByName(getHostName());	// This only happens in the debugging environment
	}

	return $ip;
}

function BotBanishGetDomainName($ip) {
	// Attempt to make sure that the domain_name contains something

	if (!BotBanishCheckIfValidIP($ip)) {
		
		$names = explode('.', $_SERVER['SERVER_NAME']);
		$domain_name = (count($names) > 2) ? $names[count($names) - 2]  . '.' . $names[count($names) - 1] : $_SERVER['SERVER_NAME'];
//		$domain_name = empty($domain_name) ? $_SERVER['HTTP_HOST'] : $domain_name;
//		$domain_name = empty($domain_name) ? $ip : $domain_name;
		
	} else {
		
		$domain_name = $ip;
	}

	// End domain_name Attempt

	return $domain_name;
}

function BotBanishLogSQL($sql) {

	$logit = (defined('BOTBANISH_DEBUG_SQL') && BOTBANISH_DEBUG_SQL) ? true : false;

	if (!$logit)
		return;
	
	if (is_array($sql)) {

		// If an array, this might be an error exception being passed, so record all elements

		$data = '';

		foreach ($sql as $key => $str)
			$data .= $key . ' => ' . $str . PHP_EOL;

	} else {

		$data = $sql;
	}

	$log_array = BotBanishLogGetLocation();

	$filename = $log_array['filename'];
	$filename = str_replace('_Activity_', '_SQLStatements_', $log_array['filename']);

	$str = function_exists('smf_db_initiate') ? 'smcFunc ' : '';

	if ($str == '')
		$str = function_exists('add_filter') ? 'wpdb ' : '';

	if ($str == '')
		$str = 'Native ';

	BotBanishWriteFile($filename, BotBanishGetCorrectDateTime() . ' (' . $str . ')  ' . $data . PHP_EOL . PHP_EOL, true);
}

function BotBanishLogProgress($data) {

	global $BotBanishSettings;
	
//	$logit = (isset($BotBanishSettings['BOTBANISH_DEBUG_LOG_PROGRESS']) && $BotBanishSettings['BOTBANISH_DEBUG_LOG_PROGRESS'] == 1) ? true : false;
	$logit = (defined('BOTBANISH_DEBUG_LOG_PROGRESS') && BOTBANISH_DEBUG_LOG_PROGRESS) ? true : false;

	if (!$logit)
		return;

	$log_array = BotBanishLogGetLocation();

	$filename = $log_array['filename'];
	$filename = str_replace('_Activity_', '_Progress_', $log_array['filename']);
	
	BotBanishWriteFile($filename, BotBanishGetCorrectDateTime() . '  ' . $data . PHP_EOL, true);
}

function BotBanishLogInfo($sql, $info_data = array()) {

	global $BotBanishSettings;
	
//	$logit = (isset($BotBanishSettings['BOTBANISH_DEBUG_LOG_INFO']) && $BotBanishSettings['BOTBANISH_DEBUG_LOG_INFO'] == 1) ? true : false;
	$logit = (defined('BOTBANISH_DEBUG_LOG_INFO') && BOTBANISH_DEBUG_LOG_INFO) ? true : false;

	if (!$logit)
		return;

	$sep = PHP_EOL . str_repeat('=', 30) . PHP_EOL;
	$data = array();

	foreach ($info_data as $info)
		$data[] = is_array($info) ? var_export($info, true) : $info;

	$str = $sql . PHP_EOL . PHP_EOL . implode(PHP_EOL . PHP_EOL, $data) . PHP_EOL;

	$log_array = BotBanishLogGetLocation();

	$filename = $log_array['filename'];
	$filename = str_replace('_Activity_', '_Info_', $log_array['filename']);
	
	BotBanishWriteFile($filename, BotBanishGetCorrectDateTime() . '  ' . $str . $sep, true);
}

function BotBanishLogGetLocation($default = '') {

	$log_array = array(
		'location' => '',
		'file_date' => '',
		'table_name' => '',
		'type' => '',
		'version' => '',
		'curloc' => '',
		'filename' => ''
		);
/*
	// get the current location

	$log_array['curloc'] = str_replace('/', '_', dirname(__FILE__));
	$log_array['curloc'] = str_replace('\\', '_', $log_array['curloc']);
	$str = str_replace('/', '_', $_SERVER['DOCUMENT_ROOT']);
	$log_array['curloc'] = str_ireplace($str, '', $log_array['curloc']);
*/
	// Set filename date

	$log_array['file_date'] = date('Y-m-d', strtotime('now'));

	// Get database error table name

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		$log_array['table_name'] = 'bbs_botbanishserver_doc_errors';
		$log_array['type'] = 'Server';
		$side = '[Serverside]';

	}else{

		$log_array['table_name'] = 'bbc_botbanishclient_doc_errors';
		$log_array['type'] = 'Client';
		$side = '[Clientside]';
	}

	$log_array['version'] = BOTBANISH_VERSION;

	// Get log file path

	$log_array['location'] = BOTBANISH_LOGS_DIR;

	// Create the log folder if it does not exist

	if (!is_dir($log_array['location'])) {
		@mkdir($log_array['location'], 0755, true);
	}

	$log_array['filename'] = BOTBANISH_LOGS_DIR . str_replace(' ', '_', BOTBANISH_VERSION) . '_' . $default . 'Activity_' . $side . BOTBANISH_USER_HOST . $log_array['curloc'] . '_' . $log_array['file_date'] . '.log';

	return $log_array;
}

function BotBanishReturnData($rows, $force = false) {

	if (BOTBANISH_CLIENT && $force === false)
		return $rows;

	$params = $rows;
	$data = $rows;

	if (is_array($rows)) {

		if (isset($rows[0][1])){

			$data = '';

			foreach ($rows as $row){

				if (is_array($row)){

					$data .= BotBanishBuildReturn($row) . '</br>';

				}else{

					$data = $row;
					break;
				}
			}

		}else{

			$data = $rows;
		}

		$params = BotBanishBuildReturn($data);
	}

	BotBanishLogTraceResponse($params);

	echo $params;

	BotBanishExit(true);
}

function BotBanishCheckReturnData(&$row) {
	// if we are returning data to an client version before 3.0
	// Remove return items that earlier version do not know how to handle and will cause errors

	global $diag, $version;

	$ver = str_replace('BotBanish Client', '', BOTBANISH_CLIENT_VERSION);

	if (intval($ver) >= 3){

		if (isset($diag) && $diag != 0){

			$rows['diag'] = $diag;
			$diag = 0;
		}

		if (isset($row['bot_ip']))
			$row['domain'] = BotBanishGetDomainOrigin($row['bot_ip']);
		else
			$row['domain'] = '';

		if (!isset($rows['forcelockout']))
			$row['forcelockout'] = 0;

	}else{

		// Remove the fields that versions prior to version 3 will not understand

		$remove = array('diag', 'domain');	// Fields to remove because older clients will not know what to do with them

		foreach ($remove as $field){

			if (isset($row[$field]))
				unset($row[$field]);
		}
	}
}

function BotBanishDisplayErrorExit($msg) {

	$msg = str_replace("\n", '<br />', $msg);
	echo $msg."<br />";

	return BotBanishExit(true);
}

function BotBanishURLFormatData($row, $alldata = false) {

	$data = '';
	$error_check = BotBanishArrayCheck($row);

	if (isset($error_check['valid']) && $error_check['valid']) {

		Foreach ($row as $item => $value){

			if (!empty($value) || $alldata == true) {

				if (!is_array($value)) {

						$data .= '&' . $item . '=' . urlencode($value);
				}else{

					foreach ($value as $key => $val) {

						$data .= '&' . $key . '=' . urlencode($val);
					}
				}
			}
		}
	}

	return $data;
}

function BotBanishBuildReturn($row) {

	$str = array();

	$error_check = BotBanishArrayCheck($row);
	$params = 'error|BotBanishBuildReturn() - ';

	if ($error_check['valid']){

		foreach($row as $key => $value){

			if ($value !== ''){
				if (is_array($value))
					$str[] .= $key . '=' . explode('*', $value);
				else
					$str[] .= $key . '=' . $value;
			}
		}

		$params = implode('|',$str);

	}else{

		$params = str_replace("\n", '<br />', $params) . '<br />';	// Possibly an error so let's make it readable
	}

	return $params;
}

function BotBanishAdjustReturn($rows) {
	// Protect from multiple rows being returned
	// If we have more than one row, only use the first row returned

	if (isset($rows) && is_array($rows)) {

		foreach ($rows as $row)
			break;

		return $row;

	}else{

		return $rows;
	}
}

function BotBanishAdjustDatabaseRows($rows) {

	$data = array();

	if (isset($rows) && is_array($rows)) {

		foreach ($rows as $row)
			$data[$row['language']] = $row['language'];
	}

	return $data;
}

function BotBanishErrorHandler($errno, $errstr, $errfile, $errline) {

	global $BotBanishText;

	date_default_timezone_set(BOTBANISH_TIMEZONE);

	$error = BotBanishGetCorrectTime() . ' - ' . BOTBANISH_VERSION . PHP_EOL . PHP_EOL;
    $error .= "\t" . $BotBanishText['php_error'] . ': [' . $errno . '] ' . $errstr . PHP_EOL;
    $error .= "\t" . $BotBanishText['error_on_line'] . ': ' . $errline . ' ' . $BotBanishText['in'] . ' ' . $errfile . PHP_EOL;
// remove, for test only !!!
// if ($errline > 2918)
//	$error .= 'BOTBANISH_HTACCESS_ARRAY = ' . var_export(BOTBANISH_HTACCESS_ARRAY) . PHP_EOL;

	BotBanishLogError($error, 2);
	return BotBanishExit(true);
}

function BotBanishDebugBackTrace() {

	global $BotBanishText;

	$error_trace = '';
	$error_trace_array = array();
	$backtrace_array = debug_backtrace();

	foreach ($backtrace_array as $key => $error_info){

		$error_trace = $BotBanishText['called_from'] . PHP_EOL;
		$error_trace .= isset($error_info['file']) ? "\t" . 'File: ' . $error_info['file'] . PHP_EOL : '';
		$error_trace .= isset($error_info['function']) ? "\t" . 'Function: ' . $error_info['function'] . PHP_EOL : '';
		$error_trace .= isset($error_info['line']) ? "\t" . 'Line: ' . $error_info['line'] . PHP_EOL : '';

		$error_trace_array[] = $error_trace . PHP_EOL;
	}

	// Reverse the trace order for better viewing

	$error_trace = '';
	$i = count($error_trace_array) - 1;

	while($i > -1) {

	  $error_trace .= $error_trace_array[$i];
	  $i--;
	}

	return $error_trace;
}

function BotBanishLogError($error, $index = 0, $data = '', $default = '') {

	global $BotBanishSQL, $BotBanishURL, $BotBanishText;

	$default_date = date_default_timezone_get();
	date_default_timezone_set(BOTBANISH_TIMEZONE);
	$logall = (defined('BOTBANISH_DEBUG_TRACE_ALL') && BOTBANISH_DEBUG_TRACE_ALL) ? true : false;
	
	$log_array = BotBanishLogGetLocation($default);

	$date = BotBanishGetCorrectDateTime();
	$error_trace = '';

	$errors = strip_tags($error, '<br><br />');
	$errors = str_replace('<br>', PHP_EOL, $errors);
	$errors = str_replace('<br />', PHP_EOL, $errors);

	if (!empty($data)) $errors .= $BotBanishText['error_data'] . ': ' . $data . PHP_EOL;
	$errData = $errors;

//	if ((stripos($errors, '403') != false) || (stripos($errors, 'PHP Error') != false))
//		$logall = true;

	if ($logall) {

		$errors .= PHP_EOL . PHP_EOL . $BotBanishText['execution_location'] . PHP_EOL . ': ' . dirname(__FILE__) . PHP_EOL;
		
		// SQL is for all errors

		if (isset($BotBanishSQL)) {

			if (!empty($BotBanishSQL))
				$errors .= $BotBanishText['last_sql'] . PHP_EOL . ': ' . substr($BotBanishSQL, 0, 2048) . PHP_EOL . PHP_EOL;
		}
	}

	// URL is only for client side errors!!!

	if (isset($BotBanishURL) && $index !== -1 && !empty($BotBanishURL))
		$errors .= str_repeat('*', 100) . PHP_EOL . $BotBanishText['last_url'] . ': ' . $BotBanishURL . PHP_EOL . str_repeat('*', 100) . PHP_EOL . PHP_EOL;

	if ($logall) {
		
		// We may need to see what the client is attempting to do...

		$errors .= BotBanishGetServerInfo($_POST, $errData);
		$errors .= BotBanishGetBotBanishInfo();
		$errors .= BotBanishGetClientInfo();
		$errors .= BotBanishGetServerInfo($_SERVER, $errData);
		
		$error_trace = BotBanishDebugBackTrace();
		
	} else {

		if (stripos($error, 'serialize') !== false) $error_trace = BotBanishDebugBackTrace();
	}

	// Error file processing

	$str = sprintf('---------------------------%s - %s-------------------------------', BOTBANISH_VERSION, $date);
	$str .= PHP_EOL . PHP_EOL;
	$str .= $BotBanishText['location'] . ': ' . BOTBANISH_LOCATION . PHP_EOL . $errors . PHP_EOL;

	if (!empty($error_trace))
		$str .= $error_trace . PHP_EOL;

	// Write to a flat file in case of a database error
	
	BotBanishWriteFile($log_array['filename'], $str, true);

	$str = '';
	$errors = '';
	$error_trace = '';
	$backtrace_array;

	date_default_timezone_set($default_date);
}

function BotBanishGetBotBanishInfo() {

	global $BotBanishText, $botbanish_info;

	$data = '';

	if (empty($botbanish_info) || !is_array($botbanish_info))
		return $data;

	$data = PHP_EOL . '=====================================================================' . PHP_EOL;
	$data .= $BotBanishText['database_name'] . ': ' . BOTBANISH_DB_NAME . PHP_EOL . $BotBanishText['host'] . ': ' . BOTBANISH_DB_SERVERNAME . PHP_EOL . $BotBanishText['botbanish_data'] . ': $botbanish_info' . PHP_EOL . PHP_EOL;
	$data .= BotBanishFormatArray($botbanish_info) . PHP_EOL;
	$data .= '=====================================================================' . PHP_EOL;

	return addslashes($data);
}

function BotBanishGetServerInfo($serverdata = array(), $errData = '') {

	global $BotBanishText, $BotBanishSQL, $BotBanishURL;

	// Check if processing an Error Document Redirection

	$data = '';

	if (empty($serverdata) || !is_array($serverdata))
		return $data;

	$data = PHP_EOL . '=====================================================================' . PHP_EOL;

	if (defined('BOTBANISH_DB_SERVERNAME'))
		$data .= $BotBanishText['database_name'] . ': ' . BOTBANISH_DB_NAME . PHP_EOL . $BotBanishText['host'] . ': ' . BOTBANISH_DB_SERVERNAME . PHP_EOL . PHP_EOL;

	$cause = isset($serverdata['REDIRECT_URI']) ? $serverdata['REDIRECT_URI'] : '';
	$cause = empty($cause) && isset($serverdata['REDIRECT_QUERY_STRING']) ? $serverdata['REDIRECT_QUERY_STRING']  : '';
	$cause = empty($cause) && isset($serverdata['SCRIPT_URL']) ? $serverdata['SCRIPT_URL']  : '';
	$cause = !empty($cause) ? $cause . PHP_EOL . $BotBanishText['filenotfound'] : '';

	if (isset($serverdata['REMOTE_ADDR']) && !empty($cause)) {

		$geo_data = BotBanishGetGeoData($serverdata['REMOTE_ADDR']);
		$country_info = BotBanishGetCountryData($geo_data);

		$cause .= PHP_EOL . $BotBanishText['offending_country'] . ': ' . $country_info['country'];
	}

	$data .= BOTBANISH_VERSION . PHP_EOL;
	$data .= !empty($BotBanishURL) ? $BotBanishText['last_url'] . ': ' . $BotBanishURL . PHP_EOL : '';
	$data .= !empty($BotBanishSQL) ? $BotBanishText['last_sql'] . ': ' . $BotBanishSQL . PHP_EOL : '';
	$data .= !empty($cause) ? $BotBanishText['cause'] . ': ' . str_replace('&', PHP_EOL, urldecode($cause)) . PHP_EOL : '';
	$data .= !empty($errData) ? PHP_EOL . $BotBanishText['error_data'] . ': ' . $errData . PHP_EOL : '';
	$data .= PHP_EOL . BotBanishFormatArray($serverdata) . PHP_EOL;
	$data .= '=====================================================================' . PHP_EOL;

	return addslashes($data);
}

function BotBanishGetClientInfo() {

	global $BotBanishText, $client_info;

	$data = '';

	if (empty($client_info) || !is_array($client_info))
		return $data;

	// Check if processing an Error Document Redirection

	$data = PHP_EOL . '=====================================================================' . PHP_EOL;
	$data .= $BotBanishText['database_name'] . ': ' . BOTBANISH_DB_NAME . PHP_EOL . $BotBanishText['host'] . ': ' . BOTBANISH_DB_SERVERNAME . PHP_EOL . $BotBanishText['client_data'] . ': $client_info' . PHP_EOL . PHP_EOL;
	$data .= BotBanishFormatArray($client_info) . PHP_EOL;
	$data .= '=====================================================================' . PHP_EOL;

	return addslashes($data);
}

function BotBanishFormatArray($dataarray = array()) {

	$data = '';

	if (empty($dataarray) || !is_array($dataarray))
		return $data;

	foreach ($dataarray as $key => $value) {

		if (is_array($value)) {

			$data .= $key . ' Array(' . PHP_EOL;

			foreach ($value as $arr_key => $arr_value)
				$data .= "\t" . $arr_key . ' = ' . $arr_value . PHP_EOL;

			$data .= ')' . PHP_EOL;

		} else {

			$data .= $key . ' = ' . $value . PHP_EOL;
		}
	}

	return $data;
}

function BotBanishUpdateBlockLogs($subject, $ip) {

	$subject = trim(str_ireplace(array($_SERVER['SERVER_NAME'], ' - ', 'BotBanishClient:'), '', $subject));
	$table = 'bbc_botbanishclient_website_blocks';
	
	$sql = 'SELECT `id_no`, `bot_ip`, `subject`, `blocks` FROM `' . $table . '` WHERE `bot_ip` = "' . $ip . '" AND `subject` = "' . $subject . '" AND `date` = CURDATE()';
	$rows = BotBanishExecuteSQL($sql, true, true);

	$row = array();
	$row['subject'] = $subject;
	$row['bot_ip'] = $ip;
	$row['date'] = BotBanishGetCorrectDateTime();

	if (is_array($rows) && isset($rows['id_no']) && !empty($rows['id_no'])) {

//		$row['blocks'] = (isset($rows['blocks']) && !empty($rows['blocks'])) ? $rows['blocks']++ : 1;		
		$row['blocks'] = isset($rows['blocks']) ? $rows['blocks']++ : 1;		
		$sql = 'UPDATE ' . $table . ' SET `blocks` = ' . $row['blocks'] . ' WHERE `id`_no` = ' . $rows['id_no'];
		
	} else {

		$row['blocks'] = 1;		
		$params = BotBanishBuildSQLStatementInsert($row, $table);
		$sql = 'INSERT INTO `' . $table . '` ('.$params['columns'].') VALUES ('.$params['values'].')';
	}

	BotBanishExecuteSQL($sql, true, true);	
}

function BotBanishSendMail($subject, $body, $ip = '') {

	global $store_info, $BotBanishText;

	$body .= '<br><br>' . BOTBANISH_VERSION;

	if (!empty($ip)) {

		$geo_data = BotBanishGetGeoData($ip);
		$country_info = BotBanishGetCountryData($geo_data);
		$body = $BotBanishText['Server'] . ': ' . $_SERVER['SERVER_NAME'] . '<br>' . $BotBanishText['offending_country'] . ': ' . $country_info['country'] . '<br><br>' . $body;
	}

	BotBanishUpdateBlockLogs($subject, $ip);
	
	// If we do not send an email then we will place the message in the SMF error log or the BotBanish activity log

	if (isset($GLOBALS['sourcedir']))
		loadLanguage('BotBanish/BotBanishLanguage');

	// If we are not sending emails then we should write to an activity log

	if (defined('BOTBANISH_SEND_EMAIL_ALERTS') && BOTBANISH_SEND_EMAIL_ALERTS) {

		if (defined('DIR_APPLICATION')) {

			// If OpenCart, get the admin email address
			// for OpenCart 2.1 (maybe below) the $store_info variable contained the email address
			// If not found then go to the database to get the email address.

			if (isset($store_info['config_email']) && !empty($store_info['config_email']))
				$email = $store_info['config_email'];	// Pre-Opencart 3.x
			else
				$row = BotBanishExecuteSQL("SELECT `value` FROM `" . DB_NAME . '`.`' . DB_PREFIX . "setting` WHERE `key` = 'config_email'");
				$email = $row['value'];	// OpenCart 3.x

		} else {

			$email = BOTBANISH_WEBMASTER_EMAIL;
		}

		$bool = false;

		if (!empty($email))
			$bool = BotBanishSendPHPMail($email, $subject, $body);	// Use BotBanish to send email													// Try other methods if this fails

		if (!$bool) {

			// Prepare the email

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=ISO-8859-1" . "\r\n";
			$headers .= 'To: ' . $email . "\r\n";
			$headers .= 'From: BotBanish Client <support@botbanish.com>' . "\r\n";

			if (isset($GLOBALS['sourcedir'])) {

				BotBanishSendSMFMail($headers, $subject, $body, $email);

			} else {

				if (defined('ABSPATH')) {

					$bool = wp_mail($email, $subject, $body, $headers);

					// If email could not be send, place it in the error log

					if (!$bool)
						BotBanishLogError($subject . PHP_EOL . $body, -1);
				}else{

					BotBanishLogError($subject . PHP_EOL . $body, -1);
				}
			}
		}

	} else {

		if (isset($GLOBALS['sourcedir'])){

			BotBanishSMFLogError($subject, $body);
		}else{

			BotBanishLogError($subject . PHP_EOL . $body, -1);
		}
	}
}

function BotBanishSendPHPMail($email, $subject, $message) {

	global $mail;

	return false;			// This routine needs to be tested for SMTP connection errors

	try {

		//Server settings

		$mail->SMTPDebug = 3;                                 // Enable verbose debug output
//		$mail->Debugoutput = 'error_log';
//		$mail->Debugoutput = function($str, $level) {$file = fopen('BotBanish_PHPMailer_Errors.log', 'a'); fwrite($file, $level . ' - ' . $str . PHP_EOL); fclose($file);};
		$mail->Debugoutput = function($str, $level) {BotBanishWriteFile(BOTBANISH_LOG_DIR . 'BotBanish_PHPMailer_Errors.log', $level . ' - ' . $str . PHP_EOL, true);};

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = BOTBANISH_SMTP_SERVER;  				  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = BOTBANISH_SMTP_USERNAME;            // SMTP username
		$mail->Password = BOTBANISH_SMTP_PASSWORD;            // SMTP password
//		$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = BOTBANISH_SMTP_PORT;                    // TCP port to connect to
		$mail->SMTPKeepAlive = true;
		$mail->CharSet = 'utf-8';

		$mail->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);

		//Recipients

		$mail->setFrom('support@botbanish.com', BOTBANISH_VERSION);
		$mail->addAddress($email);     						  // Add a recipient
//		$mail->addAddress('ellen@example.com');               // Name is optional
		$mail->addReplyTo('support@botbanish.com', 'BotBanish Support');
//		$mail->addCC('cc@example.com');
//		$mail->addBCC('bcc@example.com');

		//Attachments

//		$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//		$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

		//Content

		$mail->isHTML(false);                                  // Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body    = $message;
		$mail->AltBody = $message;

		$mail->send();

	} catch (Exception $e) {

		BotBanishLogError('Message could not be sent. PHPMailer Error: ' .
									$mail->ErrorInfo . PHP_EOL . PHP_EOL .
									'Host: ' . $mail->Host . PHP_EOL .
									'Port:' . $mail->Port . PHP_EOL .
									'Username: ' . $mail->Username . PHP_EOL .
									'Password: ' . $mail->Password . PHP_EOL . PHP_EOL .
									$subject . PHP_EOL . PHP_EOL .
									$message);
		return false;
	}

	return true;
}

function BotBanishDecodeURL(&$row) {

	global $client_info;

	$client_info = array();

	foreach ($row as $key => $value) {

		if (strpos($key, 'BOTBANISH_') !== false)
			$client_info[str_replace('BOTBANISH_', '', $key)] = urldecode($value);
		else
			$row[$key] = urldecode($value);
	}
}

function BotBanishRemoveDirectory($path) {

	if (!is_dir($path) || $path === '\\' || $path === '/')
		return;

	$files = scandir($path . '/');

    foreach ($files as $file) {

		if (($file !== '.') && ($file !== '..')) {
			$item = $path . '/' . $file;
			is_dir($item) ? BotBanishRemoveDirectory($item) : unlink($item);
		}
    }

    @rmdir($path);
    return;
}

function BotBanishMakeTree($srcPath, $mode) {

	@mkdir($srcPath, 0755, true);

	$path = str_replace('\\', '/', $srcPath);
    $paths = explode('/', $path);
	$fullPath = '';

	foreach ($paths as $path) {

		$fullPath .= $path;

		if (!is_dir($fullPath))
			@mkdir($fullPath, 0755);

		$fullPath .= '/';
	}
}

function BotBanishRecursiveCopy($source, $dst) {

	$src = realpath($source);

	if (is_dir($src)) {

		$dir = opendir($src);

		@mkdir($dst, 0755, true);

		while (false !== ( $file = readdir($dir)) ) {

			if (( $file != '.' ) && ( $file != '..' )) {

				if ( is_dir($src . '/' . $file) ) {

					BotBanishRecursiveCopy($src . '/' . $file, $dst . '/' . $file);
				}
				else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}

		closedir($dir);

	}else{

		BotBanishLogError('Source folder does not exist: ' . $src);
	}
}

function BotBanishClientIPInsertLocal($row, $ip) {

	$row['bot_ip'] = $ip;

	if (isset($row['domain'])) {
		$row['domain_name'] = $row['domain'];
		unset($row['domain']);
	}

	if (isset($row['ip_first_hit']))
		unset ($row['ip_first_hit']);

	if (isset($row['ip_hit_count']))
		unset ($row['ip_hit_count']);

	BotBanishClientInsertLocal($row);
}

function BotBanishClientIPRemoveLocal($ip) {

	BotBanishClientRemoveLocal($ip);
}

function BotBanishCollectData($domain_name = '') {

	global $BotBanishURL;

	// Attempt to run the data collection script on the client for debugging purposes

	if (!empty($domain_name)){

		$url = BOTBANISH_LOCALAPPURL . '/Client/BotBanishClientCheck.php';

		$row = array();
		$row['domain'] = $domain_name;
		$row['APIKEY'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'free';

		$data = BotBanishURLFormatData($row);
		$BotBanishURL = $data;
		$returned_data = BotBanishSendPostData($url, $data);
	}
}

function BotBanishFlushOutputBuffer() {

	$level = ob_get_level();

	while (ob_get_level() > $level)	{
		ob_end_flush();
	}
}

function BotBanishGetRedirectData() {

	global $client_info;

	$row = array();

	if (in_array($client_info['REDIRECT_STATUS'], unserialize(BOTBANISH_DOC_ERRORS))) {

		if (isset($client_info['REDIRECT_QUERY_STRING'])) {

			parse_str($client_info['REDIRECT_QUERY_STRING'], $row);	// Get all passed parameters
			BotBanishDecodeURL($row);
		}
	}

	return $row;
}

function BotBanishReceivePostData() {

	global $client_info;

	// Separate out passed information

	$row = array();
	$client_info = array();

	foreach($_POST as $key => $value) {

		if (stripos($key, 'BOTBANISH_') !== false)
			$client_info[str_replace('BOTBANISH_', '', $key)] = urldecode($value);
		else
			$row[$key] = urldecode($value);
	}

	return $row;
}

function BotBanishReceiveGetData() {

	global $client_info;

	$row = array();
	$client_info = array();

	foreach($_GET as $key => $value) {

		if (stripos($key, 'BOTBANISH_') !== false)
			$client_info[str_replace('BOTBANISH_', '', $key)] = urldecode($value);
		else
			$row[$key] = urldecode($value);
	}

	return $row;
}

function BotBanishCheckForAvailableUpdate() {

	global $user_info, $BotBanishSettings;

	if (isset($GLOBALS['sourcedir'])){

		$type = 'SMF';

		if (!$user_info['is_admin'])
			return;

	}else
		if (defined('DIR_APPLICATION')) {
			$type = 'OpenCart';

	}else
		if (defined('ABSPATH')){
			$type = 'WordPress';

	}else{
		$type = 'Website';
	}

	if (!BOTBANISH_CHECK_UPDATES)
		return;

	$url = str_ireplace('BotBanishServer.php', 'BotBanishUpdateCheck.php', BOTBANISH_SERVER_URL);

	$row = array();
	$row['version'] = BOTBANISH_VERSION_CLIENT;
	$row['type'] = $type;
	$row['language'] = $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'];

	$data = BotBanishURLFormatData($row);
	$BotBanishURL = $data;

	$html = BotBanishSendPostData($url, $data);
	$BotBanishHTML = '';

	// If we have an available update, alert the user. HTML code borrowed from w3schools.com

	if (!empty($html)) {

		$BotBanishHTML = '

			<style>
				.container{padding-right:15px;padding-left:15px;margin-right:auto;margin-left:auto}
				.btn-lg{padding:10px 16px;font-size:18px;line-height:1.3333333;border-radius:6px}
				.btn-info{color:#fff;background-color:#5bc0de;border-color:#46b8da}
				.btn .caret{margin-left:0}
			</style>

			<div class="container" align="center">
			  <button type="button" class="btn btn-info btn-lg">' . $html . '</button>
			</div>';
	}

	return $BotBanishHTML;
}

function BotBanishGetDomainOrigin($ip) {

	if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		return '';

	$host = gethostbyaddr($ip);
	$host_ip = gethostbyname($host);

	$host_part = explode('.', $host);
	$index = count($host_part);
	$name = '';

	if (($ip === $host_ip) && ($ip != $host)){

		if ($index > 1){

			$name = $host_part[$index - 2] . '.' . $host_part[$index - 1];

			if (stripos($ip, $name) !== false)
				$name = '';
		}
	}

	return $name;
}

function BotBanishSetLanguage($lang = 'english') {
	
	global $BotBanishSettings;
	
	if (defined('SMF')) {
		
		global $language;
		$arr = explode('_', $language);
		$lang = $arr[0];
	}
	
	if (!BotBanishLanguageSupported($lang))
		$lang = isset($BotBanishSettings['BOTBANISH_LANGUAGE_SELECT']) ? $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'] : 'english';
	else
		$lang = empty($lang) && isset($BotBanishSettings['BOTBANISH_LANGUAGE_SELECT']) ? $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'] : $lang;

	$table = 'botbanish_language_text_' . strtolower($lang);
	$tables = BotBanishGetTableNames($table);
	
	if (!empty($tables)) {
	
		$sql = 'SELECT `language` FROM `botbanish_language` WHERE 1';
		$rows = BotBanishExecuteSQL($sql);		
		BotBanishDefineGlobals('BOTBANISH_LANGUAGES', BotBanishAdjustDatabaseRows($rows));

		$sql = 'SELECT `lang_id` FROM `botbanish_language` WHERE `language` = "'. $lang . '"';
		$rows = BotBanishExecuteSQL($sql);
		
		$sql = 'SELECT `lang_key`, `lang_text` FROM `' . $table .'` WHERE `lang_id` = '. $rows[0]['lang_id'];
		$rows = BotBanishExecuteSQL($sql);

		$txt = array();
		
		foreach ($rows as $row)
			$txt[$row['lang_key']] = html_entity_decode($row['lang_text'], ENT_QUOTES, 'utf-8');

		if (!empty($txt))
//			$BotBanishText = $txt;
			$GLOBALS['BotBanishText'] = $txt;

		} else {

		// If language table does not exist, use the language file
		
		BotBanishDefineGlobals('BOTBANISH_LANGUAGES', array('english' => 'english', 'french' => 'french', 'german' => 'german', 'italian' => 'italian', 'portuguese' => 'portuguese', 'spanish' => 'spanish', 'swedish' => 'swedish'));
		$lang = BotBanishGetLanguageFile($lang);
	}
		
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_SELECT', $lang);
}

function BotBanishGetLanguageFile($lang = 'english') {	
	global $BotBanishSettings;
	
	// If the language file does not exist OR the global 'BOTBANISH_LANGUAGE_DATABASE' does not exist
	// OR the global exists and is marked false we will be using the database instead of the flat files for languages
	
	$language = empty($lang) && isset($BotBanishSettings['BOTBANISH_LANGUAGE_SELECT']) ? $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'] : $lang;
	
	$filename = 'BotBanishLanguage.' . $language . '.php';

	if (file_exists(BOTBANISH_LANGUAGE_DIR . $filename)) {

		$txt = array();
		include BOTBANISH_LANGUAGE_DIR . $filename;

		foreach ($txt as $key => $value)
			$txt[$key] = html_entity_decode($value, ENT_QUOTES, 'utf-8');
			
		if (!empty($txt))
//			$BotBanishText = $txt;
			$GLOBALS['BotBanishText'] = $txt;
		
	} else {
		
		$language = BotBanishGetLanguageFile();
	}
	
	return $language;
}

function BotBanishDatabaseSetLanguage($language) {

	$table = 'botbanish_language_text_' . strtolower($language);
	$sql = 'SELECT `lang_key`, `lang_text` FROM `' . $table . '`';
	$rows = BotBanishExecuteSQL($sql);

	$txt = array();

	foreach ($rows as $row)
		$txt[$row['lang_key']] = html_entity_decode($row['lang_text']);

	$table = 'botbanish_country_code';
	$sql = 'SELECT `country_code`, `country` FROM `' . $table . '`';
	$txt['CCodes'] = BotBanishExecuteSQL($sql);

	if (!empty($txt))
		$GLOBALS['BotBanishText'] = $txt;

	return $language;
}

function BotBanishExit($exit = false) {

	BotBanishDatabaseClose();

	$handler = BotBanishGetErrorHandler();

	// Make sure we don't have stacked error handlers

	while ($handler == 'BotBanishErrorHandler') {

		if ($handler == 'BotBanishErrorHandler')
			restore_error_handler();

		$handler = BotBanishGetErrorHandler();
	}

	BotBanishFlushOutputBuffer();

	if (BOTBANISH_SERVER && (session_status() == PHP_SESSION_ACTIVE))
		session_destroy();

	if ($exit)
		exit;

	return false;
}

function BotBanishRedirectExit() {

	BotBanishExit(false);

// 	POST to another file which does the redirection via javascript(Basically for WordPress)
 	BotBanishSendPostData(BOTBANISH_INSTALL_URL . 'Subs/BotBanish_Redirect.php', '');

	if (!headers_sent())
		header('Location: https://google.com');
	
	exit;
}

function BotBanishGetErrorHandler() {

    $handler = set_error_handler(function(){});
    restore_error_handler();
    return $handler;
}

/*
function BotBanishClient_CheckUserLogin($username, $password, $cookietime) {
	BotBanishClient(false, $username, $username, false);	// Will not return if a bad user
	return 'ok';											// If we get here then all is ok
}
*/

function BotBanishClient_CheckUserRegistration($regOptions, $theme_vars) {

	BotBanishClient(false, $regOptions['username'], $regOptions['email'], false);
}

function BotBanishClient_UserLoggedIn($username, $password, $cookietime) {

	BotBanishClient(false, '', '', true);		// Remove IP from tracking after being logged on
}

function BotBanishClient_UpdateConfig() {
}

function BotBanishCopyCheckIt($source, $destination, $file) {

	clearstatcache();

	if (!is_dir($destination))
		BotBanishMakeTree($destination, 0755);

	if (!is_dir($destination))
		return false;

	copy($source . $file, $destination . $file);

	if (!file_exists($destination . $file)) {

		BotBanishLogError('Copy did not complete - ' . $destination . $file);
		return false;

	}else{

		BotBanishLogError('Not a source file / does not exist: ' . $source . $file);
		return false;
	}

	return true;
}

function BotBanishAddClientData(&$row) {

	global $client_info;

	foreach ($client_info as $key => $value) {

		$row['BOTBANISH_' . $key] = $value;
	}
}

function BotBanishFindLanguage() {

	global $user_info;

	// See if BotBanish can match the current selected SMF language

	$index = 0;
	$language_types = BOTBANISH_LANGUAGES;

	foreach ($language_types as $key => $botbanish_language)
	{
		if ($botbanish_language == $user_info['language'])
			break;

		$index++;
	}

	if ($index >= count($language_types))
		$index = 0;

	return $index;
}

function BotBanishGetPassedData() {

	$row = array();

	switch ($_SERVER['REQUEST_METHOD']){

		case 'POST':
			$row = BotBanishReceivePostData();			// Get all Posted data
			break;

		case 'GET':
		default:

			// Special processing if using wget with special formatted parameter string

			$str = substr_count($_SERVER['QUERY_STRING'], '*') > 0 ? str_replace('*', '&', $_SERVER['QUERY_STRING']) : $_SERVER['QUERY_STRING'];
			parse_str($str, $row);	// Get all passed parameters
			BotBanishDecodeURL($row);
			$row['bot_ip'] = !isset($row['bot_ip']) ? $_SERVER['REMOTE_ADDR'] : $row['bot_ip'];
			break;
	}

	return $row;
}

function BotBanishGrabDump($var) {

	return '<pre>' . var_export($var, true) . '</pre>';
}

function BotBanishGrabArray($var) {

	$str = '<br>';

	foreach ($var as $item) {

		$str .= $item . '<br>';
	}

	return $str;
}

function BotBanishGetIPRangeList($ip) {

	// Only for IPv4 addresses

	if (!BotBanishCheckIfValidIP($ip))
		return array(); // Not a valid IP address

	$parts = explode('.', $ip);

	$ip_list = array();
	$ip_list[] = $ip;
	$ip_list[] = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.';
	$ip_list[] = $parts[0] . '.' . $parts[1] . '.';

	return $ip_list;
}

function BotBanishCheckIfValidIP($ip) {

	// Check if a valid IP address format

	if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
				return false; // Not a valid IP address
	}

	return true;	// A valid IPv4 or IPv6 address
}

function BotBanishCheckIfValidIPv4($ip) {

	// Check if a valid IPv4 address format

	if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		return false; // Not a valid IPv4 address

	return true;	// A valid IPv4 address
}

function BotBanishCheckIfValidIPv6($ip) {

	// Check if a valid IPv6 address format

	if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		return false; // Not a valid IPv6 address

	return true;	// A valid IPv6 address
}

function BotBanishFixUserAgent($useragent) {

	$useragent = urldecode($useragent);
	$useragent = html_entity_decode($useragent);		// Make sure no HTML encoded strings are in User-Agent

	// Some bots try to be sneaky and put carriage returns, line feeds and other strings in to thwart detection

	$useragent = str_replace(array('"', "'", PHP_EOL, "\n", "\r", "\0", 'string:quot;', 'quot;', '{', '}', '&quot', 'quot;'), '', $useragent);
	$useragent = addslashes ($useragent);				// Escape any slashes, quotes etc...

	return $useragent;
}

function BotBanishDatabaseOperations($sql) {

	// Force database operations if operating under SMF

	if (isset($GLOBALS['sourcedir']))
		$GLOBALS['override'] = true;

	foreach ($sql as $statement)
		BotBanishExecuteSQL($statement, true, true);

	if ((isset($GLOBALS['override']) && $GLOBALS['override']) === true)
		$GLOBALS['override'] = false;
}

function BotBanishCheckForEOL($line, &$sql, &$start) {

	$sql .= ' ' . $line;

	// Line terminator MUST be at the very end of the line!

	if (strpos(rtrim($line), ';') === (strlen(rtrim($line)) - 1)) {
		return true;	// Found EOL
	}else{
		return false;
	}
}

function BotBanishReadFile($filename) {

	global $BotBanishText;

	$data = '';

	if (file_exists($filename) && (filesize($filename) > 0)){

		$data = file_get_contents($filename);
	}else{
		echo $filename . $BotBanishText['BotBanish_NotFound'];
	}

	return $data;
}

function BotBanishWriteFile($filename, $data, $append = false) {

	if (empty($filename)) {

		BotBanishLogError('BotBanishWriteFile() - No filename present');
		return;
	}

	if (empty($data)) {

		BotBanishLogError('BotBanishWriteFile() - No data present');
		return;
	}

	$data = BotBanish_EncodeToUtf8($data);
	
	if ($append)
		file_put_contents($filename, $data, FILE_APPEND);
	else
		file_put_contents($filename, $data);
}

function BotBanishSpiderCheck($useragent, $dbtable) {

	// Good spider check

	$sql = 'SELECT `id_spider`, `spider_name` FROM ' . $dbtable . '_good
		WHERE  LOCATE(`user_agent_part`, "'.$useragent.'")';

	$row = BotBanishExecuteSQL($sql);

	// Bad spider check

	if (is_array($row) && isset($row[0]['spider_name']))
		return $row[0]['spider_name'];

	$sql = 'SELECT `id_spider`, `spider_name` FROM ' . $dbtable . '_bad
				WHERE  LOCATE(`user_agent_part`, "'.$useragent.'")';

	$row = BotBanishExecuteSQL($sql);

	if (is_array($row) && isset($row[0]['spider_name']))
		return $row[0]['spider_name'];

	return '';
}

function BotBanish_stripos($haystack, $needle) {

	$repstr = '  ';
	$findstr = array("\r\n", "\r", "\n");

	$haystack = str_ireplace($findstr, $repstr, $haystack);
	$needle = str_ireplace($findstr, $repstr, $needle);

	$pos = stripos($haystack, $needle);
	$haystack = '';
	$needle = '';

	return $pos;
}

function BotBanish_str_ireplace($find, $replace, $haystack) {

	$repstr = '~`~';
	$newstr = array("\r\n", "\r", "\n");

	$find = str_ireplace($newstr, $repstr, $find);
	$replace = str_ireplace($newstr, $repstr, $replace);
	$haystack = str_ireplace($newstr, $repstr, $haystack);

	$str = str_ireplace($find, $replace, $haystack);
	$str = str_ireplace($repstr, PHP_EOL, $str);

	$haystack = '';
	$find = '';
	$replace = '';

	return $str;
}

function BotBanishRemoveExtraCRLF($data) {

	$data = str_replace(PHP_EOL . PHP_EOL . PHP_EOL, PHP_EOL, $data);
	return $data;
}

function BotBanishFindConfigLocation($config = 'config.php') {

	$dir = rtrim(dirname(__FILE__), '/') . '/';
	$dir = str_replace('\\', '/', $dir);
	$found = false;

	while (true){

		if (file_exists($dir . $config)) {
			$found = true;
			break;
		}

		if ($dir == '/')
			break;

		$pre = $dir;
		$dir = rtrim($dir, '/');

		if ($dir === $pre)
			break;

		$dir = substr($dir, 0, strrpos($dir, '/') + 1);
	}

	if (!$found)
		$dir = '';

	return $dir;
}

function BotBanishFindSettingsLocation($folder = 'Client') {

	$dir = rtrim(dirname(__FILE__), '/') . '/';
	$dir = str_replace('\\', '/', $dir);
	$found = false;

	while (true){

		if (file_exists($dir . $folder) && is_dir($dir . $folder)) {
			$found = true;
			break;
		}

		if ($dir == '/')
			break;

		$pre = $dir;
		$dir = rtrim($dir, '/');

		if ($dir === $pre)
			break;

		$dir = substr($dir, 0, strrpos($dir, '/') + 1);
	}

	if (!$found)
		$dir = '';

	return $dir;
}

function BotBanishCheckForSpiders() {

	// Let's see if you are a spider...

	$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

	if (empty($useragent))
		exit;

	// If so, we don't need them in the analytics!!! Off you go!
	if (BotBanishCheckForClientBadBotOverride($useragent))
		exit;

	// If so, we don't need them in the analytics!!! Off you go!
	if (BotBanishCheckForClientGoodBotOverride($useragent))
		exit;
}

function BotBanishSetTimeZone($tz = BOTBANISH_TIMEZONE) {

	date_default_timezone_set($tz);
	return $tz;
}

function BotBanishGetCorrectTime($tz = BOTBANISH_TIMEZONE) {

	$dateB = date_create(BotBanishGetCorrectDateTime($tz));
	$date = date_format($dateB, 'H:i:s');
	return $date;
}


function BotBanishGetCorrectDateTime($tz = BOTBANISH_TIMEZONE) {

	$userTimezone = new DateTimeZone($tz);
	$gmtTimezone = new DateTimeZone('GMT');
	$myDateTime = new DateTime('now', $gmtTimezone);
	$offset = $userTimezone->getOffset($myDateTime);
	$myInterval=DateInterval::createFromDateString((string)$offset . 'seconds');
	$myDateTime->add($myInterval);
	$date = $myDateTime->format('Y-m-d H:i:s');
	return $date;
}

function BotBanishGetCorrectDateB() {

	return strtotime(date('Y-m-d', strtotime(BotBanishGetCorrectDateTime())));
}

function BotBanishGetCorrectTimeB() {

	return strtotime(date('H:i:s', strtotime(BotBanishGetCorrectDateTime())));
}

function BotBanishGetCorrectDateTimeB() {

	return strtotime(date('Y-m-d H:i:s', strtotime(BotBanishGetCorrectDateTime())));
}
function BotBanishSetupDatabaseEntry(&$seq, $value, $type, $htaccess_entry, $skipipcheck) {

	// We can identify these characters in a UA but CANNOT use them in the .htaccess file alone or the server will crash!
	if ((strlen($value) === 1) && in_array($value, array('|', '[', '+', '*', '^', '(', ')', '\\', '\/', ':', ';', '?', '$')))
		return;

	if ($skipipcheck == false && $type == 'ip') {

		// If not a valid ip; we don't want it
		if (!BotBanishCheckIfValidIP($value))
			return;
	}

	$entry = stripslashes(sprintf($htaccess_entry['str'], $value));

	$row = array();
	$row['data'] = $entry;
	$row['type'] = $type;

	switch ($type) {

		case 'ip':
		case 'bot':

			$row['seq'] = 0;
			break;

		default:

			$row['seq'] = $seq++;
			break;
	}

	return $row;
}

function BotBanishModifyFile($filename, $items) {

	global $BotBanishText;

	$data = BotBanishReadFile($filename);

	if (empty($data))
		return;

	foreach ($items as $item)
		BotBanishModData($data, $item);		// Data is changed in place

	BotBanishWriteFile($filename, $data);

	$data = '';
	$output = '';
}

function BotBanishUnModifyFile($filename) {

	if (!file_exists($filename))
		return;

	$source = BotBanishReadFile($filename);

	if (empty($source))
		return;

	BotBanishRemoveRuleBlock(BOTBANISH_CODE_START, BOTBANISH_CODE_END, $source);
	BotBanishWriteFile($filename, $source);
}

function BotBanishRemoveFileData($data, $filename) {

	$source = BotBanishReadFile($filename);

	if (empty($data))
		return;

	str_replace($data, '', $source);
	BotBanishWriteFile($filename, $source);
}

function BotBanishRemoveRuleBlock($start_entry, $end_entry, &$source_data) {

	// Find all rule blocks and remove them

	while (true) {

		// Look for the start with a new line at the begining

		$pos_start = stripos($source_data, PHP_EOL . $start_entry);

		// If not found then look for the start without the new line at the beginning

		if ($pos_start === false)
			$pos_start = stripos($source_data, $start_entry);

		$pos_end = stripos($source_data, $end_entry . PHP_EOL);

		if ($pos_end === false)
			$pos_end = stripos($source_data, $end_entry);

		if (($pos_start !== false) && ($pos_end !== false)) {

			if ($pos_end > $pos_start)
				$source_data = substr($source_data, 0, $pos_start) . substr($source_data, $pos_end + strlen($end_entry . PHP_EOL));
		}else{
			break;
		}
	}
}

function BotBanishModData(&$source, $data_array) {
//==========================================================================================
// Example of a ModData call array
//
//	$items[] = array(
//		'finddata' => "require_once('config.php');",
//		'moddata' => PHP_EOL . "	require_once('BotBanish_Settings.php');" . PHP_EOL,
//		'location' => 'after'
//	);
//
// ModData ALWAYS add the code in a code block for easy removal
//==========================================================================================

	// Break the data into two parts (before and after) to insert the new data string after the header
	// Encase the inserted data with tags so we can find it to remove it on uninstall

	if (($pos = stripos($source, $data_array['finddata'])) === false)
		return;

	$len = strlen($data_array['finddata']);
	
	switch ($data_array['location']){

		case 'after':

			$start_pos = $pos + strlen($data_array['finddata']) + 1;
			$data_start = substr($source, 0, $start_pos);
			$data_end = substr($source, $start_pos);
			break;

		case 'before':

			$start_pos = $pos;
			$data_start = substr($source, 0, $start_pos);
			$data_end = substr($source, $start_pos);
			break;
			
		case 'replace':
		default:

			$start_pos = $pos;
			$data_start = substr($source, 0, $start_pos);
			$data_end = substr($source, $len, $start_pos);
			break;
	}

	$source = $data_start . PHP_EOL . BOTBANISH_CODE_START . PHP_EOL . $data_array['moddata'] . PHP_EOL . BOTBANISH_CODE_END . PHP_EOL . $data_end;

	return;
}

function BotBanishCheckImage($source, $filename, $alt_filename) {

	$image = $source . $filename;

	// If we are on the BotBanish server, the file will exist.
	// HTTP request for headers on the local server don't work the way we expect.

	$localimage = str_ireplace(BOTBANISH_WEBSERVER, $_SERVER['DOCUMENT_ROOT'] . '/', $image);

	if (file_exists($localimage))
		return $image;

	if (!defined('ABSPATH')) {

		$headers = BotBanishGetHeaders($image);
		
		if (!isset($headers['Status']) || stripos($headers['Status'], '200 OK') === false)
			$image = $source . $alt_filename;

	} else {

		if (!BotBanishRemoteImageFileExists($image))
			$image = $source . $alt_filename;
	}

	return $image;
}

function BotBanishCheckFileExists($url) {

    $curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	$result = curl_exec($curl);
	$ret = false;

    if ($result !== false) {
        //if request was ok, check response code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($statusCode == 200)
            $ret = true;
    }

    curl_close($curl);
	return $ret;
}

function BotBanishUrlExists($url) {

    if ((strpos($url,  "http ")) === false) $url =  "http:// " . $url;

    if (is_array(BotBanishGetHeaders($url)))
          return true;
     else
          return false;
}

function BotBanishFindSimilar(&$list, $item) {

	$rows = array();

	foreach ($list as $key => $value) {

		if (substr($value, 0, strlen($item)) === $item)
			$rows[$key] = $value;
	}

	return $rows;
}

function BotBanishCleanUp($deletedata = false, $botbanish_system = 'CLIENT', $write_log = true) {

	// Remove our code from the .htaccess file to handle error processing etc.
	
	$GLOBALS['override'] = true;
	
	BotBanishHTACCESSMaintenance('clean');

	// Un-Modify HTML files that we have modified

	$records = BotBanishUnModifyHTMLFiles($write_log);
	$records['ip'] = 0;
	$records['bot'] = 0;

	// Un-Modify the system files that we have modified


	if (defined('SMF')) {

		// Simple Machines Forum
		// IMPORTANT!!! In case uninstall fails after this point SMF will still be able to function
		// Remove Pretty URLs settings if present

		global $boarddir;
		BotBanishRemoveSMFSettings();
		BotBanishUnModifyFile($boarddir . '/index.php');
		BotBanishUnModifyFile($boarddir . '/SSI.php');
		BotBanishRemoveFileData('require_once($boarddir . \'/BotBanish/bot/Settings_Client.php\');', $boarddir . '/index.php');
		BotBanishRemoveFileData('require_once($boarddir . \'/BotBanish/bot/Settings_Client.php\');', $boarddir . '/SSI.php');
	}

	// Opencart
	// IMPORTANT!!! In case uninstall fails after this point OpenCart will still be able to function

	$files = array();

	switch (BOTBANISH_SYSTEM) {

		case 'OPENCART':

			$files = array(
					DIR_APPLICATION . '../' . 'index.php',
					DIR_APPLICATION . '../' . 'admin/index.php',
					DIR_SYSTEM . 'framework.php',
				);

			foreach ($files as $file)
				BotBanishUnModifyFile($file);
			break;

		default:
			break;
	}

	// Delete all BotBanish tables if requested

	BotBanishUninstallTables($deletedata);

	// Attempt to cleanup all files that we may have installed or created that may have been left behind

	$files = array();
	$folders = array();

	switch ($botbanish_system) {

		case 'SERVER':

			$files = array(
				'Settings_Server.php',
			);

		default:

			$files = array_merge($files, array(
				'BotBanish_RegisterAccount.html',
				'BotBanish_RegisterAccount.php',
				'Settings_Client.php',
//				'BotBanishRootIndex.php',
				'BotBanish_Settings.php',
				)
			);
			break;
	}

	$folders = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	switch (BOTBANISH_SYSTEM) {

		case 'OPENCART':

			$folders[] = realpath(DIR_APPLICATION . '../vqmod/xml/');
			$files[] = 'BotBanish Firewall Client.xml';
			break;

		default:
			break;
	}

	if (!empty($folders)) {
		
		foreach ($folders as $folder) {

			foreach ($files as $file) {

				if (stripos($folder, '.htaccess') === false)
					$filename = $folder . '\\' . $file;
				else
					$filename = str_ireplace('.htaccess', $file, $folder);

				if (file_exists($filename))
					unlink($filename);
			}

			BotBanishRemoveDirectory($folder . 'BotBanish');
		}
	}

	// If BotBanish started a session then we can destroy it.
	// Otherwise just erase our information in the session

	if (BOTBANISH_CLIENT) {

		$destroy = isset($_SESSION['BotBanish']['Active']) ? true : false;
		
		if (isset($_SESSION['BotBanish']))
			unset($_SESSION['BotBanish']);

		if (!defined('SMF')) {
			
			if (session_status() === PHP_SESSION_ACTIVE) {

				if ($destroy)
					session_destroy();
			}
		}
	}

	return $records;
}

function BotBanishGetTimeExtension() {

	$ext = str_ireplace(array('/', '-', ':', ' '), '', BotBanishGetCorrectTime());
	return $ext;
}

function BotBanishDumpDatabase($info = array()) {

	global $context, $BotBanishText, $boarddir;

	$sqlpath = BOTBANISH_BACKUPS_DIR . 'SQL/';

	if (empty($info)) {

		$info = array(
			'prefix' => 'botbanish',
			'data' => true,
			'structure' => true,
			'indexes' => true,
			'compress' => true,
			'type' => 'Full',
			'location' => $sqlpath,
		);

	}  else {

		$info['prefix'] = isset($info['prefix']) ? $info['prefix'] : 'botbanish';
		$info['data'] = isset($info['data']) ? $info['data'] : true;
		$info['structure'] = isset($info['structure']) ? $info['structure'] : true;
		$info['indexes'] = isset($info['indexes']) ? $info['indexes'] : true;
		$info['compress'] = isset($info['compress']) ? $info['compress'] : true;
		$type = $info['data'] == true ? 'Full' : 'Structure';
		$info['type'] = isset($info['type']) ? $info['type'] : $type;
		$info['location'] = isset($info['location']) ? $info['location'] : $sqlpath;
	}

	$url = BOTBANISH_INSTALL_URL . 'Subs/BotBanish_DumpDatabase.php';
	$include = '&include=' . urlencode(BOTBANISH_LOCATION . 'BotBanish_Settings.php');
	$root = '&root=' . urlencode($boarddir);
//	$info = 'info=' . BotBanishSafeSerialize($info); // does not work here for some reason (investigate)
	$info = 'info=' . serialize($info);

	// Enable completion message with the file download link
	$context['BotBanishDownloadLink'] = BotBanishSendPostData($url, $info . $include . $root);
	$context['BotBanishMsg'] = $BotBanishText['BotBanishDumpTablesComplete'];
}

function BotBanishGetTableNames ($tablename) {

	$tables = array();

	// If BOTBANISH_INSTALL is defined then we are doing a website install and no tables exist in database
	
	if (!defined('BOTBANISH_INSTALL')) {
		
		$sql = 'SELECT `TABLE_NAME` FROM information_schema.TABLES WHERE (`TABLE_SCHEMA` = "' . BOTBANISH_DB_NAME . '") AND (`TABLE_NAME` LIKE "%' . $tablename . '%")';
		$rows = BotBanishExecuteSQL($sql, true, false);

		if (!empty($rows) && is_array($rows)) {

			// Strip off any quotes and or database name that may be in the prefix

			$db_name = str_replace('`', '', BOTBANISH_DB_NAME);
			$pos = strripos($db_name, '.');

			if ($pos !== false)
				$db_name = substr($db_name, $pos + 1);

			foreach ($rows as $row) {

				$table = str_ireplace(array($db_name . '.', '`'), '', $row['TABLE_NAME']);
				$tables[] = $table;
			}
		}
	} 
		
	return $tables;
}

function BotBanishExtendTime($sleep = 0) {

	if (function_exists('apache_reset_timeout'))
		@apache_reset_timeout();

	if ($sleep !== 0) sleep($sleep);
}

function BotBanishCleanOutput() {

	// Get rid any output already present.

	if(ob_get_level() > 0)
		ob_end_clean();

	// If we can, clean anything already sent from the output buffer...

	if (function_exists('ob_clean') && ob_get_length() != 0)
		ob_clean();
}

function BotBanishAddDays($date, $days) {

	date_default_timezone_set(BOTBANISH_TIMEZONE);
	$new_date = date_create($date);
	date_add($new_date, date_interval_create_from_date_string($days . ' days'));
	return date_format($new_date, "Y-m-d H:i:s");
}

function BotBanish_Array_iunique($array) {

    return array_intersect_key(
        $array,
        array_unique( array_map( "strtolower", $array ) )
    );
}

function BotBanishSettingsSetGlobalValues($dataarray = array()) {

	if (!empty($dataarray)) {

		foreach($dataarray as $key => $value)
			BotBanishDefineGlobals($key, $value);
	}
}

function BotBanishLogTrace($ip)
{
	// This function gets called after receiving the data from the client to record what we have received.
	// it does not get written to the database until the server has answered the request in BotBanishReturnData.
	// That is when the sister routine BotBanishLogTraceResponse is called to write the information to the database.

	// Also added functionality to log basic information from other functions to help in the debugging of this system.
	// more functionality can be added with assorted globals in the IF statement.

	if ((defined('BOTBANISH_DEBUG_TRACE_ALL') && BOTBANISH_DEBUG_TRACE_ALL)
		|| (defined('BOTBANISH_DEBUG_FORCELOCKOUT') && BOTBANISH_DEBUG_FORCELOCKOUT)) {

		global $debugtracerow;

		$request = (isset($_POST) && !empty($_POST)) ? $_POST : $_GET;
		$date = BotBanishGetCorrectDateTimeB();
		$debugtracerow = array();
		$debugtracerow['bot_ip'] = $ip;
		$debugtracerow['hit_count'] = 1;
		$debugtracerow['last_hit'] = $date;
		$debugtracerow['datarow'] = BotBanishFormatArray($request);

		if (defined('BOTBANISH_SERVER') && BOTBANISH_SERVER) {

			global $botbanish_info, $client_info;

			$debugtracerow['domain_name'] = isset($botbanish_info['DOMAIN_NAME']) && !empty($botbanish_info['DOMAIN_NAME']) ? $botbanish_info['DOMAIN_NAME'] : $_SERVER['REMOTE_ADDR'];
			$debugtracerow['server_info'] = BotBanishGetServerInfo($_SERVER);
			$debugtracerow['client_info'] = BotBanishGetClientInfo($client_info);
			$debugtracerow['botbanish_info'] = BotBanishGetBotBanishInfo($botbanish_info);

		} else {

			global  $BotBanishSettings;;

			$debugtracerow['domain_name'] = isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['REMOTE_ADDR'];
			$debugtracerow['server_info'] = '';
			$debugtracerow['client_info'] = BotBanishGetClientInfo($_SERVER);
			$debugtracerow['botbanish_info'] = BotBanishGetBotBanishInfo($BotBanishSettings);
		}
	}
}

function BotBanishLogTraceResponse($datarow, $ip = '', $forcelockout = 0)
{
	// Sister routine to BotBanishLogTrace which sets up most of data that will be written to the database

	if ((defined('BOTBANISH_DEBUG_TRACE_ALL') && BOTBANISH_DEBUG_TRACE_ALL)
		|| (defined('BOTBANISH_DEBUG_FORCELOCKOUT') && BOTBANISH_DEBUG_FORCELOCKOUT)) {

		global $debugtracerow;

		if (!isset($debugtracerow['bot_ip']) && empty($ip))
			return;

		if (!isset($debugtracerow['bot_ip']))
			$debugtracerow['bot_ip'] = $ip;

		$debugtracerow['response'] = BotBanishFormatArray($datarow);

		if (defined('BOTBANISH_SERVER') && BOTBANISH_SERVER) {

			$table = 'bbs_botbanishserver_debug_trace';

		} else {

			$table = 'bbc_botbanishclient_debug_trace';
			$debugtracerow['server_info'] = 'ForceLockout = ' . $forcelockout . PHP_EOL . BotBanishDebugBackTrace();
		}

		$params = BotBanishBuildSQLStatementInsert($debugtracerow, $table);

		$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')' .
				' ON DUPLICATE KEY UPDATE ' . $params['updates'];

		BotBanishExecuteSQL($sql, true);
	}
}

function BotBanishReplaceQuotes($data) {

	$str = str_ireplace(array('``', '`.', '.`'), array('`','.', '.'), $data);
	return $str;
}

function BotBanishStripQuotes($data) {

	$str = str_ireplace(array('`', '"', '"'), '', $data);
	return $str;
}

function BotBanishCompressFolders($selections, $zipfile = 'BotBanish_Backup') {

/////////////////////////////////////////////////////////////////////////////////////
//
// Usage: This routine can place individual files as well as complete folders into a
//			compressed zip file. A date/time stamp will be appended to each filename.
//			do not include the file extension in the call, 'zip' will automatically
//			be appended to the filename.
//
//	$selections = array(
//		$_SERVER['DOCUMENT_ROOT'] . '/smf_2.1rc2/Sources',
//		$_SERVER['DOCUMENT_ROOT'] . '/smf_2.1rc2/Themes',
//		$_SERVER['DOCUMENT_ROOT'] . '/smf_2.1rc2/index.php'
//	);
//
//	BotBanishCompressFolders($selections, 'BotBanish_PreBackup');
/////////////////////////////////////////////////////////////////////////////////////

	if (empty($selections))
		return;

	clearstatcache();
	$dir = substr($zipfile, 0, strrpos($zipfile, '/'));

	if (!empty($dir))
		@mkdir($dir);

	// Name the zip file
	$zipfile = $zipfile . '_' . BotBanishGetTimeExtension() . '.zip';

	// Initialize archive object
	// Utilize PHP ZipArchive object if it is installed
	// otherwise use a programmed class object to compress the files

	if (function_exists('ZipArchive'))
		ZipArchiveIt($selections, $zipfile);
	else
		ZipIt($selections, $zipfile);
}

function ZipArchiveIt($selections, $zipfile) {

	$zip = new ZipArchive();
	$zip->open($zipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	foreach ($selections as $selection) {

		// Get real path for our folder
		$absPath = str_ireplace('\\', '/', realpath($selection));
		$rootPath = substr($absPath, 0, strrpos($absPath, '/') + 1);

		if (is_dir($selection)) {

			// Create recursive directory iterator
			// @var SplFileInfo[] $files //
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($absPath),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ($files as $name => $file)
			{
				if ($file->isDir())
				{
					$filename = $file->getFileName();

					if (($filename !== '.') && ($filename !== '..')) {

						// Get real and relative path for current file
						$filePath = str_ireplace('\\', '/', $file->getPathName());
						$relativePath = str_ireplace($rootPath, '', $filePath);

						$zip->addGlob($filePath . '/*.*', GLOB_BRACE, array("add_path" => $relativePath . '/', "remove_all_path" => true));
					}
				}
			}

		} else {

			// Get real and relative path for current file
			// Add current file to archive
			$filename = str_ireplace('\\', '/', $selection);
			$relativePath = str_ireplace($rootPath, '', $filename);

			$zip->addFile($filename, $relativePath);
		}
	}

	// Zip archive will be created only after closing object
	$zip->close();
}

function ZipIt($selections, $zipfile) {

/////////////////////////////////////////////////////////////////////////////////////
//	$zip->zip_add("path/to/example.png"); // adding a file
//	$zip->zip_add(array("path/to/example1.png","path/to/example2.png")); // adding two files as an array
//	$zip->zip_add(array("path/to/example1.png","path/to/directory/")); // adding one file and one directory
//	$zip->zip_end();
/////////////////////////////////////////////////////////////////////////////////////

	$zip = new Zip();
	$zip->zip_start($zipfile);
	$zip->zip_add($selections);

	// Zip archive will be created only after closing object
	$zip->zip_end();
}

function BotBanishModifyHTMLFiles() {

	global $BotBanishSettings, $context, $BotBanishText;

	$records = array();
	$records['filecount'] = 0;
	$records['updated'] = 0;
	$records['files'] = array();

	if ($BotBanishSettings['BOTBANISH_UPDATE_HTML_ACTIVE'])
		return $records;

	if (!$BotBanishSettings['BOTBANISH_UPDATE_HTML']) {

		if (defined('SMF'))
			$context['BotBanishMsg'] = $BotBanishText['BotBanishModifyHTMLInComplete'];

		return $records;
	}

	$Website_Code = PHP_EOL . stripslashes(BOTBANISH_HIDDEN_HONEYPOT_LINK) . PHP_EOL;

	if (defined('BOTBANISH_ANALYTICS_WEBSITE') && (BOTBANISH_ANALYTICS_WEBSITE == true))
		$Website_Code .= PHP_EOL . stripslashes(BOTBANISH_ANALYTICS_HTML) . PHP_EOL;

	$body_tag = '</body>';

	$path = rtrim($BotBanishSettings['BOTBANISH_UPDATE_HTML_FOLDER'], '/') . '/';

	if (stripos($path, $_SERVER['DOCUMENT_ROOT']) === false)
		$path = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($rii as $file) {

        if (!$file->isDir()) {

            $htmlfile = $file->getPathname();
			$len = strlen($htmlfile);
			$found = false;

			// Check for either type of HTML files extensions

			if ((stripos($htmlfile, '.html') === ($len - 5)) ||	(stripos($htmlfile, '.htm') === ($len - 4)))
				$found = true;

			if ($found) {

				$data = file_get_contents($htmlfile);
				$pos = stripos($data, $body_tag);

				if ($pos !== false) {

					$data = substr($data, 0, $pos - 1) . PHP_EOL . '<br><!-- ' . BOTBANISH_CODE_START . ' --><br>' . $Website_Code . '<br><!-- ' . BOTBANISH_CODE_END . ' --><br>' . PHP_EOL . substr($data, $pos);
					BotBanishWriteFile($htmlfile, $data);
					$records['updated']++;
					$records['files'][] = str_ireplace(array('\\', $_SERVER['DOCUMENT_ROOT']) , array('/', '') , $htmlfile);
				}

				$records['filecount']++;
			}
		}
	}

	if ($records['updated'] > 0) {

		BotBanishSettingsTablePutValues(array('BOTBANISH_UPDATE_HTML_ACTIVE' => 1));
		BotBanishWriteFile(BOTBANISH_LOGS_DIR . 'BotBanish_htmlfiles_modified.log', BotBanishGrabDump($records['files']));
	}

	if (defined('SMF')) {

		$str = $records['updated'] > 0 ? '<br>' . BotBanishGrabArray($records['files']) : '';;
		$context['BotBanishRows'] = $records['updated'] + 3;
		$context['BotBanishMsg'] = sprintf($BotBanishText['BotBanishModifyHTMLComplete'], $records['filecount'], $records['updated']) . $str;
		BotBanishUpdatePrettyURLs();
	}


	return $records;
}

function BotBanishUnModifyHTMLFiles($write_log = true) {

	global $BotBanishSettings, $context, $BotBanishText;

	$records = array();
	$records['filecount'] = 0;
	$records['updated'] = 0;
	$records['files'] = array();

	if (!$BotBanishSettings['BOTBANISH_UPDATE_HTML_ACTIVE'])
		return $records;

	if (!$BotBanishSettings['BOTBANISH_UPDATE_HTML']) {

		if (defined('SMF'))
			$context['BotBanishMsg'] = $BotBanishText['BotBanishUnModifyHTMLInComplete'];

		return $records;
	}

	$code_start = '<br><!-- ' . BOTBANISH_CODE_START . ' --><br>';
	$code_end = '<br><!-- ' . BOTBANISH_CODE_END . ' --><br>';
	$code_end_lth = strlen($code_end);

	$path = rtrim($BotBanishSettings['BOTBANISH_UPDATE_HTML_FOLDER'], '/') . '/';
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($rii as $file) {

        if (!$file->isDir()) {

            $htmlfile = $file->getPathname();
			$len = strlen($htmlfile);
			$found = false;

			// Check for either type of HTML files extensions

			if ((stripos($htmlfile, '.html') === ($len - 5)) ||	(stripos($htmlfile, '.htm') === ($len - 4)))
				$found = true;

			if ($found) {

				$data = file_get_contents($htmlfile);
				$found = false;

				// Remove all code placed into HTML files. Sometimes a failure can place code into files multiple times.
				// We need to remove all previous instances of our code!!!

				$records['filecount']++;

				while (true) {

					$pos_start = stripos($data, $code_start);
					$pos_end = stripos($data, $code_end);

					if (($pos_start !== false) && ($pos_end !== false)) {

						$data = substr($data, 0, $pos_start - 1) . substr($data, $pos_end + $code_end_lth);
						$found = true;

					} else {

						if ($found) {

							BotBanishWriteFile($htmlfile, $data);
							$records['updated']++;
							$records['files'][] = str_ireplace(array('\\', $_SERVER['DOCUMENT_ROOT']) , array('/', '') , $htmlfile);
						}

						break;
					}
				}
			}
		}
	}

	if ($records['updated'] > 0) {

		BotBanishSettingsTablePutValues(array('BOTBANISH_UPDATE_HTML_ACTIVE' => 0));

		// Do not write the log if we are uninstalling. The folder will be deleted or has already been deleted

		if ($write_log == true)
			BotBanishWriteFile(BOTBANISH_LOGS_DIR . 'BotBanish_htmlfiles_unmodified.log', BotBanishGrabDump($records['files']));
	}

	if (defined('SMF')) {

		$str = $records['updated'] > 0 ? '<br>' . BotBanishGrabArray($records['files']) : '';
		$context['BotBanishRows'] = $records['updated'] + 3;
		$context['BotBanishMsg'] = sprintf($BotBanishText['BotBanishUnModifyHTMLComplete'], $records['filecount'], $records['updated']) . $str;
		BotBanishUpdatePrettyURLs();
	}

	return $records;
}

function BotBanishRemoveCodeBlock($start_entry, $end_entry, $filename) {

	// Find all code blocks and remove them

	$data = file_get_contents($filename);
	$lth = strlen($data);

	while (true) {

		// Look for the start with a new line at the begining

		$pos_start = stripos($data, PHP_EOL . $start_entry);

		// If not found then look for the start without the new line at the beginning

		if ($pos_start === false)
			$pos_start = stripos($data, $start_entry);

		$pos_end = stripos($data, $end_entry . PHP_EOL);

		if ($pos_end === false)
			$pos_end = stripos($data, $end_entry);

		if (($pos_start !== false) && ($pos_end !== false)) {

			if ($pos_end > $pos_start)
				$data = substr($data, 0, $pos_start) . substr($data, $pos_end + strlen($end_entry . PHP_EOL));
		}else{
			break;
		}
	}

	if (strlen($data) < $lth)
		file_put_contents($filename, $data);
}

function BotBanishMaintainLogs($num = 0) {

	if (!defined('BOTBANISH_LOG_FILES') || (BOTBANISH_LOG_FILES < 5))
		return;

	if (empty($num))
		$num = BOTBANISH_LOG_FILES;

	$files_AccessError = glob(BOTBANISH_LOGS_DIR . '*_AccessError_*.log');
	$files_Error = glob(BOTBANISH_LOGS_DIR . '*_Activity_*.log');
	$files_Info = glob(BOTBANISH_LOGS_DIR . '*_Info_*.log');
	$files_SQL = glob(BOTBANISH_LOGS_DIR . '*_SQLStatements_*.log');

	$items = array($files_AccessError, $files_Error, $files_Info, $files_SQL);

	foreach ($items as $item) {

		sort($item);
		BotBanishRemoveLogs($item, $num);
	}
}

function BotBanishRemoveLogs($files, $max_files) {

	if ($max_files < 5)
		return;

	if (!is_array($files))
		$files = array($files);

	if (empty($files) || (count($files) <= $max_files))
		return;

	$i = 0;
	$x = count($files) - $max_files;

	if ($x > 0) {

		// Delete earliest version of files
		for ($i = 0; $i <= $x; $i++)
			unlink($files[$i]);
	}
}

function BotBanishCountryCodeBlockGet($CCode) {

	if ($CCode == 'null')
		return false;

	$table = 'botbanish_country_code';
	$sql = 'SELECT `country_code` FROM `' . $table . '` WHERE `country_code` = "' . $CCode . '" and `active` = 1';
	$rows = BotBanishExecuteSQL($sql);
	$flag = true;

	if (!is_array($rows) || empty($rows))
		$flag = false;

	return $flag;
}

function BotBanishCountryCodeBlockSet($CCode) {

	if ($CCode == 'null')
		return;

	$table = 'botbanish_country_code';
	$sql = 'UPDATE `' . $table . '` SET `active` = 1 WHERE `country_code` = "' . $CCode . '"';
	$rows = BotBanishExecuteSQL($sql);
}

function GoogleTranslate($text, $source, $target) {

    $apiKey = 'AIzaSyBdaA6ZsExyG5KWXTDXDMxsMcftlQ-aQKc';

	$url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q=' . rawurlencode($text) . '&source=' . $source . '&target=' . $target;

	$fields = array(
		'sl' => urlencode($source),
		'tl' => urlencode($target),
		'q' => urlencode($text)
	);

	// URL-ify the data for the POST
	$fields_string = "";
	foreach ($fields as $key => $value) {
		$fields_string .= '&' . $key . '=' . $value;
	}

	rtrim($fields_string, '&');

	// Open connection
	$ch = curl_init();

	// Set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	// Execute post
	$result = curl_exec($ch);

	// Close connection
	curl_close($ch);

	// Array ( [data] => Array ( [translations] => Array ( [0] => Array ( [translatedText] => Ändringsperiod ) [1] => Array ( [translatedText] => Ändringsperiod ) ) ) )
    $translated = (json_decode($result, true));

	if (!isset($translated['error'])) {

		return $translated['data']['translations'][0]['translatedText'];

	} else {

		$error = var_export($translated['error'], true);	
//		BotBanishLogError($error);
		echo $error . '<br><br>';
		return false;
	}
}

function BotBanishCheckForBlockedCountry($ip) {

	global $BotBanishText, $country_info;

	//------------------------------------------
	// Check if this country code is blocked
	//------------------------------------------

	$geo_data = BotBanishGetGeoData($ip);
	$country_info = BotBanishGetCountryData($geo_data);

	$flag = BotBanishCountryCodeBlockGet($country_info['country_code']);

	if ($flag)
		BotBanishLogError(sprintf($BotBanishText['countryblock'], $country_info['country'], $ip));

	return $flag;

	//------------------------------------------
	// End Check for country code block
	//------------------------------------------
}

function BotBanishRemoveCountryInfo($row, $botbanish_info) {

	// Client Versions below 4.1.00 will not understand the country fields so we must remove them
	// We must in the future pass the field BOTBANISH_VERSION_NO from the client
	// so that we can better check the client in the future

	if (BotBanishIsVersionBelow4100($botbanish_info)) {

		if (isset($row['country'])) unset($row['country']);
		if (isset($row['country_code'])) unset($row['country_code']);
		if (isset($row['geo_info'])) unset($row['geo_info']);
	}

	return $row;
}

function BotBanishIsVersionBelow4100($botbanish_info) {

	if ((stripos($botbanish_info['VERSION'], '4.1.') == false)
		|| (isset($botbanish_info['VERSION_NO'])
			&& $botbanish_info['VERSION_NO'] < 4100))

		return true;
	else
		return false;
}

function BotBanishSafeUnserialize($data) {

//	return unserialize(htmlspecialchars_decode($data));
	return unserialize($data);
}

function BotBanishSafeSerialize($data) {

//	return htmlspecialchars(serialize($data), ENT_QUOTES, 'utf-8');
	return serialize($data);
}

function BotBanishGetRidofBlockedCountry() {

	// If a blocked country, get rid of it

	$ip = BotBanishGetIP();

	if (BotBanishCheckForBlockedCountry($ip))
		BotBanishRedirectExit();
}

function BotBanishCreateSMFSettings() {
	
	// Add BotBanish to Pretty URLs skipaction setting.

	if (defined('SMF')) {

		// See if Pretty URLs is installed

		$name = 'pretty_';
		$tables = BotBanishGetTableNames($name);

		if (is_array($tables) && !empty($tables)) {

			global $sourcedir, $db_prefix, $modSettings;

			include_once $sourcedir . '/Subs.php';
			$botSettings = array();
			$value = 'pretty_skipactions';

			if (isset($modSettings[$value]) && !empty($modSettings[$value])) {
				
				if (stripos($modSettings[$value], 'botbanish') === false) {

					$botSettings[$value] = $modSettings[$value] . ',botbanish';
					updateSettings($modSettings[$value], true);
				}

			} else {

				if (!isset($modSettings[$value]) || empty($modSettings[$value])) {

					$botSettings[$value]= 'botbanish';
					updateSettings($botSettings, false);
				}
			}
		}
	}
}

function BotBanishRemoveSMFSettings() {

	// Remove BotBanish from Pretty URLs skipaction setting.

	if (defined('SMF')) {

		// See if Pretty URLs is installed
		
		if (BotBanishIsPrettyURLsInstalled()) {

			global $sourcedir, $db_prefix, $modSettings;

			if (isset($modSettings['pretty_skipactions']) && stripos($modSettings['pretty_skipactions'], 'botbanish') !== false) {

				include_once $sourcedir . '/Subs.php';
				$botSettings = array();
				$botSettings['pretty_skipactions'] = str_ireplace(array(',botbanish', 'botbanish,', 'botbanish'), '', $modSettings['pretty_skipactions']);
				updateSettings($botSettings, true);
			}
		}
	}
}

function BotBanishLoadLanguage() {

	loadLanguage('BotBanish/BotBanishLanguage');
}

function BotBanishAddErrorTypes(&$other_error_types, &$error_type, $error_message, $file, $line) {

	$error_type = 'BotBanish';
	$other_error_types[] = 'BotBanish';
	return 'BotBanish';
}

function BotBanish_UTF8_Encode(string $str): string {

	if (function_exists('mb_convert_encoding')) {

		$output = mb_convert_encoding($str, 'UTF-8', mb_list_encodings());
		
	} else {

		if (function_exists('iconv')) {
			
			$output = iconv('ISO-8859-1', 'UTF-8', $str);
			
		} else {
				
			$str .= $str;
			$len = strlen($str);

			for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
				
				switch (true) {
					
					case $str[$i] < "\x80": $str[$j] = $str[$i]; break;
					case $str[$i] < "\xC0": $str[$j] = "\xC2"; $str[++$j] = $str[$i]; break;
					default: $str[$j] = "\xC3"; $str[++$j] = \chr(\ord($str[$i]) - 64); break;
				}
			}
			
			$output = substr($str, 0, $j);
		}
	}

    return $output;
}

function BotBanish_EncodeToUtf8($string) {
     return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}

function BotBanish_EncodeToIso($string) {
     return mb_convert_encoding($string, "ISO-8859-1", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
}

function BotBanishIsPrettyURLsInstalled() {
	
	$name = 'pretty_';
	$tables = BotBanishGetTableNames($name);
	
	if (is_array($tables) && !empty($tables))
		return true;

	return false;
}

function BotBanishGetGeoData($ip) {
	
	if (BOTBANISH_CLIENT) {

		$row = array();
		$row['action'] = 'geo';
		$row['APIKEY'] = defined('BOTBANISH_APIKEY') ? BOTBANISH_APIKEY : 'free';
		$row['ip'] = $ip;
		$row['domain_name'] = $_SERVER['SERVER_NAME'];
		$row['contact'] = '';
		$row['htaccess'] = BOTBANISH_HTACCESS_ARRAY;
		$row['domain_root'] = $_SERVER['DOCUMENT_ROOT'];
		$row['version'] = BOTBANISH_VERSION_CLIENT;

		$data = BotBanishURLFormatData($row);
		$geo_data = BotBanishProcessRequest($data, BOTBANISH_REQUEST_METHOD);

		// If we get invalid data back from the POST, then we are most likely on a private local network.
		// Private local networks cannot be resolved to a country so we will use the country of install

		if (!is_array($geo_data) || empty($geo_data)) {
			
			$geo_data = BOTBANISH_COUNTRY_INFO;
			
		} else {
				
			$_SESSION['BotBanish']['country_ip'] = $ip;
			$_SESSION['BotBanish']['geo_data'] = $geo_data;
		}
	
	} else {
		
		require_once BOTBANISH_SERVER_DIR . 'BotBanishServer_Subs_Country.php';
		$geo_data = BotBanishGetGeoInfo($ip);
	}

	return $geo_data;
}

function BotBanishGetCountryData($geo_data) {

	$country_info = BOTBANISH_COUNTRY_INFO;
	$country_code = isset($geo_data['geoplugin_countryCode']) ? $geo_data['geoplugin_countryCode'] : '';
	
	if (empty($country_code))
		 $country_code = isset($geo_data['countryCode2']) ? $geo_data['countryCode2'] : $country_info['country_code'];
	
	$country = isset($geo_data['geoplugin_countryName']) ? $geo_data['geoplugin_countryName'] : '';
	
	if (empty($country))
		$country = isset($geo_data['country_name']) ? $geo_data['country_name'] : $country_info['country'];

	$flag = isset($geo_data['country_flag']) ? substr($geo_data['country_flag'], strripos($geo_data['country_flag'], '/') + 1) : 'null';
	
	$process = isset($geo_data['geoplugin_countryCode']) ? 'geoplugin' : '';
	
	if (empty($process))
		$process = isset($geo_data['countryCode2']) ? 'ipgeolocation' : '';
	
	return array('country' => $country, 'country_code' => $country_code, 'flag' => $flag, 'process' => $process);
}

function BotBanishCheckContactInfo($firstname, $lastname, $subject = '', $message = '') {
	
	$flag = false;

	// Message has url
	
	if (!empty($message)) {
		
		$disable = array('https://', 'http://', '@');
		$len = strlen($message);
		$newlen = strlen(str_ireplace($disable, '', $message));
		
		if ($len != $newlen) {
			
			BotBanishLogError('Message has URL\'s.' . $str);
			return true;
		}
	}

	// Subject and message the same
	
	if ((!empty($subject) && !empty($message)) && ($message == $subject)) {
		
		BotBanishLogError('Subject and Message the same.' . $str);
		return true;
	}
	
	if (empty($firstname) && empty($lastname))
		return true;;
	
	$str = PHP_EOL . 'Firstname: ' . $firstname . PHP_EOL . 'Lastname: ' . $lastname . PHP_EOL . 'Subject: ' . $subject . PHP_EOL . 'Message: ' . $message;
	
	// Lastname is same as first name except last two characters
	
	if (!empty($firstname) && !empty($lastname)) {
		
		if ((strpos($firstname, $lastname) == 0) && 
			(strlen($lastname) > strlen($firstname)) &&
			(strlen($lastname) - strlen($firstname) == 2)) {
				
				BotBanishLogError('First and Last Names the same plus 2 characters.' . $str);
				$flag = true;
		}
	}
	
	// First and last name the same
	
	if ($firstname == $lastname) {
		
		BotBanishLogError('First and Last Names the same.' . $str);
		$flag = true;
	}

	// Name and subject the same
	
	if (trim($firstname) == trim($subject)) {
		
		BotBanishLogError('Name and Subject the same.' . $str);
		$flag = true;
	}

	
	if ($flag) {
		
		$ip = BotBanishGetIP();
		BotBanishClient(true, null ,null ,null ,$ip ,null);
		BotBanishExit(true);
		return true;
	}
	
	return false;
}

?>