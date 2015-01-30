<?php
trait GeneratorAggregateHack {
	public function send ($value) {
		static $generator = null;
		
		if ( $generator === null ) {
			$generator = $this->getGenerator();
		}
		$generator->send($value);
	}
}
