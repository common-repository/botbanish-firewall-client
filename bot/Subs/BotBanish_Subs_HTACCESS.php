<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.2.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish HTACCESS Subroutines
// Date: 06/01/2024
// Usage:
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishHTACCESSImport() {

	//------------------------------------------------------------------------------------------
	//	Place all information for blocked IP's and BOT's into .htaccess file from our tables
	//------------------------------------------------------------------------------------------

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER)) {

		BotBanishHTACCESSAddEntry(BotBanishBlockedIPGetList('bbs_botbanishserver_ip'), 'ip');
		BotBanishHTACCESSAddEntry(BotBanishBlockedBOTGetList('bbs_botbanishserver_spiders_bad'), 'bot');
	}

	BotBanishHTACCESSAddEntry(BotBanishBlockedIPGetList('bbc_botbanishclient_ip'), 'ip');
	BotBanishHTACCESSAddEntry(BotBanishBlockedBOTGetList('bbc_botbanishclient_spiders_bad'), 'bot');
}

function BotBanishCheckIfValidItem($bot) {

	// Check for an invalid RegEx value in the proper location for SetEnvIfNoCase
	// Only presents a problem in Apache version 2.2 (maybe earlier...)

	return true;
}

function BotBanishHTACCESSMaintenance($func = 'clean', $rtn = '') {

	global $BotBanishSettings;

	$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	foreach ($htaccess_array as $htaccess_name) {

		// Make sure comments in .htaccess file are not taken as commands.

		$BotBanishSettings['REMOVE_HTACCESS_COMMENTS'] = true;	// Gets unset when .htaccess file is rewritten

		$htaccess_data = BotBanishHTACCESSReadFile($htaccess_name);

		// ALL CALLED ROUTINES MODIFY THE .htaccess BUFFER IN PLACE

		if (empty($rtn))
			BotBanishMaintenanceNormal($htaccess_data, $func);
		else
			BotBanishMaintenanceSpecific($htaccess_data, $func, $rtn);

		BotBanishHTACCESSWriteFile($htaccess_data, array(), $htaccess_name);
	}
}

function BotBanishMaintenanceNormal(&$htaccess_data, $func = '') {
	global $BotBanishSettings;

	$root = defined('ABSPATH') ? BOTBANISH_PLUGIN_DIR . 'bot/' : BOTBANISH_LOCATION_ROOT;
	$root = str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $root);
	$url = defined('ABSPATH') ? BOTBANISH_INSTALL_URL . 'bot/': BOTBANISH_INSTALL_URL;

	// SQL Injection Rules

	$Warning = PHP_EOL . PHP_EOL . '# DO NOT REMOVE OR CHANGE!!!' . PHP_EOL . '# Everything between the BotBanish Start and End markers will be removed when the BotBanish is uninstalled';

	$SQL_Injection_Entry = PHP_EOL . 'Options All -Indexes' . PHP_EOL . 'RewriteEngine On' . PHP_EOL . PHP_EOL .
	'RewriteCond %{QUERY_STRING} ^.*(loopback|127\.0\.0\.1).* [NC,OR]' . PHP_EOL .
	'RewriteCond %{QUERY_STRING} ^.*(md5|benchmark|union|insert|drop|alter).* [NC]' . PHP_EOL .
	'RewriteRule ^(.*)$ ' . $url . 'Subs/BotBanish_AccessError.php?docerror=401 [L,QSA]' . PHP_EOL;

	// PHP Injection Rules

	$PHP_Injection_Entry = PHP_EOL .
	'RewriteCond %{QUERY_STRING} ^.*(else|require|include|isset|foreach|global).* [NC]' . PHP_EOL .
	'RewriteRule ^(.*)$ ' . $url . 'Subs/BotBanish_AccessError.php?docerror=400 [L,QSA]' . PHP_EOL;

	$ErrorDoc_Entry = '';

//	if (BOTBANISH_CLIENT) {
		// Error Document Rules

		$errortypes = BotBanishSafeUnserialize(BOTBANISH_DOC_ERRORS);
		$ErrorDoc_Entry = PHP_EOL;

		foreach ($errortypes as $type)
			$ErrorDoc_Entry .= 'ErrorDocument ' . $type . ' ' . $root .  'Subs/BotBanish_AccessError.php' . PHP_EOL;
