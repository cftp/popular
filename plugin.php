<?php
/*
 * Plugin Name: CFTP Popular
 * Plugin URI: http://disqus.com/
 * Description: Queries Analytic services for web traffic data
 * Author: Tom J Nowell, Code For The People
 * Version: 1.0
 * Author URI: http://codeforthepeople.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Command line support for development
	include __DIR__ . '/wp-cli.php';
}

require_once( 'php/cftp_analytics_factory.php' );
require_once( 'php/cftp_analytics_model.php' );
require_once( 'php/cftp_analytics_option_model.php' );
require_once( 'php/cftp_analytics.php' );
require_once( 'php/cftp_analytics_cron_task.php' );
require_once( 'php/cftp_analytics_settings_page.php' );
require_once( 'php/cftp_analytics_source.php' );
require_once( 'php/cftp_google_analytics_auth.php' );
require_once( 'php/cftp_google_analytics_source.php' );
require_once( 'php/cftp_facebook_likes_source.php' );
require_once( 'php/cftp_facebook_shares_source.php' );
require_once( 'php/cftp_twitter_source.php' );
require_once( 'php/cftp_linkedin_source.php' );
require_once( 'php/cftp_total_shares_source.php' );
require_once( 'php/cftp_decay_views_source.php' );
require_once( 'php/cftp_decay_shares_source.php' );


$factory = new cftp_analytics_factory();
$model   = new cftp_analytics_option_model();

// Sources - order determines column order on Posts page.

$analytics = $factory->googleAnalyticsSource();
$model->addSource( $analytics );

$twitter = $factory->twitterSharesSource();
$model->addSource( $twitter );

$linkedin = $factory->linkedinSource();
$model->addSource( $linkedin );

$fblikes = $factory->facebookLikesSource();
$model->addSource( $fblikes );

$fbshares = $factory->facebookSharesSource();
$model->addSource( $fbshares );

$totalshares = $factory->totalSharesSource();
$model->addSource( $totalshares );

$decayviews = $factory->decayViewsSource();
$model->addSource( $decayviews );

$decayshares = $factory->decaySharesSource();
$model->addSource( $decayshares );

$popular = new cftp_analytics( $factory, $model );
$popular->run();