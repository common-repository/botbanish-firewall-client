<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.01
// Randem Systems: https://randemsystems.com/support/
// BotBanish Website Statistics
// Date: 04/04/2024
//
// On Server (Not currently used)
// Usage: https://botbanish.com/BotBanish/bot_v4.x.xx/Analytics/BotBanishAnalytics.php?user_id=xxxxx
//
// Local
// Usage: https://YOURDOMAIN/BotBanish/bot/Analytics/BotBanishAnalytics.php
//
// Function: View website and file download analytics
// NOTE: The user_id parameter is only used for analytics stored on the server.
/////////////////////////////////////////////////////////////////////////////////////////////////////////

//	define('BOTBANISH_DEBUG_SQL',1);
//	define('BOTBANISH_DEBUG_LOG_INFO',1);
	
	if (defined('ABSPATH')) {

		$filename = BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';
		$GLOBALS['parameter'] = '&';

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
		$GLOBALS['parameter'] = '?';
	}

	require_once $filename;

	// Make sure user is allowed to access system
	
	require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';
	BotBanishClient();
	
	// For debugging only
//	restore_error_handler();
//	@ini_set('display_errors', E_ALL);
	//

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '512M');	// Insure we have enough memory for large data

	$botbanish_system = BotBanishCheckSystem();

	define('BOTBANISH_ANALYTICS', true);
	define('BA_USER_ID', !empty($_GET['user_id']) ? intval($_GET['user_id']) : 0);

	switch ($botbanish_system['system']) {

		case 'WORDPRESS':

			// Load WordPress core functions. This will also load our routines
			// WordPress will handle errors

			$dir = BotBanishGetConfigLocation('wp-load.php');

			if (!empty($dir)) {

				require_once($dir . 'wp-load.php');
				if (!defined('BOTBANISH_TIMEZONE')) define('BOTBANISH_TIMEZONE', BotBanish_getWpTimezone());
			}
			
			break;

		default:

			break;
	}
		
	define('BOTBANISH_ORGANIZATION', strtoupper($_SERVER['SERVER_NAME']));
	define('BOTBANISH_ICONS', BOTBANISH_WEBSERVER . '/BotBanish/Images/icons/');

	// Statuses

	define('BOTBANISH_ANALYTICS_DOWNLOAD_STATUS', BOTBANISH_ANALYTICS_DOWNLOADS == true ? 'Active' : 'InActive');
	define('BOTBANISH_ANALYTICS_WEBSITE_STATUS', BOTBANISH_ANALYTICS_WEBSITE == true ? 'Active' : 'InActive');

	// url to script

	if (defined('ABSPATH')) {

		define('PHPURL', get_site_url() . '/wp-admin/admin.php?page=botbanishfirewall-analytics');
		define('GIFURL', get_site_url() . '/wp-content/themes/BotBanish/status/stat/gif');
		define('CSSURL', BOTBANISH_INSTALL_URL . '/bot/Analytics/css/');

	} else {
	
		define('PHPURL', BOTBANISH_INSTALL_URL . 'Analytics/BotBanishAnalytics.php');
		define('GIFURL', BOTBANISH_INSTALL_URL . 'Analytics/status/stat/gif');
		define('CSSURL', BOTBANISH_INSTALL_URL . 'Analytics/css/');
	}

	// url to the image files without trailing "/"

	define('FLAGURL', BOTBANISH_ICONS . 'flags/4x3');
	define('BROWSERURL', BOTBANISH_ICONS . 'browsers');
	define('SPIDERURL', BOTBANISH_ICONS . 'spiders');
	define('OSURL', BOTBANISH_ICONS . 'os');

	// General globals

	$GLOBALS['last_update'] = "";
	$GLOBALS['last_update_text'] = "";
	$GLOBALS['last_date'] = '';
	$GLOBALS['browser_names'] = array();

	// Calling parameters to call this script after user click on selection

	$GLOBALS['cperiod'] = '';
	$GLOBALS['cdate'] = '';
	$GLOBALS['cfile'] = '';
	$GLOBALS['cfunc'] = '';
	$GLOBALS['caction'] = '';
	$GLOBALS['cid'] = '';

	define('BA_REFRESH', 10*60);		// Minutes

	// minimum Visits before to show in list

	$GLOBALS['dl_file_min'] = 1;
	$GLOBALS['ip_address_min'] = 1;
	$GLOBALS['referer_min'] = 1;
	$GLOBALS['webpage_min'] = 1;
	$GLOBALS['max_colors'] = 12;

	define('SHOWMAX', 25);

	// misc configurations

	define('FONT_SIZE', "8pt"); 		// font size
	define('TABLE_WIDTH', 1500); 		// table width
	define('MAX_BAR_LENGTH', 500); 		// bar length of host,os,browser,etc. in relation to TABLE_WIDTH
	define('MAX_BAR_DAY_LENGTH', 170);  // bar length of day should be set in relation to TABLE_WIDTH

	define('HITS_BY_WEEKDAY', true);
	define('TOP_REFFER', false);
	define('TOP_BROWSERS', true);
	define('TOP_OS', true);
	define('TOP_SPIDERS', true);
	define('TOP_COUNTRIES', true);
	define('TOP_HOSTS', true);
	define('TOP_IP', true);

//	define('MAX_COUNTRIES', 100);

	$page = "";

	if (defined('ABSPATH'))
		global $BotBanishText;
	
	$GLOBALS['weekdays'] = array(
			$BotBanishText['Sunday'],
			$BotBanishText['Monday'],
			$BotBanishText['Tuesday'],
			$BotBanishText['Wednesday'],
			$BotBanishText['Thursday'],
			$BotBanishText['Friday'],
			$BotBanishText['Saturday']
	);

	////////////////////////////////////////////////////////////////////////////////
	// End of definitions
	////////////////////////////////////////////////////////////////////////////////

	$GLOBALS['gif_array '] = array ("purple","orange","green","pink","blue","yellow","red","gold","brown","darkgreen","aqua","h_green","black");
	$GLOBALS['this_date'] = date('Y-m-d', strtotime(BotBanishGetCorrectTime(BOTBANISH_TIMEZONE)));
	$GLOBALS['current_time'] = BotBanishGetCorrectTime(BOTBANISH_TIMEZONE);
	$GLOBALS['flags_array'] = array();

	BotBanishGetParameters();

	$db_table = SetTable();
	define('BOTBANISH_DB_TABLE', $db_table);

	$rpt_date = empty($GLOBALS['cid']) ? $GLOBALS['this_date'] : date("Y-m-d", strtotime($GLOBALS['cid']));
	$where_clause = GetWhereAndClause($rpt_date);
	$str = isset($_GET['BA_MONTH']) || !isset($_GET['func']) ? date('Y', strtotime($rpt_date)) : date('d, Y', strtotime($rpt_date));
	$GLOBALS['current_month'] = date('F', strtotime($rpt_date)) . ' ' . $str;

	// If the first time here, re-evaluate the date we want to use

	if (empty($GLOBALS['cid']) && !empty($GLOBALS['last_date'])) {

		$rpt_date = $GLOBALS['last_date'];
		$where_clause = GetWhereAndClause($rpt_date);
	}
	
	switch ($GLOBALS['cfunc']) {

		case 'page':
			$rpt_date = ShowWebsiteVisitStats($rpt_date, $where_clause);
			break;

		case 'file':
			$rpt_date = ShowFileDownloadStats($rpt_date, $where_clause);
			break;
	}

	HTML_Footer();

function GetWhereAndClause($rpt_date) {

	$and_clause = '';
	$where_clause = ($GLOBALS['cperiod'] == 'today') || ($GLOBALS['cperiod'] == '') ? "`date` = '".$rpt_date."'" : "Month(`date`) = ".date('m', strtotime($rpt_date));

	if (!empty($GLOBALS['cfile']) && ($GLOBALS['cfile'] !== ''))
		$and_clause = ($GLOBALS['cfunc'] == 'file') ? " AND filename = '".$GLOBALS['cfile']."'" : " AND page_name = '".$GLOBALS['cfile']."'";

//	$and_clause .= ' AND user_id = '.BA_USER_ID;

	return $where_clause . $and_clause;
}

