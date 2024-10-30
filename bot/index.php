<?php

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Randem Systems "BotBanishClient" Installation Instructions ver 3.x
// VQMOD package for use with the OpenCart, PHP, mySQL & Apache
// For more information and support.
//
// BotBanish Version 4.0.01
// Website Client Install
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

	define('BOTBANISH_CLIENT', 1);
	
//	* * * * * BotBanish * * * * *
	require_once BOTBANISH_CLIENT_DIR . 'BotBanishClient.php';

	BotBanishClient();
	
	header('location: https://botbanish.com/botbanish.html');

//	header('location: ' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . '/[YourDomainPage.html]');
	
// or if in the same area of your domain you can simply use	header('location: [YourDomainPage.html]');
?>