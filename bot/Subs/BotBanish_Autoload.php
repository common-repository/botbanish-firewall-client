<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 4.0.05
// Randem Systems: https://randemsystems.com/support/
// BotBanish Autoload Functions
// Date: 10/09/2022
// Usage: autoload(array(dir1, dir2,...));
//
// Function:
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanish_Autoload($autoload_folder, $ignores = array()) {

	$count = 50;
	$start = 'autoload start ';
	$end = 'autoload end ';
	
	$lth = intval((($count - intval(strlen($start)) / 2)));
	$sep = str_repeat('* ', $lth);
	$start_str = $sep . $start . $sep;
	
	$lth = intval((intval($count - strlen($end))) / 2);
	$sep = str_repeat('* ', $lth);
	$end_str = $sep . $end . $sep;
	
	BotBanishLogProgress($start_str);
			
	$paths = array();
	
	$dirs = glob($autoload_folder . '*', GLOB_ONLYDIR);

	foreach ($dirs as $dir) {
		
		$folder = FindFolderSrc($dir, 'src');
		$paths = array_unique(array_merge($paths, getAllFolders($folder)));
	}

	$namesArray = array();

	// Get all files in each folder

	foreach ($paths as $path) {
		
		$array = glob($path . '/*.php');
		
		if (!empty($array) && is_array($array)) {
			
			foreach ($array as $key => $value) {
				
				foreach ($ignores as $ignore) {
					
					if (stripos($value, $ignore) !== false)
						unset ($array[$key]);
				}
			}
		}
		
		if (!empty($array))
			$namesArray[] = $array;
	}

	// Load all exceptions first

	$exceptions = array('interface ', ' implements ', 'abstract class');

	foreach ($exceptions as $exception) {

		foreach ($namesArray as $key => $names)
			loadFirst($names, $namesArray, $key, $exception);
	}

	// Load the rest of the files

	foreach ($namesArray as $names) {

		foreach ($names as $key => $file) {

			require_once $file;
			BotBanishLogProgress($file);
		}
	}
	
	BotBanishLogProgress($end_str);
}

function FindFolderSrc ($dir, $str) {
	
	$paths = array();
	$found = false;
	$paths = array_unique(array_merge($paths, getAllFolders($dir)));
	 
	foreach($paths as $path) {
	
		$dir = str_replace('\\', '/', $path);
		$parts = explode('/', $dir);
		
		if ($parts[count($parts) - 1] == $str) {
			$found = true;
			break;
		}
	}
	
	if ($found)
		return $path;
	else
		return false;
}

function loadFirst(&$names, &$namesArray, $index, $exception) {

	// Load all files that could possibly be needed first to prevent terminal load errors

	foreach ($names as $key => $file) {

		if (empty($file))
			continue;

		$fh = fopen($file, 'r');
		$data = fread($fh, filesize($file));
		fclose($fh);

		if ((strpos($data, $exception) === false))
			continue;

		$str = ' * * * ' . $exception . ' * * * : ' . $file;
		BotBanishLogProgress($str);
		
		require_once $file;
		
		unset($namesArray[$index][$key]);
		unset($names[$key]);
/*		
		$namesArray[$index][$key] = '';
		$names[$key] = '';
*/
	}
}

function getAllFolders($pathname) {

	$dirs = array();
	
	// If user has given the full path, we use it. Otherwise the user has given a relative path from the root
	
	if (is_dir($pathname))
		$root = $pathname;
	else
		$root = realpath(rtrim(getcwd(), '/')) . '/' . $pathname;
	
	// Slash changes are needed to Work on WampServer (Windows) or Linux systems
	
	$root = isset($_SERVER['WINDIR']) ? str_replace('/', '\\', $root) :  str_replace('\\', '/', $root);	
	$index = isset($_SERVER['WINDIR']) ? explode('\\', $root) : explode('/', $root);
	
	for ($i=count($index)-1; $i>0; $i--) {
		
		if (empty($index[$i]))
			array_pop($index);
	}
	
	array_pop($index);
	$root = isset($_SERVER['WINDIR']) ? implode('\\', $index) : implode('/', $index);

	$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($pathname),
				RecursiveIteratorIterator::LEAVES_ONLY);

	foreach($iterator as $file) {

		if ($file->isDir()) {

			$folder = $file->getRealpath();

			if ($folder == $root)
				continue;

			$dirs[] = $folder;
		}
	};

	return array_unique($dirs);
}

function adjustPath($pathname, $changeOnly = false) {
	
	$root = $pathname;
	if (!$changeOnly) $root = realpath(rtrim(getcwd(), '/')) . '/' . $root;

	// Slash changes are needed to Work on WampServer (Windows) or Linux systems

	$root = isset($_SERVER['WINDIR']) ? str_replace('/', '\\', $root) :  str_replace('\\', '/', $root);	
	$index = isset($_SERVER['WINDIR']) ? explode('\\', $root) : explode('/', $root);
	
	if (!$changeOnly) {

		for ($i=count($index)-1; $i>0; $i--) {
			
			if (empty($index[$i]))
				array_pop($index);
		}
		
		array_pop($index);
	}
	
	$root = isset($_SERVER['WINDIR']) ? implode('\\', $index) : implode('/', $index);
	return $root;
}
?>