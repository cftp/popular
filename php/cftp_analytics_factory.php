<?php

class cftp_analytics_factory {
	private $model = null;
	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;
	}
}
