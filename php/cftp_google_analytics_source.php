<?php

/**
 * Class cftp_google_analytics_source
 */
class cftp_google_analytics_source implements cftp_analytics_source {

	/**
	 *
	 */
	public function __construct() {
		//
	}

	/**
	 * @return string
	 */
	public function sourceName() {
		return 'googleanalytics';
	}

	/**
	 *
	 * @return null
	 */
	public function registerSettings() {
		// TODO: Implement registerSettings() method.
		register_setting(
			'cftp_popular_setting_auth_section', // Option group
			'cftp-popular-google-analytics' // Option name
		);

		add_settings_field(
			'cftp-popular-google-analytics', // ID
			'Google Analytics', // Title
			array( $this, 'authUI' ), // Callback
			'cftp_popular_settings_page', // Page
			'cftp_popular_setting_auth_section' // Section
		);
	}

	public function authUI() {
		echo 'moomins';
	}

	/**
	 *
	 */
	public function displaySettings() {
		// TODO: Implement displaySettings() method.
	}
}
