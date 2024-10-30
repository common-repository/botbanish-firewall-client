<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Pre-install Check
// Date: 05/30/2024
// Usage: Called directly
// Description: Checks to see if the needed modules and function are present before installation begins
//
//	Array structure
//		'extensions' - Any PHP module that may need to be present
//		'functions' - Array of any PHP fuctions that may need to be present
//		'level' 	- Type of checking to comply with
//		'module'	- Array of Apache module that need to be loaded
//
//
// Levels:
// 0 - One item must exists
// 1 - Every item must exist
// 2 - Not needed but may be used if existing
///////////////////////////////////////////////////////////////////////////////////////////////////////

	$php_version_max = '8.2.18';

	if (defined('BOTBANISH')) {
		$filename = BOTBANISH_INCLUDE_DIR . 'BotBanishVersion.php';

		if (file_exists($filename)) {

			include_once $filename;
			$php_version_max = BOTBANISH_VERSION_PHP;
		}
	}

	// Will not work on PHP-FPM!!! But we get all the information that we can

	$apache_modules = array();

	if (function_exists('apache_get_modules'))
		$apache_modules[] = apache_get_modules();

	$items = BotBanish_PHPInfo();
	$apache_modules[] = isset($items['loaded modules']) ? explode(' ', $items['loaded modules']) : array();
	$server_api = isset($items['server api']) ? $items['server api'] : 'Unknown';
		
	BotBanishCheckPreInstall($php_version_max, $apache_modules, $server_api);

