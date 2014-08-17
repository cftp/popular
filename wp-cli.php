<?php
// Popular WP-CLI commands
// Docs: https://github.com/wp-cli/wp-cli/wiki/Commands-Cookbook

class Popular_Command extends WP_CLI_Command {

	function __construct() {

		// Replicate what happens in plugin.php

		$this->factory = new cftp_analytics_factory();
		$model         = new cftp_analytics_option_model();

		$analytics = $this->factory->googleAnalyticsSource();
		$model->addSource( $analytics );

		$twitter = $this->factory->twitterSharesSource();
		$model->addSource( $twitter );

		$fblikes = $this->factory->facebookLikesSource();
		$model->addSource( $fblikes );

		$fbshares = $this->factory->facebookSharesSource();
		$model->addSource( $fbshares );

		$totalshares = $this->factory->totalSharesSource();
		$model->addSource( $totalshares );

		$decayshares = $this->factory->decaySharesSource();
		$model->addSource( $decayshares );

		$decayviews = $this->factory->decayViewsSource();
		$model->addSource( $decayviews );

		$this->popular = new cftp_analytics( $this->factory, $model );
	}

	/**
	 * Force updates so you don't have to wait for cron to run.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : google|facebook|twitter
	 *
	 * ## EXAMPLES
	 *
	 *     wp popular cron facebook
	 */
	function cron( $args, $assoc_args ) {

		list( $source ) = $args;
		$queue = [ ];    // list of tasks we need to do

		switch ( $source ) {
			case 'google':
				$queue[] = 'googleAnalyticsSource';
				$queue[] = 'decayViewsSource';
				break;
			case 'facebook':
				$queue[] = 'facebookLikesSource';
				$queue[] = 'facebookSharesSource';
				$queue[] = 'totalSharesSource';
				$queue[] = 'decaySharesSource';
				break;
			case 'twitter':
				$queue[] = 'twitterSharesSource';
				$queue[] = 'totalSharesSource';
				$queue[] = 'decaySharesSource';
				break;
			case 'total':
				$queue[] = 'totalSharesSource';
				break;
			default:
				echo "Unknown source.\n";

				return false;
				break;
		}

		foreach ( $queue as $v ) {
			echo( $this->factory->cronTask( $this->factory->{$v}() )->task() );
		}

	}

	/**
	 * For debugging. Test data collection on a single post.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : facebook|twitter
	 * <post_id>
	 * : int
	 *
	 * ## EXAMPLES
	 *
	 *     wp popular pageviews facebook 1
	 */
	function pageviews( $args, $assoc_args ) {

		list( $source, $post_id ) = $args;

		$class = '';

		switch ( $source )  {
			case 'facebook':
				$class = 'facebookLikesSource';
				break;
		}

		$this->factory->{$class}()->getPageViewsByPostID( $post_id );
	}

}

WP_CLI::add_command( 'popular', 'Popular_Command' );
