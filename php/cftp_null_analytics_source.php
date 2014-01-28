<?php

/**
 * Class cftp_null_analytics_source
 */
class cftp_null_analytics_source implements cftp_analytics_source {

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
		return 'nullsource';
	}

	/**
	 * Register settings for the WordPress options page
	 *
	 * @return null
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		// TODO: Implement registerSettings() method.
	}

	/**
	 * @return bool
	 */
	public function isConfigured() {
		return false;
	}

	/**
	 *
	 */
	public function displaySettings() {
		// TODO: Implement displaySettings() method.
	}
} 