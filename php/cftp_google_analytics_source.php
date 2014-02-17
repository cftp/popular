<?php

/**
 * Class cftp_google_analytics_source
 */
class cftp_google_analytics_source implements cftp_analytics_source {

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
		$this->client->setUseObjects(true );
		$this->service = new Google_AnalyticsService( $this->client );

		$token = '';

		if ( isset( $_GET['code'] ) ) {
			try {
				$this->client->authenticate();
				$newtoken = $this->client->getAccessToken();
				update_option('cftp_popular_ga_token', $newtoken );
				$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
				wp_safe_redirect( $redirect );
			} catch ( Google_IOException $e ) {
				wp_die( 'Unrecoverable error, please try re-authenticating to recover. Google IO Exception thrown with message: '.$e->getMessage());
			}
		} else {
			$token = get_option( 'cftp_popular_ga_token' );
		}
		if ( !empty( $token ) ) {
			$this->client->setAccessToken( $token );
		}

		add_filter( 'kqw_orderby_options', array( $this, 'query_widget_order' ) );
		add_filter( 'request', array( $this, 'orderby' ) );
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
		return admin_url().'/options-general.php?page=cftp_popular_settings_page';
	}

	function query_widget_order( $orders ) {
		$orders['google_last30'] = 'GA Page Views last ~30 days';
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
		$defaults['google_last30'] = 'Page Views ~30 days';
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ( $column_name == 'google_last30' ) {
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
		add_settings_field(
			$option_name.'_client_id', // ID
			'GA Client ID', // Title
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
		<?php
	}

	/**
	 * @return bool
	 */
	public function isConfigured() {
		if ( $this->client->getAccessToken() ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 */
	public function displaySettings() {
		if ( !$this->isConfigured() ) {
			$authUrl = $this->client->createAuthUrl();
			?>
			<a href="<?php echo $authUrl; ?>" class="button">Activate Google Analytics</a>
		<?php
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
	private function getPageViewsURL( Google_Profile $profile, $url ) {
		$url = trailingslashit( $url );
		$to = date('Y-m-d');
		$from = strtotime( $to.' -30 day' );
		$from = date( 'Y-m-d', $from );
		$data = $this->service->data_ga->get(
			'ga:'.$profile->id,
			$from,
			$to,
			'ga:pageviews',
			array(
				'dimensions' => 'ga:pageTitle,ga:pagePath',
				'sort' => '-ga:pageviews',
				'filters' => 'ga:pagePath=='.$url,
				'max-results' => '1'));

		$result = $data->totalsForAllResults['ga:pageviews'];
		return $result;
	}

	/**
	 * @param $url
	 *
	 * @return null
	 */
	private function getProfileIDByURL( $url ) {
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
	private function getFirstProfile( Google_Webproperty $property ) {
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
		$permalink = get_permalink( $post_id );
		$permalink = str_replace( site_url(), '', $permalink );
		return $this->getPageViewsForURL( $permalink );
	}
}