function BotBanishCheckPreInstall($php_version_max, $apache_modules, $server_api) {

	echo '
	<!DOCTYPE html>
	<html>
	<head>
	<title>BotBanish PreiInstall Checker</title>
	</head>';

	echo 'BotBanish Pre-Install Checker<br><br>';

	echo '
	<form name="theForm" id="theForm" action="" method="post" enctype="multipart/form-data">' . PHP_EOL;

	$exists = array(
		array('extensions' => array('curl'), 'functions' => array('fetch_web_data', 'file_get_contents'), 'level' => 0),
		array('extensions' => array('ftp'), 'functions' => array('ftp_connect'), 'level' => 2),
		array('extensions' => array('mbstring', 'mysqli'), 'functions' => array('fopen', 'mb_convert_encoding'), 'modules' => array('mod_env'), 'level' => 1),
	);

	$phpversion = phpversion();
	$error = '';
	$maxphpversion = '';
	$curphpversion = '';

	$arr = explode('.', $phpversion);
	
	foreach($arr as $num)
		$curphpversion .= substr($num . '0', 0 , 2);

	$arr = explode('.', $php_version_max);

	foreach($arr as $num)
		$maxphpversion .= substr($num . '0', 0 , 2);

	if ($curphpversion > $maxphpversion)
		$error = '<div style="background-color:Red; color:White";>WARNING: Not tested on PHP ' . $phpversion . '. Only tested up to PHP ' . $php_version_max . '</div>' . PHP_EOL;

	// Check for all modules and functions that we need to operate

	$error_txt = '';

	foreach ($exists as $exist) {

		$count = 0;
		$array_func = array();
		$array_ext = array();
		$error_txt .= '<table width=60%>
						<tr>
							<td width=20%><b>Status</b></td>
							<td width=20%><b>Name</b></td>
							<td width=20%><b>Type</b></td>
						</tr>';

		// Find all extensions and function that are in our lists that may need to be loaded

		if (isset($exist['extensions'])) {

			foreach ($exist['extensions'] as $extension) {

				if (BotBanishIsExtensionLoaded($extension)) {

					$array_ext[] = '
									<tr>
										<td>Loaded: </td><td><b>' . $extension . '</b></td><td>PHP Extension</td>
									</tr>' . PHP_EOL;
					$count++;

				} else {

					$array_ext[] = '
									<tr>
										<td>Not Loaded: </td><td>' . $extension . '</td><td>PHP Extension</td>
									</tr>' . PHP_EOL;
				}
			}
		}

		if (isset($exist['functions'])) {

			foreach ($exist['functions'] as $function) {

				if (BotBanishIsFunctionLoaded($function)) {

					$array_func[] = '
									<tr>
										<td>Loaded: </td><td><b>' . $function . '</b></td><td>PHP Function</td>
									</tr>' . PHP_EOL;
					$count++;

				} else {

					$array_func[] = '
									<tr>
										<td>Not Loaded: </td><td>' . $function . '</td><td>PHP Function</td>
									</tr>' . PHP_EOL;
				}
			}
		}

		if (isset($exist['modules'])) {

			$i = 0;
			
			foreach ($apache_modules as $apache_module)
				$i .+ count($apache_module) . '</br>';
				
			$php = $i == 0 ? '</br>* * * Apache modules cannot be detected if PHP is running as a module of Apache (PHP-FPM). * * *' : '';

			foreach ($exist['modules'] as $module) {

				if (BotBanishIsModuleEnabled($module, $apache_modules)) {

					$array_func[] = '
									<tr>
										<td>Enabled: </td><td><b>' . $module . '</b></td><td>Apache Module</td>
									</tr>' . PHP_EOL;
					$count++;

				} else {

					$array_func[] = '
									<tr>
										<td>Not Enabled: </td><td>' . $module . '</td><td>Apache Module</td>
									</tr>' . PHP_EOL;
				}
			}
		}

		$txt = '<div>';
		$txt .=  !empty($array_ext) ? implode('<br>', $array_ext) : '';
		$txt .= !empty($array_func) ? implode('<br>', $array_func) : '';
		$txt .= '</div></table>' . PHP_EOL;
		$txt = str_ireplace('<br>', '', $txt);

		$found = false;

		switch ($exist['level']) {

			case 0:

				if ($count == 0) {

					$error_txt .= '<div style="background-color:Red; color:White";>One extensions or functions must be loaded - BAD (None loaded)</color></div>' . PHP_EOL;

				} else {

					$error_txt .= '<div style="background-color:Green; color:White";>At least one extension or function must be loaded - GOOD<color></div>' . PHP_EOL;
				}


				break;

			case 1:

				$item_count = 0;

				if (isset($exist['extensions']))
					$item_count += count($exist['extensions']);

				if (isset($exist['functions']))
					$item_count += count($exist['functions']);

				if (isset($exist['modules']))
					$item_count += count($exist['modules']);

				if ($count !== $item_count) {

					$error_txt .= '<div style="background-color:Red; color:White;">Every extension, function and module must be loaded - BAD (All must be loaded) ' . $php . '</div>' . PHP_EOL;

				} else {

					$error_txt .= '<div style="background-color:Green; color:White;">Every extension, function and module is loaded - GOOD</div>' . PHP_EOL;
				}

				break;

			case 2:

				$error_txt .= '<div style="background-color:Yellow; color:Black;">Extensions, functions and modules are not needed but may be used if loaded - OK</div>' . PHP_EOL;

				break;
		}

		$error_txt .= $txt . '<br>';
	}

	echo $error;	
	echo '<div>PHP Version: ' . $phpversion . '</div><br>';
	echo '<div>Server API: ' . $server_api . '</div><br>' . PHP_EOL;
	echo 	'<table <table width=60%>'
			. PHP_EOL . $error_txt .
		'</table>' . PHP_EOL;

	echo '
		</form>
		</body>
	</html>';
}

function BotBanishIsFunctionLoaded($function_name) {

	if (empty($function_name))
		return false;
	
	return function_exists($function_name);
}

function BotBanishIsExtensionLoaded($extension_name) {

	if (empty($extension_name))
		return false;
	
	return extension_loaded($extension_name);
}

function BotBanishIsModuleEnabled($module, $apache_modules) {

	if (empty($apache_modules))
		return false;
	
	$flag = false;
	
	foreach($apache_modules as $apache_module) {
		
		$flag = array_search($module, $apache_module);
		if ($flag !== false) break;
	}
	
	return $flag;
}

function Botbanish_PHPInfo() {

	ob_start();
	phpinfo();
	$page = ob_get_clean();

	$lines = explode("\n", $page);
	$items = array();

	foreach ($lines as $line) {

		if (stripos($line, '<tr><td class="e">') !== false) {

			$line = str_ireplace('</td><td class="v">', '</td><td class="v">=>', $line);
			$line = strip_tags($line);
			$tmp_array = explode('=>', $line);
			$name = isset($tmp_array[0]) ? strtolower(trim($tmp_array[0])) : '';
			$text = isset($tmp_array[1]) ? trim($tmp_array[1]) : '';

			if (!empty($name) && !empty($text))
				$items[$name] = $text;
		}
	}

	return $items;
}
?>