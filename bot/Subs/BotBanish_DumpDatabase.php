<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.00
// Randem Systems: https://randemsystems.com/support/
// Exports BotBanish Table Structures
// Date: 04/30/2024
//
// Based on SMF DumpDatabase.php
// 	This file has a single job - database backup.
//
//
//	void BotBanishDumpTables($info = array())
//		- Writes all of the database to file output.
//		- Uses gzip compression.
//		- May possibly time out in some cases.
//		- The data dumped depends on whether "structure", "indexes" and "data" are passed.
//		- How the data is dumped depends on whether "compressed" is passed and if gzip compression routines exist.
//		- The tables that will be dumped will contain "prefix"
//
//	$info = array(
//		'prefix' => 'botbanish',				// Tablename to search for containing
//		'data' => true,							// Include table data
//		'structure' => true,					// Include structure
//		'indexes' => true,						// Include indices
//		'compress' => true,						// Compress the file
//		'type' => ''							// String that will be appended to the filename (if any)
//	);
//
// NOTE; When debugging this application, any errors that are generated will be one or all of the
//       following locations: The output file (in HTML format), The php_error.log file or the
//       BotBanish error log in the BotBanish_Logs folder.
///////////////////////////////////////////////////////////////////////////////////////////////////////

	// If we are called via a POST / GET, perform all operations otherwise we were called by an INCLUDE / REQUIRE call
	// in that case we just load the functions so they can be called

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'BotBanish_DumpDatabase.php') !== false)
		BotBanish_Dump();

