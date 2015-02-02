<?php
/*
 * Provides access to a set of configuration options.
 * Functions using this trait have to:
 *  - declare a static $defaults variable
 *  - call $this->load_defaults() in order to load defaults
 *  - call $this->options(...) to get/set options
 */
trait WithOptions {
	protected $opts = null;
	
	public static function get_defaults () {
		return isset(static::$defaults) ? static::$defaults : null;
	}
	
	// reads options from static::$defaults, if it exists
	// $clear (boolean): if true, options are emptied before
	private function load_defaults ($clear = false) {
		if ( $clear ) {
			$this->opts = array();
		}
		if ( isset(static::$defaults) ) {
			foreach ( static::$defaults as $k => $v ) {
				$this->opts[$k] = $v;
			}
		}
	}
	
	/* 
     * Gets/sets options
     * Depending on the passed values of $key and $value parameters, the function:
     *  1) null / whatever --> gets array with all options
	 *  2) key / null --> gets option key
	 *  3) key / value --> sets option key to value
	 *  4) array / whatever --> sets options according to each key => value of the array
     */
	public function options ($key = null, $value = null) {
		if ( is_null($this->opts) ) {
			$this->load_defaults(true);
		}
		
		if ( $key === null ) {
			// 1
			return $this->opts;
		}
		if ( !is_array($key) ) {
			if ( $value === null ) {
				// 2
				if ( !isset($this->opts[$key]) ) {
					throw new Exception("Option $key does not exist.");
				}
				return $this->opts[$key];
			} else {
				// 3
				// convert $key and $value to array and go on to 4
				$key = array ( $key => $value );
			}
		}
		// 4
		foreach ( $key as $k => $v ) {
			if ( !isset($this->opts[$k]) ) {
				throw new Exception("Option $k does not exist.");
			}
			$this->opts[$k] = $v;
		}
	}
}
