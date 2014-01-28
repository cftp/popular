<?php
/*
 * Plugin Name: CFTP Analytics
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

$model = new cftp_analytics_option_model();
$factory = new cftp_analytics_factory( $model );
$plugin = new cftp_analytics( $factory );
