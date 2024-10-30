<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish Install Complete Screen
// Date: 04/30/2024
// Usage: 
//
///////////////////////////////////////////////////////////////////////////////////////////////////////

function CompleteScreen($doc, $type) {
	global $BotBanishText;

	echo '
		<!DOCTYPE html>
		<html>
		<head>
		<title>' . $BotBanishText["BotBanish_Configuration"] . '</title>' .
BOTBANISH_PAGE_HEADER .
'
	</head>
	<body>
	<table width="60%" border="0" cellspacing="0" cellpadding="0" align="center">';

			$heading = BOTBANISH_SERVER_TYPE . ' ' . $BotBanishText['Configuration'];
			$title = $type;
			include BOTBANISH_TEMPLATES_DIR . 'BotBanish_Banner.php';


			$url = BOTBANISH_INSTALL_URL . '/Docs/BotBanish_Readme_' . BOTBANISH_SYSTEM . ' - ' . ucwords(BOTBANISH_LANGUAGE_SELECT) . '.html';
	
	echo '
		<tr width="100%">
			<div>
				<table width="60%" cellspacing="0" cellpadding="0" align="center" style="background-color: #0099cc;" border="10" style="border-collapse:collapse">
					<tr width="100%">
						<td width="100%" class="face_padding_cell">
							<form name="theForm" id="theForm" action="" method="post" enctype="multipart/form-data">
								<table width="90%" style="background-color: #0099cc;" align="center">
									<tr align="left">
										<td>' . $doc . '</td>
									</tr>
									<tr><td>
									<div id="readme">
										<iframe id="ReadMe" align="center" width="90%" height="1000" style="background-color: #ffffff;" style="border-collapse:collapse" border=10 scrolling=auto allowtransparency="true" src="' . $url . '"></iframe>
									</div>
									</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
				</table>
			</div>
		</tr>
	</table>
	</body>
	</html>';
}
?>