<?php

class cftp_total_shares_source implements cftp_analytics_source {

	/**
	 *
	 */

	const title = "Total Shares";

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
		$orders['total_shares'] = constant('cftp_total_shares_source::title');
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
		// Tooltip (duplicate title so wherever they hover they see it)
		$defaults['total_shares'] = sprintf( '<span title="%1$s">%2$s</span> <span class="dashicons dashicons-editor-help" title="%1$s"></span> ',
			'Tweets + Facebook shares + Facebook likes',
			constant( 'cftp_total_shares_source::title' ) );

		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'total_shares' ) {
			$source_name = $this->sourceName();
			$views = get_post_meta( $post_id, 'cfto_popular_views_'.$source_name, true );
			if ( $views === '' ) {
				echo constant('cftp_analytics_source::column_html_pending');
			} else  if ( is_numeric( $views ) ) {

				if ( in_array('picshare/picshare.php',get_option('active_plugins')) ) {
					// Link to full stats breakdown (served by Picshare but loaded from post_meta)
					printf( '%d <a title="View full stats" href="%s"><span class="dashicons dashicons-chart-bar"></span></a>',
						intval( $views ),
						esc_url( admin_url( 'options-general.php?page=picshare-setting-admin&post-stats-cftp-popular=' . $post_id ) )
					);
				} else {
					echo intval( $views );
				}

			} else {
				echo constant('cftp_analytics_source::column_html_na');
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
	 *
	 * @return null|void
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
	 *
	 * @return int|mixed
	 */
	public function getPageViewsByPostID( $post_id ) {
		// Changed this back to the version with typo in post_meta key so stats are visible. William 2014-08-16

		$twitter_shares = get_post_meta( $post_id, 'cfto_popular_views_twitter_shares' , true );

		if ( in_array( 'picshare/picshare.php', get_option( 'active_plugins' ) ) ) {
			$ps_stats        = get_post_meta( $post_id, 'cfto_popular_picshare_facebook', true );
			$facebook_likes  = isset( $ps_stats['total']['like_count'] ) ? $ps_stats['total']['like_count'] : 0;
			$facebook_shares = isset( $ps_stats['total']['share_count'] ) ? $ps_stats['total']['share_count'] : 0;
		} else {
			$facebook_likes  = get_post_meta( $post_id, 'cfto_popular_views_facebook_likes', true );
			$facebook_shares = get_post_meta( $post_id, 'cfto_popular_views_facebook_shares', true );
		}

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

