<?php

class cftp_facebook_shares_source implements cftp_analytics_source {

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
		$orders['facebook_shares'] = 'Facebook Shares';
		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'facebook_shares' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_facebook_shares',
				'orderby' => 'meta_value_num'
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['facebook_shares'] = 'facebook_shares';
		return $columns;
	}

	function columns_head($defaults) {
		$defaults['facebook_shares'] = 'FB Shares';
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'facebook_shares' ) {
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
		return 'facebook_shares';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cftp_popular_facebook_shares';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		add_settings_field(
			$option_name, // ID
			'Facebook Shares', // Title
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
		echo '<p>Facebook Shares requires no configuration</p>';
		echo 'Facebook Shares:'.$this->getPageViewsForURL('http://www.tomjn.com/398/add_action-considered-harmful/');
	}

	/**
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsForURL( $url ) {
		$api = 'http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls=';
		$response = wp_remote_get( $api.$url );
		if ( !is_wp_error( $response ) ) {
			if ( $response['response']['code'] == 200 ) {
				$json = json_decode( $response['body'], true );
				return $json[0]['share_count'];
			}
		}
		return false;
	}

	/**
	 * @param $post_id
	 */
	public function getPageViewsByPostID( $post_id ) {
		$permalink = get_permalink( $post_id );
		return $this->getPageViewsForURL( $permalink );
	}
}

