<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Pre-Incude Routines
// Date: 05/25/2024
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

BotBanish_SetFolder();

function BotBanishCheckSystem() {

	$system = getenv('BOTBANISH_SYSTEM') != false ? getenv('BOTBANISH_SYSTEM') : '';
	$system = empty($system) && defined('SMF') ? 'SMF' : $system;
	$system = empty($system) && defined('ABSPATH') ? 'WORDPRESS' : $system;
	$system = empty($system) && defined('DIR_APPLICATION') ? 'OPENCART' : $system;
	$system = empty($system) ? 'WEBSITE' : $system;

	$dir = '';
	$paths = get_included_files();
	$i = count($paths) - 1;

	do {

		$path = str_replace('\\', '/', $paths[$i]);

		if (stripos($path, '/bot/Include/') !== false) {

			$index = strrpos($path, '/');
			$dir = realpath(substr($path, 0, $index + 1) . '../');
			$dir = str_replace('\\', '/', $dir);
			break;
		}

		$i--;

	} while ($i > 0);
		$install_type = is_dir($dir . '/Server') ? 'Server' : 'Client';
	return array('system' => $system, 'install_type' => $install_type);
}

function BotBanish_getWpTimezone() {

	$timezone_string = get_option( 'timezone_string' );

	if (!empty($timezone_string ))
		return $timezone_string;

	return 'Pacific/Honolulu';
}

function BotBanishDefineGlobals($variable, $value) {
	global $BotBanishSettings;

	// Defines cannot be redefined (they are static) but we can update the BotBanish Settings array for dynamic changes
	
	$value = is_numeric($value) ? intval($value) : $value;
	if (!defined($variable)) define($variable, $value);
	$BotBanishSettings[$variable] = $value;
}

function BotBanishGetConfigLocation($config = 'config.php') {

	$dir = rtrim(getcwd(), '/') . '/';
	$dir = str_replace('\\', '/', $dir);
	$found = false;

	while (true){

		if (file_exists($dir . $config)) {
			$found = true;
			break;
		}

		if (($dir == $_SERVER['DOCUMENT_ROOT']) || empty($dir) || ($dir == '/'))
			break;

		$pre = $dir;
		$dir = rtrim(realpath($dir . '../'), '/') . '/';

		if ($dir === $pre)
			break;
	}

	if (!$found)
		$dir = '';

	return $dir;
}

function BotBanish_SetFolder() {

	// This is called upon loading BotBanish_PreIncludes.php directly in loading Settings_Client or Settings_Server
	// for this to work properly

	if (defined('ABSPATH')) {
		BotBanishDefineGlobals('BOTBANISH_CURRENT_FOLDER', 'bot');
		return;
	}

	$paths = get_included_files();
	$i = count($paths) - 1;

	do {

		if (stripos($paths[$i], 'BotBanish_PreIncludes.php') !== false) {

			$path = str_replace('\\', '/', $paths[$i]);
			break;
		}

		$i--;

	} while ($i > 0);

	$dirs = explode('/', $path);
	unset ($dirs[count($dirs) - 1]);
	$i = 0;

	foreach ($dirs as $dir) {

		if ($dir == 'BotBanish') {
			BotBanishDefineGlobals('BOTBANISH_CURRENT_FOLDER', $dirs[$i + 1]);
			break;
		}

		$i++;
	}

	if (!defined('BOTBANISH_CURRENT_FOLDER')) 
		BotBanishDefineGlobals('BOTBANISH_CURRENT_FOLDER', $dirs[$i - 1]);
}

function BotBanishGetHeaders($url) {
	
	$headers = @get_headers($url);
	
	// If php fails try curl
	
	if (empty($headers)) {
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		$output = curl_exec($ch);
		curl_close($ch);

		if (!empty($output)) {
			
			$headers = array();
			$output = rtrim($output);
			$data = explode("\n",$output);
			
		} else {
			
			return false;
		}
		
	} else {
		
		$data = $headers;
		$headers = array();
	}

	$headers['Status'] = $data[0];
	array_shift($data);

	foreach($data as $part){
		
		//some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
		$middle = explode(":",$part,2);

		//Supress warning message if $middle[1] does not exist, Thanks to @crayons
		if ( !isset($middle[1]) ) { $middle[1] = null; }

		$headers[trim($middle[0])] = trim($middle[1]);
	}
	
	return $headers;
}

function BotBanishRemoteImageFileExists( $url ) {
	
	$response = wp_remote_head( $url );

	return 200 === wp_remote_retrieve_response_code( $response );
}

function BotBanishLanguageSupported(&$language) {
	
	global $BotBanishSettings;
	
	if (!isset($BotBanishSettings['BOTBANISH_LANGUAGES'])) {
		
		$language = 'english';
		return false;
	}
	
	$flag = array_search($language, $BotBanishSettings['BOTBANISH_LANGUAGES']);
	
	if ($flag === false) {
		
		$language = $BotBanishSettings['BOTBANISH_LANGUAGE_SELECT'];
		return $flag;
		
	} else {
		
		return true;
	}
}
?>