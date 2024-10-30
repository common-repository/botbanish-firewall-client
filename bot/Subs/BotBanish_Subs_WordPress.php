<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 4.0.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Setup
// Date: 06/03/2022
// Usage:
//
// Function:
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

function BotBanishProcessWordPressSQL($sql_statement) {
	global $wpdb;

	$keywords = array(
						'SELECT',
						'INSERT',
						'UPDATE',
						'CREATE',
						'DELETE',
						'DROP',
						'SHOW',
						'RENAME',
						'TRIGGER',
						'ALTER',
						'DESCRIBE',
				);

	$found = false;
	$rows = array();
	$results = array();

	foreach ($keywords as $key) {

		if (stripos($sql_statement, $key) !== false) {
			
			$found = true;
			break;
		}
	}

	switch ($found) {

		case true:

			$result = $wpdb->get_results($sql_statement);
			$row = array();

			foreach ($result as $key => $value) {
				
				$row = std_class_object_to_array($value);
				$rows[] = $row;
			}
			break;

		default:

			$wpdb->query($sql_statement);
			break;
	}

	return $rows;
}

function std_class_object_to_array($stdclassobject) {
	
	$array = array();
	
	$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
	
	foreach ($_array as $key => $value) {
		
		$value = (is_array($value) || is_object($value)) ? std_class_object_to_array($value) : $value;
		$array[$key] = $value;
	}
	
	return $array;
}
?>