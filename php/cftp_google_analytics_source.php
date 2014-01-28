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
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		// TODO: Implement registerSettings() method.
		$option_name = 'cftp_popular_google_analytics';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		add_settings_field(
			$option_name, // ID
			'Google Analytics', // Title
			array( $this, 'authUI' ), // Callback
			$page, // Page
			$section_id // Section
		);
	}

	/**
	 *
	 */
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
