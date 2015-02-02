<?php

class CsvReaderTest extends PHPUnit_Framework_TestCase {
    // ...
    /*
        things to test:
            - option 'with_headers'
                - set to true, feed with headers
                - set to true, feed with no headers
                - set to false, feed with headers
                - set to false, feed with no headers
            - options 'separator', 'delimiter' and 'escape'
                - set to ',', '"', '"', feed correct data
                - set to ',', '"', '"', feed wrong data
                - set to ';', '"', '"', feed correct data
                - set to ';', '"', '"', feed wrong data
                - tres double '"' inside a field
            - option 'stop_on_blank'
                - set to true, feed with blank
                - set to false, feed with blank
    */
    
    static protected function createTmp ($text) {
        $fh = tmpfile();
        if ( !$fh ) {
            return false;
        }
        fwrite($fh, is_array($text) ? implode("\n", $text) : $text);
        fseek($fh, 0);
        return $fh;
    }
    
    /**
     * @expectedException Exception
     * @expectedExceptionMessage Input file does not exist: adsfasdf.csv
     */
    public function testFileDoesNotExistThrowsException () {
        $reader = new CsvReader('adsfasdf.csv');
    }
    
    public function testEmptyFileDoesntReturnAnything() {
        $fh = self::createTmp('');
        $reader = new CsvReader($fh);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(0, $count);
        
        $fh = self::createTmp('');
        $reader = new CsvReader($fh, [ 'with_headers' => false ]);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(0, $count);
    }

    public function testReturnsCorrectNumberOfLines() {
        $data_0rows = [
            'field_1,field_2,field_3,field_4',
        ];
        
        $fh = self::createTmp($data_0rows);
        $reader = new CsvReader($fh);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(0, $count);
        
        $fh = self::createTmp($data_0rows);
        $reader = new CsvReader($fh, [ 'with_headers' => false ]);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(1, $count);
    
        $data_5rows = [
            'field_1,field_2,field_3,field_4',
            'a1,b1,c1,d1',
            'a2,b2,c2,d2',
            'a3,b3,c3,d3',
            'a4,b4,c4,d4',
            'a5,b5,c5,d5',
        ];
        
        $fh = self::createTmp($data_5rows);
        $reader = new CsvReader($fh);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(5, $count);
        
        $fh = self::createTmp($data_5rows);
        $reader = new CsvReader($fh, [ 'with_headers' => false ]);
        $count = 0;
        foreach ( $reader as $row ) {
            $count  += 1;
        }
        $this->assertEquals(6, $count);
    }
    
    // ...
}