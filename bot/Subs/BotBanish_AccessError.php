<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.0.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Error Document Checking
// Date: 03/21/2024
//
// Function: To monitor requestes to non-existant files in an attempt to find vunerabilities
//
// Note: This also helps in finding missing files in links and calls
///////////////////////////////////////////////////////////////////////////////////////////////////////

	// If this error is from the search for a BotBanish server, let the search routine handle it!!

	if (isset($_SERVER['REDIRECT_STATUS']) && isset($_SERVER['REDIRECT_URL'])) {

		if (($_SERVER['REDIRECT_STATUS'] == 404) && (stripos($_SERVER['REDIRECT_URL'], 'BotBanishServer.php') !== false))
			return;
	}

	// If this search is for a file with one of the following extensions, it is ok, Don't penalize!!!

	$extensions = array('.png', '.jpg', '.gif', '.svg', 'ogg', '.webm');

	$url = isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : '';
	$url = empty($url) && isset($_SERVER['SCRIPT_URI']) ? $_SERVER['SCRIPT_URI'] : $url;
	$url = empty($url) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $url;
	$url = empty($url) && isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $url;

	if (!empty($url)) {

		// For SMF 2.1.2

		if (stripos($url, 'custom_avatar') != false)
			return;

		// For all else

		foreach ($extensions as $extension) {

			if (substr($url, strlen($url) - strlen($extension)) == $extension)
				return;
		}
	}

	if (defined('ABSPATH')) {

		$filename = BOTBANISH_PLUGIN_DIR . 'bot/Settings_Client.php';

	} else {

		$self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], '/') + 1);
		$filename = realpath(str_ireplace($self, '../Settings_Client.php', $_SERVER['SCRIPT_FILENAME']));
	}
	
	require_once $filename;
	require_once(BOTBANISH_CLIENT_DIR . 'BotBanishClient.php');

	@ini_set('zlib_.output_compression', 0);
	@ini_set('implicit_flush', 1);
	error_reporting(E_ALL);

	// If this is a redirect from an SetEnvIfNoCase in the .htaccess file, get rid of this bot.
	// No need to process it again

	if (isset($_SERVER['bad_bot']) || isset($_SERVER['REDIRECT_bad_bot']))
		BotBanishExit(false);

	// Redirect Status

	if (isset($_SERVER['REDIRECT_STATUS'])) {

		switch ($_SERVER['REDIRECT_STATUS']) {

			case 400:
			case 401:

				if (isset($client_info['REDIRECT_URL']) && empty($client_info['REDIRECT_URL']))
					$client_info['REDIRECT_URL'] = $client_info['REQUEST_URI'];
				
				break;

			case 404:

				if (isset($_SERVER['REDIRECT_URL']))
					BotBanish404Error();
				
				break;
				
			default:
				break;
		}
	}

	if (isset($_SERVER['REDIRECT_QUERY_STRING']) && !empty($_SERVER['REDIRECT_QUERY_STRING'])) {

		parse_str($_SERVER['REDIRECT_QUERY_STRING'], $row);

		if (isset($row['docerror'])) {

			if (in_array($row['docerror'], BotBanishSafeUnserialize(BOTBANISH_DOC_ERRORS))) {

				$client_info = $_SERVER;
				$client_info['REDIRECT_STATUS'] = $row['docerror'];

				// Treat redirected url's (ReWrite) like they were redirects from ErrorDocuments in the .htaccess file

				switch ($row['docerror']) {

					case 400:
					case 401:

						if (isset($client_info['REDIRECT_URL']) && empty($client_info['REDIRECT_URL']))
							$client_info['REDIRECT_URL'] = $client_info['REQUEST_URI'];
						
						break;

					case 404:

					if (isset($_SERVER['REDIRECT_URL']))
						BotBanish404Error();
						
						break;

					default:
						break;
				}
			}
		}

	} else {


		if (isset($_SERVER['REDIRECT_URI']) && !empty($_SERVER['REDIRECT_URI'])) {

			if (isset($_SERVER['DOCERROR'])) {

				if (in_array($_SERVER['DOCERROR'], BotBanishSafeUnserialize(BOTBANISH_DOC_ERRORS))) {

					$client_info = $_SERVER;

					// Treat redirected url's (ReWrite) like they were redirects from ErrorDocuments in the .htaccess file

					$client_info['REDIRECT_QUERY_STRING'] = isset($_SERVER['REDIRECT_QUERY_STRING']) ? $client_info['REDIRECT_QUERY_STRING'] : $_SERVER['REDIRECT_URI'];
				}
			}
		}
	}
		
	global $BotBanishText;

	$error = isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : '';
	$error = empty($error) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$error = empty($error) && isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : '';

	if (isset($_SERVER['REMOTE_ADDR']) && !empty($error)) {

		$geo_data = BotBanishGetGeoData($_SERVER['REMOTE_ADDR']);
		$country_info = BotBanishGetCountryData($geo_data);
		$error .= $BotBanishText['offending_country'] . ': ' . $country_info['country'];
	}

	BotBanishLogError($BotBanishText['filenotfound'] , 0, $error, 'Access');
	BotBanishClient();
	
function BotBanish404Error() {

	$penalize_filename = array('login.php', 'admin.php', 'login.aspx', 'login.html', 'ads.txt', 'opencart.com', 'a.php');
	$penalize_dir = array('login', 'admin');

	$filename = basename($_SERVER['REDIRECT_URL']);
	
	// If one of the bad filenames, lock them out now!!!
	
	if (in_array($filename, $penalize_filename))
		BotBanishClient(true, '', '', false, $_SERVER['REMOTE_ADDR'], true);
	
	// No favicon.ico file? We shall place one there so that we do not get errors next time

	if ($filename === 'favicon.ico') {

		$special_error = PHP_EOL . $_SERVER['REDIRECT_URL'];
				
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $_SERVER['REDIRECT_URL'] . '/favicon.ico'))
			copy(BOTBANISH_IMAGES_DIR . 'icons/favicon.ico', $_SERVER['DOCUMENT_ROOT'] . '/' . $_SERVER['REDIRECT_URL']);
	}
}
?>