//	}

	switch ($func) {

		case 'add':

			//=================================================================================================
			// Add the new rules in the .htaccess file
			//=================================================================================================

			// Place all entries in code blocks for easy extraction on uninstall

			$htaccess_rule_entry = $Warning . PHP_EOL . $SQL_Injection_Entry . $PHP_Injection_Entry . $ErrorDoc_Entry . PHP_EOL;

			if ($BotBanishSettings['BOTBANISH_HTACCESS_ACTIVE'])
				BotBanishHTACCESSInsertRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, BOTBANISH_HTACCESS_DATA, $htaccess_data);

			if ($BotBanishSettings['BOTBANISH_XMLRPC_ACTIVE'] && defined('ABSPATH'))
				BotBanishHTACCESSInsertRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, BOTBANISH_XMLRPC_DATA, $htaccess_data);

			if ($BotBanishSettings['BOTBANISH_WPCONFIG_ACTIVE'] && defined('ABSPATH'))
				BotBanishHTACCESSInsertRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, BOTBANISH_WPCONFIG_DATA, $htaccess_data);

			BotBanishHTACCESSInsertRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, $htaccess_rule_entry, $htaccess_data);	// Write our new firewall rule to stop SQL injections
			BotBanishHTACCESSInsertRule(PHP_EOL . BOTBANISH_CODE . PHP_EOL, $htaccess_data);	// Write our new Apache version finder

			break;

		case 'clean':
		default:

			//=================================================================================================
			// Check to see if we have information in the .htaccess file, if so we will try to remove it
			//=================================================================================================

			// Attempt the group entry

			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, $htaccess_data);
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_CODE_START, BOTBANISH_CODE_END, $htaccess_data);
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_TAG_BOT_REWRITE_START, BOTBANISH_TAG_BOT_REWRITE_END, $htaccess_data, 'bot');
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_TAG_IP_START, BOTBANISH_TAG_IP_END, $htaccess_data, 'ip');
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_TAG_BOT_START, BOTBANISH_TAG_BOT_END, $htaccess_data, 'bot');
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_TAG_IP_START_2_4, BOTBANISH_TAG_IP_END_2_4, $htaccess_data, 'ip');
			BotBanishHTACCESSRemoveRuleBlock(BOTBANISH_TAG_BOT_START_2_4, BOTBANISH_TAG_BOT_END_2_4, $htaccess_data, 'bot');

			break;
	}
}

function BotBanishMaintenanceSpecific(&$htaccess_data, $func, $rtn) {
	global $BotBanishSettings;

	if (empty($rtn))
		return;

	if (!isset($BotBanishSettings[$rtn]))
		return;

	if (empty($BotBanishSettings[$rtn]))
		return;

	$active = str_replace('_DATA', '_ACTIVE', $rtn);

	switch ($func) {

		case 'add':

			BotBanishHTACCESSRemoveRule(BOTBANISH_RULE_START . PHP_EOL . $BotBanishSettings[$rtn] . PHP_EOL . BOTBANISH_RULE_END, $htaccess_data);
			BotBanishHTACCESSInsertRuleBlock(BOTBANISH_RULE_START, BOTBANISH_RULE_END, $BotBanishSettings[$rtn], $htaccess_data);
			$BotBanishSettings[$active] = 1;
			break;

		case 'clean':

			BotBanishHTACCESSRemoveRule(BOTBANISH_RULE_START . PHP_EOL . $BotBanishSettings[$rtn] . PHP_EOL . BOTBANISH_RULE_END, $htaccess_data);
			$BotBanishSettings[$active] = 0;
			break;
	}
}

function BotBanishHTACCESSRemoveComments($htaccess_data) {
	global $BotBanishSettings;

	if (!isset($BotBanishSettings['REMOVE_HTACCESS_COMMENTS'])
			|| $BotBanishSettings['REMOVE_HTACCESS_COMMENTS'] == false
				|| !isset($GLOBALS['replaceArray'])
					|| empty($GLOBALS['replaceArray']))
		return $htaccess_data;

	unset($BotBanishSettings['REMOVE_HTACCESS_COMMENTS']);
	$GLOBALS['replaceArray'] = array();

	$lines = explode(PHP_EOL, $htaccess_data);
	$i = 0;

	foreach ($lines as $key => $value) {

		$line = trim($value);

		// Do not remove BotBanish Comments. We will need them to identify instructions to add/remove

		if (stripos($line, '#') === 0 && stripos($line, 'BotBanish') === false) {

			$item = 'BotBanish_' . $i;
			$GLOBALS['replaceArray'][] = array('find' => $item, 'repl' => $value);
			$lines[$key] = $item;
			$i++;
		}
	}

	$htaccess_data = implode(PHP_EOL, $lines);
	return $htaccess_data;
}

