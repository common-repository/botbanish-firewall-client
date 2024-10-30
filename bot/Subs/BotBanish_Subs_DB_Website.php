<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Database Sub-routines for Websites
// Date: 05/03/2024
// Usage:
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishExecuteSQL($sql_statement, $original = false, $ignore_errors = false, $single = false) {

	global $BotBanishText, $BotBanishConn, $BotBanishSQL, $SQL_Parser;

	$result = '';
	$rows = array();

	if (!$original)
		$sql_statement = strtolower($sql_statement);

	$BotBanishSQL = $sql_statement;

	$sql = str_ireplace('``', '`', $sql_statement);
	BotBanishLogSQL($sql);

	// Used to help debug SQL Statements (Eclipse)

//	if (stripos($sql, 'ignore') !== false)
//		xdebug_break();

	if (defined('ABSPATH') && (!isset($GLOBALS['override']) || $GLOBALS['override'] == false)) {

		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_WordPress.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_WordPress.php';
		$rows = BotBanishProcessWordPressSQL($sql_statement);
		return $rows;
	}

	// If we are on SMF we convert the sql statement to a SMF version of the statement
	// and let SMF process the request. We must remove types of database field ticks due to SMF configuration
	// of $db_prefix and $db_name fields because the parser will get confused on table names.

	if (isset($GLOBALS['sourcedir']) && (!isset($GLOBALS['override']) || $GLOBALS['override'] == false)) {

		$sqlParsed = $SQL_Parser->parse($sql);
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_SMF.php';
		require_once BOTBANISH_SUBS_DIR . 'BotBanish_Subs_DB_SMF.php';
		$rows = BotBanishCreateSMFStatement($sqlParsed, $sql);
		return $rows;
	}

	if (!is_object($BotBanishConn)) {
		$BotBanishConn = BotBanishDatabaseOpen();
		$BotBanishConn->set_charset("utf8");
	}

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);	// Allow mysqli error to be trapped

	try {

		// If we are performing an unbuffered query, return only the mysql object.
		// The caller will need to perfom all the read and object freeing operations
		if ($single) {

			if (defined('OO')) {
				
				$result = $BotBanishConn->query($sql_statement, MYSQLI_USE_RESULT);
				return $result;
				
			} else {
				
				$result = mysqli_query($BotBanishConn, $sql_statement, MYSQLI_USE_RESULT);
				return $result;
			}
		} else {

			if (defined('OO')) {
				$result = $BotBanishConn->query($sql_statement);
			}else{
				$result = mysqli_query($BotBanishConn, $sql_statement);
			}
		}
	} catch (Exception $f) {

		BotBanishLogError($f);
		BotBanishLogSQL($f);

		BotBanishResultRelease($result);

		if (!$ignore_errors)
			return BotBanishExit(true);
		else
			return false;
	}

	if (is_object($result)){

		try {

			if (defined('OO')) {
				$rowcount = $result->num_rows;
			}else{
				$rowcount = mysqli_num_rows($result);
			}
		} catch (Exception $f) {

			BotBanishLogError($f);
			BotBanishLogSQL($f);

			BotBanishResultRelease($result);

			if (!$ignore_errors)
				return BotBanishExit(true);
			else
				return false;
		}

		if ($rowcount > 0){

			try {

				if (defined('OO')) {
					$rows = $result->fetch_all(MYSQLI_ASSOC);
				}else{
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
						$rows[] = $row;
				}
			} catch (Exception $f) {

				BotBanishLogError($f);
				BotBanishLogSQL($f);

				BotBanishResultRelease($result);

				if (!$ignore_errors)
					return BotBanishExit(true);
				else
					return false;
			}

			try {

				BotBanishResultRelease($result);

			} catch (Exception $f) {

				BotBanishLogError($f);
				BotBanishLogSQL($f);

				if (!$ignore_errors)
					return BotBanishExit(true);
				else
					return false;
			}

			BotBanishLogSQL('Good(' .$rowcount . ')');
			return $rows;

		}else{

			return false;
		}
	}else{

		if ($result === false) {

			$row = array();
			$row['error'] = $BotBanishText['BotBanish_ErrorText'] . "<br /><br />".$sql."<br /><br />";

			if (defined('OO')){
				$row['error'] .= $BotBanishConn->connect_errno . ": " . $BotBanishConn->connect_error . "<br /><br />";
			}else{
				$row['error'] .= mysqli_errno($BotBanishConn) . ": " . mysqli_error($BotBanishConn) . "<br /><br />";
			}

			if (!defined('BOTBANISH_DB_SERVERNAME')) {
				global $db_server, $db_name;

				define('BOTBANISH_DB_SERVERNAME', $db_server);
				define('BOTBANISH_DB_NAME', $db_name);
			}

			$row['error'] .= $BotBanishText['BotBanish_ServerLabel'] . BOTBANISH_DB_SERVERNAME . '<br />' . $BotBanishText['botbanish_infobaseLabel'] . BOTBANISH_DB_NAME . '<br />' . $BotBanishText['BotBanish_VersionLabel'] . BOTBANISH_VERSION;

			BotBanishLogError($row['error']);
			BotBanishReturnData($row);

		} else {

			BotBanishLogSQL('Good');
		}
	}
}

function BotBanishResultRelease($result) {

	// Release any memory held by a mySQL query

	if (isset($result) && is_object($result)) {

		if (defined('OO')) {

			$result->free();
		}else{
			mysqli_free_result($result);
		}
	}
}

