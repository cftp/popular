<?php

class cftp_analytics_cron_task {

	private $source = null;

	public function __construct( cftp_analytics_source $source ) {
		$this->source = $source;
	}

	function add_intervals( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __( 'Once Weekly' )
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 minutes' )
		);
		$schedules['fiveminutes'] = array(
			'interval' => 300,
			'display' => __( '5 minutes' )
		);
		return $schedules;
	}

	// schedule tasks
	public function run() {
		$source_name = $this->source->sourceName();
		add_filter( 'cron_schedules', array( $this, 'add_intervals' ) );
		add_action( 'cftp_popular_task_'.$source_name, array( $this, 'task' ) );
		$source_name = $this->source->sourceName();
		if ( ! wp_next_scheduled( 'cftp_popular_task_'.$source_name ) ) {
			wp_schedule_event( time(), 'fiveminutes', 'cftp_popular_task_'.$source_name );
		}
		//add_action( 'admin_init', array( $this, 'task' ) );
	}

	/**
	 * Actually run the task for a particular source.
	 * Searches for posts that have no data, or are ready to be refreshed. Calls process_posts function.
	 * Note I was unable to make this work correctly in a combined 'OR' meta_query.
	 *
	 * @author William Turrell
	 *
	 * @return null|void
	 */
	public function task() {

		$source_name = $this->source->sourceName();
		$key         = 'cfto_popular_last_updated_' . $source_name;

		$common_args = array(
			// Use these for all queries
			'posts_per_page' => '7',                   // max no. of posts at a time
			'post_status'    => 'publish',
			'post_type'      => 'any',
			'meta_key'       => $key,
		);

		// 1. Posts that don't have any stats yet.
		$args = array(
			'meta_compare' => 'NOT EXISTS',
			'meta_value'   => '1',
		);

		$this->process_posts( array_merge( $common_args, $args ), $source_name );

		// 2. Posts with stats at least one day old (time to update again)
		$args = array(
			'meta_compare' => '<',
			'meta_value'   => date( 'Y-m-d' ),
		);

		$this->process_posts( array_merge( $common_args, $args ), $source_name );
	}

	/**
	 * Process posts, use source class to lookup views/shares, write it to post_meta
	 *
	 * @param array $args list of WP_Query arguments
	 * @param string $source_name e.g. "facebook_shares"
	 */
	function process_posts( $args, $source_name ) {

		$query = new WP_Query( $args, $source_name );

		if ( $query->have_posts() ) {
			global $post;
			while ( $query->have_posts() ) {

				$query->the_post();
				$views = $this->source->getPageViewsByPostID( $post->ID );

				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					printf( "Processing\t%s\t%s\n", $source_name, $post->ID );
				}

				if ( $views !== false ) {
					update_post_meta( $post->ID, 'cfto_popular_views_' . $source_name, $views );
					update_post_meta( $post->ID, 'cfto_popular_last_updated_' . $source_name, date( 'Y-m-d' ) );
				}
			}
		}

	}

}