function BotBanishHTACCESSInsertRule($entry, &$htaccess_data, $type = '') {

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	$lines = array(PHP_EOL, $entry, PHP_EOL);
	$htaccess_data = array_merge($htaccess_data, $lines);
}

function BotBanishHTACCESSInsertRuleBlock($start_entry, $end_entry, $entry, &$htaccess_data, $type = '') {

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	$lines = explode(PHP_EOL, PHP_EOL . $start_entry . PHP_EOL . $entry . PHP_EOL . $end_entry . PHP_EOL);
	$htaccess_data = array_merge($htaccess_data, $lines);
}

function BotBanishHTACCESSRemoveRule($entry, &$htaccess_data, $type = '') {
	// if rule is in the file, we will remove it

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	$key = array_search($entry, $htaccess_data);

	if ($key !== false)
		unset($htaccess_data[$key]);
}

function BotBanishHTACCESSRemoveRuleBlock($start_entry, $end_entry, &$htaccess_data, $type = '') {

	$entries = array($start_entry, $end_entry);
	$new_entries = array();

	$entries = array_merge($entries, $new_entries);
	$tags = array();

	foreach ($entries as $key => $value)
		$tags[$key] = array_keys($htaccess_data, $value, false);

	$i = 0;
	$index = array();

	foreach ($tags as $key => $value) {

		if (!empty($tags[$key])) {

			foreach ($value as $key1 => $value1)
				$index[$value1] = $value1;
		}
	}

	ksort($index);

	$num = count($index);

	if (intval($num / 2) != ($num / 2)) {

		BotBanishLogError('Mis-Matched Begin/End Markers Found - Begin: ' . $start_entry . ' - End: ' . $end_entry);
		return;
	}

	$start = 0;
	$end = 0;

	foreach ($index as $key => $value) {

		$end = empty($end) && !empty($start) ? $value : $end;
		$start = empty($start) ? $value : $start;

		if (!empty($end)) {

			for ($i = $start; $i <= $end; $i++)
				unset ($htaccess_data[$i]);

			$start = 0;
			$end = 0;
		}
	}
}

function BotBanishHTACCESSUpdate(&$row, $type = 'ip') {

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	// ------------------------------------------------------------
	// .htaccess file updates. We still need to protect our server
	// ------------------------------------------------------------

	switch ($type) {

		case 'bot':

			// Never block full user agents for BOTs

			if (isset($row['user_agent']) && strlen($row['user_agent']) < 25) {

				if (!empty($row['user_agent'])){

					// Write our new firewall rule

					BotBanishHTACCESSAddEntry($row['user_agent'], 'bot');

					$row['created'] = 1;
				}
			}

			// If a spider, update the ip table also just in case User-Agent denial (does not work properly on the server).

			sleep(1); // Give the server time to update

			break;


		case 'ip':
		default:

			if (isset($row['bot_ip'])) {

				// Must be a valid IP regardless!
				if (!BotBanishCheckIfValidIP($row['bot_ip']))
					break;

				// We can only support IPv4 in Apache 2.2. If IPv6 then disregard it

				if (isset($row['bot_ip'])){

					if (!empty($row['bot_ip'])){

						// Write our new firewall rule

						BotBanishHTACCESSAddEntry($row['bot_ip'], 'ip');	// Make sure we have the proper headers for our firewall rules

						$row['created'] = 1;
					}
				}
			}

			break;
	}
}

