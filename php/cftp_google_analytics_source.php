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
			echo '<ul>';
			$urls = array(
				'/',
				'/projects',
				'/341/crazyflie-nano-quadcopter-notes/'
			);
			foreach ( $urls  as $url ) {
				echo '<li>'.$url.' '.$this->getPageViewsForURL( $url );
			}
			echo '</ul>';

			$current = $this->getWebProperty( home_url() );
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
			}

			/*$props = $this->service->management_webproperties->listManagementWebproperties("~all");
			print "<h4>Web Properties</h4>";
			//echo "<pre>" . print_r($props, true) . "</pre>";
			echo '<ul>';
			foreach ( $props->items as $prop ) {
				echo '<li style="padding:1em; margin:0 1em;">';
				echo '<h5>'.$prop->accountId.' '.$prop->id.' '.$prop->internalWebPropertyId.' '.$prop->name.' at '.$prop->websiteUrl.'</h5>';
				try {
					$profiles = $this->service->management_profiles->listManagementProfiles( $prop->accountId, $prop->id );
					echo '<ul>';
					foreach ( $profiles->items as $prop ) {

						echo '<li>'.$prop->id.' '.$prop->name;
						$this->display( $prop->id );
						echo '</li>';
					}
					echo '</ul>';
				} catch ( Google_ServiceException $e ) {
					print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
				}
				echo '</li>';
			}
			echo '</ul>';
			$accounts = $this->service->management_accounts->listManagementAccounts();
			print "<h4>Accounts</h4>";
			echo '<ul>';
			foreach ( $accounts->items as $account ) {
				echo '<li>';
				echo $account->id.': '.$account->name;
				echo'</li>';
			}
			echo '</ul>';*/
		}
	}

	private function display( Google_Profile $profile ) {
		try {
			$data = $this->service->data_ga->get(
				'ga:'.$profile->id,
				'2013-01-01',
				'2014-01-01',
				'ga:pageviews',
				array(
					'dimensions' => 'ga:pageTitle,ga:pagePath',
					'sort' => '-ga:pageviews',
					'filters' => 'ga:pagePath!=/',
					'max-results' => '10'));
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

	private function getPageViewsURL( Google_Profile $profile, $url ) {
		$data = $this->service->data_ga->get(
			'ga:'.$profile->id,
			'2013-01-01',
			'2014-01-01',
			'ga:pageviews',
			array(
				'dimensions' => 'ga:pageTitle,ga:pagePath',
				'sort' => '-ga:pageviews',
				'filters' => 'ga:pagePath=='.$url,
				'max-results' => '1'));
		return $data;
	}

	private function getProfileIDByURL( $url ) {
		//
	}

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

	private function getWebProperty( $url ) {
		try {
			$props = $this->service->management_webproperties->listManagementWebproperties("~all");
			foreach ( $props->items as $prop ) {
				if ( $url == $prop->websiteUrl ) {
					return $prop;
				}
			}
		} catch ( Google_ServiceException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		} catch ( Google_IOException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		}
		return null;
	}

	private function getCurrentSiteWebProperty() {
		try {
			$props = $this->service->management_webproperties->listManagementWebproperties("~all");
			foreach ( $props->items as $prop ) {
				if ( home_url() == $prop->websiteUrl ) {
					return $prop;
				}
			}
		} catch ( Google_ServiceException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		} catch ( Google_IOException $e ) {
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
		}
		return null;
	}

	private function getProfileID( Google_Account $account ) {
		$profile_id = $account->id;
		if (empty($profile_id)) {
			return false;
		}

		$account_array = array();
		array_push($account_array, array('id'=>$profile_id, 'ga:webPropertyId'=>$webproperty_id));
		echo '<pre>'.print_r( $account_array, true ).'</pre>';
	}

	private function getFirstProfile( Google_Webproperty $property ) {
		$profiles = $this->service->management_profiles->listManagementProfiles( $property->accountId, $property->id );
		if ( !empty( $profiles ) ) {
			foreach ( $profiles->items as $prop ) {
				return $prop;
			}
		}
		return null;
	}

	public function getPageViewsForURL( $url ) {
		$property = $this->getWebProperty( $url );
		if ( $property != null ) {
			$profile = $this->getFirstProfile( $property );
			$views = $this->getPageViewsURL( $profile, $url);
			return $views;
		}
		return false;
	}
}

// Exceptions that the Demo can throw.
class demoException extends Exception {}
