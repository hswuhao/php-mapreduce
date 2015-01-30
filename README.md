# php-mapreduce

Implementation of the map/reduce algorithm in PHP.

## Usage

```php
require 'MapReduce.php';

$input = new <implementation of Traversable>(...)

$mapper = function ($data) {
	...
}

$reducer = function ($new_data, $carry_data) {
	...
}

$output = new <implementation of Generator>(...);

$mapreducer = new MapReduce($input, $mapper, $reducer, $output);
$mapreducer->parse();
```

---

&copy; 2015 José Luis Salinas
