<?php

/**
 * Class cftp_analytics
 */
class cftp_analytics {

	private $factory = null;
	private $model = null;
	private $cron_tasks = array();

	private $setting_page = null;

	/**
	 *
	 */
	public function __construct( cftp_analytics_factory $factory, cftp_analytics_model $model ) {
		$this->factory = $factory;
		$this->model = $model;
		$this->setting_page = $factory->settingPage( $model );
		$sources = $model->getSources();
		foreach ( $sources as $source ) {
			$this->cron_tasks[] = $factory->cronTask( $source );
		}
	}

	public function run() {
		$this->setting_page->setup();
		foreach ( $this->cron_tasks as $task ) {
			$task->run();
		}
	}

}
