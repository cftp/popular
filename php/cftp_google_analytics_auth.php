<?php

class cftp_google_analytics_auth {

	public function __construct() {
		// @TODO: there's something inherently wrong about creating the google client in here
		// must consider using an object factory and passing in as a parameter instead
		if ( !empty( $_GET['code'] ) ) {
			$this->initialiseAPIs();
		}
	}

	/**
	 * @var Google_Service_Analytics|null
	 */
	public $service;
	private $scope = 'https://www.googleapis.com/auth/analytics.readonly';

	/**
	 * @var Google_Client|null
	 */
	public $client = null;

	public $errors = array();

	public function getScope() {
		return $this->scope;
	}


	public function getClientID() {
		return get_option('cftp_popular_google_analytics_client_id');
	}

	public function getClientSecret() {
		return get_option('cftp_popular_google_analytics_client_secret');
	}

	public function getRedirectURL() {
		return admin_url().'options-general.php?page=cftp_popular_settings_page';
	}

	public function getCurrentProfile() {
		return $this->getProfileIDByURL( home_url() );
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

	public function getAuthURL() {
		$url = $this->client->createAuthUrl();
		return $url;
	}

	/**
	 * @return bool
	 */
	public function initialiseAPIs() {

		if ( $this->client != null ) {
			return true;
		}
		if ( !class_exists( 'Google_Client' ) ) {
			echo '<p><strong>Warning: The <code>Google_Client</code> class doesn\'t exist, did you run composer install to pull down the Google API library?</strong></p>';
			return false;
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

			$scope = $this->getScope();

			$this->client->setScopes( array( $scope ) );

			$this->service = new Google_Service_Analytics( $this->client );
		} catch ( Google_IO_Exception $e ) {
			$this->errors[] = $e;
			return false;
		} catch ( Google_Service_Exception $e ) {
			$this->errors[] = $e;
			return false;
		} catch ( Google_Auth_Exception $e ) {
			$this->errors[] = $e;
			return false;
		} catch ( Google_Exception $e ) {
			$this->errors[] = $e;
			return false;
		}

		$token = '';

		if ( isset( $_GET['code'] ) ) {
			try {
				$code = $_GET['code'];
				$this->client->authenticate( $code );
				$new_token = $this->client->getAccessToken();
				update_option('cftp_popular_ga_token', $new_token );
				$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
				wp_redirect( $redirect );
			} catch ( Google_IO_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Service_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Auth_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Exception $e ) {
				$this->errors[] = $e;
				return false;
			}
		} else {
			$token = get_option( 'cftp_popular_ga_token' );
		}

		if ( !empty( $token ) ) {
			try {
				$this->client->setAccessToken( $token );
			} catch ( Google_IO_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Service_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Auth_Exception $e ) {
				$this->errors[] = $e;
				return false;
			} catch ( Google_Exception $e ) {
				$this->errors[] = $e;
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function getAllAccounts() {
		$accounts = get_transient( 'cftp_popular_ga_accounts' );
		if ( $accounts === false ) {
			// It wasn't there, so regenerate the data and save the transient
			$accounts = $this->retrieveAllAccounts();
			set_transient( 'cftp_popular_ga_accounts', $accounts, DAY_IN_SECONDS );
		}
		return $accounts;
	}

	/**
	 * @return array
	 */
	public function retrieveAllAccounts() {
		$this->initialiseAPIs();
		$accounts = $this->service->management_accounts->listManagementAccounts();
		$arr_accounts = array();
		/** @var Google_Service_Analytics_Account $a */
		foreach( $accounts as $a ) {
			$arr_accounts[ $a->getId() ] = array(
				'id' => $a->getId(),
				'name' => $a->getName()
			);
		}
		return $arr_accounts;
	}

	/**
	 * Returns the cached array of web properties from the Google API
	 *
	 * @return array
	 */
	public function getAllWebProperties() {
		$properties = get_transient( 'cftp_popular_ga_webproperties' );
		if ( $properties === false ) {
			// It wasn't there, so regenerate the data and save the transient
			$properties = $this->retrieveAllWebProperties();
			set_transient( 'cftp_popular_ga_webproperties', $properties, DAY_IN_SECONDS );
		}
		return $properties;
	}

	/**
	 * Retrieves a fresh array of web properties from the Google API
	 *
	 * @return array
	 * @see getAllWebProperties
	 */
	public function retrieveAllWebProperties() {
		$this->initialiseAPIs();
		$properties = $this->service->management_webproperties->listManagementWebproperties( '~all' );
		$arr_properties = array();
		/** @var Google_Service_Analytics_Webproperty $p */
		foreach ( $properties as $p ) {
			$arr_properties[ $p->getId() ] = array(
				'id' => $p->getId(),
				'accountId' => $p->getAccountId(),
				'name' => $p->getName(),
				'profileCount' => $p->profileCount,
				'websiteUrl' => $p->getWebsiteUrl()
			);
		}
		return $arr_properties;
	}

	/**
	 * @return array
	 */
	public function getAllProfiles() {
		$profiles = get_transient( 'cftp_popular_ga_profiles' );
		if ( $profiles === false ) {
			// It wasn't there, so regenerate the data and save the transient
			$profiles = $this->retrieveAllProfiles();
			set_transient( 'cftp_popular_ga_profiles', $profiles, DAY_IN_SECONDS );
		}
		return $profiles;
	}

	/**
	 * @return array
	 */
	public function retrieveAllProfiles() {
		$this->initialiseAPIs();
		$profiles = $this->service->management_profiles->listManagementProfiles( '~all', '~all' );
		$arr_profiles = array();
		/** @var Google_Service_Analytics_Profile $p */
		foreach ( $profiles as $p ) {
			$arr_profiles[ $p->getId() ] = array(
				'id' => $p->getId(),
				'name' => $p->getName(),
				'accountId' => $p->getAccountId(),
				'webPropertyId' => $p->getWebPropertyId(),
				'websiteUrl' => $p->getWebsiteUrl()
			);
		}
		return $arr_profiles;
	}

	/**
	 * @param $url
	 *
	 * @return array|null
	 */
	public function getWebProperty( $url ) {
		if ( strpos( $url,'/') == 0 ) {
			$url = site_url();
		}
		$profiles = array();
		$matches = array();
		try {
			$props = $this->getAllWebProperties();
			foreach ( $props as $prop ) {
				$match = $this->match_urls( $url, $prop['websiteUrl'] );
				if ( $match == 0 ) {
					continue;
				}
				$profiles[ $prop['websiteUrl'] ] = $prop;
				$matches[ $prop['websiteUrl'] ] = $match;
			}
		} catch ( Google_Service_Exception $e ) {
			$this->errors[] = $e;
		} catch ( Google_IO_Exception $e ) {
			$this->errors[] = $e;
		} catch ( Google_Auth_Exception $e ) {
			$this->errors[] = $e;
		} catch ( Google_Exception $e ) {
			$this->errors[] = $e;
		}

		if ( !empty( $matches ) ) {
			arsort( $matches );
			foreach ( $matches as $key => $value ) {
				$profile = $profiles[$key];
				return $profile;
			}
		}
		return null;
	}

	/**
	 * @param $url string The URL we're basing our search on
	 * @param $property string The property URL to test
	 *
	 * @return number
	 */
	private function match_urls( $url, $property ) {
		$url = parse_url( $url, PHP_URL_HOST );
		$property = parse_url( $property, PHP_URL_HOST );
		if ( strstr( $url, $property ) ) {
			if ( $url == $property ) {
				return 2;
			}
			return 1;
		}
		return 0;
	}

	/**
	 * @param array $property
	 *
	 * @return null
	 */
	private function getFirstProfile( array $property ) {
		$profiles = $this->getAllProfiles();
		$prop_id = $property['id'];
		$acc_id = $property['accountId'];
		$xprofiles = array_filter( $profiles, function ( $arr ) use ( $prop_id, $acc_id ) {
			if ( ( $arr['webPropertyId'] == $prop_id ) && ( $arr['accountId'] == $acc_id ) ) {
				return true;
			}
			return false;
		} );
		if ( !empty( $xprofiles ) ) {
			foreach ( $xprofiles as $key => $prop ) {
				return $prop;
			}
		}
		return null;
	}

	/**
	 * Return a Profile object given a URL
	 *
	 * @param $url string A URL to compare against, must match a web property in full
	 *
	 * @return Google_Service_Analytics_Profile|null
	 */
	public function getProfileIDByURL( $url ) {
		$current = $this->getWebProperty( $url );
		if ( $current != null ) {
			try {
				$profile = $this->getFirstProfile( $current );
				return $profile;
			} catch ( Google_Service_Exception $e ) {
				$this->errors[] = $e;
			} catch ( Google_IO_Exception $e ) {
				$this->errors[] = $e;
			} catch ( Google_Auth_Exception $e ) {
				$this->errors[] = $e;
			} catch ( Google_Exception $e ) {
				$this->errors[] = $e;
			}
		}
		return null;
	}
}