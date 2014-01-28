<?php

/**
 * Interface cftp_analytics_source
 */
interface cftp_analytics_source {
	/**
	 * The identifier for this source
	 *
	 * @return mixed
	 */
	public function sourceName();

	/**
	 * Register settings for the WordPress options page
	 *
	 * @return null
	 */
	public function registerSettings();

	/**
	 * @return mixed
	 */
	public function displaySettings();
}
