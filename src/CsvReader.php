<?php
require_once 'WithOptions.php';

ini_set("auto_detect_line_endings", true);

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

class CsvReader implements IteratorAggregate {
	use WithOptions;
	
	public static $defaults = array (
		'with_headers' => true,
		'separator' => ',',
		'delimiter' => '"',
		'escape' => '"',
		'stop_on_blank' => false,
	);
	
	private $inputfile = false;
	
	public function __construct ($inputfile, $options = array()) {
		$this->inputfile = $inputfile;
		$this->load_defaults();
		$this->options($options);
        if ( (!is_resource($this->inputfile)) && (!file_exists($this->inputfile)) ) {
			throw new Exception('Input file does not exist: ' . $this->inputfile);
        }
	}
	
    private static function is_empty ($array) {
        return count($array) == 1 && ( $array[0] === null || trim($array[0]) === '' );
    }

    
	private function getLines () {
		$fh = is_resource($this->inputfile) ? $this->inputfile : @fopen($this->inputfile, 'r');
		if ( !$fh ) {
			throw new Exception('Could not open input file ' . $this->inputfile);
		}
		
		$headers = false;
		while ( !feof($fh) ) {
			if ( $headers === false && $this->options('with_headers') ) {
				$headers = fgetcsv($fh, 0, $this->options('separator'), $this->options('delimiter'), $this->options('escape'));
				if ( self::is_empty($headers) ) {
                    if ( feof($fh) ) {
                        break;
                    }
					throw new Exception('Empty headers line in file ' . $this->inputfile);
				}
			}
			
			$row = fgetcsv ($fh, 0, $this->options('separator'), $this->options('delimiter'), $this->options('escape'));
			if ( self::is_empty($row) ) {
				if ( $this->options('stop_on_blank') ) {
					break;
				}
				continue;
			}
			yield $this->options('with_headers') ? array_combine($headers, $row) : $row;
		}
		
		fclose($fh);
	}
	
	public function getIterator () {
		return $this->getLines();
	}
}
