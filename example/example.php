<?php
define('EXAMPLE_DIR', dirname(__FILE__) . '/');
define('SRC_DIR', EXAMPLE_DIR . '../');

require_once SRC_DIR . 'CsvReader.php';
require_once SRC_DIR . 'CsvWriter.php';
require_once SRC_DIR . 'KmlWriter.php';
require_once SRC_DIR . 'MapReduce.php';

// Sample data downloaded from SpatialKey website:
// http://support.spatialkey.com/spatialkey-sample-csv-data/
$input = new CsvReader(EXAMPLE_DIR . 'FL_insurance_sample.csv');

$output1 = new CsvWriter(EXAMPLE_DIR . 'output.csv', [ 'overwrite' => 1 ]);
$output2 = new KmlWriter(EXAMPLE_DIR . 'output.kml', [ 'overwrite' => 1 ]);

$map = function ($row) {
	$ret = array (
		'state_county' => $row['statecode'] . ' - ' . preg_replace('/\s+/', ' ', ucwords(strtolower($row['county']))),
		'name' => $row['statecode'] . ' - ' . preg_replace('/\s+/', ' ', ucwords(strtolower($row['county']))),
		'lat' => $row['point_latitude'],
		'lng' => $row['point_longitude'],
	);
	return $ret;
};

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

$progress = function ($type, $data = null) {
	switch ($type) {
		case MapReduce::progress_start:
			echo "Start...\n";
			break;
		case MapReduce::progress_numline:
			echo " - Line: $data\n";
			break;
		case MapReduce::progress_finish:
			echo " - Finished at line $data\n";
			break;
	}
};

MapReduce::$defaults['grouped'] = true;
$parser = new MapReduce($input, $map, $reduce, [$output1, $output2], array( 'progress_callback' => $progress ));
$parser->parse();
