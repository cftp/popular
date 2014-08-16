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

	// actually run the task
	public function task() {
		$source_name = $this->source->sourceName();
		$to = date('Y-m-d');
		$from = strtotime( $to.' -30 day' );
		$args = array(
			'meta_key' => 'cfto_popular_last_updated_'.$source_name,
			'posts_per_page' => '25', // Let's show them all.
			'post_status' => 'publish',
			'post_type' => 'any',
			'meta_query' => array( // WordPress has all the results, now, return only the events after today's date
				'relation' => 'OR',
				// either fine posts that haven't been analysed at all
				array(
					'key' => 'cfto_popular_last_updated_'.$source_name,
					'compare' => 'NOT EXISTS',
					'value' => ''
				),
				// or filter by date

				// @todo For some reason WP_Query won't find new posts (w/o existing post meta) unless I comment this out?
				// Also shouldn't it be last_updated <= $to (now), not <= $from (1 month ago)? William, 2014-08-16

				array(
					'key' => 'cfto_popular_last_updated_'.$source_name, // Check the start date field
					'value' => $from, // Set today's date (note the similar format)
					'compare' => '<=', // Return the ones greater than today's date
					'type' => 'NUMERIC,' // Let WordPress know we're working with numbers
				)
			)
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			global $post;
			while ( $query->have_posts() ) {
				$query->the_post();
				$views = $this->source->getPageViewsByPostID( $post->ID );

				if ( $views !== false ) {
					update_post_meta( $post->ID,'cfto_popular_views_'.$source_name, $views );
					update_post_meta( $post->ID,'cfto_popular_last_updated_'.$source_name, date( 'Y-m-d') );
				}
			}
		}
	}
}
