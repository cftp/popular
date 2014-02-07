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

$factory = new cftp_analytics_factory();

$model = new cftp_analytics_option_model();
$analytics = $factory->googleAnalyticsSource();
$model->addSource( $analytics );
$twitter = $factory->twitterSharesSource();
$model->addSource( $twitter );
$plugin = new cftp_analytics( $factory, $model );
$plugin->run();