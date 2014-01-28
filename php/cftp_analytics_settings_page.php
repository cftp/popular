<?php

class cftp_analytics_settings_page {

	private $model = null;

	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;
	}

	public function setup() {
		if ( is_admin() ){
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		} else {
			// non-admin enqueues, actions, and filters
		}
	}

	public function add_menu() {
		add_submenu_page( 'options-general.php', 'Popular', 'Popular', 'manage_options', 'cftp_popular_settings_page', array( $this, 'options_page' ) );
		//add_menu_page('Popular', 'Popular', 'administrator', __FILE__, 'cftp_popular_settings_page', plugins_url('/images/icon.png', __FILE__) );
	}

	public function register_settings() {
		$sources = $this->model->getSources();
		add_settings_section(
			'cftp_popular_setting_auth_section', // ID
			'Services', // Title
			array( $this, 'sectionUI' ), // Callback
			'cftp_popular_settings_page' // Page
		);

		foreach ( $sources as $source ) {
			$source->registerSettings();
		}
		register_setting( 'cftp-popular-settings-group', 'new_option_name' );

	}

	public function sectionUI() {
		echo 'hola';
	}

	public function options_page() {
		?>
		<div class="wrap">
			<h2>Popular</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'cftp-popular-settings-group' ); ?>
				<?php do_settings_sections( 'cftp-popular-settings-group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">New Option Name</th>
						<td><input type="text" name="new_option_name" value="<?php echo get_option('new_option_name'); ?>" /></td>
					</tr>
				</table>

				<?php submit_button(); ?>

			</form>
		</div>
	<?php
	}
}