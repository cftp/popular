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
	 * @return null
	 */
	public function registerSettings()
	{
		// TODO: Implement registerSettings() method.
	}

	/**
	 *
	 */
	public function displaySettings()
	{
		// TODO: Implement displaySettings() method.
	}
} 