function BotBanishDatabaseOpen($server_name = '', $user_name = '', $password = '', $db_name = '', $check = false) {
	global $BotBanishSettings, $BotBanishText;

	// $check = true, check for valid connection then return
	// Need to define 'OO' depending on type of opened connection

	$conn = null;

	if (empty($server_name)) {

		$server_name = BOTBANISH_DB_SERVERNAME;
		$user_name = BOTBANISH_DB_USERNAME;
		$password = BOTBANISH_DB_PASSWORD;
		$db_name = BOTBANISH_DB_NAME;
	}

	if (defined('OO')){

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);	// Allow mysqli error to be trapped

		try {
			$conn = new mysqli($server_name, $user_name, $password, $db_name);
		}catch (Exception $f){

			try {
				$conn = new mysqli($server_name, $user_name, $password, strtolower($db_name));
			}catch (Exception $e){

				if (!$check) {

					$msg = $BotBanishText['BotBanish_ConnectionFailed'] . PHP_EOL . $f . PHP_EOL . PHP_EOL . $e . PHP_EOL ;
					BotBanishLogError($msg);
					BotBanishExit(true);
				}
			}
		}

	}else{

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);	// Allow mysqli error to be trapped

		try {
			$conn = mysqli_connect($server_name, $user_name, $password, $db_name);
		}catch (Exception $f){

			try {
				$conn = mysqli_connect($server_name, $user_name, $password, strtolower($db_name));
			}catch (Exception $e){

				if (!$check) {

					$msg = $BotBanishText['BotBanish_ConnectionFailed'] . PHP_EOL . $f . PHP_EOL . PHP_EOL . $e . PHP_EOL ;
					BotBanishLogError($msg);
					BotBanishExit(true);
				}
			}
		}
	}

	return $conn;
}

function BotBanishDatabaseClose() {
}

function BotBanishBuildSQLStatementUpdate($row, $table, $useIndex = false) {

	$params = BotBanishBuildSQLStatementInsert($row, $table, $useIndex);
	return $params;
}

function BotBanishBuildSQLStatementInsert($row, $table, $useIndex = false) {

	// All data that needs slashes should have addslashes() done to it prior to this routine except serialized data

	$columns = '';
	$values = '';
	$updates = '';

	if (isset($row['ip_hit_count']))
		unset ($row['ip_hit_count']);

	if (isset($row['ip_first_hit']))
		unset ($row['ip_first_hit']);

	if (isset($row['diag']))
		unset ($row['diag']);

	// Make sure the table name does not already have the BotBanish prefix

	$numerics = array('integer', 'int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'bit', 'float', 'double', 'decimal', 'numeric');
	$unique = BotBanishGetTableUniqueIndexes($table);
	$table_column = BotBanishGetTableColumns($table);

	$error_check = BotBanishArrayCheck($row);

	if ($error_check['valid']) {

		foreach($row as $key => $value) {

			if ($key == 'updated') continue;	// Ignore updated columns, they are auto filled

			$columns = $columns .= '`'.$key.'`, ';
			$value = !empty($value) ? trim($value, ' ') : '';
			$str = intval($value);

			if (!in_array($table_column[$key], $numerics)) {

				if (BotBanish_is_serialized($value))
					$value = addslashes($value);

				// if data is serialized and contains double quotes, surround with single quotes instead.

				if (stripos($value, '"') !== false)
					$str = "'" . $value . "'";
				else
					$str = '"' . $value . '"';
			}

			$values .= $str . ', ';

			// Unique keys should be listed in array.
			// We won't use them on UPDATE statements

			if ((is_array($unique) && !in_array($key, $unique)) || $useIndex)
				$updates .= '`' . $key . '` = ' .  $str . ', ';
		}

		if (strlen($columns) > 1)
			$columns = substr($columns, 0, strlen($columns)-2);		// Remove

		if (strlen($values) > 1)
			$values = substr($values, 0, strlen($values)-2);		// trailing

		if (strlen($updates) > 1)
			$updates = substr($updates, 0, strlen($updates)-2);		// comma

		$params = array(
			'columns' => $columns,
			'values' => $values,
			'updates' => $updates,
		);
	}

	return $params;
}

function BotBanishBuildSQLStatementSelect($columns) {

	$column_names = array();

	foreach ($columns as $key => $value)
		$column_names[$key] = '`' . $key . '`';

	$select = implode(',', $column_names);
	return $select;
}

function BotBanishGetTableColumns($table) {

	global $BotBanishTableColumns;

	if (isset($BotBanishTableColumns[$table])) return $BotBanishTableColumns[$table];
	$table = str_replace('`', '', $table);

	$sql = 'SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE (TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '")
				AND (TABLE_NAME = "' . $table . '")';

	$rows = BotBanishExecuteSQL($sql, true, true);

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row)
				$BotBanishTableColumns[$table][$row['COLUMN_NAME']] = $row['DATA_TYPE'];

		return $BotBanishTableColumns[$table];
	}

	return false;
}

function BotBanishGetTableUniqueIndexes($table) {

	$columns = array();
	$table = str_replace('`', '', $table);

	$sql = 'SELECT * FROM information_schema.STATISTICS WHERE (`TABLE_SCHEMA` = "' . BOTBANISH_DB_NAME . '")
					AND (`TABLE_NAME` = "' . $table . '")
					AND `NON_UNIQUE` = 0';

	$rows = BotBanishExecuteSQL($sql, true, true);

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row)
			$columns[] = $row['COLUMN_NAME'];

		return $columns;
	}

	return false;
}

function BotBanishGetTableAutoIncrement($table) {

	$columns = array();

	$sql = 'SELECT COLUMN_NAME, EXTRA FROM information_schema.COLUMNS WHERE (TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '")
				AND (TABLE_NAME = "' . $table . '")
				AND (EXTRA = "auto_increment")';

	$rows = BotBanishExecuteSQL($sql, true, true);

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row)
				$columns[$row['COLUMN_NAME']] = $row['EXTRA'];

		return $columns;
	}

	return false;
}

