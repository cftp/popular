<?php

/**
 * Class cftp_google_analytics_source
 */
class cftp_google_analytics_source implements cftp_analytics_source {

	const default_post_age = "30 days";

	/**
	 * @var cftp_google_analytics_auth|null
	 */
	private $google_auth = null;

	/**
	 *
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'manage_posts_columns', array( $this, 'columns_head') );
			add_action( 'manage_posts_custom_column', array( $this, 'columns_content' ), 10, 2);
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'column_register_sortable' ) );
			add_action( 'all_admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
		$this->google_auth = new cftp_google_analytics_auth();

		add_filter( 'kqw_orderby_options', array( $this, 'query_widget_order' ) );
		add_filter( 'request', array( $this, 'orderby' ) );
	}

	public function admin_notices() {
		if ( !empty( $this->google_auth->errors ) ) {
			echo '<div class="error">';
			foreach( $this->google_auth->errors as $error ) {
				?>
				<p>Error: Popular, Google API Exception: <code>"<?php echo 'Type:'.get_class( $error ).'Code: '.$error->getCode().', Message: '.$error->getMessage(); ?>"</code></p>
				<?php
			}
			echo '</div>';
		}
	}

	/**
	 * @return bool
	 */
	public function initialiseAPIs() {

		return $this->google_auth->initialiseAPIs();

	}

	/**
	 * return max age of page views (or the default value if not set)
	 *
	 * @author William Turrell
	 * @return string option
	 */
	public function getPostAge() {
		$option = get_option( 'cftp_popular_google_analytics_post_age' );

		if ( ! isset( $option ) or trim( $option ) == '' ) {
			// although get_option has 2nd 'default' parameter, no good if you have empty (rather than unset) value
			$option = constant( 'cftp_google_analytics_source::default_post_age' );
		}

		return $option;
	}

	/**
	 * @param $orders
	 *
	 * @return mixed
	 */
	function query_widget_order( $orders ) {
		$orders['googleanalytics'] = 'GA Page Views last '.$this->getPostAge();
		return $orders;
	}