function BotBanishHTACCESSAddEntry($items, $type, $skipipcheck = false, $forcecache = false) {

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	if (empty($items))
		return;

	if (!is_array($items))
		$items = array($items);

	// If cashing is not set; we will force it to be set

	if (!defined('BOTBANISH_HTACCESS_CACHING'))
		define('BOTBANISH_HTACCESS_CACHING', 1);

	// If opted, use database for caching of htaccess writes
	if ((BOTBANISH_HTACCESS_CACHING) && (count($items) === 1) && ($forcecache === false)) {

		BotBanishDatabaseHTACCESSAddEntry($items, $type);
		return;

	} else {

		// see if opted out of inserting data into htaccess file

		if (!isset($GLOBALS['HTACCESS_OVERRIDE']) || !$GLOBALS['HTACCESS_OVERRIDE']) {

			if (defined('BOTBANISH_UPDATE_HTACCESS') && (!BOTBANISH_UPDATE_HTACCESS))
				return;
		}

		if (BotBanishHTACCESSCheckLockStatus() === false)
			BotBanishHTACCESSChangeLockStatus(BOTBANISH_HTACCESS_LOCK);
		else
			return;
	}

	// if opted out of inserting data into htaccess file, we are done!!!

		if (!isset($GLOBALS['HTACCESS_OVERRIDE']) || !$GLOBALS['HTACCESS_OVERRIDE']) {

			if (defined('BOTBANISH_UPDATE_HTACCESS') && (!BOTBANISH_UPDATE_HTACCESS))
			return;
		}

	// Let's build our new data element block to insert

	$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	$adddata = array();

	foreach ($items as $value) {

		// Attempt to get rid of strange entries.
		$str = preg_replace('/[A-Za-z0-9 !@#$%^&*().~_+-={};:<>?,|]/', '', $value);
		$str = str_ireplace(array('\\', '/'), '', $str);

		if (!empty($str))
			continue;

		if (((strlen($value) < 4) || empty($value)) && $type = 'bot')
			continue;

		if ($skipipcheck == false) {

			if ($type == 'ip') {

				// using 'deny from' could have a trailing dot but 'require not ip' cannot.
				// check for short IP notation

				if (substr_count($value, '.') === 3) {

					// If not a valid ip; we don't want it
					if (!BotBanishCheckIfValidIP($value))
						continue;
				}

			} else {

				if (!BotBanishCheckIfValidItem($value))
					continue;
			}
		}

		$adddata[] = $value;
	}

	// No new data to be added? We have nothing to do

	if (empty($adddata))
		return;

	$newlines = array();

	foreach ($htaccess_array as $htaccess_name) {

		// Search the .htaccess file for an existing header for our firewall rules.
		// If it is not there, we will add it to the end of the file.
		// If the header exist, we will add the new data to it
		// If the new data is already in the file, do nothing

		$htaccess_data = BotBanishHTACCESSReadFile($htaccess_name);
		$htaccess_entry = BotBanishHTACCESSGetHeaderType($htaccess_data, $type);
		$oldlines = BotBanishHTACCESSGetListData($htaccess_data, $htaccess_entry);

		if (!empty($adddata) && empty($newlines)) {

			$newlines = array();

			foreach ($adddata as $key => $value)
				$newlines[] = stripslashes(trim(sprintf($htaccess_entry['info']['str'], $value), PHP_EOL));
		}

		$lines = array_merge($newlines, $oldlines);

		// Eliminate duplicates

		$lines = BotBanish_Array_iunique($lines);

		natcasesort($lines);

		// place proper termination for bot block section

		if ($htaccess_entry['info']['type'] == 'bot') {

			$lines = array_values($lines);
			$key = count($lines) - 1;
			$lines[$key] = str_ireplace('[OR,NC]', '[NC]', $lines[$key]);
		}

		$newdata =  trim(implode(PHP_EOL, $lines));

		$lines = BotBanishHTACCESSPlaceHeaderInfo($htaccess_entry, $newdata);

		BotBanishHTACCESSWriteFile($htaccess_data, $lines, $htaccess_name);
	}

	BotBanishHTACCESSChangeLockStatus(BOTBANISH_HTACCESS_UNLOCK);
}