function BotBanishGetTablePrimaryKey($table) {

	$columns = array();

	$sql = 'SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS WHERE (TABLE_SCHEMA = "' . BOTBANISH_DB_NAME . '")
				AND (TABLE_NAME = "' . $table . '")
				AND (COLUMN_KEY = "PRI")';

	$rows = BotBanishExecuteSQL($sql, true, true);

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row)
				$columns[$row['COLUMN_NAME']] = $row['DATA_TYPE'];

		return $columns;
	}

	return false;
}

function BotBanishSelectIPData($ip, $table) {

	// Find IP in our local database

	$sql = 'SELECT `ip_id` FROM `' . $table . '` WHERE `bot_ip` = "' . $ip . '" LIMIT 1';
	$row = BotBanishExecuteSQL($sql);
	return $row;
}

function BotBanishBlockedIPInsert($ip_list, $table) {

	if (!is_array($ip_list))
		$ip_list = array($ip_list);

	$date =  BotBanishGetCorrectTimeB();

	// Add blocked IP to our local database (Ignore duplicates)

	foreach ($ip_list as $ip) {

		$row = array();

		if (defined('SMF')) {
			$ip_bin = implode('', $ip);
			$row['bot_ip'] = inet_ntop($ip_bin);
		} else {
			$row['bot_ip'] = $ip;
		}

		$row['hit_count'] = 99;
		$row['first_hit'] = $date;
		$row['last_hit'] = $date;
		$row['deny'] = 1;
		$row['created'] = 1;
		$row['forcelockout'] = 1;
		$row['domain_name'] = '';
		$row['user_agent'] = '';

		$params = BotBanishBuildSQLStatementInsert($row, $table);

		$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

		if (isset($params['updates']) && !empty($params['updates']))
			$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

		BotBanishExecuteSQL($sql);
	}
}

/**
 * Convert a range of given IP number into a single string.
 * It's practically the reverse function of ip2range().
 *
 * @example
 * range2ip(array(10, 10, 10, 0), array(10, 10, 20, 255)) returns '10.10.10-20.*
 *
 * @param array $low The low end of the range in IPv4 format
 * @param array $high The high end of the range in IPv4 format
 * @return string A string indicating the range
 */
function BotBanish_Range2ip($low, $high)
{
	$low = inet_dtop($low);
	$high = inet_dtop($high);

	if ($low == '255.255.255.255') return 'unknown';
	if ($low == $high)
		return $low;
	else
		return $low . '-' . $high;
}

/**
 * Convert a single IP to a ranged IP.
 * internal function used to convert a user-readable format to a format suitable for the database.
 *
 * @param string $fullip The full IP
 * @return array An array of IP parts
 */
function BotBanish_ip2Range($fullip)
{
	// Pretend that 'unknown' is 255.255.255.255. (since that can't be an IP anyway.)
	if ($fullip == 'unknown')
		$fullip = '255.255.255.255';

	$ip_parts = explode('-', $fullip);
	$ip_array = array();

	// if ip 22.12.31.21
	if (count($ip_parts) == 1 && BotBanish_isValidIP($fullip))
	{
		$ip_array['low'] = $fullip;
		$ip_array['high'] = $fullip;
		return $ip_array;
	} // if ip 22.12.* -> 22.12.* - 22.12.*
	elseif (count($ip_parts) == 1)
	{
		$ip_parts[0] = $fullip;
		$ip_parts[1] = $fullip;
	}

	// if ip 22.12.31.21-12.21.31.21
	if (count($ip_parts) == 2 && BotBanish_isValidIP($ip_parts[0]) && BotBanish_isValidIP($ip_parts[1]))
	{
		$ip_array['low'] = $ip_parts[0];
		$ip_array['high'] = $ip_parts[1];
		return $ip_array;
	}
	elseif (count($ip_parts) == 2) // if ip 22.22.*-22.22.*
	{
		$valid_low = BotBanish_isValidIP($ip_parts[0]);
		$valid_high = BotBanish_isValidIP($ip_parts[1]);
		$count = 0;
		$mode = (preg_match('/:/', $ip_parts[0]) > 0 ? ':' : '.');
		$max = ($mode == ':' ? 'ffff' : '255');
		$min = 0;
		if (!$valid_low)
		{
			$ip_parts[0] = preg_replace('/\*/', '0', $ip_parts[0]);
			$valid_low = BotBanish_isValidIP($ip_parts[0]);
			while (!$valid_low)
			{
				$ip_parts[0] .= $mode . $min;
				$valid_low = BotBanish_isValidIP($ip_parts[0]);
				$count++;
				if ($count > 9) break;
			}
		}

		$count = 0;
		if (!$valid_high)
		{
			$ip_parts[1] = preg_replace('/\*/', $max, $ip_parts[1]);
			$valid_high = BotBanish_isValidIP($ip_parts[1]);
			while (!$valid_high)
			{
				$ip_parts[1] .= $mode . $max;
				$valid_high = BotBanish_isValidIP($ip_parts[1]);
				$count++;
				if ($count > 9) break;
			}
		}

		if ($valid_high && $valid_low)
		{
			$ip_array['low'] = $ip_parts[0];
			$ip_array['high'] = $ip_parts[1];
		}
	}

	return $ip_array;
}

/**
 * Check the given String if he is a valid IPv4 or IPv6
 * return true or false
 *
 * @param string $IPString
 *
 * @return bool
 */
function BotBanish_isValidIP($IPString)
{
	return filter_var($IPString, FILTER_VALIDATE_IP) !== false;
}

