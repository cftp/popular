<?php

/**
 * Class cftp_analytics
 */
class cftp_analytics {

	private $factory = null;
	private $model = null;

	/**
	 *
	 */
	public function __construct( cftp_analytics_factory $factory, cftp_analytics_model $model ) {
		$this->factory = $factory;
		$this->model = $model;
	}

}
