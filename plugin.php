<?php
/*
 * Plugin Name: Popular
 * Plugin URI: http://codeforthepeople.com/
 * Description: Queries Analytic services for web traffic data
 * Author: Tom J Nowell, Code For The People
 * Version: 1.3
 * Author URI: http://codeforthepeople.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * This comment is added for compatibility with the null framework https://github.com/scottsweb/null
 * Widget Name: Popular Widget
 *
 * Copyright © 2013 Code for the People ltd
 *
 *                 _____________
 *                /      ____   \
 *          _____/       \   \   \
 *         /\    \        \___\   \
 *        /  \    \                \
 *       /   /    /          _______\
 *      /   /    /          \       /
 *     /   /    /            \     /
 *     \   \    \ _____    ___\   /
 *      \   \    /\    \  /       \
 *       \   \  /  \____\/    _____\
 *        \   \/        /    /    / \
 *         \           /____/    /___\
 *          \                        /
 *           \______________________/
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
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

// TODO: readd with GraphQL rather than the deprecated REST Server
//$fblikes = $factory->facebookLikesSource();
//$model->addSource( $fblikes );

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
