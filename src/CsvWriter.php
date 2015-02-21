<?php
require_once 'Writer.php';

// https://gist.github.com/johanmeiring/2894568
if( !function_exists('str_putcsv') ) {
	function str_putcsv ($input, $delimiter = ',', $enclosure = '"') {
		// Open a memory "file" for read/write...
		$fp = fopen('php://temp', 'r+');
		// ... write the $input array to the "file" using fputcsv()...
		fputcsv($fp, $input, $delimiter, $enclosure);
		// ... rewind the "file" so we can read what we just wrote...
		rewind($fp);
		// ... read the entire line into a variable...
		$data = fread($fp, 1048576);
		// ... close the "file"...
		fclose($fp);
		// ... and return the $data to the caller, with the trailing newline from fgets() removed.
		return rtrim($data, "\n");
	}
}

class CsvWriter extends Writer {
	public static $defaults = array (
		'overwrite' => false,
		'with_headers' => true,
		'separator' => ',',
		'delimiter' => '"',
		'escape' => '"',
		'buffer' => 1000000,
		'split_lines' => 0,
	);
	
	private $outputfile = false;
	
	public function __construct ($outputfile, $options = array()) {
		parent::__construct($options);
		
		$this->outputfile = $outputfile;
		
		if ( !$this->options('overwrite') ) {
			if ( file_exists($outputfile) ) {
				throw new Exception('Output file already exists: ' . $this->outputfile);
			}
		}
	}
	
	private function make_csv_row ($row, $numrow) {
		$str = str_putcsv($row, $this->options('separator'), $this->options('delimiter')) . "\n";
		if ( $numrow == 1 ) {
			$str = str_putcsv(array_keys($row), $this->options('separator'), $this->options('delimiter')) . "\n" . $str;
		}
		return $str;
	}
	
	protected function inner_generator ($filename) {
		$fh = fopen($filename, 'w');
		if ( !$fh ) {
			throw new Exception('Could not open output file ' . $filename);
		}
		
		$numline = 0;
		do {
			$row = yield;
			
			if ( $row !== null ) {
				$numline += 1;
				$line = $this->make_csv_row($row, $numline);
				if ( $line !== null && $line !== false ) {
					fwrite($fh, $line);
				}
			}
		} while ( $row !== null );
		
		fclose($fh);
	}
	
	protected function output_generator () {
		$numfile = 1;
		$numlines = 0;
		
		$gen = $this->inner_generator($this->outputfile);
		do {
			$row = yield;
			$gen->send($row);
			if ( $this->options('split_lines') && ($numlines > 0) && ($numlines % $this->options('split_lines') == 0) ) {
				$gen->send(null);
				$numfile += 1;
				$gen = $this->inner_generator($this->outputfile . '.' . $numfile . '.csv');
			}
			$numlines += 1;
		} while ( $row !== null );
	}
}
