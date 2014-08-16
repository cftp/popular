<?php

class cftp_decay_shares_source implements cftp_analytics_source {

	/**
	 *
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'manage_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'column_register_sortable' ) );
		}

		add_filter( 'kqw_orderby_options', array( $this, 'query_widget_order' ) );
		add_filter( 'request', array( $this, 'orderby' ) );
	}

	function query_widget_order( $orders ) {
		$orders['decay_shares'] = 'Shares vs Freshness';

		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'decay_shares' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_decay_shares',
				'orderby'  => 'meta_value_num',
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['decay_shares'] = 'decay_shares';

		return $columns;
	}

	function columns_head( $defaults ) {
		$defaults['decay_shares'] = 'Shares vs Freshness';

		return $defaults;
	}

	/**
	 * Shares vs Freshness column output. Lookup age (in hours) convert to days for display.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 *
	 * @author William Turrell
	 */
	function columns_content( $column_name, $post_id ) {

		if ( $column_name != 'decay_shares' ) {
			return false;
		}

		// Work out age and discard anything over 14 days
		if ( $age = $this->post_age( $post_id ) ) {

			if ( $age > 14 * 24 ) {
				echo 'Too old.<br>';
			} else {
				// Number of shares
				$total = get_post_meta( $post_id, 'cfto_popular_views_total_shares', true );

				if ( $total === '' ) {
					echo 'pending';
				} else if ( is_numeric( $total ) ) {
					echo number_format( $total / ( $age / 24 ), 2 );   // convert hours to days to give larger number
				} else {
					echo 'n/a';
				}
			}
		}
	}

	/**
	 * How many hours old is a post? Use hours rather than days so result is more stable when days increment.
	 *
	 * @param $post_id
	 *
	 * @return int number of hours (rounded up)
	 *
	 * @author William Turrell
	 */
	function post_age( $post_id ) {

		$age = time() - get_post_time( 'U', true, $post_id );

		return ceil( $age / 3600 );
	}

	/**
	 * @return string
	 */
	public function sourceName() {
		return 'decay_shares';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		//
	}

	/**
	 * @return bool
	 */
	public function isConfigured() {
		return true;
	}

	/**
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsForURL( $url ) {
		//wp_die( 'unsupported get page views for URL on total shares source' );
		return false;
	}

	/**
	 * @param $post_id
	 */
	public function getPageViewsByPostID( $post_id ) {
		return false;
	}

	/**
	 * @return mixed
	 */
	public function displaySettings() {
		return;
	}
}