function BotBanishHTACCESSRemoveEntry($items, $type) {

	// Remove requested BOT or IP data that we have place into the .htaccess file

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	if (empty($items))
		return;

	if (!is_array($items))
		$items = array($items);

	$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	foreach ($htaccess_array as $htaccess_name) {

		$htaccess_data = BotBanishHTACCESSReadFile($htaccess_name);
		$htaccess_entry = BotBanishHTACCESSGetHeaderType($htaccess_data, $type);
		$oldlines = BotBanishHTACCESSGetListData($htaccess_data, $htaccess_entry);

		// Eliminate the items requested

		if (!empty($oldlines)) {

			foreach ($items as $item) {

				foreach ($oldlines as $key => $value) {

					if (stripos($value, $item) !== false) {

						unset ($oldlines[$key]);
						break;
					}
				}
			}
		}

		// Sort and restructure the data

		$oldlines = array_unique($oldlines);
		natcasesort($oldlines);
		$newdata =  trim(implode(PHP_EOL, $oldlines));
		$lines = BotBanishHTACCESSPlaceHeaderInfo($htaccess_entry, $newdata);

		BotBanishHTACCESSWriteFile($htaccess_data, $lines, $htaccess_name);
	}
}

function BotBanishHTACCESSReadFile($htaccess_name = BOTBANISH_HTACCESS_NAME) {

	global $BotBanishSettings;

	$lines = array();

	if (file_exists($htaccess_name)) {

		$lines = file($htaccess_name, FILE_IGNORE_NEW_LINES);

		$items = array('BOTBANISH_TAG_IP_START' => BOTBANISH_TAG_IP_START,
						'BOTBANISH_TAG_IP_END' => BOTBANISH_TAG_IP_END,
						'BOTBANISH_TAG_BOT_START' => BOTBANISH_TAG_BOT_START,
						'BOTBANISH_TAG_BOT_END' => BOTBANISH_TAG_BOT_END,
						'BOTBANISH_CODE_START' => BOTBANISH_CODE_START,
						'BOTBANISH_CODE_END' => BOTBANISH_CODE_END,
						'BOTBANISH_RULE_START' => BOTBANISH_RULE_START,
						'BOTBANISH_RULE_END' => BOTBANISH_RULE_END
						);

		foreach ($items as $key => $value)
			$BotBanishSettings['BOTBANISH_HTACCESS_TAGS'][$key] = array_search($value, $lines);
	}

	return $lines;
}

function BotBanishHTACCESSWriteFile($htaccess_data, $lines, $htaccess_name = BOTBANISH_HTACCESS_NAME) {

	// If the files folder does not already exist, do NOTHING!!!

	$dir = str_ireplace('.htaccess', '', $htaccess_name);

	if (!is_dir($dir))
		return;

	// Write the new file

	$htaccess_data = array_merge($htaccess_data, $lines);
	$data = implode(PHP_EOL, $htaccess_data);

	$file = fopen($htaccess_name, 'w');

	if (isset($file)) {

		$output = BotBanish_UTF8_Encode ($data);
		$output = BotBanishRemoveExtraCRLF($output);

		fwrite ($file, $output);
		fflush($file);
		fclose($file);
	}
}

function BotBanishHTACCESSGetListData (&$htaccess_data, $htaccess_entry) {

	global $BotBanishSettings;

	// If no data was found, we skip this, nothing to do

	if ($htaccess_entry[0] === false)
		return array();

	$headers = array('<RequireAll>', '</RequireAll>', 'Require all granted', '<Files *>', '</Files>',
						'<Limit GET POST>', 'order allow,deny', 'allow from all', '</Limit>', '<Limit GET POST HEAD>',
						'Deny from env=bad_bot',
						BOTBANISH_TAG_IP_START, BOTBANISH_TAG_IP_END, BOTBANISH_TAG_BOT_START, BOTBANISH_TAG_IP_END,
						BOTBANISH_CODE_START, BOTBANISH_CODE_END, BOTBANISH_RULE_START, BOTBANISH_RULE_END,
						BOTBANISH_TAG_BOT_REWRITE_START, BOTBANISH_TAG_BOT_REWRITE_END, 'RewriteRule .* - [G,L,E=bad_bot:Yes]',

					);

	// The old style headers are still out there so we need to remove them also

	$old_headers = array();

	foreach ($headers as $header) {

		if (stripos($header, '* * * * *') !== false)
			$old_header[] = str_ireplace('* * * * *', '*****', $header);
	}

	$headers = array_merge($headers, $old_headers);

	// Gather the information requested

	$elements = array();
	$index = $htaccess_entry[0];
	$lth = ($htaccess_entry[1] - $htaccess_entry[0]) + 1;
	$elements = array_slice($htaccess_data, $index, $lth);

	// Remove all header information from this selection

	foreach ($headers as $key => $value) {

		if (($i = array_search($value, $elements)) !== false)
			unset($elements[$i]);
	}

	// Remove the selection information from the original array.

	$index = $htaccess_entry[0];

	while ($index <= $htaccess_entry[1])
		unset($htaccess_data[$index++]);

	// Remove empty lines from our selection

	foreach ($elements as $key => $value) {

		if (empty($value))

			unset($elements[$key]);
		else

		// make sure all bot elements indicate not the last in the chain

		if ($htaccess_entry['info']['type'] == 'bot')
			$elements[$key] = str_ireplace('[NC]', '[OR,NC]', $elements[$key]);
	}

	return $elements;
}

