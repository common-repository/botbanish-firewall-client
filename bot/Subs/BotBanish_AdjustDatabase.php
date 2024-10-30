<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Database Change Structure Subroutines
// Date: 06/01/2024
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

	// If we are called via a POST / GET, perform database or table dump operations otherwise we were called by an INCLUDE / REQUIRE call
	// in that case we just load the functions that will be needed so they can be called

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'BotBanish_AdjustDatabase.php') !== false)
		BotBanish_DumpIt();

function BotBanish_DumpIt() {

	$dir = (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) ? BOTBANISH_SERVER_DIR : BOTBANISH_CLIENT_DIR;

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
		require_once $filename;

		if (isset($_POST['root'])) {

			chdir($_POST['root']);
			$boarddir = $_POST['root'];
		}

		if (isset($_POST['include']))
			require_once urldecode($_POST['include']);

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
		require_once $filename;
	}

	$row = BotBanishGetPassedData();

	if (!isset($row['location']))
		return;

	$row['prefix'] = isset($row['prefix']) ? $row['prefix'] : 'botbanish';
	$row['location'] = isset($row['sqlpath']) ? rtrim($row['sqlpath'], '/') . '/' : rtrim($row['location'], '/');
//	$row['location'] = empty($row['location']) ? rtrim($row['location'], '/') . '/' : 'Backups/';

	$info = 'info=' . BotBanishSafeSerialize(array(
		'prefix' => $row['prefix'],
		'data' => false,
		'structure' => true,
		'indexes' => true,
		'compress' => false,
		'type' => 'Before',
		'location' => $row['location'],
		)
	);

	$include = '&include=' . urlencode($boarddir . '/BotBanish/bot/BotBanish_Settings.php');
	$root = '&root=' . urlencode($boarddir);

	$url = BOTBANISH_INSTALL_URL . 'Subs/BotBanish_DumpDatabase.php';

	$download_link_Before = BotBanishGetCorrectTime() . ' - ' . BotBanishSendPostData($url, $info . $include . $root);

	BotBanish_CreateDatabase();
	BotBanishHTACCESSImport();

	$info = 'info=' . BotBanishSafeSerialize(array(
		'prefix' => 'botbanish',
		'data' => false,
		'structure' => true,
		'indexes' => true,
		'compress' => false,
		'type' => 'After',
		'location' => $row['location'],
		)
	);

	$download_link_After = BotBanishGetCorrectTime() . ' - ' . BotBanishSendPostData($url, $info . $include . $root);

	echo 'Database Conversion: ' . BOTBANISH_DB_NAME . '<br />';
	echo $download_link_Before . '<br />';
	echo $download_link_After . '<br />';
	echo BotBanishGetCorrectTime() . ' - ' . 'Done';
}

function BotBanish_DumpSingleTable($tablename) {

	global $boarddir;
	
	if (empty($tablename))
		return;

	$info = array(
		'prefix' => $tablename,
		'data' => true,
		'structure' => true,
		'indexes' => true,
		'compress' => true,
		'type' => $tablename,
		'location' => BOTBANISH_BACKUPS_DIR . 'SQL/',
		);

	$arr = BotBanishDumpTables($info);
}

