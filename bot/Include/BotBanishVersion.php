<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Version defines
// Date: 05/27/2024
// Usage:
//
// Function:
//		Create BotBanish Version Information
///////////////////////////////////////////////////////////////////////////////////////////////////////

global $boarddir;


$botbanish_include_folder = defined('SMF') ? $boarddir . '/BotBanish/bot/Include/' : '';
$botbanish_include_folder = empty($botbanish_include_folder) && defined('ABSPATH') ?  __DIR__ . '/' : $botbanish_include_folder;
$botbanish_include_folder = empty($botbanish_include_folder) && defined('OPENCART') ?  __DIR__ . '/' : $botbanish_include_folder;
$botbanish_include_folder = empty($botbanish_include_folder) ? BotBanishGetIncludeLocation('BotBanishVersion.php') : $botbanish_include_folder;;
$botbanish_include_folder = empty($botbanish_include_folder) && defined('BOTBANISH_INCLUDE_DIR') ? BOTBANISH_INCLUDE_DIR : $botbanish_include_folder;

require_once  $botbanish_include_folder . 'BotBanish_PreIncludes.php';

BotBanishDefineGlobals('BOTBANISH_VERSION_CLIENT', '5.2.01');
BotBanishDefineGlobals('BOTBANISH_VERSION_SERVER', '5.0.00');
BotBanishDefineGlobals('BOTBANISH_VERSION_DATABASE', '_5.0');
BotBanishDefineGlobals('BOTBANISH_COUNTRY_INFO', array('country' => 'United States', 'country_code' => 'us', 'flag' => 'us.svg', 'process' => 'BotBanish'));

$botbanish_system = BotBanishCheckSystem();

if ($botbanish_system['install_type'] == 'Server') {
	
	BotBanishDefineGlobals('BOTBANISH_VERSION_NO', intval(str_replace('.', '', BOTBANISH_VERSION_SERVER)));
	$BotBanishVersionNo = BOTBANISH_VERSION_SERVER;
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_DIR', '/BotBanish/bot_v' . BOTBANISH_VERSION_SERVER . '/Language/');
	
} else {
	
	BotBanishDefineGlobals('BOTBANISH_VERSION_NO', intval(str_replace('.', '', BOTBANISH_VERSION_CLIENT)));
	$BotBanishVersionNo = BOTBANISH_VERSION_CLIENT;
	BotBanishDefineGlobals('BOTBANISH_LANGUAGE_DIR', realpath($botbanish_include_folder . '../Language') . '/');
}

BotBanishDefineGlobals('BOTBANISH_VERSION_PHP', '8.1.99');
	
BotBanishDefineGlobals('BOTBANISH_VERSION', 'BotBanish ' . ucfirst($botbanish_system['install_type']) . ' ' . $BotBanishVersionNo . ' (' . $botbanish_system['system'] . ')');

function BotBanishGetIncludeLocation ($filename) {

	$dir = '';
	$paths = get_included_files();
	
	foreach ($paths as $path) {
		
		if (stripos($path, $filename) > 0)	{
			$dir = str_ireplace($filename, '', $path);
			break;
		}
	}
	
	return $dir;
}
?>