<?php

/**
 * Class cftp_analytics_factory
 */
class cftp_analytics_factory {
	/**
	 * @var cftp_analytics_model|null
	 */
	private $model = null;

	/**
	 * @param cftp_analytics_model $model
	 */
	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;
	}
}
