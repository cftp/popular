<?php


/**
 * Class cftp_analytics_option_model
 */
class cftp_analytics_option_model implements cftp_analytics_model {

	private $sources = array();
	/**
	 *
	 */
	public function __construct() {
		//
	}

	/**
	 * @return string
	 */
	public function modelType() {
		return 'option';
	}

	/**
	 * @param cftp_analytics_source $source
	 */
	public function addSource( cftp_analytics_source $source ) {
		$this->sources[] = $source;
	}

	/**
	 * @param cftp_analytics_source $source
	 */
	public function removeSource( cftp_analytics_source $source ) {
		// TODO: Implement removeSource() method.
	}

	/**
	 *
	 */
	public function getSources() {
		return $this->sources;
	}
}