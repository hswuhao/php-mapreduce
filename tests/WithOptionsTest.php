<?php
require_once dirname(__FILE__) . '/../src/autoloader.php';

class WithOptionsStub_NoDefs {
	use WithOptions;
}

class WithOptionsStub_Defs {
	use WithOptions;
	
	public static $defaults = array (
		'key_true' => true,
		'key_false' => false,
		'key_closure' => null,
		'key_string' => 'asdf',
		'key_number' => 7,
		'key_null' => null,
	);
}

class WithOptionsTest extends PHPUnit_Framework_TestCase {
    public function testDefaults () {
        $this->assertEquals(false, isset(WithOptionsStub_NoDefs::$defaults));
		$defs = WithOptionsStub_NoDefs::get_defaults();
        $this->assertSame(null, $defs);
		
		WithOptionsStub_Defs::$defaults['key_closure'] = function () {};
        $this->assertEquals(true, isset(WithOptionsStub_Defs::$defaults));
        $this->assertEquals(6, count(WithOptionsStub_Defs::$defaults));
        $this->assertSame(true, WithOptionsStub_Defs::$defaults['key_true']);
        $this->assertSame(false, WithOptionsStub_Defs::$defaults['key_false']);
        $this->assertSame(null, WithOptionsStub_Defs::$defaults['key_null']);
        $this->assertSame(true, WithOptionsStub_Defs::$defaults['key_closure'] instanceOf Closure);
        $this->assertSame(7, WithOptionsStub_Defs::$defaults['key_number']);
        $this->assertSame('asdf', WithOptionsStub_Defs::$defaults['key_string']);
        $this->assertSame(false, isset(WithOptionsStub_Defs::$defaults['key_float']));
		
		$defs = WithOptionsStub_Defs::get_defaults();
		$this->assertSame(
			implode(', ', array_keys($defs)),
			implode(', ', array_keys(WithOptionsStub_Defs::$defaults)) );
		foreach ( WithOptionsStub_Defs::$defaults as $k => $v ) {
			$this->assertSame($v, $defs[$k]);
		}
	}
	
	public function testChangeDefaults () {
	}
	
	public function testValues () {
	}
	
	public function testChangeValues () {
	}
}
