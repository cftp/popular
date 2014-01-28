<?php

class cftp_null_analytics_source implements cftp_analytics_source {

	public function __construct() {
		//
	}

	public function sourceName() {
		return 'nullsource';
	}
} 