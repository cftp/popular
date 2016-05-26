<?php

class cftp_analytics_settings_page {

	private $model = null;

	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;

		register_activation_hook( '/popular/plugin.php', array( $this, 'cftp_popular_activate' ) );
	}


	/**
	 * Plugin activation hook.
	 *
	 * Popular has loads of columns - hide most (except Total views, Total shares) by default for reasons of space.
	 * Use the 'Screen Options' on edit posts page. Involves looping through existing setting for all users.
	 *
	 * @author William Turrell
	 * @return bool
	 */
	function cftp_popular_activate() {

		$success = true;    // in case of problem with one of the users

		$key = 'manageedit-postcolumnshidden';  // wp_usermeta.meta_key

		$cols_to_hide = array( 'twitter_shares', 'facebook_likes', 'facebook_shares', 'decay_views', 'decay_shares' );
		$cols_to_show = array( 'google_last30', 'total_shares' );

		$users = get_users();


		foreach ( $users as $user ) {

			$new_opt = array();

			// Get the user's current settings, if they have any
			$current = get_user_option( $key, $user->ID );

			if ( is_array( $current ) ) {

				foreach ( $current as $c ) {
					if ( ! empty( $c ) && ! in_array($c, $cols_to_show)) {
						// don't preserve empty "" strings or any columns we want to show by default
						$new_opt[] = $c;
					}
				}
			}

			// Hide it, if not already hidden
			foreach ( $cols_to_hide as $c ) {
				if ( ! in_array( $c, $new_opt ) ) {
					$new_opt[] = $c;
				}
			}

			// Save new settings (without $global = true, wp_ prefix will be prepended to the option)
			if (! update_user_option( $user->ID, $key, $new_opt, true) ) {
				$success = false;
			}
		}

		return $success;
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
			'Popular Settings',
			'Popular',
			'manage_options',
			'cftp_popular_settings_page',
			array( $this, 'options_page' )
		);
	}

	public function register_settings() {
		$option_group = 'cftp-popular-settings-group';
		$sources = $this->model->getSources();
		$header_section = 'cftp_popular_setting_header_section';
		$auth_section = 'cftp_popular_setting_auth_section';
		$page = 'cftp_popular_settings_page';

		add_settings_section(
			$header_section, // ID
			'How it works', // Title
			array( $this, 'headerUI' ), // Callback
			$page // Page
		);

		add_settings_section(
			$auth_section, // ID
			'Services', // Title
			array( $this, 'sectionUI' ), // Callback
			$page // Page
		);

		foreach ( $sources as $source ) {
			$source->registerSettings( $option_group, $auth_section, $page );
		}

	}

	public function headerUI() {
		echo '<p>When you activate the plugin, we only show two extra columns on the  <a href="'.esc_url( admin_url( 'edit.php' ) ).'">Posts</a>
		page – <em>Recent Views</em> and <em>Total Shares</em> – for reasons of space.  However there\'s plenty more you can activate by using <em>Screen Options</em>.</p>';

		echo "<p>Data is collected periodically. If it hasn't been yet (or there's a problem), you'll see a clock icon: <span title='pending' class='dashicons dashicons-clock'></span></p>";
		echo "<p>Twitter counts are powered by OpenShareCount, and will need the domain to be setup with their service. <a href='https://opensharecount.com/'>See here for more information</a>.</p>";
	}

	public function sectionUI() {
		echo 'These are the available services you can configure:';
	}

	public function options_page() {
		?>
		<div class="wrap">
			<h2>Popular</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'cftp-popular-settings-group' ); ?>
				<?php do_settings_sections( 'cftp_popular_settings_page' ); ?>

				<?php submit_button(); ?>

			</form>
		</div>
	<?php
	}
}
