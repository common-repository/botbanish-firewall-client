<?php

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Randem Systems "BotBanishClient" Installation Instructions ver 3.x
// VQMOD package for use with the OpenCart, PHP, mySQL & Apache
// For more information and support.
//
// BotBanish Version 4.0.01
// Website Client Install
// Date: 05/31/2021
//
// BotBanish Website: http://botbanish.com
// BotBanish Support: https://randemsystems.com/support/index.php#c18
// Release History: https://randemsystems.com/support/botbanish-release-history/
//
// Used to help protect the website from attacks
// Replace your domain root index.php file with this one (BotBanishIndex.php renamed to index.php)
// then have it call your renamed index.php or another .php or .html file
// Make sure you do not have a index.html file in the root. This can lead to confusion.
//
// Change [YourDomainPage.html] to the html or php file that your website would originally run when the domain is accessed
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	define('BOTBANISH_DEBUG_TRACE_ALL',1);
	define('BOTBANISH_DEBUG_SQL', 1);
	
//	* * * * * BotBanish * * * * *
	require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';

	BotBanishLogPage();
	BotBanishFlushOutputBuffer();
	
	BotBanishClient();
	
	header('Location: https://botbanish.com');

function BotBanishLogPage() {
	
	$url = BOTBANISH_INSTALL_URL . 'Analytics/BotBanishAnalyticsLog.php';

	$fields = array(
		'pagename'     => "index.html",
		'user_id'      => "0"
	);

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);
}
?>