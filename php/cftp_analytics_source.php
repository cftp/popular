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

	const column_html_pending   = '<span title="pending" class="dashicons dashicons-clock"></span>';
	const column_html_na        = 'n/a';

	public function sourceName();

	/**
	 * Register settings for the WordPress options page
	 *
	 * @return null
	 */
	public function registerSettings( $option_group, $section_id, $page );

	/**
	 * @return mixed
	 */
	public function displaySettings();

	public function isConfigured();

	public function getPageViewsForURL( $url );

	public function getPageViewsByPostID( $post_id );
}
