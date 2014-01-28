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
		add_options_page(
			'Popular',
			'Popular Settings',
			'manage_options',
			'cftp_popular_settings_page',
			array( $this, 'options_page' )
		);
	}

	public function register_settings() {
		$option_group = 'cftp-popular-settings-group';
		$sources = $this->model->getSources();
		$auth_section = 'cftp_popular_setting_auth_section';
		$page = 'cftp_popular_settings_page';
		add_settings_section(
			$auth_section, // ID
			'Services', // Title
			array( $this, 'sectionUI' ), // Callback
			$page // Page
		);

		foreach ( $sources as $source ) {
			$source->registerSettings( $option_group, $auth_section, $page );
		}
		register_setting( $option_group, 'new_option_name' );

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
				<?php do_settings_sections( 'cftp_popular_settings_page' ); ?>
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