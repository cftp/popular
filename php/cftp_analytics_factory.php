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

	public function twitterSharesSource() {
		return new cftp_twitter_source();
	}

	public function facebookLikesSource() {
		return new cftp_facebook_likes_source();
	}

	public function facebookSharesSource() {
		return new cftp_facebook_shares_source();
	}

	public function linkedinSource() {
		return new cftp_linkedin_source()();
	}

	public function totalSharesSource() {
		return new cftp_total_shares_source();
	}

	public function decayViewsSource() {
		return new cftp_decay_views_source();
	}

	public function decaySharesSource() {
		return new cftp_decay_shares_source();
	}

}
