<?php

interface cftp_analytics_model {
	public function modelType();

	public function addSource( cftp_analytics_source $source );
	public function removeSource( cftp_analytics_source $source );
	public function getSources();
}