function BotBanishBlockedBOTInsert($bot_list, $table) {

	if (!is_array($bot_list))
		$bot_list = array($bot_list);

	// Add blocked BOT to our local database (Ignore duplicates)

	foreach ($bot_list as $bot) {

		$row = array();

		$row['spider_name'] = $bot;
		$row['user_agent_part'] = $bot;
		$row['active'] = 1;

		$params = BotBanishBuildSQLStatementInsert($row, $table);

		$sql = 'INSERT INTO `' . $table.'` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

		if (isset($params['updates']) && !empty($params['updates']))
			$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

		BotBanishExecuteSQL($sql);
	}
}

function BotBanishCheckForClientIPBlockOverride($ip, $table_name, $domain_name = '') {

	$ip_items = BotBanishGetIPRangeList($ip);

	foreach ($ip_items as $ip) {

		$sql = 'SELECT `ip_id` FROM `' . $table_name . '` WHERE `active` = 1 AND `bot_ip` = "' . $ip . '" LIMIT 1';

		if (!empty($domain_name))
			$sql .= ' AND `name` = "' . $domain_name . '"';

		$row = BotBanishExecuteSQL($sql);

		if (!empty($row)) {

			if (isset($row) && is_array($row))
				return true;

			$domain_name = '';
		}
	}

	return false;
}

function BotBanishCheckForClientDomainBlockOverride($table_name, $domain_name)
{
	$sql = 'SELECT `id_domain` FROM `' . $table_name . '` WHERE `active` = 1 AND `domain` = "' . $domain_name . '" LIMIT 1';

	$row = BotBanishExecuteSQL($sql);

	if (isset($row) && is_array($row))
		return true;

	return false;
}

function BotBanishClientInsertLocal($row) {

	$table = 'bbc_botbanishclient_ip';

	$geo_data = BotBanishGetGeoData($row['bot_ip']);
	$country_info = BotBanishGetCountryData($geo_data);
	$row['country'] = $country_info['country'] !== 'null' ? $country_info['country'] : '';
	$row['country_code'] = $country_info['country_code'] !== 'null' ? $country_info['country_code'] : '';

	$row['geo_info'] = BotBanishSafeSerialize(BotBanishGetGeoData($row['bot_ip']));

	$params = BotBanishBuildSQLStatementInsert($row, $table);

	$sql = 'INSERT INTO `' . $table . '` ('.$params['columns'].') VALUES ('.$params['values'].')';

	if (isset($params['updates']) && !empty($params['updates']))
		$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql);
}

function BotBanishClientRemoveLocal($ip) {

	$table = 'bbc_botbanishclient_ip';

	$sql = 'DELETE FROM `' . $table . '` WHERE `bot_ip` = "' . $ip . '"';

	BotBanishExecuteSQL($sql);
}

function BotBanishClientLocalSelectDeny($ip)
{
	$sql = 'SELECT `deny`, `created` FROM `' . 'bbc_botbanishclient_ip` WHERE `bot_ip` = "'.$ip.'" LIMIT 1';
	$deny = BotBanishExecuteSQL($sql);
	return $deny;
}

function BotBanishClientLocalSelectHitCount($ip)
{
	$sql = 'SELECT `hit_count` FROM `' . 'bbc_botbanishclient_ip` WHERE `bot_ip` = "'.$ip.'" LIMIT 1';
	$row = BotBanishExecuteSQL($sql);
	return $row;
}

function BotBanishCheckForClientGoodBotOverride($useragent) {

	// See if the client has other plans for bots that we have NOT locked out

	$sql = 'SELECT `id_spider` FROM `' . 'bbc_botbanishclient_spiders_bad`
				WHERE  LOCATE(`user_agent_part`, "' . addslashes($useragent) . '") LIMIT 1' ;

	$row = BotBanishExecuteSQL($sql);

	if (isset($row) && $row == false)
		return false;	// No rows returned

	if (isset($row) || is_array($row))
		return true;	// Do NOT Allow this BOT

	return false;
}

function BotBanishCheckForClientBadBotOverride($useragent) {

	// See if the client has other plans for bots that we have locked out

	$sql = 'SELECT `id_spider` FROM `' . 'bbc_botbanishclient_spiders_good`'.
				' WHERE  LOCATE(`user_agent_part`, "' . addslashes($useragent) . '") LIMIT 1' ;

	$row = BotBanishExecuteSQL($sql);

	if (isset($row) && $row == false)
		return false;	// No rows returned

	if (isset($row) || is_array($row))
		return true;	// Allow this BOT

	return false;
}

function BotBanishCheckForClientURLOverride($url)
{
	// See if the client has other plans for bots that we have locked out

	$sql = 'SELECT `url_id` FROM `' . 'bbc_botbanishclient_url_dnc`'.
			' WHERE  LOCATE(`url_part`, "'. addslashes($url).'") AND `active` = 1 LIMIT 1';

	$row = BotBanishExecuteSQL($sql);

	if (isset($row) && $row == false)
		return false;	// No rows returned

	if (isset($row) || is_array($row))
		return true;	// Allow this URL

	return false;
}

function BotBanishLogErrorDB($str)
{
	global $botbanish_info, $client_info;

	$date =  BotBanishGetCorrectTime();

	$row = array();

	$row['datetime'] = $date;
	$row['domain_name'] = isset($botbanish_info['DOMAIN_NAME']) ? $botbanish_info['DOMAIN_NAME'] : $_SERVER['SERVER_NAME'];
	$row['message'] = addslashes($str);

	$table = 'bbs_botbanishserver_errors';
	$params = BotBanishBuildSQLStatementInsert($row, $table);

	$sql = 'INSERT INTO `' . $table .'` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

	if (isset($params['updates']) && !empty($params['updates']))
		$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql);
}

