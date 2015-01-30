<?php
require_once 'GeneratorAggregate.php';
require_once 'WithOptions.php';

class CsvWriter implements GeneratorAggregate {
	use GeneratorAggregateHack;
	use WithOptions;
	
	public static $defaults = array (
		'overwrite' => false,
		'with_headers' => true,
		'separator' => ',',
		'delimiter' => '"',
		'escape' => '"',
		'buffer' => 1000000,
	);
	
	private $outputfile = false;
	
	public function __construct ($outputfile, $options = array()) {
		$this->outputfile = $outputfile;
		$this->load_defaults();
		$this->options($options);
		
		if ( !$this->options('overwrite') ) {
			if ( file_exists($outputfile) ) {
				throw new Exception('Output file already exists: ' . $this->outputfile);
			}
		}
	}
	
	private function csv_row ($row, $numrow) {
		$str = str_putcsv($row, $this->options('separator'), $this->options('delimiter')) . "\n";
		if ( $numrow == 1 ) {
			$str = str_putcsv(array_keys($row), $this->options('separator'), $this->options('delimiter')) . "\n" . $str;
		}
		return $str;
	}
	
	private function putLines () {
		$fh = fopen($this->outputfile, 'w');
		if ( !$fh ) {
			throw new Exception('Could not open output file ' . $this->outputfile);
		}
		
		$numline = 0;
		do {
			$row = yield;
			
			if ( $row !== null ) {
				$numline += 1;
				$line = $this->csv_row($row, $numline);
				if ( $line !== null && $line !== false ) {
					fwrite($fh, $line);
				}
			}
		} while ( $row !== null );
		
		fclose($fh);
	}
	
	public function getGenerator () {
		return $this->putLines();
	}
}
