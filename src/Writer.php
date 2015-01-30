<?php
require_once 'GeneratorAggregate.php';
require_once 'WithOptions.php';

abstract class Writer implements GeneratorAggregate {
	use GeneratorAggregateHack;
	use WithOptions;
	
	public function __construct ($options = array()) {
		$this->load_defaults();
		$this->options($options);
	}
		
	abstract protected function output_generator ();
	
	public function getGenerator () {
		return $this->output_generator();
	}
}