function BotBanishIPCheckRange($ip, $useragent, $domain_name) {

	$ip_items = BotBanishGetIPRangeList($ip);
	$table = (BOTBANISH_SERVER == 1) ? 'bbs_botbanishserver_ip' : 'bbc_botbanishclient_ip';

	foreach ($ip_items as $ip_item) {

		$sql = 'SELECT `bot_ip` FROM `' . $table . '`
					WHERE `bot_ip` LIKE "'.$ip_item.'%"';

		if (BOTBANISH_SERVER)
			$sql .=	' AND `forcelockout` > 0';

		$rows = BotBanishExecuteSQL($sql);	// Multiple rows can be returned

		if (is_array($rows) && (count($rows) >= BOTBANISH_MAX_IP_RANGE_COUNT)) {

			BotBanishDeleteIPRange($ip_item, $table, $rows);

			if (BOTBANISH_SERVER)
				BotBanishServerIPLockOut($ip_item, BOTBANISH_IP_RANGE_LOCKOUT, 'ip', $useragent, $domain_name);
			else
				BotBanishClientIPInsertLocal($rows[0], $ip_item);
		}
	}
}

// Forcibly write to the HTACCESS file the group range block information

// function BotBanishBlockedIPRangeCheck($ip, $useragent, $domain_name) {
function BotBanishBlockedIPRangeCheck($ip) {

	$ip_items = BotBanishGetIPRangeList($ip);
	if (count($ip_items) > 1) unset($ip_items[0]);
	$table = (BOTBANISH_SERVER == 1) ? 'bbs_botbanishserver_ip' : 'bbc_botbanishclient_ip';

	foreach ($ip_items as $ip_item) {

		$sql = 'SELECT `bot_ip` FROM `' . $table . '`
					WHERE `bot_ip` = "'.$ip_item.'"';

		if (BOTBANISH_SERVER)
			$sql .=	' AND `forcelockout` > 0';

		$rows = BotBanishExecuteSQL($sql);	// only 1 rows can be returned

		if ($rows != false) {

			// We are doing range blocking so override HTACCESS processing
			$GLOBALS['HTACCESS_OVERRIDE'] = 1;
			BotBanishHTACCESSAddEntry($ip_item, 'ip');
			unset($GLOBALS['HTACCESS_OVERRIDE']);
		}
	}
}

function BotBanishBlockedIPGetList($table) {

	$sql = 'SELECT `bot_ip` FROM `' . $table . '` WHERE `forcelockout` > 0';
	$rows = BotBanishExecuteSQL($sql);
	$ip_list = array();

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row) {

			if (BotBanishCheckIfValidIP($row['bot_ip']))
				$ip_list[] = $row['bot_ip'];
		}
	}

	return $ip_list;
}

function BotBanishBlockedBOTGetList($table) {

	$sql = 'SELECT `user_agent_part` FROM `' . $table . '` WHERE `active` = 1';
	$rows = BotBanishExecuteSQL($sql);
	$bot_list = array();

	if (!empty($rows) && is_array($rows)) {

		foreach ($rows as $row) {

			if (BotBanishCheckIfValidItem($row['user_agent_part']))
				$bot_list[] = $row['user_agent_part'];
		}
	}

	return $bot_list;
}

function BotBanishDeleteIPRange($ip_item, $table, $ip_list) {

	$date =  BotBanishGetCorrectTime();

	if (!is_array($ip_list))
		$ip_list = array($ip_list);

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		$sql = 'SELECT * FROM `' . $table . '`
					WHERE `bot_ip` LIKE "'.$ip_item.'%" AND `forcelockout` > 0';

		$rows = BotBanishExecuteSQL($sql);

		if (is_array($rows) && !empty($rows)) {

			foreach ($rows as $row) {

				if (isset($row['updated'])) unset($row['updated']);
				$ip_list[] = $row['bot_ip'];
				$params = BotBanishBuildSQLStatementInsert($row, $table);

				$sql = 'INSERT INTO `' . 'bbs_botbanishserver_ip_removed` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

				if (isset($params['updates']) && !empty($params['updates']))
					$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

				BotBanishExecuteSQL($sql);
			}
		}
	}

	BotBanishHTACCESSRemoveEntry($ip_list, 'ip');
	$sql = 'DELETE FROM `' . $table . '` WHERE `bot_ip` LIKE "' . $ip_item . '%"';
	BotBanishExecuteSQL($sql);
}

function BotBanishDatabaseUninstall() {

	$date =  BotBanishGetCorrectTime();

	// Setup a new database container to hold the old data

	$sql = array();
	$sql[] = 'DROP DATABASE IF EXISTS `' . BOTBANISH_DB_NAME . '_' . $date .'`';
	BotBanishDatabaseOperations($sql);

	if (defined('BOTBANISH_RENAME_TABLES') && (BOTBANISH_RENAME_TABLES == true)){

		$sql = array();
		$sql[] = 'CREATE DATABASE IF NOT EXISTS `' . BOTBANISH_DB_NAME . '_' . $date .'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
		$sql[] = 'USE `' . BOTBANISH_DB_NAME . '_' . $date .'`';

		BotBanishDatabaseOperations($sql);

		// Transfer the old data to the new container

		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema = "' . BOTBANISH_DB_NAME . '"';
		$tables = BotBanishExecuteSQL($sql);

		if (is_array($tables)){

			foreach ($tables as $table){

				$sql = 'RENAME TABLE `' . BOTBANISH_DB_NAME . '`.`' . $table['table_name'].'` TO `' . BOTBANISH_DB_NAME . '_' . $date . '`.`' . $table['table_name'] . '`';
				BotBanishExecuteSQL($sql);
			}
		}
	}

	// Relinquish the old container

	$sql = array();
	$sql[] = 'DROP DATABASE IF EXISTS `' . BOTBANISH_DB_NAME .'`';
	BotBanishDatabaseOperations($sql);
}

