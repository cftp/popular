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
		$this->client->setApplicationName( "CFTP Popular" );

		// Visit https://code.google.com/apis/console?api=analytics to generate your
		// client id, client secret, and to register your redirect uri.
		$this->client->setClientId('428049761702-ns5qdmhmstupbpi22oo9iokohq153m5p.apps.googleusercontent.com');
		$this->client->setClientSecret('Nl8codQLU7JiuX57Rm6RLasy');
		$this->client->setRedirectUri('https://cyclingweekly.keystone.ipc/wp-admin/options-general.php?page=cftp_popular_settings_page');
		//$this->client->setDeveloperKey('insert_your_developer_key');

		$this->client->setScopes( array( ANALYTICS_SCOPE ) );
		$this->client->setUseObjects(true);
		$this->service = new Google_AnalyticsService( $this->client );
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
			$props = $this->service->management_webproperties->listManagementWebproperties("~all");
			print "<h1>Web Properties</h1><pre>" . print_r($props, true) . "</pre>";

			$accounts = $this->service->management_accounts->listManagementAccounts();
			print "<h1>Accounts</h1><pre>" . print_r($accounts, true) . "</pre>";

			$segments = $this->service->management_segments->listManagementSegments();
			print "<h1>Segments</h1><pre>" . print_r($segments, true) . "</pre>";

			$goals = $this->service->management_goals->listManagementGoals("~all", "~all", "~all");
			print "<h1>Segments</h1><pre>" . print_r($goals, true) . "</pre>";
		}
	}
}
