<?php

// GET FEATURED IMAGE
function ST4_get_featured_image($post_ID) {
	$post_thumbnail_id = get_post_thumbnail_id($post_ID);
	if ($post_thumbnail_id) {
		$post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'featured_preview');
		return $post_thumbnail_img[0];
	}
}

class cftp_analytics_settings_page {

	private $model = null;

	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;
	}

	public function setup() {
		if ( is_admin() ){
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			add_filter('manage_posts_columns', array( $this, 'columns_head') );
			add_action('manage_posts_custom_column', array( $this, 'columns_content' ), 10, 2);

		} else {
			// non-admin enqueues, actions, and filters
		}
	}

	function columns_head($defaults) {
		$defaults['popular_views'] = 'Page Views ~30 days';
		return $defaults;
	}

	function columns_content( $column_name, $post_id ) {
		if ($column_name == 'popular_views') {
			foreach ( $this->model->getSources() as $source ) {
				$source_name = $source->sourceName();
				$views = get_post_meta( $post_id, 'cfto_popular_views_'.$source_name, true );
				if ( !empty( $views ) ) {
					echo $views;
				} else {
					echo 'unknown';
				}
			}
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

	}

	public function sectionUI() {
		echo 'These are the available services you can configure';
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