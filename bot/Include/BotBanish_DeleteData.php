<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 4.0.04
// Randem Systems: https://randemsystems.com/support/
// BotBanish Client Uninstall For Websites
// Date: 08/23/2022
//
// Function:
//		Uninstalling BotBanish For Websites
///////////////////////////////////////////////////////////////////////////////////////////////////////

	global $BotBanishText;
	
	echo '
		<!DOCTYPE html>
		<html>
		<head>
		<title>' . $BotBanishText["BotBanish_Configuration"] . '</title>' .
BOTBANISH_PAGE_HEADER .
'
		<style>
			body
			{
			  font-family: "Open Sans";
			}
			#wrapper
			{
			  border: 1px solid #888;
			  display: inline-block;
			  padding:20px;
			  
			}
			#theForm {
				left      : 50%;
				top       : 50%;
				position  : absolute;
				transform : translate(-50%, -50%);
			}
		</style>
		</head>';
		
			$heading = BOTBANISH_SERVER_TYPE . ' ' . $BotBanishText['Configuration'];
			$title = $BotBanishText[BOTBANISH_INSTALL_TYPE . '_Uninstall'];
			include BOTBANISH_TEMPLATES_DIR . 'BotBanish_Banner.php';
			
		echo '
		<body>
		
		<form name="theForm" id="theForm" action="" method="post" enctype="multipart/form-data">


			<div id="wrapper">
			  <label for="yes_no_radio">' . $BotBanishText["BOTBANISH_DELETE_DATA"] . '</label>
			<p>
			<input type="radio" name="yes_no" value="yes">' . $BotBanishText["yes"] . '</input>
			</p>
			<p>
			<input type="radio" name="yes_no" value="no">' . $BotBanishText["no"] . '</input>
			</p>
			<p><input type="submit" value="Submit"></p>
			</div>

		</form>

		</body>
		</html>';
?>