function BotBanish_CreateDatabase() {

	// We need all the time we can get

	set_time_limit(0);

	if (@ini_get('memory_limit') < 512)
		@ini_set('memory_limit', '512M');

	BotBanishCheckForLicense();
	
	//*******************************************************************************************************
	// Create all the tables that we will need from our mySQL exported file. If the table already exist nothing is done.
	//*******************************************************************************************************

	// Determine if we need to drop the table and start anew
	// Create any tables that do not exist

	BotBanishCreateTables(BOTBANISH_CLIENT_DIR . 'BotBanishClient.sql', 'botbanishclient_', false);
	BotBanishCreateTables(BOTBANISH_LANGUAGE_DIR . 'BotBanishLanguage.sql', 'botbanish_language', false);

	if (defined('BOTBANISH_SERVER') && BOTBANISH_SERVER)
		BotBanishCreateTables(BOTBANISH_SERVER_DIR . 'BotBanishServer.sql', 'botbanishserver_', false);

	//*******************************************************************************************************
	// Make sure that BotBanish never automatically locks out this install from the itself or the server
	//*******************************************************************************************************

	$ip = BotBanishGetIP();
	$table = 'bbc_botbanishclient_ip_dnb';
	$row = array();
	$row['bot_ip'] = $ip;
	$row['name'] = 'BotBanish';
	$row['active'] = 1;

	$params = BotBanishBuildSQLStatementInsert($row, $table);
	$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

	if (isset($params['updates']) && !empty($params['updates']))
		$sql .=	' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql, true, true);

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		$table = 'bbs_botbanishserver_ip_dnb';
		$row = array();
		$row['bot_ip'] = $ip;
		$row['name'] = 'BotBanish';
		$row['active'] = 1;

		$params = BotBanishBuildSQLStatementInsert($row, $table);
		$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

		if (isset($params['updates']) && !empty($params['updates']))
			$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

		BotBanishExecuteSQL($sql, true, true);
	}

	//*******************************************************************************************************
	// Populate our spider, domain and otehr pre-populated tables.
	//*******************************************************************************************************

	BotBanish_PopulateTables();
	return array('ip' => 0, 'bot' => 0, 'filecount' => 0, 'updated' => 0);
}

function BotBanishCheckForLicense() {

	if (defined('BOTBANISH_CLIENT') && (BOTBANISH_CLIENT)) {
		
		$tables = BotBanishGetTableNames('bbc_botbanishclient_settings');
	
		if (is_array($tables) && in_array('bbc_botbanishclient_settings', $tables)) {
			
			$table = 'bbc_botbanishclient_settings';
			$sql = 'SELECT `value` FROM `' . $table . '` WHERE `name` = "BOTBANISH_APIKEY"';
			$rows = BotBanishExecuteSQL($sql, true, true);
			
			if (is_array($rows))	
				BotBanishDefineGlobals('BOTBANISH_APIKEY', $rows[0]['value']);
		}
	}
}

function BotBanishUpdateLicense() {
	global $BotBanishSettings;
	
	if (defined('BOTBANISH_CLIENT') && (BOTBANISH_CLIENT)) {

		$table = 'bbc_botbanishclient_settings';
		
		if (defined('BOTBANISH_APIKEY')) {
			
			$sql = 'UPDATE `' . $table . '` SET `value` = "' . $BotBanishSettings['BOTBANISH_APIKEY'] . '" WHERE `name` = "BOTBANISH_APIKEY"';
			$rows = BotBanishExecuteSQL($sql, true, true);
			
		} else {

			$sql = 'INSERT INTO `' . $table . '` (`name`, `value`, `type`) VALUES ("BOTBANISH_APIKEY", "Free", 1)';
			BotBanishExecuteSQL($sql, true, true);		
			BotBanishDefineGlobals('BOTBANISH_APIKEY', 'Free');
		}
	}
}

function BotBanish_CleanUpTables($newtables) {

	foreach ($newtables as $table) {

		$sql = 'DROP TABLE IF EXISTS `' . $table . '`';
		BotBanishExecuteSQL($sql);
	}
}

function BotBanish_PopulateTables() {

	// Process Server Tables

	if (BOTBANISH_SERVER)
		BotBanishPopulate(BOTBANISH_DATA_DIR . 'Server/', 'botbanishserver_*.php', 'bbs_');

	// Process Client Tables

	BotBanishPopulate(BOTBANISH_DATA_DIR . 'Client/', 'botbanishclient_*.php', 'bbc_');
	BotBanishPopulate(BOTBANISH_DATA_DIR, 'botbanish_language.php', '');

	BotBanishCreateLanguageTables();
	BotBanishPopulate(BOTBANISH_DATA_DIR, 'botbanish_*.php', '');

	BotBanishUpdateLicense();

	BotBanishCreateSMFSettings();
}