function ShowWebsiteVisitStats($rpt_date, $where_clause) {
//****************************************************
//	Process total page hit statistics
//****************************************************

	SetLocation ('page');
	HTML_Header($rpt_date);
/*
	// If the first time here, re-evaluate the date we want to use

	if (empty($GLOBALS['cid']) && !empty($GLOBALS['last_date'])) {

		$rpt_date = $GLOBALS['last_date'];
		$where_clause = GetWhereAndClause($rpt_date);
	}
*/
	TopBar($rpt_date);

	$rows = BotBanishGetAnalyticsDays($where_clause);
	HTML_Days($rows);

	$rows_ip = BotBanishGetAnalyticsIP($where_clause);
	BotBanishSetCountry($rows_ip);

	$rows = BotBanishGetAnalyticsHours($where_clause);
	HTML_Hours($rows);

	$rows = BotBanishGetAnalyticsBlocks($rpt_date);
	HTML_Block_Types($rows);

	if (HITS_BY_WEEKDAY) HTML_Week($rpt_date);

	$rows = BotBanishGetAnalyticsPages($where_clause);
	HTML_Pages($rows);

	if (TOP_REFFER) {

		$rows = BotBanishGetAnalyticsReferer($where_clause);
		HTML_Referer($rows);
	}

	if (TOP_BROWSERS || TOP_OS) {

		$rows = BotBanishGetAnalyticsUA($where_clause);

		if (TOP_BROWSERS) HTML_Browser($rows);
		if (TOP_OS) HTML_OS_System($rows);
	}

	if (TOP_SPIDERS) {

		$rows = BotBanishGetAnalyticsSpider($where_clause);
		HTML_Spiders($rows);
	}

	if (TOP_COUNTRIES || TOP_HOSTS) {

		$rows = BotBanishGetAnalyticsHosts($where_clause);

		if (TOP_COUNTRIES) HTML_Countries($rows);
		if (TOP_HOSTS) HTML_Remote_Host($rows);
	}

	if (TOP_IP)
		HTML_IP_Address($rows_ip);

	echo ' </table>
			   </div>';

  return $rpt_date;
}

function ShowFileDownloadStats($rpt_date, $where_clause) {
//****************************************************
//Process total file download statistics
//****************************************************

	SetLocation ('file');
	HTML_Header($rpt_date);
/*
	// If the first time here, re-evaluate the date we want to use

	if (empty($GLOBALS['cid']) && !empty($GLOBALS['last_date'])) {

		$rpt_date = $GLOBALS['last_date'];
		$where_clause = GetWhereAndClause($rpt_date);
	}
*/
	TopBar($rpt_date);

	$rows = BotBanishGetAnalyticsDays($where_clause);
	HTML_Days($rows);

	$rows_ip = BotBanishGetAnalyticsIP($where_clause);
	BotBanishSetCountry($rows_ip);
	
	$rows = BotBanishGetAnalyticsHours($where_clause);
	HTML_Hours($rows);
	
	if (HITS_BY_WEEKDAY) HTML_Week($rpt_date);

	$rows = BotBanishGetAnalyticsDownloads($where_clause);
	HTML_Downloads($rows);

	if (TOP_REFFER) {

		$rows = BotBanishGetAnalyticsReferer($where_clause);
		HTML_Referer($rows);
	}

	if (TOP_BROWSERS || TOP_OS) {

		$rows = BotBanishGetAnalyticsUA($where_clause);

		if (TOP_BROWSERS) HTML_Browser($rows);
		if (TOP_OS) HTML_OS_System($rows);
	}

	if (TOP_SPIDERS) {

		$rows = BotBanishGetAnalyticsSpider($where_clause);
		HTML_Spiders($rows);
	}

	if (TOP_COUNTRIES || TOP_HOSTS) {

		$rows = BotBanishGetAnalyticsHosts($where_clause);

		if (TOP_COUNTRIES) HTML_Countries($rows);
		if (TOP_HOSTS) HTML_Remote_Host($rows);
	}

	if (TOP_IP)
		HTML_IP_Address($rows_ip);
	
	echo ' </table>
		   </div>';

	return $rpt_date;
}

