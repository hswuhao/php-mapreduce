<?php
/*
 * Very basic KML file generator. For demonstration purposes.
 */
 
// TODO
// - add option with the default icon
// - add parameter with closure to generate Placemark from row

require_once 'GeneratorAggregate.php';
require_once 'WithOptions.php';

class KmlWriter implements GeneratorAggregate {
	use GeneratorAggregateHack;
	use WithOptions;
	
	public static $defaults = array (
		'overwrite' => false,
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
	
	private function putLines () {
		// Creates the Document.
		$dom = new DOMDocument('1.0', 'UTF-8');

		// Creates the root KML element and appends it to the root document.
		$node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
		$parNode = $dom->appendChild($node);

		// Creates a KML Document element and append it to the KML element.
		$dnode = $dom->createElement('Document');
		$docNode = $parNode->appendChild($dnode);

		// Creates the two Style elements, one for restaurant and one for bar, and append the elements to the Document element.
		$restStyleNode = $dom->createElement('Style');
		$restStyleNode->setAttribute('id', 'pinStyle');
		$restIconstyleNode = $dom->createElement('IconStyle');
		$restIconstyleNode->setAttribute('id', 'pinIcon');
		$restIconNode = $dom->createElement('Icon');
		$restHref = $dom->createElement('href', 'http://maps.google.com/mapfiles/kml/paddle/ltblu-blank.png');
		$restIconNode->appendChild($restHref);
		$restIconstyleNode->appendChild($restIconNode);
		$restStyleNode->appendChild($restIconstyleNode);
		$docNode->appendChild($restStyleNode);
		
		do {
			$row = yield;
			
			if ( $row === null ) {
				break;
			}
			
			// Creates a Placemark and append it to the Document.

			$node = $dom->createElement('Placemark');
			$placeNode = $docNode->appendChild($node);

			// Creates an id attribute and assign it the value of id column.
			$placeNode->setAttribute( 'id', 'placemark' . (isset($row['id']) ? $row['id'] : $row[ key($row) ]) );
			
			// Create name, and description elements and assigns them the values of the name and address columns from the results.
			if ( isset($row['name']) ) {
				$nameNode = $dom->createElement('name',htmlentities($row['name']));
				$placeNode->appendChild($nameNode);
			}
			if ( isset($row['description']) ) {
				$descNode = $dom->createElement('description', $row['description']);
				$placeNode->appendChild($descNode);
			}
			$styleUrl = $dom->createElement('styleUrl', '#pinStyle');
			$placeNode->appendChild($styleUrl);

			// Creates a Point element.
			$pointNode = $dom->createElement('Point');
			$placeNode->appendChild($pointNode);

			// Creates a coordinates element and gives it the value of the lng and lat columns from the results.
			$coorStr = $row['lng'] . ','  . $row['lat'];
			$coorNode = $dom->createElement('coordinates', $coorStr);
			$pointNode->appendChild($coorNode);
		} while ( $row !== null );

		$kmlOutput = $dom->saveXML();
		file_put_contents($this->outputfile, $kmlOutput);
	}
	
	public function getGenerator () {
		return $this->putLines();
	}
}
