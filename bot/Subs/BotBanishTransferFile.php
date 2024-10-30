<?php
 ///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.02
// Randem Systems: https://randemsystems.com/support/
// Transfer Files Server to Server
// Extract the zipped file to a destination
// Date: 05/17/2024
// Usage: BotBanishTransferFile.php
//
//
//	get_headers return array format
//
//		Array (
//			[0] => HTTP/1.1 200 OK
//			[Content-Type] => text/html; charset=UTF-8
//			[Connection] => close
//			[Date] => Sun, 19 May 2019 08:35:47 GMT
//			[Server] => Apache
//			[Strict-Transport-Security] => max-age=3600; includeSubDomains
//			[Cache-Control] => s-maxage=21600, max-age=3, must-revalidate
//			[Access-Control-Allow-Credentials] => true
//			[X-Frame-Options] => DENY
//			[X-Content-Type-Options] => nosniff
//			[Vary] => Accept-Encoding, Cookie
//			[X-Cache] => Miss from cloudfront
//			[Via] => 1.1 95d17b4d563934eb90636ad03f8f524e.cloudfront.net (CloudFront)
//			[X-Amz-Cf-Id] => se3QRyaWDeuHI3GrisMzAr4FJBamqMtbUNzhTPqAJhBoQZbWvy3UPw==
//	)
///////////////////////////////////////////////////////////////////////////////////////////////////////

	ini_set('allow_url_fopen',0);

	$remote_file = defined('ABSPATH') ? 'Docs_WordPress.zip' : '';
	$remote_file = empty($remote_file) && defined('SMF') ? 'Docs_SMF.zip' : $remote_file;
	$remote_file = empty($remote_file) && defined('WEBSITE') ? 'Docs_Website.zip' : $remote_file;
	$remote_file = empty($remote_file) && defined('OPENCART') ? 'Docs_Opencart.zip' : $remote_file;
	$remote_file = empty($remote_file) && defined('SERVER') ? 'Docs_Server.zip' : $remote_file;

	$local_file = BOTBANISH_DOCS_DIR . $remote_file;

	if (isset($_SERVER['SERVER_ADMIN']) && (stripos($_SERVER['SERVER_ADMIN'], 'wampserver') !== false))
		$url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/data/' . $_SERVER['SERVER_NAME'] . '/' . $remote_file;
	else
		$url = 'https://randemsystems.cloud/data/botbanish/' . $remote_file;

	$file_headers = BotBanishGetHeaders($url);

	// Proceed only if the source file is found on remote server

	if (is_array($file_headers) && !empty($file_headers) && stripos($file_headers['Status'], '404') === false)	{

		$filesize = $file_headers['Content-Length'];

		// Find which download functions are available to use

		if (function_exists('curl_init') && function_exists('fopen')) {

			BotBanishDownloadViaCURL($url, $local_file, $remote_file, $filesize);

		} else {

			if (function_exists('ftp_connect')) {

				BotBanishDownloadViaFTP($local_file, $remote_file);

			} else {

				if (function_exists('fopen')) {

					BotBanishDownloadViaHTML($url, $local_file, $remote_file, $filesize);

				} else {

					BotBanishLogError('Cannot download ' . $remote_file . ' - No Function available (cURL, fopen, ftp_connect)');
				}
			}
		}

	} else {

		BotBanishLogError('Cannot download ' . $remote_file . ' - Connection error, No headers available');
	}

function BotBanishDownloadViaFTP($local_file, $remote_file) {

    /* Source File Name and Path */
    /* FTP Account */
    $ftp_host = 'botbanish.com'; /* host */
    $ftp_user_name = 'support@botbanish.com'; /* username */
    $ftp_user_pass = 'I81U812OKrj!'; /* password */

    /* Connect using basic FTP */
    $connect_it = @ftp_connect( $ftp_host );

    /* Login to FTP */
    if (@ftp_login( $connect_it, $ftp_user_name, $ftp_user_pass )) {

		@ftp_chdir($connect_it, 'data/botbanish.com/');

		/* Download $remote_file and save to $local_file */
		@ftp_get( $connect_it, $local_file, $remote_file, FTP_BINARY );

		/* Close the connection */
		@ftp_close( $connect_it );

		BotBanishExtract($local_file, BOTBANISH_DOCS_DIR);
		unlink($local_file);
	}
}

function BotBanishDownloadViaHTML ($url, $local_file, $remote_file, $filesize) {

	BotBanishCleanOutput();
	ob_start();

	header('Pragma: ');
	header('Content-Encoding: none');//	header('Content-type: text/html; charset=ISO-8859-1');
	header('Content-type: application/octet-stream; charset=ISO-8859-1');
	header("Content-Transfer-Encoding: Binary");
	header('Content-Length: '. $filesize);
	header('Cache-Control: no-cache');
	header("Location: " . $url);

	if ( $fp = fopen($url, 'rb') ) {

		ob_end_clean();

		//File to save the contents to
		$fp_out = fopen ($local_file, 'w+');

		while( !feof($fp) and (connection_status()==0) ) {
			fwrite($fp_out, fread($fp, 8192));
			flush();
		}

		@fclose($fp);
		@fclose($fp_out);

		BotBanishExtract($local_file, BOTBANISH_DOCS_DIR);
		unlink($local_file);
	}
}

function BotBanishDownloadViaCURL ($url, $local_file, $remote_file, $filesize) {

	set_time_limit(0);

	//File to save the contents to
	$fp = fopen ($local_file, 'w+');

	//Here is the file we are downloading, replace spaces with %20
	$ch = curl_init(str_replace(" ","%20",$url));

	curl_setopt($ch, CURLOPT_TIMEOUT, 50);

	//give curl the file pointer so that it can write to it
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$data = curl_exec($ch);//get curl response

	//done
	curl_close($ch);

	clearstatcache();	// Needs this to get proper filesize after a recent write operation

	if (filesize($local_file) > 0)
		BotBanishExtract($local_file, BOTBANISH_DOCS_DIR);

	unlink($local_file);
}

function BotBanishExtract($filename, $destination) {

	$zip = new Zip;
	$zip->unzip_file($filename, $destination);
}
?>