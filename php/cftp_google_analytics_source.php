<?php

/**
 * Class cftp_google_analytics_source
 */
class cftp_google_analytics_source implements cftp_analytics_source {

	const default_post_age = "30 days";

	/**
	 * @var Google_Client|null
	 */
	private $client = null;

	/**
	 * @var Google_AnalyticsService|null
	 */
	private $service = null;

	/**
	 *
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'manage_posts_columns', array( $this, 'columns_head') );
			add_action( 'manage_posts_custom_column', array( $this, 'columns_content' ), 10, 2);
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'column_register_sortable' ) );
		}

		// @TODO: there's something inherently wrong about creating the google client in here
		// must consider using an object factory and passing in as a parameter instead
		if ( isset( $_GET['code'] ) ) {
			$this->initialiseAPIs();
		}

		add_filter( 'kqw_orderby_options', array( $this, 'query_widget_order' ) );
		add_filter( 'request', array( $this, 'orderby' ) );
	}

	public function initialiseAPIs() {

		if ( $this->client != null ) {
			return;
		}
		if ( !class_exists( 'Google_Client' ) ) {
			echo '<p><strong>Warning: The <code>Google_Client</code> class doesn\'t exist, did you run composer install to pull down the Google API library?</strong></p>';
			return;
		}

		try {
			$this->client = new Google_Client();

			$this->client->setApprovalPrompt("force");
			$this->client->setAccessType('offline');

			$this->client->setApplicationName( "CFTP Popular" );

			// Visit https://code.google.com/apis/console?api=analytics to generate your
			// client id, client secret, and to register your redirect uri.
			$this->client->setClientId( $this->getClientID() );
			$this->client->setClientSecret( $this->getClientSecret() );
			$this->client->setRedirectUri( $this->getRedirectURL() );

			$this->client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );

			$this->service = new Google_Service_Analytics( $this->client );
		} catch ( Google_IO_Exception $e ) {
			wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
		} catch ( Google_Service_Exception $e ) {
			wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
		}

		$token = '';

		if ( isset( $_GET['code'] ) ) {
			try {
				$code = $_GET['code'];
				$this->client->authenticate( $code );
				$newtoken = $this->client->getAccessToken();
				update_option('cftp_popular_ga_token', $newtoken );
				$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
				wp_safe_redirect( $redirect );
			} catch ( Google_IO_Exception $e ) {
				wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
			} catch ( Google_Service_Exception $e ) {
				wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
			}
		} else {
			$token = get_option( 'cftp_popular_ga_token' );
		}
		if ( !empty( $token ) ) {
			$this->client->setAccessToken( $token );
		}

	}

	public function getClientID() {
		//return '428049761702-ns5qdmhmstupbpi22oo9iokohq153m5p.apps.googleusercontent.com';
		return get_option('cftp_popular_google_analytics_client_id');
	}

	public function getClientSecret() {
		//return 'Nl8codQLU7JiuX57Rm6RLasy';
		return get_option('cftp_popular_google_analytics_client_secret');
	}

	public function getRedirectURL() {
		return admin_url().'options-general.php?page=cftp_popular_settings_page';
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

	function query_widget_order( $orders ) {
		$orders['google_last30'] = 'GA Page Views last '.$this->getPostAge();
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
	 */
	public function registerSettings( $option_group, $section_id, $page ) {
		$option_name = 'cftp_popular_google_analytics';
		register_setting(
			$option_group, // Option group
			$option_name // Option name
		);
		register_setting(
			$option_group, // Option group
			$option_name.'_client_id' // Option name
		);
		register_setting(
			$option_group, // Option group
			$option_name.'_client_secret' // Option name
		);
		register_setting(
			$option_group, // Option group
			$option_name.'_post_age' // Option name
		);
		add_settings_field(
			$option_name.'_client_id', // ID
			'Google Analytics Client ID', // Title
			array( $this, 'displayClientID' ), // Callback
			$page, // Page
			$section_id // Section
		);
		add_settings_field(
			$option_name.'_client_secret', // ID
			'Google Analytics Client Secret', // Title
			array( $this, 'displayClientSecret' ), // Callback
			$page, // Page
			$section_id // Section
		);
		add_settings_field(
			$option_name.'_redirect_field', // ID
			'Google Analytics Client Redirect URL', // Title
			array( $this, 'displayRedirectURL' ), // Callback
			$page, // Page
			$section_id // Section
		);

		add_settings_field(
			$option_name.'_post_age', // ID
			'Show Page Views for last…', // Title
			array( $this, 'displayPostAge' ), // Callback
			$page, // Page
			$section_id // Section
		);

		add_settings_field(
			$option_name, // ID
			'Google Analytics', // Title
			array( $this, 'displaySettings' ), // Callback
			$page, // Page
			$section_id // Section
		);

	}

