<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////
// BotBanish 5.1.00
// Randem Systems: https://randemsystems.com/support/
// BotBanish WordPress Options
// Date: 04/25/2024
//
// Function: Show BotBanish Option and Analytics
///////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 */

	// 11/15/2022 - Added delete_data option
	// 06/25/2023 - Added xmlrpc Protection Setting
	// 07/05/2023 - Added wp-config and .htaccess Protection Settings
	// 04/25/2024 - Added Black / White Lists

class BotBanishFirewallClientOptions {

	private $botbanishfirewall_client_options;

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'botbanishfirewall_client_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'botbanishfirewall_client_page_init' ) );
		add_action( 'update_option', array( $this, 'BotBanishUpdateSettings'), 10, 3 );
	}

	public function botbanishfirewall_client_add_plugin_page() {

		add_menu_page(
			'BotBanish Firewall Client', // page_title
			'BotBanish Firewall Client', // menu_title
			'manage_options', // capability
			'botbanishfirewall-client', // menu_slug
			array( $this, 'botbanishfirewall_client_create_admin_page' ), // function
			'dashicons-admin-generic', // icon_url
			21 // position
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'BotBanish Settings', // page_title
			'Settings', // menu_title
			'manage_options', // capability
			'botbanishfirewall-client' // menu_slug
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'BotBanish Black / White Lists', // page_title
			'Black / White Lists', // menu_title
			'manage_options', // capability
			'botbanishfirewall-blackwhite', // menu_slug
			array( $this, 'botbanishfirewall_client_create_blackwhitelist_page' ) // function
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'BotBanish Analytics', // page_title
			'Analytics', // menu_title
			'manage_options', // capability
			'botbanishfirewall-analytics', // menu_slug
			array( $this, 'botbanishfirewall_client_create_analytics_page' ) // function
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'BotBanish View Logs', // page_title
			'View Logs', // menu_title
			'manage_options', // capability
			'botbanishfirewall-viewlogs', // menu_slug
			array( $this, 'botbanishfirewall_client_create_logs_page' ) // function
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'BotBanish Help', // page_title
			'Help', // menu_title
			'manage_options', // capability
			'botbanishfirewall-help', // menu_slug
			array( $this, 'botbanishfirewall_client_create_help_page' ) // function
		);

		add_submenu_page(
			'botbanishfirewall-client', // parent_slug
			'Download BotBanish Tables', // page_title
			'Download Tables', // menu_title
			'manage_options', // capability
			'botbanishfirewall-download', // menu_slug
			array( $this, 'botbanishfirewall_client_create_download_page' ) // function
		);
	}

	public function BotBanishUpdateSettings($option, $old_values, $new_values) {

		 // WordPress settings do not coorelate with BotBanish setting so we need to translate the WordPress settings
		 // to the BotBanish settings table. All WordPress settings for BotBanish MUST be handled here so that they
		 // will be available to BotBanish for processing.

		global $BotBanishSettings;

		if (stripos($option, 'botbanishfirewall_client_option_name') !== false) {

			$dataArray = array();
			$save = false;

			// .htaccess rule modifications

			$names = array('_botbanishtext_botbanish_xmlrpc', '_botbanishtext_botbanish_wpconfig', '_botbanishtext_botbanish_htaccess');
			$types = array('BOTBANISH_XMLRPC_DATA', 'BOTBANISH_WPCONFIG_DATA', 'BOTBANISH_HTACCESS_DATA');

			foreach ($names as $key => $name) {

				if (!in_array($name, $new_values)
					&& (in_array($name, $old_values))) {

					BotBanishHTACCESSMaintenance('clean', $types[$key]);
					$dataArray[$types[$key]] = false;
					$save = true;

				} else {

					if (!in_array($name, $old_values)
						&& (in_array($name, $new_values))) {

						BotBanishHTACCESSMaintenance('add', $types[$key]);
						$dataArray[$types[$key]] = true;
						$save = true;
					}
				}
			}

			// True / False Settings

			$names = array('_botbanishtext_botbanish_active',
							'_botbanishtext_botbanish_delete_data',
							'_botbanishtext_botbanish_send_email_alerts',
							'_botbanishtext_botbanish_doc_errors_monitor',
							'_botbanishtext_botbanish_analytics_website',
							'_botbanishtext_botbanish_analytics_downloads',
							'_botbanishtext_botbanish_check_updates');

			$types = array('BOTBANISH_ACTIVE',
							'BOTBANISH_DELETE_DATA',
							'BOTBANISH_SEND_EMAIL_ALERTS',
							'BOTBANISH_DOC_ERRORS_MONITOR',
							'BOTBANISH_ANALYTICS_WEBSITE',
							'BOTBANISH_ANALYTICS_DOWNLOADS',
							'BOTBANISH_CHECK_UPDATES');

			foreach ($names as $key => $name) {

				if (!in_array($name, $new_values)
					&& (in_array($name, $old_values))) {

					$dataArray[$types[$key]] = false;

					if (defined('ABSPATH'))
						update_option($name, false);

					$save = true;

				} else {

					if (!in_array($name, $old_values)
						&& (in_array($name, $new_values))) {

						$dataArray[$types[$key]] = true;
						$save = true;

						if (defined('ABSPATH'))
							update_option($name, true);
					}
				}
			}

			// Value settings

			$names = array('_botbanishtext_botbanish_apikey', '_botbanishtext_botbanish_language_select','_botbanishtext_botbanish_max_hit_count', '_botbanishtext_botbanish_max_ip_range_count');
			$types = array('BOTBANISH_APIKEY', 'BOTBANISH_LANGUAGE_SELECT', 'BOTBANISH_MAX_HIT_COUNT', 'BOTBANISH_MAX_IP_RANGE');

			foreach ($names as $key => $name) {

				// Old and new value MUST exist at the same time.

				if (!isset($old_values[$name]) || !isset($new_values[$name]))
					continue;

				// If old value is not the same as the new value

				if ($old_values[$name] != $new_values[$name]) {

					$dataArray[$types[$key]] = $new_values[$name];
					$save = true;
				}
			}

			if ($save)
				BotBanishSettingsTablePutValues($dataArray);
		}
	}

	public function botbanishfirewall_client_create_admin_page() {

		$this->botbanishfirewall_client_options = get_option( 'botbanishfirewall_client_option_name' ); ?>

		<div class="wrap">
			<h2>BotBanish Firewall Client</h2>
			<p></p>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'botbanishfirewall_client_option_group' );
					do_settings_sections( 'botbanishfirewall-client-admin' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function botbanishfirewall_client_create_blackwhitelist_page() {

		?>
		<div class="wrap">
			<h2>BotBanish Black / White List</h2>
			<p></p>

			<form>
				<?php

					$dir = getcwd();
					chdir(BOTBANISH_PLUGIN_DIR . 'bot/');
					require BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_BlackWhiteScreen.php';
//					$filename = BOTBANISH_INSTALL_URL . 'bot/Docs/BotBanish_BlackWhiteScreen.html';
//					echo '<iframe src="' . $filename . '" width="100%" height="1000">';
					if (is_dir($dir)) chdir($dir);

				?>
			</form>
		</div>
		<?php
	}

	public function botbanishfirewall_client_create_analytics_page() {

		?>
		<div class="wrap">
			<h2>BotBanish Analytics</h2>
			<p></p>

			<form>
				<?php

					$dir = getcwd();
					chdir(BOTBANISH_PLUGIN_DIR . 'bot/');
					require BOTBANISH_PLUGIN_DIR . 'bot/Analytics/BotBanishAnalytics.php';
					if (is_dir($dir)) chdir($dir);

				?>
			</form>
		</div>
		<?php
	}

	public function botbanishfirewall_client_create_logs_page() {

		?>
		<div class="wrap">
			<h2>BotBanish Analytics</h2>
			<p></p>

			<form>
				<?php

					$dir = getcwd();
					chdir(BOTBANISH_PLUGIN_DIR . 'bot/');
					require BOTBANISH_PLUGIN_DIR . 'bot/Analytics/BotBanishViewLogs.php';
					if (is_dir($dir)) chdir($dir);

				?>
			</form>
		</div>
		<?php
	}

	public function botbanishfirewall_client_create_help_page() {

		?>
		<div class="wrap">
			<center>
			<h2>BotBanish Help Documentation</h2>
			</center>
			<p></p>

			<form>
				<center>
				<?php
				$filename = BOTBANISH_INSTALL_URL . 'bot/Docs/BotBanish Functions - ' . ucwords(BOTBANISH_LANGUAGE_SELECT) . '.pdf';
				echo '<iframe src="' . $filename . '" width="100%" height="1000">';
				?>
				</center>

			</form>
		</div>
		<?php
	}

	public function botbanishfirewall_client_create_download_page() {

		?>
		<div class="wrap">
			<center>
			<h2>Download BotBanish Tables</h2>
			</center>
			<p></p>

			<form>
				<?php

					$info = array(
						'prefix' => 'botbanish',
						'data' => true,
						'structure' => true,
						'indexes' => true,
						'compress' => true,
						'type' => '',
						'location' => BOTBANISH_BACKUPS_DIR . 'SQL/',
					);

					require_once BOTBANISH_PLUGIN_DIR . 'bot/Subs/BotBanish_DumpDatabase.php';
					$download_link = BotBanishDumpTables($info);
					$download_loc = $_SERVER['DOCUMENT_ROOT'] . '/data/' . BOTBANISH_USER_HOST . '/' . basename($download_link['file']);
					copy($download_link['file'], $download_loc);
					$download_file = str_ireplace($_SERVER['DOCUMENT_ROOT'], $_SERVER["REQUEST_SCHEME"] . '://' . BOTBANISH_USER_HOST, $download_loc);
				?>
			</form>
				<?php
				echo "
				<script>
					window.open('" . $download_file . "');
				</script>";
				?>
		</div>
		<?php
	}

	public function botbanishfirewall_client_page_init() {

		global $BotBanishText;

		register_setting(
			'botbanishfirewall_client_option_group', // option_group
			'botbanishfirewall_client_option_name', // option_name
			array( $this, 'botbanishfirewall_client_sanitize' ) // sanitize_callback
		);

		add_settings_field(
			'_botbanishtext_botbanish_active', // id
			$BotBanishText['BOTBANISH_ACTIVE'], // title
			array( $this, '_botbanishtext_botbanish_active_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_section(
			'botbanishfirewall_client_setting_section', // id
			'Configuration Settings', // title
			array( $this, 'botbanishfirewall_client_section_info' ), // callback
			'botbanishfirewall-client-admin' // page
		);

		add_settings_field(
			'_botbanishtext_botbanish_apikey', // id
			$BotBanishText['BOTBANISH_APIKEY'], // title
			array( $this, '_botbanishtext_botbanish_apikey_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_language_select', // id
			$BotBanishText['BOTBANISH_LANGUAGE_SELECT'], // title
			array( $this, '_botbanishtext_botbanish_language_select_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_delete_data', // id
			$BotBanishText['BOTBANISH_DELETE_DATA'], // title
			array( $this, '_botbanishtext_botbanish_delete_data_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_send_email_alerts', // id
			$BotBanishText['BOTBANISH_SEND_EMAIL_ALERTS'], // title
			array( $this, '_botbanishtext_botbanish_send_email_alerts_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_doc_errors_monitor', // id
			$BotBanishText['BOTBANISH_DOC_ERRORS_MONITOR'], // title
			array( $this, '_botbanishtext_botbanish_doc_errors_monitor_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_analytics_website', // id
			$BotBanishText['BOTBANISH_ANALYTICS_WEBSITE'], // title
			array( $this, '_botbanishtext_botbanish_analytics_website_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_analytics_downloads', // id
			$BotBanishText['BOTBANISH_ANALYTICS_DOWNLOADS'], // title
			array( $this, '_botbanishtext_botbanish_analytics_downloads_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_check_updates', // id
			$BotBanishText['BOTBANISH_CHECK_UPDATES'], // title
			array( $this, '_botbanishtext_botbanish_check_updates_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_xmlrpc', // id
			$BotBanishText['BOTBANISH_XMLRPC_ACTIVE'], // title
			array( $this, '_botbanishtext_botbanish_xmlrpc_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_wpconfig', // id
			$BotBanishText['BOTBANISH_WPCONFIG_ACTIVE'], // title
			array( $this, '_botbanishtext_botbanish_wpconfig_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_htaccess', // id
			$BotBanishText['BOTBANISH_HTACCESS_ACTIVE'], // title
			array( $this, '_botbanishtext_botbanish_htaccess_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_max_hit_count', // id
			$BotBanishText['BOTBANISH_MAX_HIT_COUNT'], // title
			array( $this, '_botbanishtext_botbanish_max_hit_count_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);

		add_settings_field(
			'_botbanishtext_botbanish_max_ip_range_count', // id
			$BotBanishText['BOTBANISH_MAX_IP_RANGE_COUNT'], // title
			array( $this, '_botbanishtext_botbanish_max_ip_range_count_callback' ), // callback
			'botbanishfirewall-client-admin', // page
			'botbanishfirewall_client_setting_section' // section
		);
	}

	public function botbanishfirewall_client_sanitize($input) {

		$sanitary_values = array();

		if ( isset( $input['_botbanishtext_botbanish_active'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_active'] = $input['_botbanishtext_botbanish_active'];
		}

		if ( isset( $input['_botbanishtext_botbanish_apikey'] ) ) {
			$value = sanitize_text_field( $input['_botbanishtext_botbanish_apikey'] );

			if (empty($value))
				$value = BOTBANISH_APIKEY;

			$sanitary_values['_botbanishtext_botbanish_apikey'] = $value;
		}

		if ( isset( $input['_botbanishtext_botbanish_language_select'] ) ) {
			$value = sanitize_text_field( $input['_botbanishtext_botbanish_language_select'] );

			if (empty($value))
				$value = BOTBANISH_LANGUAGE_SELECT;

			$sanitary_values['_botbanishtext_botbanish_language_select'] = $value;
		}

		if ( isset( $input['_botbanishtext_botbanish_delete_data'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_delete_data'] = $input['_botbanishtext_botbanish_delete_data'];
		}

		if ( isset( $input['_botbanishtext_botbanish_send_email_alerts'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_send_email_alerts'] = $input['_botbanishtext_botbanish_send_email_alerts'];
		}

		if ( isset( $input['_botbanishtext_botbanish_doc_errors_monitor'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_doc_errors_monitor'] = $input['_botbanishtext_botbanish_doc_errors_monitor'];
		}

		if ( isset( $input['_botbanishtext_botbanish_analytics_website'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_analytics_website'] = $input['_botbanishtext_botbanish_analytics_website'];
		}

		if ( isset( $input['_botbanishtext_botbanish_analytics_downloads'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_analytics_downloads'] = $input['_botbanishtext_botbanish_analytics_downloads'];
		}

		if ( isset( $input['_botbanishtext_botbanish_check_updates'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_check_updates'] = $input['_botbanishtext_botbanish_check_updates'];
		}

		if ( isset( $input['_botbanishtext_botbanish_xmlrpc'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_xmlrpc'] = $input['_botbanishtext_botbanish_xmlrpc'];
		}

		if ( isset( $input['_botbanishtext_botbanish_wpconfig'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_wpconfig'] = $input['_botbanishtext_botbanish_wpconfig'];
		}

		if ( isset( $input['_botbanishtext_botbanish_htaccess'] ) ) {
			$sanitary_values['_botbanishtext_botbanish_htaccess'] = $input['_botbanishtext_botbanish_htaccess'];
		}

		if ( isset( $input['_botbanishtext_botbanish_max_hit_count'] ) ) {
			$value = intval(sanitize_text_field($input['_botbanishtext_botbanish_max_hit_count']));

			if (($value < 1) || ($value > 30))
				$value = BOTBANISH_MAX_HIT_COUNT;

			$sanitary_values['_botbanishtext_botbanish_max_hit_count'] = $value;
		}

		if ( isset( $input['_botbanishtext_botbanish_max_ip_range_count'] ) ) {
			$value = intval(sanitize_text_field($input['_botbanishtext_botbanish_max_ip_range_count']));

			if (($value < 1) || ($value > 254))
				$value = BOTBANISH_MAX_IP_RANGE_COUNT;
			$sanitary_values['_botbanishtext_botbanish_max_ip_range_count'] = $value;
		}

		return $sanitary_values;
	}

	public function botbanishfirewall_client_section_info() {

	}

	public function _botbanishtext_botbanish_active_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_active]" id="_botbanishtext_botbanish_active" value="_botbanishtext_botbanish_active" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_active'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_active'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_apikey_callback() {

		printf(
			'<input class="regular-text" type="text" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_apikey]" id="_botbanishtext_botbanish_apikey" value="%s" size="30">',
			isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_apikey'] ) ? esc_attr( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_apikey']) : ''
		);
	}

	public function _botbanishtext_botbanish_language_select_callback() {

		echo '<select name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_language_select]" id="botbanishfirewall_client_option_name[_botbanishtext_botbanish_language_select">';

			$languages = BOTBANISH_LANGUAGES;
			$selected = isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_language_select'] ) ? esc_attr( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_language_select']) : '';

			foreach ($languages as $language)
				printf('<option value="%1$s" %2$s>%1$s</option>', $language, selected($selected, $language, false));

         echo '</select>';
	}

	public function _botbanishtext_botbanish_delete_data_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_delete_data]" id="_botbanishtext_botbanish_delete_data" value="_botbanishtext_botbanish_delete_data" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_delete_data'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_delete_data'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_send_email_alerts_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_send_email_alerts]" id="_botbanishtext_botbanish_send_email_alerts" value="_botbanishtext_botbanish_send_email_alerts" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_send_email_alerts'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_send_email_alerts'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_doc_errors_monitor_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_doc_errors_monitor]" id="_botbanishtext_botbanish_doc_errors_monitor" value="_botbanishtext_botbanish_doc_errors_monitor" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_doc_errors_monitor'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_doc_errors_monitor'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_analytics_website_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_analytics_website]" id="_botbanishtext_botbanish_analytics_website" value="_botbanishtext_botbanish_analytics_website" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_analytics_website'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_analytics_website'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_analytics_downloads_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_analytics_downloads]" id="_botbanishtext_botbanish_analytics_website" value="_botbanishtext_botbanish_analytics_downloads" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_analytics_downloads'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_analytics_downloads'] == true ) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_check_updates_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_check_updates]" id="_botbanishtext_botbanish_check_updates" value="_botbanishtext_botbanish_check_updates" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_check_updates'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_check_updates'] == true) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_xmlrpc_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_xmlrpc]" id="_botbanishtext_botbanish_xmlrpc" value="_botbanishtext_botbanish_xmlrpc" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_xmlrpc'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_xmlrpc'] == true) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_wpconfig_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_wpconfig]" id="_botbanishtext_botbanish_wpconfig" value="_botbanishtext_botbanish_wpconfig" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_wpconfig'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_wpconfig'] == true) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_htaccess_callback() {

		printf(
			'<input type="checkbox" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_htaccess]" id="_botbanishtext_botbanish_htaccess" value="_botbanishtext_botbanish_htaccess" %s>',
			( isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_htaccess'] ) && $this->botbanishfirewall_client_options['_botbanishtext_botbanish_htaccess'] == true) ? 'checked' : ''
		);
	}

	public function _botbanishtext_botbanish_max_hit_count_callback() {

		printf(
			'<input type="text" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_max_hit_count]" id="_botbanishtext_botbanish_max_hit_count" size="4" value="%s">',
			isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_max_hit_count'] ) ? esc_attr( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_max_hit_count']) : BOTBANISH_MAX_HIT_COUNT
		);
	}

	public function _botbanishtext_botbanish_max_ip_range_count_callback() {

		printf(
			'<input type="text" name="botbanishfirewall_client_option_name[_botbanishtext_botbanish_max_ip_range_count]" id="_botbanishtext_botbanish_max_ip_range_count" size="4" value="%s">',
			isset( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_max_ip_range_count'] ) ? esc_attr( $this->botbanishfirewall_client_options['_botbanishtext_botbanish_max_ip_range_count']) : BOTBANISH_MAX_IP_RANGE_COUNT
		);
	}
}

 if ( is_admin() )
	$botbanishfirewall_client = new BotBanishFirewallClientOptions();
?>