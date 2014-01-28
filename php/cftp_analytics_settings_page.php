<?php

class cftp_analytics_settings_page {

	private $model = null;

	public function __construct( cftp_analytics_model $model ) {
		$this->model = $model;
	}
}