function BotBanish_Dump() {

	if (isset($_SERVER['BOTBANISH_SYSTEM']) && ($_SERVER['BOTBANISH_SYSTEM'] == 'SERVER')) {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Server.php', $_SERVER['SCRIPT_FILENAME']));
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

	if (isset($row['info'])) {

		$info = Botbanish_is_serialized($row['info']) ? BotBanishSafeUnserialize($row['info']) : $row['info'];
		$info['prefix'] = !isset($info['prefix']) || empty($info['prefix']) ? 'botbanish' : $info['prefix'];
		$info['location'] = !isset($info['location']) || empty($info['location']) ?  $backup : $info['location'];

	} else {

		$info = array(
			'prefix' => 'botbanish',
			'data' => true,
			'structure' => true,
			'indexes' => true,
			'compress' => true,
			'type' => '',
			'location' => BOTBANISH_BACKUPS_DIR . 'SQL/',
		);
	}

	$arr = BotBanishDumpTables($info);
	BotBanishReturnData($arr['link'], true);
}

// Dumps the database or a table to a file.
function BotBanishDumpTables($data = array()) {

	$info = Botbanish_is_serialized($data) ? BotBanishSafeUnserialize($data) : $data;

	$BotBanish_Indexes = isset($info['indexes']) ? $info['indexes'] : false;
	$BotBanish_Structure = isset($info['structure']) ? $info['structure'] : false;
	$GLOBALS['BotBanish_Data'] = isset($info['data']) ? $info['data'] : false;
	$GLOBALS['BotBanish_Compress'] = isset($info['compress']) ? $info['compress'] : false;
	$BotBanish_Type = isset($info['type']) ? '_' . $info['type'] : '';

	// Attempt to stop from dying...
	@set_time_limit(600);
	if (@ini_get('memory_limit') < 256)
		@ini_set('memory_limit', '256M');

	// If there is no compression avalible, override!!
	
	if (!function_exists('gzopen'))
		$GLOBALS['BotBanish_Compress'] = false;
	
	$extension = ($GLOBALS['BotBanish_Compress'] == true) ? '.sql.gz' : '.sql';

	if (!isset($info['location']) || !empty($info['location']))
		$location = BOTBANISH_BACKUPS_DIR . 'SQL/';
	else
		$location = BOTBANISH_BACKUPS_DIR;

	// Need to turn off error handler for the mkdir not to terminate program

	$handler = BotBanishGetErrorHandler();
	if ($handler == 'BotBanishErrorHandler') restore_error_handler();
	@mkdir($location, 0777, true);
	set_error_handler('BotBanishErrorHandler');

	$date=date_create();

	if (isset($info['short']))
		$BotBanish_DB_Dumpfile = BOTBANISH_DB_NAME . $extension;
	else
		$BotBanish_DB_Dumpfile = BOTBANISH_DB_NAME . '-' . (empty($GLOBALS['BotBanish_Structure']) ? 'data' : (empty($GLOBALS['BotBanish_Data']) ? 'structure' : 'complete')) . '_' . date_format($date,'Y-m-d') . $BotBanish_Type . $extension;

	$filename = $location . $BotBanish_DB_Dumpfile;
	
	$GLOBALS['BotBanish_File'] = ($GLOBALS['BotBanish_Compress'] == true) ? gzopen($filename, 'w') : fopen($filename, 'w');

	$schema_indexes = '';

	// SQL Dump Header.
	$heading =
		'-- ==========================================================' . PHP_EOL .
		'--' . PHP_EOL .
		'-- Database dump of tables in `' . BOTBANISH_DB_NAME . '`' . PHP_EOL .
		'-- Date: ' . BotBanishGetCorrectTime() . PHP_EOL .
		'-- Program: BotBanish_DumpDatabase.php' . PHP_EOL .
		'-- Author: Randem Systems' . PHP_EOL .
		'-- System: ' . BOTBANISH_VERSION . PHP_EOL .
		'--' . PHP_EOL .
		'-- ==========================================================' . PHP_EOL;

	// Dump each table.

	BotBanishWriteData($heading);

	$tables = BotBanishGetTableNames($info['prefix']);

	foreach ($tables as $tableName)	{

		BotBanishExtendTime();
		$schema_modify = '';

		// If we are getting structures or indices we must run this routine
		// $schema_indexes is modified in place
		// Are we dumping the structures? Must get structure to prepare Indexes

		if ($BotBanish_Structure)
			BotBanishGetCreateSQL($tableName, $schema_indexes, $schema_modify);

		// How about the data?
		if ($GLOBALS['BotBanish_Data'])
			BotBanishGetInsertSQL($tableName);

		if ($BotBanish_Indexes)
			BotBanishGetIndexSQL($tableName, $schema_modify, $schema_indexes);
	}

	// Place all indexing at the end to speed up inserts
	if ($BotBanish_Indexes)
		BotBanishGetIndexes($schema_indexes);

	BotBanishWriteData('COMMIT;' . PHP_EOL);

	$result = ($GLOBALS['BotBanish_Compress'] == true) ? gzclose($GLOBALS['BotBanish_File']) : fclose($GLOBALS['BotBanish_File']);

	BotBanishCleanOutput();

	$download_link = BOTBANISH_INSTALL_URL . 'Backups/SQL/' . $BotBanish_DB_Dumpfile;

	$arr = array(
		'link' => $download_link,
		'file' => $filename,
	);

	restore_error_handler();
	return $arr;
}

//============================================================================
// Get the schema (CREATE) for a table.
//============================================================================

function BotBanishGetCreateSQL($tableName, &$schema_indexes, &$schema_modify) {

	$heading =
		PHP_EOL .
		'-- ============================================================================' . PHP_EOL .
		'-- Table structure for table `' . $tableName . '`' . PHP_EOL .
		'-- ============================================================================' . PHP_EOL .
		PHP_EOL;

	BotBanishWriteData($heading);

	$updates = array();
	$schema_create = '';
	require_once BOTBANISH_SUBS_DIR . 'BotBanish_AdjustDatabase.php';

	BotBanishRecreateTable($tableName, $updates, $schema_modify);

	foreach ($updates as $update)
		$schema_create = $schema_create .= $update . PHP_EOL;

	BotBanishWriteData($schema_create);
}

//============================================================================
// Get the content (INSERTs) for a table.
//============================================================================
function BotBanishGetInsertSQL($tableName) {

	global $BotBanishConn;

	$columns = BotBanishExecuteSQL('SHOW COLUMNS FROM `' . $tableName . '`', true);
	$fields = array();

	if (!is_object($BotBanishConn))
		$BotBanishConn = BotBanishDatabaseOpen();

	foreach ($columns as $column)
		$fields[] = $column['Field'];

	// Get everything from the table.
	// WARNING: Cannot execute any other database query while unbuffered query is in effect!!!
	$result = $BotBanishConn->query('SELECT /*!40001 SQL_NO_CACHE */ * FROM `' . $tableName . '`', MYSQLI_USE_RESULT);

	if (!is_object($result))
		return;

	$current_row = 0;

	// Start it off with the basic INSERT INTO.
	$sql = 'INSERT INTO `' . $tableName . '` (`' . implode('`, `', $fields) . '`) VALUES ' . PHP_EOL;
	$data = $sql;

	$heading =
		PHP_EOL .
		'-- ----------------------------------------------------------------------------' . PHP_EOL .
		'--' . PHP_EOL .
		'-- Dumping data in `' . $tableName . '`' . PHP_EOL .
		'--' . PHP_EOL .
		'-- ----------------------------------------------------------------------------' . PHP_EOL .
		PHP_EOL;

	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

	while (is_array($row)) {

		$current_row++;

		// Get the fields in this row...
		$field_list = array();

		foreach ($columns as $column) {

			$type = explode('(', $column['Type']);

			switch ($type[0]) {

				case 'int':
				case 'bigint':
				case 'smallint':
				case 'float':
				case 'double':
				case 'decimal':
				case 'bit':
				case 'bool':
				case 'bit':
				case 'tinyint':
				case 'boolean':
				case 'mediumint':
				case 'integer':
				case 'double':
				case 'double precision':
				case 'dec':

					$fieldValue = $row[$column['Field']];
					break;

				case 'varchar':
				case 'datetime':
				case 'text':
				case 'char':
				case 'varbinary':
				case 'binary':

				default:
				
					$value = $row[$column['Field']];
					$fieldValue = !empty($value) ? "'" . addslashes($value) . "'" : '';;
					break;
			}

			$field_list[] = $fieldValue;
		}

		// 'Insert' the data.
		$data .= str_ireplace(array(PHP_EOL, "\n\r", "\r", "\n"), '\n', '(' . implode(', ', $field_list) . ')');
		
		if (($current_row > 100 && $current_row % 101 == 0) || strlen($data) > 1024) {
			
			$data .= ';' . PHP_EOL . PHP_EOL;
			BotBanishWriteData($data);
			$data = $sql;
		}

		// Otherwise, go to the next line.
		else
			$data .= ',' . PHP_EOL;

		if ($current_row === 1)
			BotBanishWriteData($heading);
/*		
		// If max length for this statement, start over
		
		if (strlen($data) > 1024)) {
			
			BotBanishWriteData($data);
			$data = $sql;
		}
*/	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		// If at the last row, terminate it wit a semi-colon
		if (!is_array($row)) {

			$data = substr($data, 0, strpos($data, ','.PHP_EOL)) . ';' . PHP_EOL;
			BotBanishWriteData($data);
			break;
		}

		BotBanishWriteData($data);
		// Make sure each row data is presented on one line
		$data = '';
	}

	BotBanishResultRelease($result);
}

//============================================================================
// Get the content (INDEXES) for all tables.
//============================================================================
function BotBanishGetIndexSQL($tableName, $schema_modify, &$schema_indexes) {

	$schema_alter1 = 'ALTER TABLE `' . $tableName . '`' . PHP_EOL ;
	$schema_alter2 = '	ADD %s (%s)%s' . PHP_EOL ;

	// Find the keys.
	$rows = BotBanishExecuteSQL('SHOW KEYS FROM `'. $tableName . '`', true);
	$indexes = array();

	if (empty($rows))
		return;

	foreach ($rows as $row)	{

		// Is this a primary key, unique index, or regular index?
		$row['Key_name'] = $row['Key_name'] == 'PRIMARY' ? 'PRIMARY KEY' : (empty($row['Non_unique']) ? 'UNIQUE KEY' : ($row['Comment'] == 'FULLTEXT' || (isset($row['Index_type']) && $row['Index_type'] == 'FULLTEXT') ? 'FULLTEXT ' : 'KEY ')) . '`' . $row['Key_name'] . '`';

		// Is this the first column in the index?
		if (empty($indexes[$row['Key_name']]))
			$indexes[$row['Key_name']] = array();

		// A sub part, like only indexing 15 characters of a varchar.
		if (!empty($row['Sub_part']))
			$indexes[$row['Key_name']][$row['Seq_in_index']] = '`' . $row['Column_name'] . '`(' . $row['Sub_part'] . ')';
		else
			$indexes[$row['Key_name']][$row['Seq_in_index']] = '`' . $row['Column_name'] . '`';
	}

	// Build the ALTER statement for the keys.

	$num_indexes = count($indexes);
	$j = 1;

	if ($num_indexes > 0)
		$schema_indexes .= sprintf($schema_alter1, $tableName);

	foreach ($indexes as $keyname => $columns) {

		// Ensure the columns are in proper order.
		ksort($columns);
		$schema_indexes .= sprintf($schema_alter2, $keyname, implode(',', $columns), $j == $num_indexes ? ';' : ',');
		$j++;
	}

	if (!empty($schema_modify))
		$schema_indexes .= PHP_EOL . $schema_modify . PHP_EOL;

	$schema_indexes .= PHP_EOL;
}

function BotBanishGetIndexes($schema_indexes) {

	// No indexes to get - skip it.
	if (!empty($schema_indexes)) {

		$heading =
		PHP_EOL .
		'-- ----------------------------------------------------------------------------' . PHP_EOL .
		'--' . PHP_EOL .
		'-- Dumping indexes in `' . BOTBANISH_DB_NAME . '`' . PHP_EOL .
		'--' . PHP_EOL .
		'-- ----------------------------------------------------------------------------' . PHP_EOL .
		PHP_EOL;

		BotBanishWriteData($heading . $schema_indexes);
	}
}

function BotBanishWriteData($data) {

//	$result = ($GLOBALS['BotBanish_Compress'] == true) ? gzwrite($GLOBALS['BotBanish_File'], $data) : fwrite($GLOBALS['BotBanish_File'], $data);
	$result = ($GLOBALS['BotBanish_Compress'] == true) ? gzwrite($GLOBALS['BotBanish_File'], $data) : BotBanishWriteFile($GLOBALS['BotBanish_File'], $data);
}
?>