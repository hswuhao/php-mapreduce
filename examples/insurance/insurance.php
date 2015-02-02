<?php
define('EXAMPLE_DIR', dirname(__FILE__) . '/');
define('SRC_DIR', EXAMPLE_DIR . '../../src/');

require_once SRC_DIR . 'CsvReader.php';
require_once SRC_DIR . 'CsvWriter.php';
require_once SRC_DIR . 'KmlWriter.php';
require_once SRC_DIR . 'MapReduce.php';

// Sample data downloaded from SpatialKey. See README.
$input = new CsvReader(EXAMPLE_DIR . 'FL_insurance_sample.csv');

$output_csv = new CsvWriter(EXAMPLE_DIR . 'output.csv', [ 'overwrite' => 1 ]);
$output_kml = new KmlWriter(EXAMPLE_DIR . 'output.kml', [ 'overwrite' => 1 ]);

$map = function ($row) {
	$ret = array (
		'state_county' => $row['statecode'] . ' - ' . preg_replace('/\s+/', ' ', ucwords(strtolower($row['county']))),
		'name' => $row['statecode'] . ' - ' . preg_replace('/\s+/', ' ', ucwords(strtolower($row['county']))),
		'count' => 1,
		'lat' => $row['point_latitude'],
		'lng' => $row['point_longitude'],
	);
	return $ret;
};

// $new is the new data to be aggregated
// $carry is the previous data or null if this is the first call
// returns the data to be passed to the new call of the function or to be exported if this is the last call
$reduce = function ($new, $carry) {
	if ( !$carry ) {
		return array_merge($new, array('count' => 1));
	}
	
	$reduced = $carry;
	$reduced['count'] += 1;
	$reduced['lat'] = ( ($carry['lat'] * $carry['count']) + $new['lat']) / $reduced['count'];
	$reduced['lng'] = ( ($carry['lng'] * $carry['count']) + $new['lng']) / $reduced['count'];
	return $reduced;
};

class LogToConsole {
	public function send ($data) {
		if ( !is_null($data) ) {
			print_r($data);
		} else {
			echo "Finished!\n";
		}
	}
}

$logger = new LogToConsole();

$progress = function ($type, $input = null, $numlines = null, $numlines_input = null) {
	switch ($type) {
		case MapReduce::PROGRESS_START:
			echo "Start...\n";
			break;
		case MapReduce::PROGRESS_START_INPUT:
			echo "Start input " . (preg_match('/^\d+$/', $input) ? "#$input" : $input) . "...\n";
			break;
		case MapReduce::PROGRESS_NUMLINE:
			echo " - Line: $numlines_input\n";
			break;
		case MapReduce::PROGRESS_FINISH_INPUT:
			echo " - Finished input " . (preg_match('/^\d+$/', $input) ? "#$input" : $input) . ". Lines read: $numlines_input. Total: $numlines.\n";
			break;
		case MapReduce::PROGRESS_FINISH:
			echo " - Finished. Total lines read: $numlines.\n";
			break;
	}
};

MapReduce::$defaults['group_by'] = true;
MapReduce::$defaults['progress_callback'] = $progress;
$parser = new MapReduce($input, $map, $reduce, [$output_csv, $output_kml /* , $logger */ ]);
$parser->run();