function BotBanishLogDownload($file_name, $system = '') {

	if (empty($file_name))
		return;

	// If the user does not want analytics, then don't do it!

	if (!defined('BOTBANISH_ANALYTICS_DOWNLOADS') || BOTBANISH_ANALYTICS_DOWNLOADS == false)
		return;

	date_default_timezone_set(BOTBANISH_TIMEZONE);

	// If this user is in our DNB table; there is no reason to log them

	$ip = BotBanishGetIP();

	if ($ip === '')
		return false;	// Nothing we can do here!!!

	// We don't count our own downloads
	// If we should not block IP, Don't record it either
	
	if (BotBanishCheckForClientIPBlockOverride($ip, 'bbc_botbanishclient_ip_dnb'))
		return;

	// Ok, ready to log

	$table = 'bbc_botbanishclient_website_downloads';

	$row = array();
	$row['ip_addr'] = $ip;
	$row['http_user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$row['http_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$row['rpt_date'] = date('Y-m-d');
	$row['date'] = date('Y-m-d');
	$row['hostname'] = gethostbyaddr($row['ip_addr']);
	$row['filename'] = $file_name;
	$row['system'] = $system;

	$row['http_referer'] = BotBanishEliminateData($row['http_referer']);

	$geo_data = BotBanishGetGeoData($row['ip_addr']);
	$country_info = BotBanishGetCountryData($geo_data);
	$row['country'] = $country_info['country'] !== 'null' ? $country_info['country'] : '';
	$row['country_code'] = $country_info['country_code'] !== 'null' ? $country_info['country_code'] : '';

	$row['geo_info'] = BotBanishSafeSerialize(BotBanishGetGeoData($ip));

	$params = BotBanishBuildSQLStatementInsert($row, $table);
	$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

	if (isset($params['updates']) && !empty($params['updates']))
		$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql, true);
}

function BotBanishLogWebsitePage($pagename, $userid = 0, $system = '') {

	// If this user is in our DNB table; there is no reason to log them

	$ip = BotBanishGetIP();

	if ($ip === '')
		return;	// Nothing we can do here!!!

	if (BotBanishCheckForClientIPBlockOverride($ip, 'bbc_botbanishclient_ip_dnb')) // If we should not block IP, Don't record it either
		return;

	// Ok, ready to log

	$table = 'bbc_botbanishclient_website_visits';
	$datetime = BotBanishGetCorrectDateTime(BOTBANISH_TIMEZONE);

	$row = array();
	$row['user_id'] = intval($userid);
	$row['page_name'] = addslashes(urldecode($pagename));
	$row['ip_addr'] = $_SERVER['REMOTE_ADDR'];
	$row['http_user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? addslashes(BotBanishFixUserAgent($_SERVER['HTTP_USER_AGENT'])) : '';
	$row['http_referer'] = isset($_SERVER['HTTP_REFERER']) ? addslashes($_SERVER['HTTP_REFERER']) : '';
	$row['rpt_date'] = $datetime;
	$row['date'] = $datetime;
	$row['hostname'] = addslashes(gethostbyaddr($_SERVER['REMOTE_ADDR']));
	$row['system'] = $system;

	$row['page_name'] = BotBanishEliminateData($row['page_name']);
	$row['http_referer'] = BotBanishEliminateData($row['http_referer']);

	// Add country and country code

	$geo_data = BotBanishGetGeoData($ip);
	$country_info = BotBanishGetCountryData($geo_data);

	$row['country'] = $country_info['country'] !== 'null' ? $country_info['country'] : '';
	$row['country_code'] = $country_info['country_code'] !== 'null' ? $country_info['country_code'] : '';

	$row['geo_info'] = BotBanishSafeSerialize(BotBanishGetGeoData($ip));

	$params = BotBanishBuildSQLStatementInsert($row, $table);
	$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

	if (isset($params['updates']) && !empty($params['updates']))
		$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql);
}

function BotBanishUninstallTables($deletedata = false) {

//	require_once BOTBANISH_TEMPLATE_DIR . 'BotBanishConfirmDelete.php';

	if (!$deletedata)
		return;

	$tables = BotBanishGetTableNames('botbanish');

	foreach ($tables as $table) {

		$sql = 'DROP TABLE `' . $table . '`';
		BotBanishExecuteSQL($sql, false, true);
	}
}

