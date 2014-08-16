<?php

class cftp_total_shares_source implements cftp_analytics_source {

	/**
	 *
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter('manage_posts_columns', array( $this, 'columns_head') );
			add_action('manage_posts_custom_column', array( $this, 'columns_content' ), 10, 2);
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'column_register_sortable' ) );
		}

		add_filter( 'kqw_orderby_options', array( $this, 'query_widget_order' ) );
		add_filter( 'request', array( $this, 'orderby' ) );
	}

	function query_widget_order( $orders ) {
		$orders['total_shares'] = 'Total Shares';
		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'total_shares' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_total_shares',
				'orderby' => 'meta_value_num'
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['total_shares'] = 'total_shares';
		return $columns;
	}

	function columns_head($defaults) {
		$defaults['total_shares'] = 'Total Shares';
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'total_shares' ) {
			$source_name = $this->sourceName();
			$views = get_post_meta( $post_id, 'cfto_popular_views_'.$source_name, true );
			if ( $views === '' ) {
				echo 'pending';
			} else  if ( is_numeric( $views ) ) {
				echo $views;
			} else {
				echo 'n/a';
			}
		}
	}

	/**
	 * @return string
	 */
	public function sourceName() {
		return 'total_shares';
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
		// Changed this back to the version with the typo in so stats are visible. William 2014-08-16
		$facebook_likes = get_post_meta( $post_id, 'cfto_popular_views_facebook_likes' , true );
		$facebook_shares = get_post_meta( $post_id, 'cfto_popular_views_facebook_shares' , true );
		$twitter_shares = get_post_meta( $post_id, 'cfto_popular_views_twitter_shares' , true );
		if ( empty( $facebook_likes ) ) {
			$facebook_likes = 0;
		}
		if ( empty( $facebook_shares ) ) {
			$facebook_shares = 0;
		}
		if ( empty( $twitter_shares ) ) {
			$twitter_shares = 0;
		}
		return $twitter_shares + $facebook_likes + $facebook_shares;
	}

	/**
	 * @return mixed
	 */
	public function displaySettings() {
		return;
	}
}

