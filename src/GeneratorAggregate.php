<?php
/*
 * Crude attempt to mimic the IteratorAggregate interface.
 * 
 * The idea behind this is that, when you call $obj->send() on a GeneratorAggregate implementation,
 * PHP automatically calls $obj->getGenerator(), gets the generator and calls send() on it,
 * like it does when iterating on an object implementing IteratorAggregate.
 * 
 * A possible hack to get it is create a send() function that calls getGenerator() only the first time,
 * storing its returned value in a static variable. Second and subsequent calls use the static variable
 * instead of calling getGenerator() again. See trait GeneratorAggregateHack.
 */

// Just in case some day PHP maintainers decide to add it to the language
if ( !interface_exists('GeneratorAggregate') ) {
    interface GeneratorAggregate {
        public function getGenerator();
    }

    trait GeneratorAggregateHack {
        public function send ($value) {
            static $generator = null;
            
            if ( $generator === null ) {
                $generator = $this->getGenerator();
            }
            $generator->send($value);
        }
    }
} else {
    trait GeneratorAggregateHack {
    }
}
