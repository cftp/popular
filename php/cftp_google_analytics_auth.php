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
			//$this->client->setRedirectUri( $this->getRedirectURL() );

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
}