	public function displayClientID() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_id" value="<?php echo $this->getClientID(); ?>"/>
		<?php
	}
	public function displayClientSecret() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_secret" value="<?php echo $this->getClientSecret(); ?>"/>
		<?php
	}
	public function displayRedirectURL() {
		?>
		<input class="widefat" name="cftp_popular_google_analytics_client_redirect_url" value="<?php echo $this->getRedirectURL(); ?>" disabled />
		<p class="description">You'll need to create an API ID and secret, save them here, then use the above redirect URL in the google cloud panel before authenticating</p>
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

		$token = get_option( 'cftp_popular_ga_token' );
		if ( !empty( $token ) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 */
	public function displaySettings() {

		$this->initialiseAPIs();
		if ( !class_exists( 'Google_Client' ) ) {
			return;
		}
		if ( !$this->isConfigured() ) {

			try {
				$authUrl = $this->client->createAuthUrl();
				?>
				<a href="<?php echo $authUrl; ?>" class="button">Activate Google Analytics</a>
				<?php
			} catch ( Google_IOException $e ) {
				wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
			}
		} else {
			?>
			<a class="button disabled" disabled >Deactivate Google Analytics</a>
		<?php
		}
	}

	/**
	 * @param Google_Profile $profile
	 * @param                $url
	 *
	 * @return mixed
	 */
	private function getPageViewsURL( Google_Service_Analytics_Profile $profile, $url ) {
		$this->initialiseAPIs();
		$url = trailingslashit( $url );
		$to = date('Y-m-d');
		$from = strtotime( $to.' -'.$this->getPostAge() );
		$from = date( 'Y-m-d', $from );
		$data = $this->service->data_ga->get(
			'ga:'.$profile->id,
			$from,
			$to,
			'ga:pageviews',
			array(
				'dimensions' => 'ga:pageTitle,ga:pagePath',
				'sort' => '-ga:pageviews',
				'filters' => 'ga:pagePath=~'.$url,
				'max-results' => '1'
			)
		);

		echo 'GA from = ', $from,"\n";

		$result = $data->totalsForAllResults['ga:pageviews'];
		return $result;
	}

	/**
	 * @param $url
	 *
	 * @return null
	 */
	private function getProfileIDByURL( $url ) {
		$this->initialiseAPIs();
		$current = $this->getWebProperty( $url );
		if ( $current != null ) {
			try {
				$profile = $this->getFirstProfile( $current );
				return $profile;
			} catch ( Google_ServiceException $e ) {
				print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
			} catch ( Google_IOException $e ) {
				print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
			}
		} else {
			echo 'current is null?!';
		}
		return null;
	}

	/**
	 * @param $url
	 *
	 * @return null
	 */
	private function getWebProperty( $url ) {
		$this->initialiseAPIs();
		if ( strpos( $url,'/') == 0 ) {
			$url = site_url();
		}
		try {
			$props = $this->service->management_webproperties->listManagementWebproperties("~all");
			foreach ( $props->items as $prop ) {
				if ( $url == $prop->websiteUrl ) {
					return $prop;
				}
			}
			echo 'not found property..';
		} catch ( Google_ServiceException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		} catch ( Google_IOException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		}
		return null;
	}

	/**
	 * @param Google_Webproperty $property
	 *
	 * @return null
	 */
	private function getFirstProfile( Google_Service_Analytics_Webproperty  $property ) {
		$this->initialiseAPIs();
		$profiles = $this->service->management_profiles->listManagementProfiles( $property->accountId, $property->id );
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
	 */
	public function getPageViewsByPostID( $post_id ) {
		$this->initialiseAPIs();
		$permalink = get_permalink( $post_id );
		$permalink = str_replace( site_url(), '', $permalink );
		return $this->getPageViewsForURL( $permalink );
	}
}