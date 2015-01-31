<?php
require_once 'WithOptions.php';

class MapReduce {
	use WithOptions;
	
	const progress_start   = 0;
	const progress_numline = 1;
	const progress_finish  = 2;
	
	public static $defaults = array (
		'in_memory' => true, // not used
		'grouped' => false,
		'progress_callback' => false,
		'progress_each' => 10000,
	);
	
	private $input = false;
	private $map = false;
	private $reduce = false;
	private $outputs = false;
	
	public function __construct ($input, Closure $map, Closure $reduce, $outputs, $options = array()) {
		if ( is_array($input) ) {
			$input = new ArrayIterator($input);
		} else if ( ! $input instanceOf Traversable ) {
			throw new Exception('Input is not Traversable.');
		}
		
		if ( !is_array($outputs) ) {
			$outputs = [$outputs];
		}
		foreach ( $outputs as $k => $o ) {
			if ( (! $o instanceOf Generator) && (!method_exists($o, 'send')) ) {
				throw new Exception("Output #$k is not a Generator and has no 'send' method.");
			}
		}
		
		$this->input = $input;
		$this->map = $map;
		$this->reduce = $reduce;
		$this->outputs = $outputs;
		
		$this->load_defaults();
		$this->options($options);
	}
	
	public function run () {
		// $this->map($data) does not work :(
		// http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
		$func_map = $this->map;
		$func_reduce = $this->reduce;
		$func_progress = $this->options('progress_callback') instanceOf Closure ? $this->options('progress_callback') : function () {};
		
		$reduced = array();
		
		$func_progress(self::progress_start);
		
		$numlines = 0;
		foreach ( $this->input as $row ) {
			$numlines += 1;
			//if ( $numlines > 1000 ) {
			//	break;
			//}
			
			if ( $func_progress !== false && $numlines % $this->options('progress_each') == 0 ) {
				$func_progress(self::progress_numline, $numlines);
			}
			
			$mapped = $func_map($row);
			if ( $mapped === null ) {
				continue;
			}
			
			assert( is_array($mapped), 'Returned value from map() is not an array.' );
			assert( count($mapped) > 0, 'Returned array from map() is empty.' );
			
			// since give the possibility to return several values to be reduced,
			// if returned value is not an array of array, we make it
			if ( !is_array(reset($mapped)) ) {
				$mapped = array($mapped);
			}
			
			foreach ( $mapped as $row2 ) {
				assert( is_array($row2), 'One of the returned values from map() is not an array.' );
				assert( count($row2) > 0, 'One of the returned arrays from map() is empty.' );
				
				$key = $this->options('grouped') ? reset($row2) : '_no_key_';
				$reduced[$key] = $func_reduce($row2, isset($reduced[$key]) ? $reduced[$key] : null);
			}
		}
		
		$func_progress(self::progress_finish, $numlines);
		
		foreach ( $reduced as $row ) {
			foreach ( $this->outputs as $out ) {
				$out->send($row);
			}
		}
		foreach ( $this->outputs as $out ) {
			$out->send(null);
		}
	}
}