function BotBanishHTACCESSGetHeaderType($htaccess_data, $type = 'ip') {

	if (!BotBanishHTACCESSCheckActive($type))
		return array();

	global $BotBanishSettings;

	$position = array();
	$tags = array();

	switch ($type) {

		case 'bot':

			$entries = array(BOTBANISH_TAG_BOT_REWRITE_START, BOTBANISH_TAG_BOT_REWRITE_END);

			foreach ($entries as $key => $value) {

				$tags[$key] = array_search($value, $htaccess_data);
				$BotBanishSettings['BOTBANISH_HTACCESS_TAGS'][$key] = $tags[$key];
			}

			$tags['info'] = BotBanishSafeUnserialize(HTACCESS_ENTRY_BOT_2_4);

			break;

		default:


			$entries = array(BOTBANISH_TAG_IP_START_2_4, BOTBANISH_TAG_IP_END_2_4);

			foreach ($entries as $key => $value) {

				$tags[$key] = array_search($value, $htaccess_data);
				$BotBanishSettings['BOTBANISH_HTACCESS_TAGS'][$key] = $tags[$key];
			}

			$tags['info'] = BotBanishSafeUnserialize(HTACCESS_ENTRY_IP_2_4);
			break;
	}

	return $tags;
}

function BotBanishHTACCESSPlaceHeaderInfo($header_entry, $data) {

	if (empty($data))
		return array();

	$info = ($header_entry['info']['type'] == 'ip') ? PHP_EOL . $header_entry['info']['start'] : PHP_EOL;
	$data = $header_entry['info']['tag_start'] . $info . trim($data) . PHP_EOL . $header_entry['info']['end'] . PHP_EOL . $header_entry['info']['tag_end'];
	$lines = explode(PHP_EOL, $data);
	return $lines;
}

function BotBanishHTACCESSCombineBlockedIP() {

	if (!BotBanishHTACCESSCheckActive('ip'))
		return;

	$ip_list = array();

	// Get IP's from the database
	$table = (BOTBANISH_SERVER == 1) ? 'bbs_botbanishserver_ip' : 'bbc_botbanishclient_ip';
	$ip_list = BotBanishBlockedIPGetList($table);

	// Let's build our new data element block to insert

	$htaccess_array = BotBanishSafeUnserialize(BOTBANISH_HTACCESS_ARRAY);

	foreach ($htaccess_array as $htaccess_name) {

		// Get IP's from the htaccess file

		$htaccess_data = BotBanishHTACCESSReadFile($htaccess_name);
		$htaccess_entry = BotBanishHTACCESSGetHeaderType($htaccess_data, $type);
		$oldlines = BotBanishHTACCESSGetListData($htaccess_data, $htaccess_entry);

		// Combine both IP lists

		$lines = array_merge($oldlines, $ip_list);
		$lines = array_unique($lines);
		natcasesort($lines);

		BotBanishHTACCESSAddEntry($lines, 'ip', true);
	}
}

function BotBanishHTACCESSGetCacheTable() {

	$table = (BOTBANISH_SERVER == 1) ? 'bbs_botbanishserver_htaccess' : 'bbc_botbanishclient_htaccess';
	return $table;
}

function BotBanishAddScheduledTasks() {

	$table = BotBanishHTACCESSGetCacheTable();
	BotBanishDeleteScheduledTasks();

	$row = array();
	$row['type'] = 'cache';
	$row['text'] = 'BotBanish_Cache_Flush';

	$params = BotBanishBuildSQLStatementInsert($row, $table);

	$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';
	BotBanishExecuteSQL($sql);
}

