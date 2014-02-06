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
		// @TODO: there's something inherently wrong about creating the google client in here
		// must consider using an object factory and passing in as a parameter instead
		$this->client = new Google_Client();

		$this->client->setApprovalPrompt("force");
		$this->client->setAccessType('offline');

		$this->client->setApplicationName( "CFTP Popular" );

		// Visit https://code.google.com/apis/console?api=analytics to generate your
		// client id, client secret, and to register your redirect uri.
		$this->client->setClientId('428049761702-ns5qdmhmstupbpi22oo9iokohq153m5p.apps.googleusercontent.com');
		$this->client->setClientSecret('Nl8codQLU7JiuX57Rm6RLasy');
		$this->client->setRedirectUri('http://www.tomjn.com/wp-admin/options-general.php?page=cftp_popular_settings_page');
		//$this->client->setDeveloperKey('insert_your_developer_key');

		$this->client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
		$this->client->setUseObjects(true);
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

		add_settings_field(
			$option_name, // ID
			'Google Analytics', // Title
			array( $this, 'displaySettings' ), // Callback
			$page, // Page
			$section_id // Section
		);
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
			echo '<h4>Current site</h4>';

			echo '<pre>'.print_R( wp_get_schedules(), true ).'</pre>';
			echo '<ul>';
			$urls = array(
				'/',
				'/projects',
				'/341/crazyflie-nano-quadcopter-notes/',
				'/528/calling-constructors/'
			);
			foreach ( $urls  as $url ) {
				echo '<li>'.$url.' '.$this->getPageViewsForURL( $url );
			}
			echo '</ul>';
			$id = $this->getPageViewsByPostID( 528 );
			echo '528 id is: '.$id;

			/*$current = $this->getWebProperty( home_url() );
			if ( $current != null ) {
				echo '<p><strong>'.$current->accountId.' '.$current->id.' '.$current->internalWebPropertyId.' '.$current->name.' at '.$current->websiteUrl.'</strong></p>';
				try {
					$profile = $this->getFirstProfile( $current );
					echo '<p><strong>'.$prop->id.' '.$prop->name.'</strong></p>';
					$this->displayPageViewsURL( $profile, '/' );
					$this->displayPageViewsURL( $profile, '/341/crazyflie-nano-quadcopter-notes/' );
					$this->displayPageViewsURL( $profile, '/projects/' );
				} catch ( Google_ServiceException $e ) {
					print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
				} catch ( Google_IOException $e ) {
					print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
				}
			} else {
				echo '<p>Error occurred</p>';
			}*/
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
		/*if ( $result == 0 ) {
			echo '<pre>'.print_r( $data, true ).'</pre>';
		}*/
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
	 * @param Google_Profile $profile
	 * @param                $url
	 */
	private function displayPageViewsURL( Google_Profile $profile, $url ) {
		try {
			$data = $this->getPageViewsURL( $profile, $url );
			echo '<table>';
			echo '<thead><tr><th>'.$data->columnHeaders[0]->name.'</th><th>'.$data->columnHeaders[1]->name.'</th><th>'.$data->columnHeaders[2]->name.'</th></tr></thead>';
			foreach ( $data->rows as $row ) {
				echo '<tr><td>'.$row[0].'</td><td>'.$row[1].'</td><td>'.$row[2].'</td></tr>';
			}
			echo '</table>';
			//echo "<pre>" . print_r( $data, true) . "</pre>";
		} catch( Google_ServiceException $e ) {
			echo 'Google_ServiceException thrown with message: '.$e->getMessage();
		}
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
		} else {
			return 'unknown profile?';
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

// Exceptions that the Demo can throw.
class demoException extends Exception {}
