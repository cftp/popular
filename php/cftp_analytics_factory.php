<?php

/**
 * Class cftp_analytics_factory
 */
class cftp_analytics_factory {
	/**
	 * @param cftp_analytics_model $model
	 */
	public function __construct() {
		//
	}

	public function settingPage( cftp_analytics_model $model ) {
		return new cftp_analytics_settings_page( $model );
	}

	public function cronTask( cftp_analytics_source $source ) {
		return new cftp_analytics_cron_task( $source );
	}

	public function googleAnalyticsSource() {
		return new cftp_google_analytics_source();
	}
}