function BotBanishCheckScheduledTasks() {

	if (!defined('BOTBANISH_HTACCESS_CACHING') ||  (!BOTBANISH_HTACCESS_CACHING))
		return false;

	$table = BotBanishHTACCESSGetCacheTable();
	$date = BotBanishGetCorrectTime();

	$sql = 'SELECT `updated` from `' . $table . '` WHERE `type` = "cache" LIMIT 1';
	$row = BotBanishExecuteSQL($sql);

	if (empty($row) || !is_array($row)) {

		BotBanishAddScheduledTasks();

	} else {

		if (isset($row['updated']) && (strtotime($date) >= (strtotime($row['updated']) + BOTBANISH_HTACCESS_FLUSH_TIME * 60))) {

			BotBanishAddScheduledTasks();
			return true;

		} else {

			$sql = 'SELECT COUNT(`updated`) AS items FROM ' . $table;
			$row = BotBanishExecuteSQL($sql);

			if (empty($row) || !is_array($row))	return false;

			if (isset($row[0]['items']) && $row[0]['items'] >= BOTBANISH_HTACCESS_FLUSH_COUNT) {

				BotBanishAddScheduledTasks();
				return true;
			}
		}
	}
}

function BotBanishDeleteScheduledTasks() {

	$table = BotBanishHTACCESSGetCacheTable();

	$sql = 'DELETE FROM `' . $table . '` WHERE `type` = "cache"';
	BotBanishExecuteSQL($sql);
}

function BotBanishHTACCESSCheckLockStatus() {

	$table = BotBanishHTACCESSGetCacheTable();

	$sql = 'SELECT `id`, `text` FROM `' . $table . '` WHERE `type` = "' . BOTBANISH_HTACCESS_LOCK . '"';
	$row = BotBanishExecuteSQL($sql);

	if (!is_array($row) || empty($row)) {

		$_SESSION['BotBanish']['BOTBANISH_CACHE'] = false;

	} else {

		BotBanishSetHTACCESSLock();
		$_SESSION['BotBanish']['BOTBANISH_CACHE'] = (isset($row['text']) && $row['text'] == BOTBANISH_HTACCESS_ACTIVE) ? $row['id'] : false;
	}

	return $_SESSION['BotBanish']['BOTBANISH_CACHE'];
}

function BotBanishHTACCESSSetLockStatus() {

	$table = BotBanishHTACCESSGetCacheTable();

	$sql = 'INSERT INTO `' . $table . '` (`type`, `text`) VALUES ("' . BOTBANISH_HTACCESS_LOCK . '", "' . BOTBANISH_HTACCESS_ACTIVE . '")';
	BotBanishExecuteSQL($sql);
	$_SESSION['BotBanish']['id'] = mysql_insert_id();
}

function BotBanishHTACCESSChangeLockStatus($action = BOTBANISH_HTACCESS_UNLOCK) {

	$table = BotBanishHTACCESSGetCacheTable();

	$state = ($action == BOTBANISH_HTACCESS_LOCK) ? BOTBANISH_HTACCESS_ACTIVE : 0;
	$sql = 'UPDATE `' . $table . '` SET `text` = "' . $state . '", `updated` = NOW() WHERE `id` = "' . $_SESSION['BotBanish']['BOTBANISH_CACHE'] . '"';
	BotBanishExecuteSQL($sql);
}

function BotBanishDatabaseHTACCESSDeleteEntry($items, $type, $htaccess_entry) {

	$table = BotBanishHTACCESSGetCacheTable();

	foreach ($items as $key => $value) {

		$entry = stripslashes(sprintf($htaccess_entry['str'], $value));
		$sql = 'DELETE FROM `' . $table . '` WHERE `data` = "' . $entry . '"';
		BotBanishExecuteSQL($sql);
	}
}

function BotBanishDatabaseHTACCESSAddEntry($items, $type) {

	if (!BotBanishHTACCESSCheckActive($type))
		return;

	$table = BotBanishHTACCESSGetCacheTable();

	foreach ($items as $item) {

		$row = array();

		$row['type'] = $type;
		$row['text'] = $item;

		$params = BotBanishBuildSQLStatementInsert($row, $table);
		$sql = 'INSERT INTO `' . $table . '` (' . $params['columns'] . ') VALUES (' . $params['values'] . ')';

		if (isset($params['updates']) && !empty($params['updates']))
			$sql .= ' ON DUPLICATE KEY UPDATE ' . $params['updates'];

		BotBanishExecuteSQL($sql);
	}

	$sql = 'SELECT `id`, `text`, `updated` FROM `' . $table . '` WHERE `type` = "' . $type . '"';
	$rows = BotBanishExecuteSQL($sql);

	if (!empty($rows) && is_array($rows)) {

		if (count($rows) >= BOTBANISH_HTACCESS_FLUSH_COUNT)
			BotBanishDatabaseHTACCESSFlushCache();
	}

	BotBanishCheckScheduledTasks();
}