function BotBanishPopulate($folder, $name, $db_prefix = '') {

	$files = BotBanishGetFileNames($folder . $name);

	if ($files === false)
		return;

	foreach ($files as $filename) {

		$str =  str_ireplace('\\', '/', $filename);
		$str = explode('/', $str);
		$table = $db_prefix . str_ireplace('.php', '', $str[count($str) - 1]);

		if (BotBanishTableHasData($table) === false)
			BotBanishImportTableData($table, $filename);
	}
}

function BotBanishTableHasData($table) {

	$sql = 'SELECT * FROM information_schema.tables WHERE table_schema = "' . BOTBANISH_DB_NAME . '" AND table_name = "' . $table . '"';
	$rows = BotBanishExecuteSQL($sql, false, true);

	if (!empty($rows) && is_array($rows)) {

		$sql = 'SELECT * FROM `' . $table . '` WHERE 1 LIMIT 2';
		$rows = BotBanishExecuteSQL($sql, false, true);

		if (!empty($rows) && is_array($rows))
			return true;
		else
			return false;

	} else {

		return false;
	}
}

function BotBanishImportTableData($tablename, $filename) {

	if (!file_exists($filename))
		return;

	$numerics = array('integer', 'int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'bit', 'float', 'double', 'decimal', 'numeric');

	// Find all the columns in the destination table in order to eliminate the missing columns from the source

	$sql = 'SELECT `COLUMN_NAME`, `DATA_TYPE` FROM information_schema.COLUMNS WHERE (`TABLE_SCHEMA` = "' . BOTBANISH_DB_NAME . '") AND (`TABLE_NAME` = "' . $tablename . '")';
	$rows = BotBanishExecuteSQL($sql, true, false);

	if (!empty($rows) && is_array($rows)) {

		$columns = array();
		$datatype = array();

		foreach ($rows as $row) {

			$columns[] = $row['COLUMN_NAME'];
			$datatype[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
		}

		$items = include ($filename);

		// Create the insert for the destination table.

		foreach ($items as $item) {

			$row = array();

			foreach ($item as $key => $value) {

				// Make sure only the columns that exist in our destination table are used
				// If a datetime field's value is zero, remove the field and let the database fill in the field

				if (in_array($key, $columns)) {

					if (isset($datatype[$key]) && ($datatype[$key] == 'datetime')) {

						if ($value == '0')
							unset($row[$key]);
						else
							$row[$key] = is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;

					} else {

						$row[$key] = in_array($datatype[$key], $numerics) ? intval($value) : $value;
					}
				}
			}

			$params = BotBanishBuildSQLStatementInsert($row, $tablename);

			$sql = 'INSERT INTO `' . $tablename . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

			if (isset($params['updates']) && !empty($params['updates']))
				$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

			BotBanishExecuteSQL($sql, true, true);
		}
	}
}

function BotBanishCreateLanguageTables() {

	// Create all of the language tables based on the language table structure
	// for the different languages that are in the language selection table.

	$sql = 'SELECT `language` FROM `botbanish_language`';
	$rows = BotBanishExecuteSQL($sql, true, true);

	foreach ($rows as $row) {

		$table = 'botbanish_language_text_' . strtolower($row['language']);

		$sql = 'DROP TABLE IF EXISTS `' . $table . '`';
		$rows = BotBanishExecuteSQL($sql, true, true);

		$sql = 'CREATE TABLE `' . $table . '` LIKE `botbanish_language_text`';
		$rows = BotBanishExecuteSQL($sql, true, true);
	}
}

function BotBanishCreateTables($sql_filename, $table_name, $db_ops = true) {

	global $BotBanishText;

	$tables = BotBanishGetTableNames($table_name);

	$cmd = array('DROP', 'CREATE', 'INSERT', 'ALTER', 'USE', 'UPDATE');
	$db_ops = false;

	$lines = file($sql_filename,  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	$sql = '';
	$start = true;

	if (!isset($lines) || !is_array($lines)) {

		$msg = $BotBanishText['BotBanish_NoInfo'] . ': ' . $sql_filename . "<br />";
		BotBanishLogError($msg);
		BotBanishReturnData($msg);
	}

	foreach ($lines as $line_no => $line) {

		$line = trim($line);

		if (!empty($line)) {

			if ($start === true) {
				$sql = '';
				$type = BotBanishFindStartOfSQL($line, $cmd, false);

				// The $type is the actual command in the statement from the $cmd array

				if ($type !== false)
					$start = false;
			}

			if ($type !== false) {

				if (BotBanishCheckForEOL($line, $sql, $start)) {

					// Replace the database prefix with the new one given by the install

					$sql = str_ireplace(array('$$', 'DELIMITER'), array('', ''), $sql);

					// * * * NO Database Level Operations should be done on OpenCart and SMF systems * * *

					if ((stripos($sql, '`' . BOTBANISH_DB_NAME . '`') !== false) || stripos($sql, 'Database') !== false) {

						$statement = array();
						$statement[] = $sql;

						// Modify sql for table prefix if on server

						BotBanishDatabaseOperations($statement);

					} else {

						// Do not do table operations on tables that already exist
						// Operator MUST manually delete them first!!!

						if (!BotBanishCheckForTable($sql, $tables))
							BotBanishExecuteSQL($sql, true, true);
					}

					$start = true;
					$sql = '';
				}
			}
		}
	}

	BotBanishFixCountry($tables);
}

function BotBanishFixCountry($tables) {

	foreach ($tables as $table) {

//		$table = str_ireplace(BOTBANISH_DB_PREFIX, '', $table);
		$table_column = BotBanishGetTableColumns($table);

		if (isset($table_column['country'])) {

			$sql = 'ALTER TABLE `' . $table . '`
					CHANGE `country` `country` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'';

			BotBanishDatabaseOperations(array($sql));
		}

		if (isset($table_column['country_code'])) {

			$sql = 'ALTER TABLE `' . $table . '`
					CHANGE `country_code` `country_code` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\'';

			BotBanishDatabaseOperations(array($sql));
		}
	}
}

function BotBanishCheckForTable($sql, $tables) {

	$found = false;

	foreach ($tables as $table) {

		if (stripos($sql, $table) !== false) {
			$found = true;
			break;
		}
	}

	return $found;
}

function BotBanishFindStartOfSQL($string, $cmd_array, $caseSensitive = true) {

	//-------------------------------------------------------------------------------------
	//  Find Start Of Statement in SQL File
	//	See if any string in the array is present in original line
	//-------------------------------------------------------------------------------------

	$skip = array('/*','*/','--');
	$found = false;

	// If we find one of the items in the skip array we should skip this line

	foreach ($skip as $item){

		if (substr($string, 0, strlen($item)) == $item)
			return false;
	}

	if ($found === false){

		$found = BotBanishFindArrayInStringRaw($string, $cmd_array, $caseSensitive);
		return $found;

	}else{

		return false;
	}
}

function BotBanishGetTableColumnInfo($table) {

	$columns = array();
	$sql = 'SELECT * from information_schema.columns WHERE `TABLE_SCHEMA` = "' . BOTBANISH_DB_NAME . '" AND `TABLE_NAME` = "' . $table . '"';
	$columns = BotBanishExecuteSQL($sql, true, false);
	return $columns;
}

function BotBanishGetTableIndexInfo($table) {

	$indexes = array();
	$sql = 'SHOW INDEX FROM `' . $table . '` WHERE `Seq_in_index` = 1';
	$indexes = BotBanishExecuteSQL($sql, true, false);
	return $indexes;
}

function BotBanishGetKeyString($rows) {

	$columns = array();

	switch (intval($rows[0]['Non_unique'])) {

		case 0:

			$str = ($rows[0]['Key_name'] == 'PRIMARY') ? 'PRIMARY KEY' : 'UNIQUE KEY';
			break;

		case 1:

			$str = 'KEY';
			break;

	}

	foreach ($rows as $row) {

		$length = !empty($row['Sub_part']) ? '(' . intval($row['Sub_part']) . ')' : '';
		$columns[] = '`' . $row['Column_name'] . '`' . $length;
	}

	switch ($str) {

		case 'PRIMARY KEY':

			$str .= ' ' . '(' . implode(',', $columns) . ')';
			break;

		case 'UNIQUE KEY':
		case 'KEY':

			$str .= ' `' . $row['Key_name'] . '` (' . implode(',', $columns) . ')';
			break;
	}

	return $str;
}

function BotBanishGetFileNames($search) {

	// Get all sql files to load

	$files = glob($search);

	if (is_array($files) && count($files) > 0)
		return $files;
	else
		return false;
}

function BotBanishRecreateTable($table, &$updates, &$schema_modify) {

	$schema_extra = '';
	$rows = BotBanishExecuteSQL('DESCRIBE `' . $table . '`', true, true);

	$schema_create = 'CREATE TABLE `' . $table . '` (' . PHP_EOL;
	$lastrow = $rows[count($rows)-1]['Field'];

	foreach ($rows as $row) {

		$extra = !empty($row['Extra']) ? ' ' . $row['Extra'] : '';
		$null = $row['Null'] == 'NO' ? ' NOT NULL' : '';
		$default = $row['Default'] == '' ? '' : ' DEFAULT \'' . $row['Default'] . '\'';
		$default = $row['Default'] == 'CURRENT_TIMESTAMP' ?  ' DEFAULT ' . $row['Default'] : $default;
		$terminator = $row['Field'] == $lastrow ? PHP_EOL . ')' : ',' . PHP_EOL;
		$arr = explode(')', $row['Type']);
		$type = count($arr) > 1 ? $arr[0] . ')' . strtoupper($arr[1]) : $row['Type'];
//		$schema_create .= ' `' . $row['Field'] . '` ' . $type . strtoupper($null) . strtoupper($default) . strtoupper($extra) . $terminator;
		$schema_create .= ' `' . $row['Field'] . '` ' . $type . strtoupper($null) . strtoupper($default) . $terminator;

		// Get extra indexing information

		if (!empty($extra))
			$schema_extra .= "\t" . 'MODIFY `' . $row['Field'] . '` ' . $type . strtoupper($null) . strtoupper($default) . strtoupper($extra) . ',' . PHP_EOL;
	}

	// Now just get the comment and type... (MyISAM, etc.)

	$row = BotBanishExecuteSQL('SHOW TABLE STATUS LIKE "' . $table . '"', true, true);
	$row = BotBanishAdjustReturn($row); //

	$default = explode('_',$row['Collation']);

	// Probably MyISAM.... and it might have a comment.

	$schema_create .= ' ENGINE=' . (isset($row['Type']) ? $row['Type'] : $row['Engine']) . ($row['Comment'] != '' ? ' COMMENT="' . $row['Comment'] . '"' : '') .
		(!empty($row['Collation']) ? ' DEFAULT CHARSET=' . $default[0] : '') . ';' . PHP_EOL . PHP_EOL;

	$updates[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
	$updates[] = $schema_create;

	// Add extra indexing such as AUTO_INCREMENT

	if (!empty($schema_extra))
		$schema_modify .= 'ALTER TABLE `' . $table . '`' . PHP_EOL . rtrim($schema_extra, PHP_EOL . ',') . ';' . PHP_EOL;
//		$schema_modify .= rtrim($schema_extra, PHP_EOL . ',') . ';' . PHP_EOL . PHP_EOL;
}
?>