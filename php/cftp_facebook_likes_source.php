<?php

class cftp_facebook_likes_source implements cftp_analytics_source {

	/**
	 *
	 */

	const title = 'FB Likes';

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
		$orders['facebook_likes'] = constant('cftp_facebook_likes_source::title');

		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'facebook_likes' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_facebook_likes',
				'orderby'  => 'meta_value_num',
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['facebook_likes'] = 'facebook_likes';

		return $columns;
	}

	function columns_head( $defaults ) {
		// Tooltip (duplicate title so wherever they hover they see it)
		$defaults['facebook_likes'] = sprintf( '<span title="%1$s">%2$s</span> <span class="dashicons dashicons-editor-help" title="%1$s"></span> ',
			'Facebook Likes',
			constant( 'cftp_facebook_likes_source::title' ) );
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {

		if ( $column_name == 'facebook_likes' ) {
			$source_name = $this->sourceName();

			if ( in_array('picshare/picshare.php',get_option('active_plugins')) ) {
				$ps_stats = get_post_meta( $post_id, 'cfto_popular_picshare_facebook', true );
				$views    = isset( $ps_stats['total']['like_count'] ) ? $ps_stats['total']['like_count'] : '';
			} else {
				$views = get_post_meta( $post_id, 'cfto_popular_views_' . $source_name, true );
			}

			if ( $views === '' ) {
				echo constant('cftp_analytics_source::column_html_pending');
			} else if ( is_numeric( $views ) ) {
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
		return 'facebook_likes';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cfto_popular_facebook_likes';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		add_settings_field(
			$option_name, // ID
			'Facebook Likes', // Title
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
		echo '<p>Facebook Likes requires no configuration</p>';
	}

	/**
	 * Get Facebook stats for a URL or URL(s).
	 *
	 * @author William Turrell
	 *
	 * @param string|array $input single URL as string OR multiple URLs as image_filename > query string key/value pairs
	 *
	 * @return int|array either individual like_count, or an array of descriptions and like/share counts
	 */
	public function getPageViewsForURL( $input ) {

		// Can be called in two ways. We'll use is_array and is_string throughout on $input to check which.

		// Prepare Facebook API URL
		$fb_param = '';

		if ( is_array( $input ) && count( $input ) > 0 ) {
			// the unique permalinks are in the array values, join them with commas so FB can check all at once
			// @todo need to loop through and run esc_url on each link
			$fb_param = implode( ',', array_values( $input ) );
		} elseif ( is_string( $input ) ) {
			// just the one URL
			$fb_param = esc_url( $input );
		} else {
			// invalid
			return false;
		}

		// Call the API
		$api      = 'http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls=';
		$response = wp_remote_get( $api . $fb_param );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( $response['response']['code'] == 200 ) {
			$json = json_decode( $response['body'], true );

			// Store Facebook stats
			$stats = [ ];

			if ( is_array( $input ) ) {
				// we want to store image filename in DB, not a complete URL
				// so we use array_flip to swap key/values pairs so we can do a reverse lookup by URL to get image info
				// NB: use array_flip NOT array_reverse, latter merely reverses order of elements...
				$input_rev = array_flip( $input );
			}

			// Loop through stats by URL

			// We'll need a total for the end (new fields will be added by ->increment_total)
			$total = [ ];

			foreach ( array_values( $json ) as $v ) {

				// Get the correct description
				$desc = '';

				if ( is_string( $input ) ) {
					$desc = 'permalink';    // with only one URL, it *must* be the permalink
				} else {
					if ( isset( $input_rev[ $v['url'] ] ) ) {
						// use our reverse lookup (see above)
						$desc = $input_rev[ $v['url'] ];
					} else {
						// fallback to full URL. Shouldn't happen unless image deleted etc.
						$desc = $v['url'];
					}
				}

				$stats[ $desc ] = [
					'like_count'        => $v['like_count'],
					'share_count'       => $v['share_count'],
					'comment_count'     => $v['comment_count'],
					'commentsbox_count' => $v['commentsbox_count'],
					'click_count'       => $v['click_count'],
					'total_count'       => $v['total_count'],
					// Yes, this is the facebook_likes class, but I'm trying to cut down on API calls.
					// Let's keep all the Picshare data together.
					// Also this means we don't need to duplicate any code in facebook_shares etc. either.
				];

				$total = $this->increment_total( $total, $v );
			}

			$stats['total'] = $total;

			// Return count to Popular...

			if ( is_string( $input ) ) {
				// ... either as a single total if there was only one URL..

				if ( isset( $stats['permalink']['like_count'] ) ) {
					return $stats['permalink']['like_count'];
				}
			} else {
				// ... or an array of data if we were using Picshare
				return $stats;

			}
		}

		return false;
	}

	function increment_total( $current, $new ) {
		foreach ( $new as $k => $v ) {

			if ( is_int( $v ) ) {

				if ( isset( $current[ $k ] ) ) {
					// Key already exists, add to existing value
					$current[ $k ] += $v;
				} else {
					// Key needs to be created
					$current[ $k ] = $v;
				}
			}
		}

		return $current;
	}


	/**
	 * If Picshare is installed, get full Facebook stats for permalink AND all images on page, and store them in DB.
	 * If it isn't, just use the permalink.  In both cases, return total number of "likes".
	 *
	 * @author William Turrell
	 *
	 * @param int $post_id
	 *
	 * @return int number of FB "likes"
	 */
	public function getPageViewsByPostID( $post_id ) {
		$permalink = get_permalink( $post_id );

		if ( in_array('picshare/picshare.php',get_option('active_plugins')) ) {

			// Use picshare to get a list of Facebook permalinks (with a unique query string for each image)
			$picshare = new Picshare();

			$urls              = $picshare->build_fb_img_array( $post_id );  // NB: empty if no sharable images
			$urls['permalink'] = $permalink;

			// Get array with breakdown by URL, rather than just a single total
			if ( $stats = $this->getPageViewsForURL( $urls, $post_id ) ) {
				// Store full data in post_meta.
				// (arguably this would be in a separate function, except we can only return an integer from this one...)
				update_post_meta( $post_id, 'cfto_popular_picshare_facebook', $stats );
				update_post_meta( $post_id, 'cfto_popular_picshare_last_updated', date( 'Y-m-d' ) );

				return $stats['total']['like_count'];
			}
		} else {

			return $this->getPageViewsForURL( $permalink );
		}

	}
}

