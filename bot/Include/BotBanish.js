

<script>

	function Redirect($url) {

		location.href($url);
	}

	function pad2(number) {
		var i = parseInt(number);
		return (i.length < 2 ? '0' : '') + number;
	}

	function onSubmit(formId) {

		document.getElementById(formId.name).submit();
	}

	function onSubmit_delete(formId, elementId) {

		elementId.value = 'delete';
		document.getElementById(formId.name).submit();
	}
	
	function onSubmit1(formId, elementId) {

		elementId.value = 1;
		document.getElementById(formId.name).submit();
	}

	function onSubmit2(formId, elementId) {

		elementId.value = 2;
		document.getElementById(formId.name).submit();
	}

	function onSubmitIt(formId, elementId, value) {

		elementId.value = value;
		document.getElementById(formId.name).submit();
	}

	function validate_Connection(formId, elementId) {
		elementId.value = 3;
		document.getElementById(formId.name).submit();
	}

	function validateForm_BotBanishInstall(formId, elementId) {

		var valid = true;
		var str = '';

		if (document.getElementById('BOTBANISH_LANGUAGE_SELECT').value === '') {
			str = str.concat("BOTBANISH_LANGUAGE_SELECT\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_WEBMASTER_EMAIL').value === '') {
			str = str.concat("BOTBANISH_WEBMASTER_EMAIL\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_DB_NAME').value === '') {
			str = str.concat("BOTBANISH_DB_NAME\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_DB_SERVERNAME').value === '') {
			str = str.concat("BOTBANISH_DB_SERVERNAME\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_DB_USERNAME').value === '') {
			str = str.concat("BOTBANISH_DB_USERNAME\n");
			valid = false;
		}

		if (valid) {

			onSubmit2(formId, elementId);

		} else {

			str = str.concat("Fields cannot be blank. Please check form for valid entries");
			alert(str);
		}

		return valid;
	}

	function validateForm_BotBanishSettingsClient(formId, elementId) {

		var valid = true;
		var str = '';

		if (document.getElementById('BOTBANISH_LANGUAGE_SELECT').value === '') {
			str = str.concat("BOTBANISH_LANGUAGE_SELECT\n");
		}

		if (document.getElementById('BOTBANISH_UPDATE_HTML_FOLDER').value === '') {
			str = str.concat("BOTBANISH_UPDATE_HTML_FOLDER\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_APIKEY').value === '') {
			str = str.concat("BOTBANISH_APIKEY\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_MAX_HIT_COUNT').value === '') {
			str = str.concat("BOTBANISH_MAX_HIT_COUNT\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_MAX_IP_RANGE_COUNT').value === '') {
			str = str.concat("BOTBANISH_MAX_IP_RANGE_COUNT\n");
			valid = false;
		}

		if (valid) {

			onSubmit2(formId, elementId);

		} else {

			str = str.concat("Fields cannot be blank. Please check form for valid entries");
			alert(str);
		}

		return valid;
	}

	function validateForm_BotBanishSettingsServer(formId, elementId) {

		var valid = true;
		var str = '';

		if (document.getElementById('BOTBANISH_LANGUAGE_SELECT').value === '') {
			str = str.concat("BOTBANISH_LANGUAGE_SELECT\n");
		}

		if (document.getElementById('BOTBANISH_APIKEY').value === '') {
			str = str.concat("BOTBANISH_APIKEY\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_MAX_HIT_COUNT').value === '') {
			str = str.concat("BOTBANISH_MAX_HIT_COUNT\n");
			valid = false;
		}

		if (document.getElementById('BOTBANISH_MAX_IP_RANGE_COUNT').value === '') {
			str = str.concat("BOTBANISH_MAX_IP_RANGE_COUNT\n");
			document.getElementById("BOTBANISH_MAX_IP_RANGE_COUNT").style.color = "red";
			valid = false;
		}

		if (valid) {

			onSubmit2(formId, elementId);

		} else {

			str = str.concat("Fields cannot be blank. Please check form for valid entries");
			alert(str);
		}

		return valid;
	}

	function validateForm_BotBanishBlackWhiteUpdate(formId, elementId) {

		var valid = true;
		var str = '';

		if (document.getElementById(elementId.name).value == 'add') {
			
			valid = validateForm_BotBanishBlackWhiteAdd(formId, elementId);
			return valid;
		}
		
		if (valid) {

			elementId.value = 'update';	
			document.getElementById(formId.name).submit();

		} else {

			str = str.concat("Nothing Selected");
			alert(str);
		}

		return valid;
	}

	function validateForm_BotBanishBlackWhiteSearch(formId, elementId) {

		var valid = false;
		var str = '';

		if ((document.getElementById("search_id").value !== '')
			|| (document.getElementById("search_name").value !== '')
			|| (document.getElementById("search_uap").value !== '')) {

			valid = true;
		}

		if (valid) {

			elementId.value = "search";
			onSubmit(formId);

		} else {

			BotBanishError("search_id");
			BotBanishError("search_name");
			BotBanishError("search_uap");
		
			str = str.concat("Nothing Entered or More than one Entered (Only one can be searched)");
			alert(str);
		}

		return valid;
	}

	function validateForm_BotBanishBlackWhiteAdd(formId, elementId) {

		var valid = true;
		var str = '';

		if (valid) {

			elementId.value = "add";
			onSubmit(formId);

		} else {

			str = str.concat("Nothing Entered)");
			alert(str);
		}

		return valid;
	}

	function BotBanishError(name) {

		document.getElementById(name).style.border = "solid";
		document.getElementById(name).style.borderColor = "#e52213";		
	}
	
	function logout() {
		
		var x = location.href;
		var index = x.split('/');
		index.pop();
		str = index.join('/')+'/logout.php';
		location.href = str;
	}

	function saveIt(elementId){

		var a = document.body.appendChild(
			document.createElement("a")
		);

		a.download = "export.html";
		a.href = "data:text/html;charset=UTF-8," + elementId.outerHTML;
		a.click();
		postFilename(a.download);
	}

	function postFilename(filename) {

		var file = '<input typ="hidden" name="file" value="'+filename+'">';
		/* Get from elements values */
		var values = $(file).serialize();
		var referer = document.getElementById('referer').value;

		$.ajax({
			url: referer,
			type: "post",
			data: values,
			success: function (response) {

			   // You will get response from your PHP page (what you echo or print)
			},
			error: function(jqXHR, textStatus, errorThrown) {
			   console.log(textStatus, errorThrown);
			}
		});
	}

	function postIt(elementId) {

		/* Get from elements values */
		var values = $(elementId).serialize();
		var referer = document.getElementById('referer').value;

		$.ajax({
			url: referer,
			type: "post",
			data: values ,
			success: function (response) {

			   // You will get response from your PHP page (what you echo or print)
			},
			error: function(jqXHR, textStatus, errorThrown) {
			   console.log(textStatus, errorThrown);
			}
		});
	}

	function uploadFile() {

		let imageUpload = document.getElementById('uploadfile');
		let uploadMsg = document.getElementById('logo');

		// display file name if file has been selected

		let text = '';

		//process input

		let str = imageUpload.value;
		let index = str.split('\\');
		text = index[index.length - 1];
		uploadMsg.value = text;
	}

	function closeWindow() {

		window.close();
	}

	function readMe(url) {

		window.open(url, '_blank').focus();
	}

	function windowpopup(url, width, height) {
		var leftPosition, topPosition;
		//Allow for borders.
		leftPosition = (window.screen.width / 2) - ((width / 2) + 10);
		//Allow for title and status bars.
		topPosition = (window.screen.height / 2) - ((height / 2) + 50);
		//Open the window.
		window.open(url, "Window2", "status=no,height=" + height + ",width=" + width + ",resizable=yes,left=" + leftPosition + ",top=" + topPosition + ",screenX=" + leftPosition + ",screenY=" + topPosition + ",toolbar=no,menubar=no,scrollbars=no,location=no,directories=no");
	}
</script>