	function orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'google_last30' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'cfto_popular_views_googleanalytics',
				'orderby' => 'meta_value_num'
			) );
		}

		return $vars;
	}

	function column_register_sortable( $columns ) {
		$columns['google_last30'] = 'google_last30';
		return $columns;
	}

	function columns_head($defaults) {
		// Tooltip (duplicate heading so wherever they hover they see it)
		$defaults['google_last30'] = sprintf( '<span title="%1$s">Views</span> <span class="dashicons dashicons-editor-help" title="%1$s"></span> ',
			'Page Views last '.$this->getPostAge().' (Google Analytics)' );
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'google_last30' ) {
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
		return 'googleanalytics';
	}

	/**
	 * @param $option_group
	 * @param $section_id
	 * @param $page
	 *
	 * @return null|void
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cftp_popular_google_analytics';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);

		register_setting(
			$option_group, // Option group
			$option_name . '_client_id' // Option name
		);
		register_setting(
			$option_group, // Option group
			$option_name . '_client_secret' // Option name
		);
		register_setting(
			$option_group, // Option group
			$option_name . '_post_age' // Option name
		);
		if ( !$this->isConfigured() ) {

			add_settings_field(
				$option_name . '_client_id', // ID
				'Google Analytics Client ID', // Title
				array( $this, 'displayClientID' ), // Callback
				$page, // Page
				$section_id // Section
			);
			add_settings_field(
				$option_name . '_client_secret', // ID
				'Google Analytics Client Secret', // Title
				array( $this, 'displayClientSecret' ), // Callback
				$page, // Page
				$section_id // Section
			);
			add_settings_field(
				$option_name . '_redirect_field', // ID
				'Google Analytics Client Redirect URL', // Title
				array( $this, 'displayRedirectURL' ), // Callback
				$page, // Page
				$section_id // Section
			);
		}

		add_settings_field(
			$option_name, // ID
			'Google Analytics', // Title
			array( $this, 'displaySettings' ), // Callback
			$page, // Page
			$section_id // Section
		);

		add_settings_field(
			$option_name . '_post_age', // ID
			'Show Page Views for last…', // Title
			array( $this, 'displayPostAge' ), // Callback
			$page, // Page
			$section_id // Section
		);

	}

	public function admin_menu() {
		add_submenu_page( '', 'Popular Google Analytics Tests', 'Popular Google Analytics Tests', 'manage_options', 'cftp_google_analytics_test', array( $this, 'test_page' ) );
	}

	public function displayClientID() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_id" value="<?php echo $this->google_auth->getClientID(); ?>"/>
		<?php
	}

	public function displayClientSecret() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_secret" value="<?php echo $this->google_auth->getClientSecret(); ?>"/>
		<?php
	}

	public function displayRedirectURL() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_redirect_url" value="<?php echo $this->google_auth->getRedirectURL(); ?>" disabled />
		<p class="description">To activate Google Analytics support, you will need to fill out the above fields with credentials. When creating those credentials use the above redirect URL.</p>
		<p class="description">You can create credentials to authenticate with Google by going to the <a href="https://console.developers.google.com/">Google Cloud Console</a>. You'll need to create a project,then create new OAuth credentials of type Web Application, using the redirect URL shown above.</p>
		<p class="description">Remember to activate the Google Analytics API in your Google Cloud Console, and to add a support email under the credentials section. Failure to do so will generate errors when activating or using Google Analytics.</p>
		<?php
	}

	public function displayPostAge() {
		?>
		<input name="cftp_popular_google_analytics_post_age" value="<?php echo $this->getPostAge(); ?>" placeholder="<?php echo $this->getPostAge(); ?>" />
		<p class="description">e.g. "30 days", "2 weeks", "3 months", "1 year" (parsed with <a href="http://php.net/strtotime">strtotime</a>).
			<ul>
			 <li>Note: if you have existing stats, they won't update instantly if you change this.</li>
			 <li>The time period only applies to Page Views from Google Analytics, not any of the Facebook or Twitter stats.</li>
			</ul>
		</p>
		<?php
	}

	/**
	 * @return bool
	 */
	public function isConfigured() {
		return $this->google_auth->isConfigured();
	}

	/**
	 *
	 */
	public function displaySettings() {

		if ( !empty( $_GET['cftp_pop_ga_reset'] ) ) {
			// delete the tokens and things?
			delete_option( 'cftp_popular_ga_token' );
		}

		$this->initialiseAPIs();
		if ( !class_exists( 'Google_Client' ) ) {
			return;
		}
		if ( !$this->isConfigured() ) {

			$show_activate = true;

			$secret = $this->google_auth->getClientSecret();
			$client_id = $this->google_auth->getClientID();

			if ( empty( $secret ) ) {
				$show_activate = false;
			} else if ( empty( $client_id ) ) {
				$show_activate = false;
			}

			if ( $show_activate ) {

				try {
					$authUrl = $this->google_auth->getAuthURL();
					?>
					<a href="<?php echo $authUrl; ?>" class="button">Activate Google Analytics</a>
				<?php
				} catch ( Google_Service_Exception $e ) {
					$this->google_auth->errors[] = $e;
					return;
				} catch ( Google_IO_Exception $e ) {
					$this->google_auth->errors[] = $e;
					return;
				} catch ( Google_Auth_Exception $e ) {
					$this->google_auth->errors[] = $e;
					return;
				} catch ( Google_Exception $e ) {
					$this->google_auth->errors[] = $e;
					return;
				}
			} else {
				?>
				<a class="button disabled">Activate Google Analytics</a>
				<p class="description">You need to enter a valid client ID and secret before activation can occur. Enter the values above and save the page to continue.</p>
				<?php
			}
		} else {
			?>
			<a class="button" href="options-general.php?page=cftp_popular_settings_page&cftp_pop_ga_reset=true" >Deactivate Google Analytics</a>
			<a class="button" href="?page=cftp_google_analytics_test">Test</a>
			<?php
		}
	}

	/**
	 * @param Google_Profile|Google_Service_Analytics_Profile $profile
	 * @param                                                 $url
	 *
	 * @return mixed
	 */
	private function getPageViewsURL( Google_Service_Analytics_Profile $profile, $url ) {
		$this->initialiseAPIs();
		try {
			$url  = trailingslashit( $url );
			$to   = date( 'Y-m-d' );
			$from = strtotime( $to . ' -' . $this->getPostAge() );
			$from = date( 'Y-m-d', $from );
			if ( isset( $this->google_auth->service->data_ga ) ) {
				$data = $this->google_auth->service->data_ga->get(
					'ga:' . $profile->id,
					$from,
					$to,
					'ga:pageviews',
					array(
						'dimensions'  => 'ga:pageTitle,ga:pagePath',
						'sort'        => '-ga:pageviews',
						'filters'     => 'ga:pagePath=~' . $url,
						'max-results' => '1'
					)
				);

				$result = $data->totalsForAllResults['ga:pageviews'];

				return $result;
			}
		} catch ( Google_Service_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_IO_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_Auth_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_Exception $e ) {
			$this->google_auth->errors[] = $e;
		}
		return false;


	}

	/**
	 * Return a Profile object given a URL
	 *
	 * @param $url string A URL to compare against, must match a web property in full
	 *
	 * @return Google_Service_Analytics_Profile|null
	 */
	private function getProfileIDByURL( $url ) {
		$this->initialiseAPIs();
		$current = $this->getWebProperty( $url );
		if ( $current != null ) {
			try {
				$profile = $this->getFirstProfile( $current );
				return $profile;
			} catch ( Google_Service_Exception $e ) {
				$this->google_auth->errors[] = $e;
			} catch ( Google_IO_Exception $e ) {
				$this->google_auth->errors[] = $e;
			} catch ( Google_Auth_Exception $e ) {
				$this->google_auth->errors[] = $e;
			} catch ( Google_Exception $e ) {
				$this->google_auth->errors[] = $e;
			}
		}
		return null;
	}

	/**
	 * @param $url
	 *
	 * @return Google_Service_Analytics_Webproperty|null
	 */
	private function getWebProperty( $url ) {
		$this->initialiseAPIs();
		if ( strpos( $url,'/') == 0 ) {
			$url = site_url();
		}
		try {
			$props = $this->google_auth->service->management_webproperties->listManagementWebproperties("~all");
			foreach ( $props->items as $prop ) {
				if ( $url == $prop->websiteUrl ) {
					return $prop;
				}
			}
		} catch ( Google_Service_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_IO_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_Auth_Exception $e ) {
			$this->google_auth->errors[] = $e;
		} catch ( Google_Exception $e ) {
			$this->google_auth->errors[] = $e;
		}
		return null;
	}

	/**
	 * @param Google_Service_Analytics_Webproperty|Google_Webproperty $property
	 *
	 * @return null
	 */
	private function getFirstProfile( Google_Service_Analytics_Webproperty  $property ) {
		$this->initialiseAPIs();
		$profiles = $this->google_auth->service->management_profiles->listManagementProfiles( $property->accountId, $property->id );
		if ( !empty( $profiles ) ) {
			foreach ( $profiles->items as $prop ) {
				return $prop;
			}
		}
		return null;
	}

	/**
	 * @param $url
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsForURL( $url ) {
		$this->initialiseAPIs();
		$profile = $this->getProfileIDByURL( $url );
		if ( $profile != null ) {
			$views = $this->getPageViewsURL( $profile, $url);
			return $views;
		}
		return false;
	}

	/**
	 * @param $post_id
	 *
	 * @return bool|mixed|string
	 */
	public function getPageViewsByPostID( $post_id ) {
		$this->initialiseAPIs();
		$permalink = get_permalink( $post_id );
		$permalink = str_replace( site_url(), '', $permalink );
		return $this->getPageViewsForURL( $permalink );
	}

	public function test_page() {

		?>
		<div class="wrap">
			<h2>CFTP Popular Tests</h2>
			<?php

			if ( !$this->initialiseAPIs() ) {
				echo '<p><strong>Failed to initialise APIs</strong></p>';
				echo '</div>';
				return;
			}
			if ( !$this->isConfigured() ) {
				echo '<p>You haven\'t configured Google Analytics</p>';
			} else {
				try {
					?>
					<p>Here are some test data retrievals of useful information and debug data. All data is freshly grabbed directly from Google. If you're having issues, check the profile ID and web profile being used matches your site, and make sure you've activated the Google Analytics API.</p>

					<table class="wp-list-table widefat fixed">
						<thead>
							<th width="120px">Data</th>
							<th>Message</th>
						</thead>
						<tbody>
						<?php

						$service         = $this->google_auth->service;
						$current_profile = $this->getProfileIDByURL( home_url() );
						echo "<tr><td>Current Profile</td><td><pre>";
						if ( $current_profile != null ) {
							echo $current_profile->getName() . ", " . $current_profile->getAccountId().", ".$current_profile->getId();
						} else {
							echo "Current profile couldn't be found";
						}
						echo "</pre></td></tr>";

						$current_property = $this->getWebProperty( home_url() );
						echo "<tr><td>Current Web Property</td><td><pre>";
						if ( $current_property != null ) {
							echo $current_property->getName() . ", " . $current_property->getId(). ", ".$current_property->getWebsiteUrl();
						} else {
							echo "Current web property couldn't be found";
						}
						echo "</pre></td></tr>";

						if ( $current_profile != null ) {
							echo "<tr><td>Most Popular between 1/1/2014 and 1/1/2015</td><td>";
							try {
								$data = $service->data_ga->get(
									'ga:' . $current_profile->getId(),
									'2014-01-01',
									'2015-01-01',
									'ga:pageviews',
									array(
										'dimensions'  => 'ga:pageTitle,ga:pagePath',
										'sort'        => '-ga:pageviews',
										'filters'     => 'ga:pagePath!=/',
										'max-results' => '10' ) );
								echo '<table>';
								echo '<thead><tr><th>' . $data->columnHeaders[0]->name . '</th><th>' . $data->columnHeaders[1]->name . '</th><th>' . $data->columnHeaders[2]->name . '</th></tr></thead>';
								foreach ( $data->rows as $row ) {
									echo '<tr><td>' . $row[0] . '</td><td>' . $row[1] . '</td><td>' . $row[2] . '</td></tr>';
								}
								echo '</table>';
								//echo "<pre>" . print_r( $data, true) . "</pre>";
							} catch ( Google_Service_Exception $e ) {
								$this->google_auth->errors[] = $e;
							} catch ( Google_IO_Exception $e ) {
								$this->google_auth->errors[] = $e;
							} catch ( Google_Auth_Exception $e ) {
								$this->google_auth->errors[] = $e;
							} catch ( Google_Exception $e ) {
								$this->google_auth->errors[] = $e;
							}
							echo "</td></tr>";
						}

						$props = $service->management_webproperties->listManagementWebproperties( "~all" );
						echo "<tr><td>Web Properties</td><td><table>";
						echo "<thead><tr><th>URL</th><th>Account ID</th><th>Property ID</th></tr></thead>";
						foreach ( $props->items as $prop ) {
							echo '<tr><td>'.$prop->websiteUrl."</td><td>".$prop->getAccountId()."</td><td>".$prop->getId()."</td></tr>";
						}
						echo "</table></td></tr>";
						echo "<tr><td>Web Properties Raw</td><td><pre>" . print_r( $props, true ) . "</pre></td></tr>";

						$accounts = $service->management_accounts->listManagementAccounts();
						echo "<tr><td>Accounts</td><td><pre>" . print_r( $accounts, true ) . "</pre></td></tr>";

						$segments = $service->management_segments->listManagementSegments();
						echo "<tr><td>Segments</td><td><pre>" . print_r( $segments, true ) . "</pre></td></tr>";

						$goals = $service->management_goals->listManagementGoals( "~all", "~all", "~all" );
						echo "<tr><td>Goals</td><td><pre>" . print_r( $goals, true ) . "</pre></td></tr>";
						?>
						</tbody>
					</table>
					<?php
				} catch ( Google_Service_Exception $e ) {
					$this->google_auth->errors[] = $e;
				} catch ( Google_IO_Exception $e ) {
					$this->google_auth->errors[] = $e;
				} catch ( Google_Auth_Exception $e ) {
					$this->google_auth->errors[] = $e;
				} catch ( Google_Exception $e ) {
					$this->google_auth->errors[] = $e;
				}
				$this->admin_notices();
			}
		echo '</div>';
	}
}