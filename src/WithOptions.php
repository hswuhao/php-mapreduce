<?php
/*
 * Provides access to a set of configuration options.
 * Functions using this trait have to:
 *  - declare a static $defaults variable
 *  - call $this->reset_options() in order to load defaults
 *  - call $this->options(...) to get/set options
 */
trait WithOptions {
	protected $opts = array();
	
	// reads options from static::$defaults, if it exists
	// $clear (boolean): if true, options are emptied before
	public function reset_options ($clear = false) {
		if ( isset(static::$defaults) ) {
			if ( $clear ) {
				$this->opts = array();
			}
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
		if ( $key === null ) {
			// 1
			return $this->opts;
		}
		if ( !is_array($key) ) {
			if ( $value === null ) {
				// 2
				return $this->opts[$key];
			} else {
				// 3
				// convert $key and $value to array and go on to 4
				$key = array ( $key => $value );
			}
		}
		// 4
		foreach ( $key as $k => $v ) {
			assert( isset($this->opts[$k]), "Wrong options key: $k" );
			$this->opts[$k] = $v;
		}
	}
}