function BotBanishSettingsTablePutValues($dataArray = array()) {

	global $BotBanishSettings;

	if (empty($dataArray))
		return;

	if (defined('ABSPATH'))
		$GLOBALS['override'] = true;
	
	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER))

		// If we are installing the server create the client settinge table also

		$tables = array('bbs_botbanishserver_settings', 'bbc_botbanishclient_settings');
	else
		$tables = array('bbc_botbanishclient_settings');

	foreach ($tables as $table) {

		if ($table == 'bbc_botbanishclient_settings') {

			$dataArray['BOTBANISH_CLIENT'] = 1;
			$dataArray['BOTBANISH_SERVER'] = 0;
			$dataArray['BOTBANISH_LOGS_DIR'] = str_replace('Server', 'Client', BOTBANISH_LOGS_DIR);
			$dataArray['BOTBANISH_CLIENT_DIR'] = str_replace('Server', 'Client', BOTBANISH_CLIENT_DIR);
			$dataArray['BOTBANISH_INSTALL_DIR'] = str_replace('Server', 'Client', BOTBANISH_INSTALL_DIR);

		} else {

			$dataArray['BOTBANISH_CLIENT'] = 0;
			$dataArray['BOTBANISH_SERVER'] = 1;
			$dataArray['BOTBANISH_LOGS_DIR'] = str_replace('Client', 'Server', BOTBANISH_LOGS_DIR);
			$dataArray['BOTBANISH_CLIENT_DIR'] = str_replace('Client', 'Server', BOTBANISH_CLIENT_DIR);
			$dataArray['BOTBANISH_INSTALL_DIR'] = str_replace('Client', 'Server', BOTBANISH_INSTALL_DIR);
		}

		// Remove any data not to be written to database.
		// These values can be changed at runtime so don't store them.

		$excluded = array('BOTBANISH_SERVER_URL', 
							'BOTBANISH_WEBSERVER', 
							'BOTBANISH_TIMEZONES', 
							'BOTBANISH_TIMEZONE_AREAS', 
							'REMOVE_HTACCESS_COMMENTS', 
							'BOTBANISH_LANGUAGES', 
							'BOTBANISH_HTACCESS_TAGS',
							'BOTBANISH_COUNTRY_INFO',
							'BOTBANISH_POST_CODE'
						);

		foreach ($excluded as $exclude) {

			if (isset($dataArray[$exclude]))
				unset($dataArray[$exclude]);
		}

		foreach ($dataArray as $key => $value) {

		// Check to see if the row already exists, if so just update it

			$rows = BotBanishExecuteSQL('SELECT `id`, `name`, `value`, `type` FROM `' . $table . '` WHERE `name` = "' . $key . '" AND `type` = 1');

			if (!is_array($rows) || empty($rows)) {
	
				$row = array(
							'name' => $key,
							'value' => is_array($value) || BotBanish_is_serialized($value) ? $value : addslashes($value),
							'type' => 1
							);
		
				$params = BotBanishBuildSQLStatementInsert($row, $table);
				BotBanishExecuteSQL('INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')', true, true);
		
			} else {

				// If license key exists do not overwrite!!! We will overwrite Free & Demo version keys

				$update = true;

				if ($key == 'BOTBANISH_APIKEY') {

					// If database values does not match entered value, do not change it unless database value is "Free" or "Demo" or entering new APIKEY
					
					if (($value != 'Free') && ($value != 'Demo') && ($rows[0]['value'] != $value))
						$update = true;
				}

				if ($update) {

					$row = array(
							'value' => addslashes($value),
								);

					$params = BotBanishBuildSQLStatementUpdate($row, $table);
					BotBanishExecuteSQL('UPDATE `' . $table . '` SET ' . $params['updates'] . ' WHERE `name` = "' . $key . '"' , true, true);
				}
			}

			$BotBanishSettings[$key] = $value;
		}
	}
	
	if (defined('ABSPATH'))
		$GLOBALS['override'] = false;
}

function BotBanishSettingsTableGetValues() {

	$dataarray = array();
	$excluded = array('BOTBANISH_SERVER_URL', 'BOTBANISH_WEBSERVER', 'BOTBANISH_TIMEZONES', 'BOTBANISH_TIMEZONE_AREAS', 'BOTBANISH_LANGUAGES');

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER))

		$table = 'bbs_botbanishserver_settings';
	else
		$table = 'bbc_botbanishclient_settings';

	// If the table does not exist, return an empty array.
	// Table may not exist at installation time.

	$tables = BotBanishGetTableNames($table);

	if (!empty($tables)) {

		// If this function is called while installing, "BOTBANISH_INSTALL_FOLDER" will not exist

		if (!defined('BOTBANISH_CONFIGURATION') || BOTBANISH_CONFIGURATION == 0) {

			$sql = 'SELECT `name`, `value` FROM `' . $table . '` WHERE `type` = 1 AND `name` = "BOTBANISH_INSTALL_FOLDER"';
			$row = BotBanishExecuteSQL($sql, true, true);

			if (is_array($row) && !empty($row)) {

				$data = stripslashes($row[0]['value']);
				BotBanishDefineGlobals($row[0]['name'], $data);

			} else {

				echo '"BOTBANISH_INSTALL_FOLDER" Not found in database. Cannot continue';
				BotBanishExit();
			}
		}

		// If using WordPress, we have to ignore the WordPress call on this database call because WordPress 
		// eliminates HTML data on the return and our database has some HTML in this table.
		
		if (defined('ABSPATH'))
			$GLOBALS['override'] = true;
		
		$sql = 'SELECT `name`, `value` FROM `' . $table . '` WHERE `type` = 1 ORDER BY `name`';
		$rows = BotBanishExecuteSQL($sql, true, true);

		if (defined('ABSPATH'))
			$GLOBALS['override'] = false;
		
		if (is_array($rows) && !empty($rows)) {

			// Change the operating folder to the current operating folder

			foreach ($rows as $row) {

				$row['value'] = stripslashes($row['value']);

				// Since we do not allow these variables to be saved, do not allow them to be extracted!

				if (in_array($row['name'], $excluded)) {

					$sql = 'DELETE FROM `' . $table . '` WHERE `name` = "' . $row['name'] . '"';
					BotBanishExecuteSQL($sql);

				} else {

					$dataarray[$row['name']] = str_replace(BOTBANISH_INSTALL_FOLDER, BOTBANISH_CURRENT_FOLDER, $row['value']);
				}
			}
		}
	}

	// Setup time zones

	$table = 'botbanish_timezones';
	$tables = BotBanishGetTableNames($table);

	if (!empty($tables)) {

		$sql = 'SELECT `gmt`, `area`, `timezone` FROM `' . $table . '` WHERE 1 ORDER BY `gmt` DESC';
		$rows = BotBanishExecuteSQL($sql, true, true);
		$time_zones = array();
		$time_zone_areas = array();

		if (is_array($rows) && !empty($rows)) {

			foreach($rows as $row) {

				$tz = '[' . $row['gmt'] . '] - ' . $row['area'];
				$time_zone_areas[$tz] = $tz;
				$time_zones[$tz] = $row['timezone'];
			}

			$dataarray['BOTBANISH_TIMEZONES'] = $time_zones;
			$dataarray['BOTBANISH_TIMEZONE_AREAS'] = $time_zone_areas;
		}
	}

	BotBanishSettingsSetGlobalValues($dataarray);
	BotBanishSetLanguage();

	if (defined('BOTBANISH_TIMEZONE'))
		date_default_timezone_set(BOTBANISH_TIMEZONE);
	else
		$dataarray['BOTBANISH_TIMEZONE'] = $_SERVER['TZ'];

	return $dataarray;
}