function BotBanishGetAnalyticsSpider($where_clause) {

	$query = "SELECT http_user_agent,
		Count(http_user_agent) as Hits
		FROM " . BOTBANISH_DB_TABLE . "
		WHERE " . $where_clause . "
			Group by ip_addr, http_user_agent
			Order by Hits DESC, ip_addr ASC";

	$rows = BotBanishExecuteSQL($query, true);

	if (!is_array($rows) || count($rows) == 0) return array();

	// Only keep spiders in the reporting

	foreach ($rows as $key => $value) {

		$spider_name = CheckIfSpider($value['http_user_agent']);

		if (empty($spider_name))
			unset($rows[$key]);
		else
			$rows[$key]['http_user_agent'] = $spider_name;
	}

	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsReferer($where_clause) {

	$query = "SELECT http_referer as Referer, http_user_agent,
					Count(http_referer) as Hits
					FROM " . BOTBANISH_DB_TABLE . "
					WHERE " . $where_clause . "
						Group by Referer, http_user_agent
						Order by Hits DESC, Referer ASC";


	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsPages($where_clause) {

	$query = "SELECT page_name as Page, http_user_agent,
					Count(http_referer) as Hits
					FROM " . BOTBANISH_DB_TABLE . "
					WHERE " . $where_clause . "
					Group by Page, http_user_agent
					ORDER by Hits DESC, Page ASC";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsHours($where_clause) {

	$query = "SELECT Hour(`rpt_date`) as Hours, Count(Hour(`rpt_date`)) as Hits, http_user_agent
					FROM " . BOTBANISH_DB_TABLE . "
					WHERE " . $where_clause . "
						Group by Hours, http_user_agent
						order by Hours";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsBlocks($rpt_date) {

	$where_clause = ($GLOBALS['cperiod'] == 'today') || ($GLOBALS['cperiod'] == '') ? "`date` = '".$rpt_date."'" : "Month(`date`) = ".date('m', strtotime($rpt_date));

	$table = 'bbc_botbanishclient_website_blocks';
	
	$query = "SELECT `subject`, `blocks`
					FROM `" . $table . "`
					WHERE " . $where_clause . "
					Order by subject";
					
	$rows = BotBanishExecuteSQL($query, true);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsDownloads($where_clause) {

	$query = "SELECT filename as File, count(filename) as Hits, http_user_agent
					FROM " . BOTBANISH_DB_TABLE . "
					WHERE " . $where_clause . "
					Group by File, http_user_agent
					Order by Hits DESC, File ASC";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);

	return $rows;
}

function BotBanishGetAnalyticsUA($where_clause) {

	$query = "SELECT http_user_agent,
		Count(http_user_agent) as Hits
		FROM " . BOTBANISH_DB_TABLE . "
		WHERE " . $where_clause . "
			Group by ip_addr, http_user_agent
			Order by Hits DESC, ip_addr ASC";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsHosts($where_clause) {

	$query = "SELECT id_no, ip_addr, hostname, http_user_agent, country, country_code,
		Count(ip_addr) as Hits
		FROM " . BOTBANISH_DB_TABLE . "
		WHERE " . $where_clause . "
			Group by ip_addr, http_user_agent, hostname, country_code, country, id_no
			Order by Hits DESC, ip_addr ASC";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsIP($where_clause) {

	$query = "SELECT ip_addr, country, country_code, http_user_agent,
		Count(ip_addr) as Hits
		FROM " . BOTBANISH_DB_TABLE . "
		WHERE " . $where_clause . "
			Group by ip_addr, country, country_code, http_user_agent
			Order by Hits DESC, ip_addr ASC";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);
	$rows = BotBanishCheckRows($rows);

	return $rows;
}

function BotBanishGetAnalyticsDays($where_clause) {

	$query = "SELECT DATE_FORMAT(`date`, '%a') as DOW,
								Day(`date`) as Day,
								DATE_FORMAT(`date`,'%M') as Month,
								Year(`date`) as Year,
								count(date) as Hits
									FROM `" . BOTBANISH_DB_TABLE . "`
									WHERE " . $where_clause . "
									Group by Day, DOW, Month, Year
									ORDER by Day";

	$rows = BotBanishExecuteSQL($query, true);
	$rows = BotBanishCheckRows($rows);
	
	return $rows;
}

function BotBanishCheckRows($rows) {

	$rows = !is_array($rows) ? array() : $rows;
	return $rows;
}

function BotBanishEliminateSpiders(&$rows) {

	// Eliminate spiders from the reporting

	if (!is_array($rows) || empty($rows)) return;

	foreach ($rows as $key => $row) {

		if (isset($row['http_user_agent']) && !empty(trim($row['http_user_agent']))) {

			$spider_name = CheckIfSpider($row['http_user_agent']);

			if (!empty(trim($spider_name)))
				unset($rows[$key]);
		}
	}
}

function SetTable() {

	$db_table = ($GLOBALS['cfunc'] !== 'file') ? 'bbc_botbanishclient_website_visits' : 'bbc_botbanishclient_website_downloads';
	return $db_table;
}

function BotBanishGetParameters() {
//****************************************************
//	Get input parameters from form
//****************************************************

	global $BotBanishText;

	if (!isset($_GET['func'])) {

		$GLOBALS['cdate'] = 'all';
		$GLOBALS['cperiod'] = 'month';
		$GLOBALS['caction'] = 'stats';
		$GLOBALS['cid'] = $GLOBALS['this_date'];
		$GLOBALS['cfile'] = '';
		$GLOBALS['cfunc'] = "page";
		$GLOBALS['pterm'] = $BotBanishText['Visits'];
		return;
	}

	$GLOBALS['pterm'] = $_GET['func'] !== 'file' ? $BotBanishText['Visits'] : $BotBanishText['Downloads'];

	// pick up all the parameter for future calls

	$GLOBALS['cdate'] = isset($_GET['date']) ? $_GET['date'] : '';
	$GLOBALS['cperiod'] = isset($_GET['period']) ? $_GET['period'] : '';
	$GLOBALS['caction'] = isset($_GET['action']) ? $_GET['action'] : '';
	$GLOBALS['cid'] = isset($_GET['id']) ? $_GET['id'] : '';
	$GLOBALS['cfile'] = isset($_GET['file']) ? $_GET['file'] : '';
	$GLOBALS['cfunc'] = isset($_GET['func']) ? $_GET['func'] : '';

	// Check to see if user has selected a different month to view

	$GLOBALS['cid'] = isset($_GET['BA_MONTH']) ? $_GET['BA_MONTH'] : $GLOBALS['cid'];
}

function SetLocation($loc) {
//****************************************************
//	Set directory location for process
//****************************************************

	global $BotBanishText;

	if ($loc == 'file') {

		$GLOBALS['location'] = 'downloads';
		$GLOBALS['runtype'] = $BotBanishText['FileDownload'];
		$GLOBALS['ostat'] = 'Page Hit';
		$GLOBALS['ofunc'] = 'page';

	} else {

		$GLOBALS['location'] = 'website';
		$GLOBALS['cfunc'] = 'page';
		$GLOBALS['runtype'] = 'Page Hit';
		$GLOBALS['ostat'] = $BotBanishText['FileDownload'];
		$GLOBALS['ofunc'] = 'file';
	}
}

function SetLimits() {
//****************************************************
//	Set-up the limits for showing items when displayed
//****************************************************

   $period = $_GET['period'];

	if ($period == "today") {
	// minimum Visits before to show in list for the day
//		$host_min = 1;
//		$browser_min = 1;
//		$spider_min = 1;
//		$os_min = 1;
		$GLOBALS['dl_file_min'] = 1;
		$GLOBALS['ip_address_min'] = 1;
		$GLOBALS['referer_min'] = 1;
		$GLOBALS['webpage_min'] = 1;
//		$country_min = 1;
	}else {
	// minimum Visits before to show in list for the month
//		$host_min = 8;
//		$browser_min = 1;
//		$spider_min = 1;
//		$os_min = 1;
		$GLOBALS['dl_file_min'] = 1;
		$GLOBALS['ip_address_min'] = 10;
		$GLOBALS['referer_min'] = 8;
		$GLOBALS['webpage_min'] = 1;
//		$country_min = 10;
	}
}

//*************************************************************
//	HTML Header
//*************************************************************
function HTML_Header($rpt_date) {

	global $BotBanishText;

	$time_in_seconds = BA_REFRESH*60;
	$status = $GLOBALS['cfunc'] === 'file' ? BOTBANISH_ANALYTICS_DOWNLOAD_STATUS : BOTBANISH_ANALYTICS_WEBSITE_STATUS;

	$scripturl = isset($_SESSION['BotBanish']['scripturl']) ? $_SESSION['BotBanish']['scripturl'] : BOTBANISH_INSTALL_URL;

	echo '
	<!DOCTYPE html>
	<html>
	<head>
	<title>' . BOTBANISH_ORGANIZATION . ' - '.$BotBanishText['BotBanishWebsiteAnalytics'].'</title>
	<META http-equiv="refresh" Content="'.$time_in_seconds.'">
    <META http-equiv="Cache-Control : max-age=86400">
	<link rel="stylesheet" href="'.CSSURL.'BotBanishAnalytics.css">
	</head>

	<body>
	<center>
	<table id="heading">
		<tr>
			<td><tt>' . BOTBANISH_ORGANIZATION . '</tt><td>';

	if (!defined('ABSPATH')) {

		if ($scripturl === BOTBANISH_INSTALL_URL)
			echo '<td><tt><button type="button" onclick="parent.open(\'' . $scripturl . 'Analytics/BotBanishViewLogs.php\')">' . $BotBanishText['View Logs'] . '</button></tt></td>';
	}
	
		echo '</tr>
		<tr>
			<td><tt>' . $BotBanishText['BotBanishWebsite'] . ' ' . $GLOBALS['runtype'] . ' ' . $BotBanishText['Analytics'] . ' (' . $status . ')</tt></td>
		</tr>
		<tr>
			<td><tt>' . BOTBANISH_VERSION . '</tt></td>
		</tr>
			<table>
			   <td width="30%"><a href="' . PHPURL . $GLOBALS['parameter'] . 'func=' . $GLOBALS['ofunc'] . '&user_id=' . BA_USER_ID . '">'.$GLOBALS['ostat'].' '.$BotBanishText['Analytics'].'</a></td>
			   <td width="30%" align="center">' . GetAvailableMonthSelectionsHTML($rpt_date) . '</td>
			   <td width="30%" align="right"><b>' . $GLOBALS['current_month'] . str_repeat('&nbsp', 10) . $BotBanishText['Time'] . ': ' . $GLOBALS['current_time'] . '</b></td>
			</table>
	  <tr>
	</table>';

	CollapseStart($BotBanishText['Dates']);

	echo '
	  <table width="100%" border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="25%"><b>' . $BotBanishText['Date'] . '</b></td>
		  <td width="25%"><b>' . $BotBanishText['Weekday'] . '</b></td>
		</tr>
		';

	GetAvailableDates($rpt_date);

	echo '
		</table>
		';

	CollapseEnd();
}

function GetAvailableDates($rpt_date) {

	$query = "Select distinct `date` as report_date from " . "bbc_botbanishclient_website_blocks
					WHERE Month(`date`) = Month('".$rpt_date."')
					AND Year(`date`) = Year('".$rpt_date."')
					order by report_date";

	$rows_blocks = BotBanishExecuteSQL($query, true);
	
	$rows_blocks = is_array($rows_blocks) ? $rows_blocks : array();
	
	$query = "Select distinct `date` as report_date from " . BOTBANISH_DB_TABLE . "
					WHERE Month(`date`) = Month('".$rpt_date."')
					AND Year(`date`) = Year('".$rpt_date."')
					order by report_date";

	$rows = BotBanishExecuteSQL($query, true);

	$rows = is_array($rows) ? $rows : array();
	
	$rows = array_merge($rows_blocks, $rows);
	$rows = array_unique($rows, SORT_REGULAR);
	arsort($rows);

	if (is_array($rows) && !empty($rows)) {

		foreach ($rows as $row)	{

			$rpt_date = date('Y-m-d', strtotime($row['report_date']));
			$weekday = $GLOBALS['weekdays'][date('w', strtotime($row['report_date']))];

			echo '  <tr>
						<td width=25%><img src="'.GIFURL.'/arrow-3.gif" width=11 height=11 border=0><a href="'.PHPURL.$GLOBALS['parameter'].'func='.$GLOBALS['cfunc'].'&id='.$rpt_date.'&period=today&action=stats&date=' . $GLOBALS['this_date'] . '&refresh=' . BA_REFRESH.'&user_id='.BA_USER_ID.'">'.$rpt_date.'</a></td>
						<td width=25%><div align=left><a>' . $weekday . '</a></div></td>
					</tr>
					';
		}

		// Last date in our list

		$GLOBALS['last_date'] = $rpt_date;
	}
}

function GetAvailableMonthSelectionsHTML($rpt_date) {

	global $BotBanishText;

	$query = "Select distinct CONCAT(Year(`date`), '-', Month(`date`), '-1') as report_date from " . BOTBANISH_DB_TABLE . "
					ORDER BY report_date DESC LIMIT 12";

	$rows = BotBanishExecuteSQL($query, true);
	$select = '';

	if (is_array($rows) && !empty($rows)) {

		$select = '<select id="BA_MONTH" onchange="repopulate()">

		<option></option>
		';

		foreach ($rows as $row)	{

			$date = date_create($row['report_date']);
			$select .=  '<option value="'.$row['report_date'].'">'.date_format($date, "F Y").'</option>
			';
		}

		$select .= 	'</select>
		';
	}

	return $select;
}

//*************************************************************
//	Top Bar Headings
//*************************************************************
function TopBar($rpt_date) {

	global $BotBanishText;

	CollapseStart($BotBanishText['Statistics']);

	$month = date('F', strtotime($rpt_date));
	$year = date('Y', strtotime($rpt_date));

	if ($GLOBALS['cfunc'] == 'page') {

		$field = "page_name";
		$GLOBALS['last_update_text'] = $BotBanishText['LastPageVisit'];
	}
	else {

		$field = "filename";
		$GLOBALS['last_update_text'] = $BotBanishText['LastDownload'];
	}

	// Get the last page/download that was visited

	$query = "SELECT `".$field."`, rpt_date from " . BOTBANISH_DB_TABLE . " ORDER BY id_no DESC LIMIT 1";
	$rows = BotBanishExecuteSQL($query, true);

	$hits_per_hour = 0;
	$hits_per_day = 0;
	$total_days = 0;
	$total = 0;
	$GLOBALS['last_update'] = '';

	if (is_array($rows) && !empty($rows)) {

		foreach ($rows as $row) {

			$GLOBALS['last_update'] = $row[$field];

			// Get number of days for the period

			$where_clause = ($GLOBALS['cperiod'] == 'today') ? "cast(rpt_date as date) = '".$rpt_date."' " : "Month(rpt_date) = " . date('m',strtotime($rpt_date));

			$query = "SELECT count(id_no) as Hits from " . BOTBANISH_DB_TABLE . " WHERE ".$where_clause;

			$row_hits = BotBanishExecuteSQL($query, true);
			$total = isset($row_hits[0]['Hits']) ? $row_hits[0]['Hits'] : 0;

			// get total hit count for this period

			$query = "SELECT distinct cast(rpt_date as date) FROM " . BOTBANISH_DB_TABLE . " Where ".$where_clause;

			$row_date = BotBanishExecuteSQL($query, true);
			$total_days = is_array($row_date) ? count($row_date) : 0;

			$hits_per_day = !empty($total_days) ? sprintf("%.2f",($total / $total_days)) : 0;
			$hits_per_hour = !empty($hits_per_day) ? sprintf("%.2f",($hits_per_day / 24)) : 0;
		}
	}

	$width = '75%';

	echo '

		<img src="'.GIFURL.'/black.gif" width="100%" height="1">
		<br>

		<table border="1" cellspacing="0" cellpadding="4">
			<tr>
				<td id="weekday"><b>'.$BotBanishText['CurrentDay'].'</b></td>
				<td width="'.$width.'" ><font color="CC0000"><b>'.$rpt_date.'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$GLOBALS['last_update_text'].'</b></td>
				<td width="'.$width.'"><font color="000000"><b>'.$GLOBALS['last_update'].'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$BotBanishText['Month'].'</b></td>
				<td width="'.$width.'"><font color="CC0000"><b>'.$month.' '.$year.'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$BotBanishText['TotalDays'].'</b></td>
				<td width="'.$width.'"><font color="CC0000"><b>'.$total_days.'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$BotBanishText['Total'].' '.$GLOBALS['pterm'].'</b></td>
				<td width="'.$width.'"><font color="CC0000"><b>'.$total.'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$BotBanishText['Average'].' '.$GLOBALS['pterm'].' per day</b></td>
				<td width="'.$width.'"><font color="CC0000"><b>'.$hits_per_day.'</b></font></td>
			</tr>
			<tr>
				<td id="weekday"><b>'.$BotBanishText['Average'].' '.$GLOBALS['pterm'].' per hour</b></td>
				<td width="'.$width.'"><font color="CC0000"><b>'.$hits_per_hour.'</b></font></td>
			</tr>
		</table>

		<img src="'.GIFURL.'/black.gif" width="100%" height="1">';

	CollapseEnd();
}

function HTML_Days($rows) {
//*************************************************************
//	Visits by Day
//*************************************************************

	global $BotBanishText;

	CollapseStart($GLOBALS['pterm'] . ' '.$BotBanishText['byday'], array());

	echo '
	<table border="1" cellspacing="0" cellpadding="4">
		<tr>
		  <TD width="7%" bgcolor="DDDDE8"><B>'.$BotBanishText['Day'].'</B></TD>
		  <TD width="17%" bgcolor="DDDDE8"><B>'.$BotBanishText['Date'].'</B></TD>
		  <TD width="7%" bgcolor="DDDDE8"><B>' . $GLOBALS['pterm'] . '</B></TD>
		  <TD width="69%" bgcolor="DDDDE8">&nbsp;</TD>
		</tr>';

	$top_hits = 0;
	$lastday = "";
	$total_days = 0;
	$total_hits = 0;
	$hits_per_day = 0;
	$img_width = 0;

	if (is_array($rows) && !empty($rows)) {

		foreach ($rows as $row) {

			$hits = isset($row['Hits']) ? $row['Hits'] : 0;
			$days = isset($row['Day']) ? $row['Day'] : 0;

			$total_hits += $hits;

			if ($lastday !== $days){
				$total_days = $total_days + 1;
				$lastday = $days;
			}
		}

		foreach ($rows as $row) {

			$week_day = isset($row['DOW']) ? $row['DOW'] : 0;
			$day = isset($row['Day']) ? $row['Day'] : 0;
			$month = isset($row['Month']) ? $row['Month'] : 0;
			$year = isset($row['Year']) ? $row['Year'] : 0;
			$hits = isset($row['Hits']) ? $row['Hits'] : 0;

			$this_date = $day.'-'.$month.'-'.$year;
			if ($hits > $top_hits) $top_hits = $hits;
			$img_width = (int)(($hits * MAX_BAR_DAY_LENGTH) / $total_hits);

			echo '    <tr>';

			switch ($week_day) {

				case 'Sat':
					$font_color = '993399';
					$pic = '/gold.gif';
					break;

				case 'Sun':
					$font_color = 'FF0000';
					$pic = '/red.gif';
					break;

				default:
					$font_color = '000000';
					$pic = '/blue.gif';
					break;
			}

			echo '
				<td width="7%" bgcolor="F2FBFF"><font color='.$font_color.'>'.$week_day.'</font> </td>
			    <td width="17%" bgcolor="F2FBFF"><a href='.PHPURL.$GLOBALS['parameter'].'func='.$GLOBALS['cfunc'].'&period=today&action=stats&date=' . $this_date . '&refresh='.BA_REFRESH.'&user_id='.BA_USER_ID.'>' . $this_date . '</a </td>
			    <td width="7%" bgcolor="F2FBFF">'.$hits.'</td>
			    <td width="69%" bgcolor="FFFAF4"><img src="'.GIFURL.$pic .'" width='.$img_width.' height=10></td>

			</tr>';
		}

		$hits_per_day = sprintf("%2.2f",($total_hits / $total_days));
		$img_width = (int)(($hits_per_day * MAX_BAR_DAY_LENGTH) / $total_hits);
	}

	echo '
		<tr>
			<td width="7%" bgcolor="F2FBFF">&nbsp;</td>
			<td width="17%" bgcolor="F2FBFF"><b>'.$BotBanishText['Days'].': '.$total_days.'</b></td>
			<td width="7%" bgcolor="F2FBFF"><b>'.$total_hits.'</b></td>
			<td width="69%" bgcolor="FFFAF4"><img src="'.GIFURL.'/brown.gif" width='.$img_width.' height=10> '.$hits_per_day.'</td>
		</tr>
	</table>  <hr size=1 width="100%">
';

	CollapseEnd();
}

function HTML_Referer($rows) {
//*************************************************************
//	HTTP Referer
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['Referer'], BotBanishGetCount($rows, 'Referer'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="8%" align="center"<b>' . $GLOBALS['pterm'] . '</b></td>
		  <td width="10%"><b>'.$BotBanishText['Percent'].'</b></td>
		  <td width="82%"><b>'.$BotBanishText['Referer'].'</b></td>
		</tr>';

		if (is_array($rows) && !empty($rows)) {

			$total = count($rows);
			$total_hits = 0;
			$i = 0;
			$refs = array();
			
			foreach ($rows as $row) {
				
				$total_hits += $row['Hits'];
				
				if (!isset($refs[$row['Referer']])) {
					
					$refs[$row['Referer']] = $row['Hits'];
					
				} else {
					
					$refs[$row['Referer']] += $row['Hits'];
				}
			}

			foreach ($refs as $key => $value)	{

				$referer = $key;
				$hits = $value;
				
				if ($total >= $GLOBALS['referer_min']) {

					$percent = sprintf("%.2f", ($hits/$total_hits)*100);

					echo '    <tr>      <td width="8%">'.$hits.' </td>      <td width="10%">'.$percent.'% </td>';
					$text = empty($referer) ? '      <td width="82%">'.$BotBanishText['NOREFERER'].'</td>    </tr>' : '      <td width="82%">'.$referer.'</td>    </tr>';
					echo $text;
					$i++;

				}else{

					break;
				}
			}

			echo '
				<tr>
					<td width="8%">'.$total_hits.' </td>
					<td width="10%">'.$BotBanishText['Total'].'</td>
					<td width="82%">'.$BotBanishText['Rows'].$total.'</td>
				</tr>';
		}

	echo '
	</table>  <hr size=1 width="100%">
';

	CollapseEnd();
}

function HTML_Week($rpt_date) {
//*************************************************************
//	Week-days
//*************************************************************

	global $BotBanishText;

//	CollapseStart($GLOBALS['pterm'] . ' by week-day');

	if (!isset($_GET['period']) || $_GET['period'] != 'month')
		$period = 'WEEK';
	else
		$period = 'MONTH';

	$query = "SELECT date_format(`date`, '%a') as DOW, http_user_agent FROM " . BOTBANISH_DB_TABLE . "
					WHERE ".$period."(`date`) = ".$period."('".$rpt_date."') AND YEAR(`date`) = YEAR('".$rpt_date."') ORDER BY DOW";

	$rows = BotBanishExecuteSQL($query, true);
	BotBanishEliminateSpiders($rows);

	$week_days = array(
		'Sun' => 0,
		'Mon' => 0,
		'Tue' => 0,
		'Wed' => 0,
		'Thu' => 0,
		'Fri' => 0,
		'Sat' => 0
	);

	if (is_array($rows) && !empty($rows)) {

		foreach ($rows as $row)	{

			$DOW = isset($row['DOW']) ? $row['DOW'] : 1;

			switch ($DOW) {

				case 'Sat':
					$week_days['Sat']++;
					break;

				case 'Sun':
					$week_days['Sun']++;
					break;

				case 'Mon':
					$week_days['Mon']++;
					break;

				case 'Tue':
					$week_days['Tue']++;
					break;

				case 'Wed':
					$week_days['Wed']++;
					break;

				case 'Thu':
					$week_days['Thu']++;
					break;

				case 'Fri':
					$week_days['Fri']++;
					break;
			}
		}
	}

	echo '
	  <table id="weekday">
		<tr>
		<center><b><br>
	  ' . $GLOBALS['pterm'] . ' by week-day<a name="week-day"></a> <br>
	  <br></center>
	  </tr>
	  </b>
	  <tr>
		  <td><font color="FF0000">'.$BotBanishText['Sunday'].'</font></td>
		  <td>'.$BotBanishText['Monday'].'</td>
		  <td>'.$BotBanishText['Tuesday'].'</td>
		  <td>'.$BotBanishText['Wednesday'].'</td>
		  <td>'.$BotBanishText['Thursday'].'</td>
		  <td>'.$BotBanishText['Friday'].'</td>
		  <td><font color="009900">'.$BotBanishText['Saturday'].'</font></td>
		</tr>
		<tr>
		  <td><b>'.$week_days['Sun'].'</b></td>
		  <td><b>'.$week_days['Mon'].'</b></td>
		  <td><b>'.$week_days['Tue'].'</b></td>
		  <td><b>'.$week_days['Wed'].'</b></td>
		  <td><b>'.$week_days['Thu'].'</b></td>
		  <td><b>'.$week_days['Fri'].'</b></td>
		  <td><b>'.$week_days['Sat'].'</b></td>
		</tr>
	  </table>
	  <hr width="100%" size="1">
	  <br>';

//	 CollapseEnd();

}

function HTML_Remote_Host($rows) {
//*************************************************************
//	Host
//*************************************************************

	global $BotBanishText;

	$name_array = array();

	CollapseStart($BotBanishText['Hosts'], BotBanishGetCount($rows, 'hostname'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="30%">' . $BotBanishText['Hostname'] . '</td>
		  <td width="5%">' . $BotBanishText['Qty'] . '</td>
		  <td width="5%">' . $BotBanishText['Flag'] . '</td>
		  <td width="60%">' . $BotBanishText['Percentage'] . '</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		$total = count($rows);
		$host_only_hits = 0;
		$ip_only_hits = 0;
		$total_hits = 0;
		$percent = 0;
		$img_width = 0;

		foreach ($rows as $row)	{

			$total_hits += $row['Hits'];

			if ($row['hostname'] == $row['ip_addr']) {

				$ip_only_hits = $ip_only_hits + $row['Hits'];

			} else {

				if (!empty($row['hostname'])) {

					$items = explode('.', $row['hostname']);
					$last = count($items);
					$hostname = $last > 1 ? $items[$last-2].'.'.$items[$last-1] : $row['hostname'];
					$host_only_hits = $host_only_hits + $row['Hits'];
					UpdateNameCounts($hostname, $row['Hits'], $name_array, $row['country']);
				}
			}
		}

		arsort($name_array);
		$j = 0;
		$line_no = 0;

		foreach ($name_array as $names) {

			$line_no++;

			$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
			$percent = sprintf ("%.2f", (($names['hits'] / $total_hits) * 100));
			$country_image = BotBanishCheckImage(FLAGURL.'/', strtolower($GLOBALS['flags_array'][$names['country']]), '1_general/general_512x512.svg');

			echo '
				<tr>
					<td width=30%>'.$line_no.') '.$names['name'].'</td>
					<td width=5%>'.$names['hits'].'</td>
					<td width=5% title="'. $names['country'] . '"><img src="' . $country_image . '" width=50 height=20 ></td>
					<td width=60%"><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
			    </tr>';
			$j++;
			if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		$percent = sprintf ("%.2f", (int)(($ip_only_hits / $total_hits) * 100));
		$img_width = (int)(($ip_only_hits * MAX_BAR_LENGTH) / $total_hits);

		echo '
			<tr>
				<td width=30%>'.$BotBanishText['IP_ONLY'].'</td>
				<td width=5%>'.$ip_only_hits.'</td>
				<td width=5%></td>
				<td width=60%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][9].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
		    </tr>';

		echo '
			<tr>
				<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=5%>'.$total_hits.'</td>
				<td width=5%></td>
				<td width=60%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Browser($rows) {
//*************************************************************
//	Browsers
//*************************************************************

	global $BotBanishText;

	$name_array = array();

	if (is_array($rows) && !empty($rows)) {

		$browser_array = array();
		$total_hits = 0;
		$GLOBALS['browser_names'] = array();
		$total = count($rows);

		foreach ($rows as $row) {

			// Get to the browser area of the UA
			$temp = explode(' ', $row['http_user_agent']);

			$browser = 'unknown';

			// Find an item with a version number
			foreach (array_reverse($temp) as $tmp) {

				if (strpos($tmp, '/') === false)
					continue;

				$tmp1 = explode('/', $tmp);

				// Browser must have a version
				if ((floatval($tmp1[1]) === 0) || stripos($tmp1[1], '.') === false || stripos($tmp1[0], '(') !== false) {
					continue;
				}else{
					$browser = $tmp1[0];
					break;
				}
			}

			// Browsers may use the same engines
			if ((stripos($browser, 'safari') !== false) && (stripos($row['http_user_agent'], 'chrome') !== false))
				$browser = 'chrome';

			if ((stripos($browser, 'trident') !== false) && (stripos($row['http_user_agent'], 'msie') !== false))
				$browser = 'msie';

			// Show browser info
			if (!empty($browser)) {

				$total_hits += $row['Hits'];
				$brw = explode('/', $browser);

				$browser_array[$browser] = $brw[0];
				$GLOBALS['browser_names'][] = $brw[0];

				UpdateNameCounts($browser, $row['Hits'], $name_array);
			}
		}
	}

	CollapseStart($BotBanishText['Browsers'], $name_array);

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="5%">&nbsp;</td>
		  <td width="30%">&nbsp;</td>
		  <td width="8%">&nbsp;</td>
		  <td width="62%">&nbsp;</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		arsort($name_array);
		$j = 0;
		$line_no = 0;

		foreach ($name_array as $names) {

			if (!isset($browser_array[$browser]))
				continue;

			$line_no++;

			$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
			$percent = sprintf ("%.2f",(($names['hits'] / $total_hits) * 100));

			$browser_name = str_ireplace(' ', '_', strtolower($browser_array[$names['name']]));
			$browser_image = BotBanishCheckImage(BROWSERURL.'/', $browser_name.'/'.$browser_name.'_512x512.png', '1_general/general_512x512.png');

			echo '
				<tr>
					<td width=5%><img src="'.$browser_image. '" width=50 height=20 ></td>
					<td width=30%>'.$line_no.') '.$names['name'].'</td>
					<td width=8%>'.$names['hits'].'</td>
					<td width=62%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
			    </tr>';

			 $j++;
			 if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		echo '
			<tr>
				<td width=5%>&nbsp</td>
				<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=8%>'.$total_hits.'</td>
				<td width=62%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Spiders($rows) {
//*************************************************************
//	Spiders
//*************************************************************

	global $BotBanishText;

	$total_hits = BotBanishCountHits($rows);

	CollapseStart($BotBanishText['SpidersRobots'], BotBanishGetCount($rows, 'http_user_agent'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="5%">&nbsp;</td>
		  <td width="30%">&nbsp;</td>
		  <td width="8%">&nbsp;</td>
		  <td width="62%">&nbsp;</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		$name_array = array();
		$spider_array = array();
		$total = count($rows);

		foreach ($rows as $row) {

			UpdateNameCounts($row['http_user_agent'], $row['Hits'], $name_array);
			$spider_array[$row['http_user_agent']] = $row['http_user_agent'];
		}

		arsort($name_array);
		$line_no = 0;
		$j = 0;

		foreach ($name_array as $names) {

			$line_no++;

			if (!isset($spider_array[$names['name']]))
				continue;

			$spider_name = str_ireplace(' ', '_', strtolower($spider_array[$names['name']]));

			$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
			$percent = sprintf ("%.2f", (($names['hits'] / $total_hits) * 100));
			$spider_image = BotBanishCheckImage(SPIDERURL.'/', $spider_name . '/' . $spider_name.'_512x512.png', '1_general/general_512x512.png');

			echo '
				<tr>
					<td width=5%><img src="'.$spider_image.'" width=50 height=20 ></td>
					<td width=30%>'.$line_no.') '.$names['name'].'</td>
					<td width=8%>'.$names['hits'].'</td>
					<td width=62%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
			    </tr>';

			$j++;
			if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		if ($total_hits > 0) {

			echo '
				<tr>
					<td width=5%>&nbsp</td>
					<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
					<td width=8%>'.$total_hits.'</td>
					<td width=62%></td>
			    </tr>';
		}
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_OS_System($rows) {
//*************************************************************
//	Operating System
//*************************************************************

	global $BotBanishText;

	$name_array = array();

	if (is_array($rows) && !empty($rows)) {

		$os_array = array();
		$total_hits = 0;
		$line_no = 0;
		$j = 0;
		$total = count($rows);

		foreach ($rows as $row) {

			$useragent = BotBanishFixUserAgent($row['http_user_agent']);
			$startpos = strpos($useragent, '(');
			$endpos = strpos($useragent, ')');
			$useragent = substr($useragent, $startpos + 1, ($endpos - $startpos) - 1);
			$useragent = str_ireplace(array('windows;', '(', ')'), ';', $useragent);

			$temp = explode(';', $useragent);

			$os = 'unknown';

			foreach ($temp as $tmp) {

				$tmp1 = str_ireplace(array('compatible', 'macintosh', 'mozilla', 'rv:', 'wow64', 'gecko',
										'msie', ' u', 'win64', 'x64', 'en-us', 'applewebkit'),
										'', $tmp);

				// Remove all captured browser information
				if (!empty($GLOBALS['browser_names']))
					$tmp1 = str_ireplace($GLOBALS['browser_names'], '', $tmp1);

				if (!empty($tmp1)) {

					if (strlen($tmp) === strlen($tmp1)) {
						$os = trim($tmp);
						break;
					}
				}
			}

			// Detect phone types
			if ((stripos($os, 'Linux') !== false) && (stripos($row['http_user_agent'], 'Android') !== false))
				$os = 'Android';

			$total_hits += $row['Hits'];
			$os_array[$os] = $os;
			UpdateNameCounts($os, $row['Hits'], $name_array);
		}
	}

	CollapseStart($BotBanishText['OperatingSystem'], $name_array);

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="5%">&nbsp;</td>
		  <td width="30%">&nbsp;</td>
		  <td width="8%">&nbsp;</td>
		  <td width="62%">&nbsp;</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		arsort($name_array);

		foreach ($name_array as $names) {

			$line_no++;

			$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
			$percent = sprintf ("%.2f",(($names['hits'] / $total_hits) * 100));

			// MAC OS X
			$name = 'mac_os_x_';
			$os_pic = str_ireplace(' ', '_', strtolower($names['name']));

			if (stripos($os_pic, $name) !== false) {

				$os_pic = str_ireplace('.', '_', $os_pic);
				$temp = explode('_', $os_pic);

				if (count($temp) > 6) {

					unset($temp[count($temp)-1]);
					$os_pic = implode('_', $temp);
				}
			}

			$os_image = BotBanishCheckImage(OSURL.'/', $os_pic.'_512x512.png', '1_general/general_512x512.png');

			echo '
				<tr>
					<td width=5%><img src="'.$os_image.'" width=50 height=20 ></td>
					<td width=30%>'.$line_no.') '.$names['name'].'</td>
					<td width=8%>'.$names['hits'].'</td>
					<td width=62%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
				</tr>';

			$j++;
			if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		echo '
			<tr>
				<td width=5%>&nbsp</td>
				<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=8%>'.$total_hits.'</td>
				<td width=62%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Countries($rows) {
//*************************************************************
//	Countries
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['TopCountries'], BotBanishGetCount($rows, 'country_code'));

	echo '
	<table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="5%">' . $BotBanishText['Flag'] . '</td>
		  <td width="30%">' . $BotBanishText['Country'] . '</td>
		  <td width="5%">' . $BotBanishText['Qty'] . '</td>
		  <td width="60%">' . $BotBanishText['Percentage'] . '</td>
		</tr>
';

	if (is_array($rows) && !empty($rows)) {

		$name_array = array();
		$ip_only_hits = 0;
		$cc_only_hits = 0;
		$total_hits = 0;
		$img_width = 0;
		$percent = 0;
		$total = count($rows);

		foreach ($rows as $row) {

			$country = !empty($row['country']) ? $row['country'] : $BotBanishText['Unknown'];				
			UpdateNameCounts($country, $row['Hits'], $name_array, $country);
			$total_hits += $row['Hits'];
		}

		arsort($name_array);

		$j = 0;
		$line_no = 0;

		foreach ($name_array as $names) {

			$line_no++;

			$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
			$percent = sprintf ("%.2f",(($names['hits'] / $total_hits) * 100));
			$country_image = BotBanishCheckImage(FLAGURL.'/', strtolower($GLOBALS['flags_array'][$names['country']]), '1_general/general_512x512.svg');

			echo '
				<tr>
					<td width=5% title="' . $names['country'] . '"><img src="' . $country_image . '" width=50 height=20 ></td>
					<td width=30%>'.$line_no.') '.$names['country'].'</td>
					<td width=5%>'.$names['hits'].'</td>
					<td width=60%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
				</tr>
';

			$j++;
			if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		$percent = sprintf ("%.2f",(($ip_only_hits / $total_hits) * 100));
		$img_width = (int)(($ip_only_hits * MAX_BAR_LENGTH) / $total_hits);

		echo '
			<tr>
				<td width=5%></td>
				<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=5%>'.$total_hits.'</td>
				<td width=60%></td>
			</tr>
';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Block_Types($rows) {
//*************************************************************
//	IP Addresses
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['BLOCKS'], BotBanishGetCount($rows, 'subject'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="45%">' . $BotBanishText['Subject'] . '</td>
		  <td width="5%">' . $BotBanishText['Qty'] . '</td>
		  <td width="50%">' . $BotBanishText['Percentage'] . '</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {
		
		$j = 0;
		$total_blocks = 0;
		$img_width = 0;
		$percent = 0;
		$total = count($rows);
		$name_array = array();
		
		foreach ($rows as $row) {

			UpdateNameCounts($row['subject'], $row['blocks'], $name_array);
			$total_blocks += $row['blocks'];
		}
		
		arsort($name_array);
		
		foreach ($name_array as $names) {

			$subject = $names['name'];
			$blocks = $names['hits'];
			$img_width = (int)(($blocks * MAX_BAR_LENGTH) / $total_blocks);
			$percent = sprintf ("%.2f",($blocks / $total_blocks * 100));
				  
			echo '
				<tr>
					<td width=45%>' . $subject . '</td>
					<td width=5%>' . $blocks.'</td>
					<td width=50%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
				</tr>';

			$j++;
			if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
		}

		echo '
			<tr>
				<td width=45%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=5%>'.$total_blocks.'</td>
				<td width=50%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_IP_Address($rows) {
//*************************************************************
//	IP Addresses
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['IPAddress'], BotBanishGetCount($rows, 'ip_addr'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="30%">' . $BotBanishText['IPAddress'] . '</td>
		  <td width="5%">' . $BotBanishText['Qty'] . '</td>
		  <td width="5%">' . $BotBanishText['Flag'] . '</td>
		  <td width="60%">' . $BotBanishText['Percentage'] . '</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		$j = 0;
		$line_no = 0;
		$total_hits = 0;
		$total = count($rows);
		$name_array = array();

		foreach ($rows as $row) {

			$ip = $row['ip_addr'];
			UpdateNameCounts($ip, $row['Hits'], $name_array, $row['country']);
			$total_hits += $row['Hits'];
		}

		arsort($name_array);
		
		foreach ($name_array as $names) {

			$ip = $names['name'];
			$hits = $names['hits'];
			$line_no++;

			  if ($total >= $GLOBALS['ip_address_min']) {
				  
				$img_width = (int)(($hits * MAX_BAR_LENGTH) / $total_hits);
				$percent = sprintf ("%.2f",($hits / $total_hits * 100));
				$country_image = BotBanishCheckImage(FLAGURL.'/', strtolower($GLOBALS['flags_array'][$names['country']]), '1_general/general_512x512.svg');

				echo '
					<tr>
						<td width=30%>'.$line_no.') '.$ip.'</td>
						<td width=5%>'.$hits.'</td>
						<td width=5% title="' . $names['country'] . '"><img src="' . $country_image . '" width=50 height=20 ></td>
						<td width=60%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
				    </tr>';

				$j++;
				if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
			}
		}

		echo '
			<tr>
				<td width=30%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=5%>'.$total_hits.'</td>
				<td width=5%></td>
				<td width=60%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Downloads($rows) {
//*************************************************************
//	File Downloads
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['Downloads'], BotBanishGetCount($rows, 'File'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="55%" align="center"<b>'.$BotBanishText['Downloads'].'</b></td>
		  <td width="8%">&nbsp;</td>
		  <td width="37%">&nbsp;</td>
		</tr>';

	if (is_array($rows) && !empty($rows)) {

		$total_hits = 0;
		$line_no = 0;

		$total = count($rows);
		$name_array = array();
		
		foreach ($rows as $row) {
			
			$total_hits += $row['Hits'];
			UpdateNameCounts($row['File'], $row['Hits'], $name_array);
		}

		arsort($name_array);
			
		$j = 0;

		foreach ($name_array as $names)	{

			  if ($total >= $GLOBALS['dl_file_min']) {

				$line_no++;
				$dl = $names['name'];
				$hits = $names['hits'];
				$img_width = (int)(($hits * MAX_BAR_LENGTH) / $total_hits);
				// adjust for the percentage we incremented the first cell
				// and decremented the third one
				$img_width =  $img_width - ($img_width * .05);
				$percent = sprintf ("%.2f",(($hits / $total_hits) * 100));

				echo '
					<tr>
						<td width=55%>'.$line_no.') <a href="'.PHPURL.$GLOBALS['parameter'].'func='.$GLOBALS['cfunc'].'&id='.$GLOBALS['cid'].'&period='.$GLOBALS['cperiod'].'&action='.$GLOBALS['caction'].'&date='.$GLOBALS['cdate'].'&file='.$dl.'&refresh='.BA_REFRESH.'&user_id='.BA_USER_ID.'">'.$dl.'</a></td>
						<td width=8%>'.$hits.'</td>
						<td width=37%><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
					</tr>';

				 $j++;
				if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
			  }
		}

		echo '
			<tr>
				<td width=55%>'.$BotBanishText['Rows'].$total.'</td>
				<td width=8%>'.$total.'</td>
				<td width=37%></td>
		    </tr>';
	}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Pages($rows) {
//*************************************************************
//	Website pages
//*************************************************************

	global $BotBanishText;

	CollapseStart($BotBanishText['Pages'], BotBanishGetCount($rows, 'Page'));

	echo '
	  <table border="1" cellspacing="0" cellpadding="4">
		<tr id="header">
		  <td width="30%" align="center"><b>'.$BotBanishText['URL'].'</b></td>
		  <td width="8%">&nbsp;</td>
		  <td width="62%">&nbsp;</td>
		</tr>';

		if (is_array($rows) && !empty($rows)) {

			$total_hits = 0;
			$name_array = array();

			foreach ($rows as $row) {

				$total_hits += $row['Hits'];
				UpdateNameCounts($row['Page'], $row['Hits'], $name_array);
			}

			$j = 0;
			$line_no = 0;
			$total = count($rows);
			arsort($name_array);

			foreach ($name_array as $names) {

				if ($total >= $GLOBALS['webpage_min']) {

					$line_no++;
					$img_width = (int)(($names['hits'] * MAX_BAR_LENGTH) / $total_hits);
					$percent = sprintf ("%.2f",(($names['hits'] / $total_hits) * 100));

					echo '
						<tr>
							<td width=50%>'.$line_no.') <a href="'.PHPURL.$GLOBALS['parameter'].'func='.$GLOBALS['cfunc'].'&id='.$GLOBALS['cid'].'&period='.$GLOBALS['cperiod'].'&action='.$GLOBALS['caction'].'&date='.$GLOBALS['cdate'].'&file='.$names['name'].'&refresh='.BA_REFRESH.'&user_id='.BA_USER_ID.'">'.$names['name'].'</a></td>
							<td width=8%>'.$names['hits'].'</td>
							<td><img src="'.GIFURL.'/'.$GLOBALS['gif_array '][$j].'.gif" width='.$img_width.' height=10> '.$percent.'%</td>
					    </tr>';

					$j++;
					 if ($j > ($GLOBALS['max_colors'] - 1)) $j = 0;
				}
			}

			echo '
				<tr>
					<td width=50%>'.$BotBanishText['Rows'].$total.'</td>
					<td width=8%>'.$total_hits.'</td>
					<td></td>
			    </tr>';
		}

	echo '
	</table>  <hr width="100%" size=1>
';

	CollapseEnd();
}

function HTML_Hours($rows) {
//*************************************************************
//	Visits by hour
//*************************************************************

	global $BotBanishText;

	CollapseStart($GLOBALS['pterm'] . ' '.$BotBanishText['byhour'], array());

	 echo '
	 <table id="chart">';

		$totalhits = 0;

		$top_hour = 1;
		$hour = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);

		// Gather the hourly information

		if (is_array($rows) && !empty($rows)) {

			foreach ($rows as $row)	{

				$totalhits += $row['Hits'];
				$index = $row['Hours'];
				$hour[$index] += $row['Hits'];
				if ($hour[$index] > $top_hour ) $top_hour = $hour[$index];
			}
		}

		$max_height = 100;

		// Calculate size of 1 unit of height

		$size = ($top_hour / $max_height);
		$size = 1 / $size;

		echo '<tr>
				<td>&nbsp</td>
			';

		// create the bars

		if (is_array($rows) && !empty($rows)) {

			for ($i = 0; $i < 24; $i++){

				$img_height = (int)($hour[$i] * $size);
				if (($img_height < 1) && ($img_height > -1)) $img_height = 1;

				echo '  <td style="vertical-align: bottom">
							<img src="'.GIFURL.'/h_green.gif" width=50 height='.$img_height.'>
						</td>';
			}
		}

		echo '</tr>
			<tr>
				<td colspan=26>
					<div align=right><img src="'.GIFURL.'/black.gif" width="100%" height=1></div>
				</td>
			</tr>
			<tr>
				<td>'.$BotBanishText['Hour'].'</td>';

		// Place the Hours in the table

		for ($hour_j = 0;$hour_j < 24;$hour_j++) {

		  $pad_hour = $hour_j;
		  if ($pad_hour < 10) $pad_hour="0".$pad_hour." ";

		  echo '    <td>'.$pad_hour.'</td>';
		}

		echo '
			</tr>
			<tr>
				<td>' . $GLOBALS['pterm'] . '</td>';

		// Place the Hourly Hits in the table

		for ($val = 0;$val < 24;$val++) {

		  $pad_val = $hour[$val];
		  if ($pad_val < 10) $pad_val = "0".$pad_val;
		  $hits=sprintf("%2d",($pad_val));

		  echo '    <td>'.$hits.'</td>';
		}

		echo '
			</tr>';

	echo '
		</table>
';

	CollapseEnd();
}

function HTML_Footer() {
//*************************************************************
//	Page Footer
//*************************************************************

	echo '
		<script>
			var coll = document.getElementsByClassName("collapsible");
			var i;

			for (i = 0; i < coll.length; i++) {
			  coll[i].addEventListener("click", function() {
				this.classList.toggle("active");
				var content = this.nextElementSibling;
				if (content.style.display === "block") {
				  content.style.display = "none";
				} else {
				  content.style.display = "block";
				}
			  });
			}
		</script>';

	echo "
		<script>
			function repopulate() {
			  var x = document.getElementById('BA_MONTH');
			  var mth = x.options[x.selectedIndex].text;
			  mth = mth.trim();
			  location.href = '" . PHPURL . $GLOBALS['parameter'] . 'func='.$GLOBALS['cfunc'].'&period=month&action=stats&date=all&refresh='.BA_REFRESH.'&user_id='.BA_USER_ID . '&BA_MONTH=' . "'+mth;
			}
		</script>";

     echo '
	 </div></center></body></html>
';
}

function CollapseStart($text, $rows = array()) {

	if (!is_array($rows)) $rows = array();
	$str = (count($rows) > 0) ? ' (' . count($rows) . ')' : '';
	
echo '
	<div class="collapsible">' . $text . $str . '</div>
	<div class="content" id="'.$text.'">';
}

function CollapseEnd() {

echo '
	</div>
	';
}

function UpdateNameCounts($name, $hits, &$name_array, $country = '') {

	$item = array('name' => $name, 'hits' => $hits, 'country' => $country);
	$str = strtolower($name);

	if (isset($name_array[$str]))
		$name_array[$str]['hits'] += $hits;
	else
		$name_array[$str] = $item;
}

function CheckIfSpider($useragent) {

	if (defined('BOTBANISH_SERVER') && (BOTBANISH_SERVER == 1))
		return BotBanishSpiderCheck(BotBanishFixUserAgent($useragent), 'bbs_botbanishserver_spiders');
	else
		return BotBanishSpiderCheck(BotBanishFixUserAgent($useragent), 'bbc_botbanishclient_spiders');
}

function BotBanishCountHits($rows) {

	$total_hits = 0;

	if (is_array($rows) && !empty($rows)) {

		foreach ($rows as $row)
			$total_hits += $row['Hits'];
	}

	return $total_hits;
}

function BotBanishGetCount($rows, $column) {

	$CountArray = array();
	
	if (is_array($rows) || !empty($rows)) {
		
		foreach ($rows as $row) {

			switch ($column) {

				case 'hostname':

					// If we are processing hostnames, don't include ip only records

					if (($row['hostname'] != $row['ip_addr']) && !empty($row['hostname'])) {

						$items = explode('.', $row['hostname']);
						$last = count($items);
						$hostname = $last > 1 ? $items[$last-2].'.'.$items[$last-1] : $row['hostname'];

						if (!isset($CountArray[$hostname]))
							$CountArray[$hostname] = 0;
						else
							$CountArray[$hostname]++;
					}
					break;

				case 'Referer':

					if (!isset($CountArray[$row[$column]]))
						$CountArray[$row[$column]] = 0;
					else
						$CountArray[$row[$column]]++;

					break;

				default:

					// Update the actual row counts

					if (!empty($row[$column])) {

						if (!isset($CountArray[$row[$column]]))
							$CountArray[$row[$column]] = 0;
						else
							$CountArray[$row[$column]]++;
					}

					break;
			}
		}
	}

	return $CountArray;
}

function BotBanishSetCountry($rows) {

	global $BotBanishText;
	
	foreach ($rows as $row) {
		
		// If our country colums are empty, we will attempt to dynamically fill them and update the database table

		if (isset($row['country']) && empty($row['country'])) {

			$geo_data = BotBanishGetGeoData($row['ip_addr']);
			$country_info = BotBanishGetCountryData($geo_data);

			$country = !empty($country_info['country']) ? $country_info['country'] : $BotBanishText['Unknown'];
			$country_code = !empty($country_info['country_code']) ? $country_info['country_code'] : '';
			$GLOBALS['flags_array'][$country] = !empty($country_info['flag']) ? $country_info['flag'] : $country_code . '.svg';

			if (!empty($country_code)) {

				$sql = 'UPDATE ' . BOTBANISH_DB_TABLE . '
							SET `country` = "' . $country . '", `country_code` = "' . $country_code . '"
								WHERE `id_no` = ' . $row['id_no'];

				BotBanishExecuteSQL($sql);
			}

		} else {

			$country = !empty($row['country']) ? $row['country'] : $BotBanishText['Unknown'];
			$country_code = !empty($row['country_code']) ? $row['country_code'] : '';
			$GLOBALS['flags_array'][$country] = !empty($country_code) ? $country_code.'.svg' : 'us.svg';
		}
	}
}
?>