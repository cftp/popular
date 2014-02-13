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

require_once( 'php/cftp_analytics_factory.php');
require_once( 'php/cftp_google_analytics_source.php');
require_once( 'php/cftp_analytics_model.php');
require_once( 'php/cftp_analytics_option_model.php');
require_once( 'php/cftp_analytics.php');
require_once( 'php/cftp_analytics_cron_task.php');
require_once( 'php/cftp_analytics_settings_page.php');
require_once( 'php/cftp_analytics_source.php');
require_once( 'php/cftp_facebook_likes_source.php');
require_once( 'php/cftp_facebook_shares_source.php');
require_once( 'php/cftp_twitter_source.php');



$factory = new cftp_analytics_factory();

$model = new cftp_analytics_option_model();
$analytics = $factory->googleAnalyticsSource();
$model->addSource( $analytics );
$twitter = $factory->twitterSharesSource();
$model->addSource( $twitter );
$fblikes = $factory->facebookLikesSource();
$model->addSource( $fblikes );
$fbshares = $factory->facebookSharesSource();
$model->addSource( $fbshares );
$popular = new cftp_analytics( $factory, $model );
$popular->run();