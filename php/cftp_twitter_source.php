<?php

class cftp_twitter_source implements cftp_analytics_source {

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
		$orders['twitter_shares'] = 'Twitter Shares';
		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'twitter_shares' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_twitter_shares',
				'orderby' => 'meta_value_num'
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['twitter_shares'] = 'twitter_shares';
		return $columns;
	}

	function columns_head($defaults) {
		$defaults['twitter_shares'] = 'Twitter Shares';
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'twitter_shares' ) {
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
		return 'twitter_shares';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cftp_popular_twitter_shares';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		add_settings_field(
			$option_name, // ID
			'Twitter Shares', // Title
			array( $this, 'displaySettings' ), // Callback
			$page, // Page
			$section_id // Section
		);
	}

	/**
	 * @return bool
	 */
	public function isConfigured() {
		return true;
	}

	/**
	 *
	 */
	public function displaySettings() {
		echo '<p>Twitter shares requires no configuration</p>';
	}

	/**
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsForURL( $url ) {
		$api = 'http://urls.api.twitter.com/1/urls/count.json?url=';
		$response = wp_remote_get( $api.$url );
		if ( !is_wp_error( $response ) ) {
			if ( $response['response']['code'] == 200 ) {
				$json = json_decode( $response['body'], true );
				return $json['count'];
			}
		}
		return false;
	}

	/**
	 * @param $post_id
	 */
	public function getPageViewsByPostID( $post_id ) {
		$permalink = get_permalink( $post_id );
		//$permalink = str_replace( site_url(), '', $permalink );
		return $this->getPageViewsForURL( $permalink );
	}
}