function BotBanish_is_serialized( $data, $strict = true ) {

    // If it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' === $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
    } else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace ) {
            return false;
        }
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 ) {
            return false;
        }
        if ( false !== $brace && $brace < 4 ) {
            return false;
        }
    }
    $token = $data[0];
    switch ( $token ) {
        case 's':
            if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
            } elseif ( false === strpos( $data, '"' ) ) {
                return false;
            }
            // Or else fall through.
        case 'a':
        case 'O':
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
    }
    return false;
}

function BotBanishEliminateData($str) {

	// Eliminate any session or other extraneous information

	if (((defined('BOTBANISH_REFERER_DATA') && BOTBANISH_REFERER_DATA))
		|| (defined('BOTBANISH_PAGE_DATA') && BOTBANISH_PAGE_DATA)) {

		$eliminate = array('?');

		foreach ($eliminate as $item) {

			$pos = stripos($str, $item);

			if ($pos !== false)
				$str = substr($str, 0, $pos);
		}
	}

	return $str;
}

function BotBanishUpdateLanguage($full = false, $language_table = false) {

	// Start with English and match with other languages that are missing

	$source = 'en';
	$count = 0;

	$lang_table = 'botbanish_language';
	$table = $lang_table;
	$sql = 'SELECT `lang_id`, `language`, `lang_code` FROM ' . $table . ' WHERE `lang_id` > 1 ORDER BY `lang_id`';
	$lang_rows = BotBanishExecuteSQL($sql);

	// if we are in a restart, locate the language id for the starting language then add 1 to get to the next language

	if ($language_table !== false) {

		foreach ($lang_rows as $row) {

			if ($row['language'] == $language_table) {

				$language_id = $row['lang_id'] + 1;
				break;
			}
		}
	}

	// Get all English starting entries

	$text_table = 'botbanish_language_text_';
	$table1 = $text_table . 'english';
	$sql = 'SELECT `lang_key`, `lang_text` FROM ' . $table1 . ' WHERE `lang_id` = 1';
	$text_rows = BotBanishExecuteSQL($sql);

	foreach ($lang_rows as $lang_row) {

		$id = $lang_row['lang_id'];

		// If we are in a restart skip to the proper language

		if (isset($language_id)) {

			if ($language_id != $id)
				continue;
		}

		$target = $lang_row['lang_code'];
		$language = strtolower($lang_row['language']);
		$id = $lang_row['lang_id'];

		BotBanishDisplayIt(date('m-d-Y h:i:s') . ' - Start ' . $lang_row['language'] . ' Language Update</br></br>');

		$table2 = $text_table . $language;

		// If in a restart, we will start at the next language table

		if (($language_table === false) || (isset($language_id))) {

			if (!$full) {

				$sql = 'SELECT table1.`lang_id`, table1.`lang_key`, table1.`lang_text`, table2.`lang_key` as lang_key_' . $language . ',
					table2.`lang_text` as lang_text_' . $language . ' FROM ' . $table1 . ' as table1
						LEFT JOIN ' . $table2 . ' as table2 ON table1.`lang_key` = table2.lang_key';

			} else {

				BotBanishExecuteSQL('TRUNCATE TABLE `' . $table2 . '`', true, true);
				$sql = 'SELECT `lang_id`, `lang_key`, `lang_text` FROM ' . $table1;
			}

			$rows = BotBanishExecuteSQL($sql);

			foreach ($rows as $row) {

				if ($full || empty($row['lang_text_' . $language])) {

					$key = $row['lang_key'];
					$text = $row['lang_text'];

					if (!BotBanishInsertTextData($source, $target, $id, $key, $text, $table2))
						return false;
				}
			}

			BotBanishDisplayIt(date('m-d-Y h:i:s') . ' - Finish ' . $lang_row['language'] . ' Language Update</br></br>');
		}

		// Restart program after each language table that is processed to by-pass the execution timeout.

		if ($id !== $lang_rows[count($lang_rows) - 1]['lang_id']) {

			BotBanishFlushOutputBuffer();
//			ob_end_flush();
			$type = ($full === true) ? '&full=true' : '';
			$url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?table=' . $lang_row['language'] . $type;
			header('Location: ' . $url);
			exit();
		}
	}

	return true;
}

function BotBanishInsertTextData($source, $target, $id, $key, $text, $table) {

	$txt = GoogleTranslate($text, $source, $target);

	// If Google Translate is not active just return

	if ($txt === false)
		return false;

	$row = array();
	$row['lang_id'] = $id;
	$row['lang_key'] = $key;
	$row['lang_text'] = htmlentities($txt);

	$params = BotBanishBuildSQLStatementInsert($row, $table);

	$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')' .
			' ON DUPLICATE KEY UPDATE ' . $params['updates'];

	BotBanishExecuteSQL($sql, true, true);
	return true;
}
?>