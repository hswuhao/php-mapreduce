<?php
require_once 'WithOptions.php';

class MapReduce {
	use WithOptions;
	
	const PROGRESS_START        = 0;
	const PROGRESS_START_INPUT  = 1;
	const PROGRESS_NUMLINE      = 2;
	const PROGRESS_FINISH_INPUT = 3;
	const PROGRESS_FINISH       = 4;
	
	public static $defaults = array (
		'in_memory' => true, // not used
		'group_by' => false,
		'progress_callback' => false,
		'progress_each' => 10000,
	);
	
	private $inputs = false;
	private $map = false;
	private $reduce = false;
	private $outputs = false;
	
	public function __construct ($inputs, Closure $map, Closure $reduce, $outputs, $options = array()) {
		// array of Traversable --> ok
        // array of array --> ok
        // array of anything else --> convert to [ $inputs ]
        // Traversable --> convert to [ $inputs ]
        // throw error
        
        if ( is_array($inputs) ) {
			if ( (! is_array(reset($inputs))) && (! reset($inputs) instanceOf Traversable) ) {
				$inputs = [ $inputs ];
			}
		} else if ( $inputs instanceOf Traversable ) {
				$inputs = [ $inputs ];
        } else {
			throw new Exception('Input is not Traversable.');
		}
        
		if ( !is_array($outputs) ) {
			$outputs = [ $outputs ];
		}
		foreach ( $outputs as $k => $o ) {
			if ( (! $o instanceOf Generator) && (!method_exists($o, 'send')) ) {
				throw new Exception("Output #$k is not a Generator and has no 'send' method.");
			}
		}
		
		$this->inputs = $inputs;
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
		$func_key = false;
		
		$reduced = array();
		
		$func_progress(self::PROGRESS_START);
		$numlines = 0;
		
		foreach ( $this->inputs as $k => $input ) {
			$func_progress(self::PROGRESS_START_INPUT, $k);
			$numlines_input = 0;
			
			foreach ( $input as $row ) {
				$numlines += 1;
                $numlines_input += 1;
				//if ( $numlines > 1000 ) {
				//	break;
				//}
				
				if ( $numlines_input > 0 && $numlines_input % $this->options('progress_each') == 0 ) {
					$func_progress(self::PROGRESS_NUMLINE, $k, $numlines, $numlines_input);
				}
				
				$mapped = $func_map($row);
				if ( $mapped === null ) {
					continue;
				}
				
				$key = '__no_key__';
				if ( $this->options('group_by') === true ) {
					$key = reset($mapped);
				} else if ( $this->options('group_by') instanceOf Closure ) {
					if ( $func_key === false ) {
						$func_key = $this->options('group_by');
					}
					$key = $func_key($mapped);
				} else if ( $this->options('group_by') !== false ) {
					// check for key
					$key = $mapped[ $this->options('group_by') ];
				}
				if ( !isset($reduced[$key]) ) {
					$reduced[$key] = $mapped;
				} else {
					$reduced[$key] = $func_reduce($mapped, $reduced[$key]);
				}
			}
			
			$func_progress(self::PROGRESS_FINISH_INPUT, $k, $numlines, $numlines_input);
		}
		
		$func_progress(self::PROGRESS_FINISH, null, $numlines);
		
		foreach ( $reduced as $row ) {
			foreach ( $this->outputs as $output ) {
				$output->send($row);
			}
		}
		foreach ( $this->outputs as $output ) {
			$output->send(null);
		}
	}
}