function BotBanishDatabaseHTACCESSRemoveEntry($ids) {

	$table = BotBanishHTACCESSGetCacheTable();

	if (empty($ids))
		return;

	if (!is_array($ids))
		$ids = array($ids);

	$data = trim(implode(',', $ids), ',');
	$table = BotBanishHTACCESSGetCacheTable();

	$sql = 'DELETE FROM `' . $table . '` WHERE `id` IN (' . $data . ')';
	BotBanishExecuteSQL($sql);
}

function BotBanishDatabaseHTACCESSFlushCache() {

	// Only flush cache at scheduled times UNLESS overriden!!!
/*
	if (!defined('BOTBANISH_FLUSH_NOW') || (!BOTBANISH_FLUSH_NOW)) {

		if (!BotBanishCheckScheduledTasks())
			return;
	}
*/
	$table = BotBanishHTACCESSGetCacheTable();
	$types = array('ip', 'bot');

	foreach ($types as $type) {

		$sql = 'SELECT `id`, `text` FROM `' . $table . '` WHERE `type` = "' . $type . '"';
		$rows = BotBanishExecuteSQL($sql);

		$list = array();
		$ids = array();

		if (!empty($rows) && is_array($rows)) {

			foreach ($rows as $row) {

				$list[] = $row['text'];
				$ids[] = $row['id'];
			}

			BotBanishHTACCESSAddEntry($list, $type, false, true);
			BotBanishDatabaseHTACCESSRemoveEntry($ids);
		}
	}
}

function BotBanishHTACCESSLockIP($lock_ip, $ForceLockout, $lock_add) {

	if (!BotBanishHTACCESSCheckActive('ip'))
		return;

	// If this is a request to lockout an IP in the .htaccess file only, then only do that!!!

	if (!empty($lock_ip)) {

		if (BotBanishCheckIfValidIP($lock_ip)) {

			if ($lock_add) {

				BotBanishHTACCESSAddEntry($lock_ip, 'ip');

				$row = array('forcelockout' => $ForceLockout,
							'hit_count' => BOTBANISH_MAX_HIT_COUNT,
							'user_agent' => $_SERVER['HTTP_USER_AGENT'],
							'domain_name' => $_SERVER['SERVER_NAME'],
							);

				BotBanishClientIPInsertLocal($row, $lock_ip);		// Update local IP Database

			} else {

				BotBanishHTACCESSRemoveEntry($lock_ip, 'ip');
				BotBanishClientIPRemoveLocal($lock_ip);		// Update local IP Database
			}
		}

		return BotBanishExit(false);
	}
}

function BotBanishHTACCESSCheckActive($type) {

	// If we are doing special HTACCESS overriding all is ok

	if (isset($GLOBALS['HTACCESS_OVERRIDE']) && $GLOBALS['HTACCESS_OVERRIDE'])
		return true;

	// No .htaccess usage on Nginx, Lighttpd or LiteSpeed servers

	if (defined('BOTBANISH_SERVER_NGINX') && BOTBANISH_SERVER_NGINX)
		return false;

	if (defined('BOTBANISH_SERVER_LIGHTTPD') && BOTBANISH_SERVER_LIGHTTPD)
		return false;

	if (defined('BOTBANISH_SERVER_LITESPEED') && BOTBANISH_SERVER_LITESPEED)
		return false;

	switch ($type) {

		case 'ip':

			$flag = (!defined('BOTBANISH_HTACCESS_RECORD_IP') || !BOTBANISH_HTACCESS_RECORD_IP) ? false : true;
			break;

		case 'bot':

			$flag = (!defined('BOTBANISH_HTACCESS_RECORD_BOT') || !BOTBANISH_HTACCESS_RECORD_BOT) ? false : true;
			break;

		default:

			$flag = false;
			break;
	}

	return $flag;
}
?>