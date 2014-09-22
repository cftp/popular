<?php

class cftp_linkedin_source implements cftp_analytics_source {

	const title = "LinkedIn Shares";

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
		$orders['linkedin_shares'] = constant('cftp_linkedin_source::title');
		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'linkedin_shares' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_linkedin_shares',
				'orderby' => 'meta_value_num'
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['linkedin_shares'] = 'linkedin_shares';
		return $columns;
	}

	function columns_head($defaults) {
		$defaults['linkedin_shares'] = constant('cftp_linkedin_source::title');
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'linkedin_shares' ) {
			$source_name = $this->sourceName();
			$views = get_post_meta( $post_id, 'cfto_popular_views_'.$source_name, true );
			if ( $views === '' ) {
				echo constant('cftp_analytics_source::column_html_pending');
			} else  if ( is_numeric( $views ) ) {
				echo $views;
			} else {
				echo constant('cftp_analytics_source::column_html_na');
			}
		}
	}

	/**
	 * @return string
	 */
	public function sourceName() {
		return 'linkedin_shares';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 *
	 * @return null|void
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cftp_popular_linkedin_shares';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		add_settings_field(
			$option_name, // ID
			constant('cftp_linkedin_source::title'), // Title
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
		echo '<p>'.constant('cftp_linkedin_source::title').' requires no configuration</p>';
	}

	/**
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsForURL( $url ) {
		$api = 'http://www.linkedin.com/countserv/count/share?url=';
		$response = wp_remote_get( $api. urlencode( $url ). '&lang=en_US&callback=?' );
		if ( !is_wp_error( $response ) ) {
			if ( $response['response']['code'] == 200 ) {
				$content = $response['body'];
				$content = trim( $content, '?();' );
				$json = json_decode( $content, true );
				return $json['count'];
			}
		}
		return false;
	}

	/**
	 * @param $post_id
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsByPostID( $post_id ) {
		$permalink = get_permalink( $post_id );
		//$permalink = str_replace( site_url(), '', $permalink );
		return $this->getPageViewsForURL( $permalink );
	}
}

