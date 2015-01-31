# php-mapreduce

Implementation of the map/reduce algorithm in PHP.

## Usage

Include `MapReduce.php` file into your code.

```php
require_once 'MapReduce.php';
```

**Set up the input**

The input has to be an implementation of `Traversable`. Classes implementing `Iterator` or `IteratorAggregate`, like `Generator`, work as well. `array`, not being a class, does not work. But fear not, `ArrayIterator` can be used to get the iterator of the array. 

In this example, a veterinarian has a list of all the pets they have treated, with many fields for each one:

```php
$pets = [
	  [ 'name' => 'Bono',  'spices' => 'dog',     'birthday' => '2010-01-01', 'visits' => '3', 'revenue' =>  98.00 ]
	, [ 'name' => 'Lenny', 'spices' => 'cat',     'birthday' => '2005-02-12', 'visits' => '2', 'revenue' => 128.00 ]
	, [ 'name' => 'Bruce', 'spices' => 'dog',     'birthday' => '2008-03-31', 'visits' => '3', 'revenue' => 155.00 ]
	, [ 'name' => 'Sting', 'spices' => 'turtle',  'birthday' => '2010-04-06', 'visits' => '2', 'revenue' =>  58.00 ]
	, [ 'name' => 'Jay',   'spices' => 'papagay', 'birthday' => '2012-05-16', 'visits' => '1', 'revenue' =>  19.00 ]
];

$input = new ArrayIterator($pets);
```

Or using the included `CsvReader` class:

```php
$pets = new CsvReader('pet_data.csv');
```

**Create the mapping function**

This function transforms each item in the input into another item, more suitable to be processed in the `reduce` function.

´´´
     Input #1     Input #2     Input #3     Input #4     Input #5     ...
       |            |            |            |            |
       ↓            ↓            ↓            ↓            ↓
     Mapped #1    Mapped #2    Mapped #3    Mapped #4    Mapped #5    ...
´´´

Basically, this function is used to pick only the values we are going to use and transform them, if needed, so that they are easily aggregable (or reducible). I.e. convert a date in format YYYY-MM-DD into a decimal number, an address into lat/lng coordinates, etc.

In this example, we want to get: number of animals, average age, total number of visits, average number of visits, total revenue, average revenue per animal and average revenue per visit.

```php
$mapper = function ($pet) {
	return [
		  'animals'            => 1
		, 'avg_age'            => (time() - strtotime($pet['birthday'])) / 60 / 60 / 24 / 365.25
		, 'total_visits'       => $pet['visits']
		, 'avg_visits'         => $pet['visits']
		, 'total_revenue'      => $pet['revenue']
		, 'avg_revenue_animal' => $pet['revenue']
		, 'avg_revenue_visit'  => $pet['revenue'] / $pet['visits']
	];
};
```

Bear in mind that this function _should_ return the same kind of result that the `reduce` function: if an input consisted of just one item, there would be no need to call `reduce` and the result of this `map` function would be the final result of the algorithm.

**Create the reducing function**

This function takes two results of the mapping function and creates a new result. This new result will be merged with the next mapped item, and so on until there are no more mapped items and the last result is the output of the algorithm.

How it works now:

´´´
     null         Mapped #1    Mapped #2    Mapped #3    Mapped #4    Mapped #5    ...
       |            |            |            |
       +------------|            |            |
                    ↓            |            |
                  Reduced        |            |
                    |            |            |
                    +------------|            |
                                 ↓            |
                               Reduced        |
                                 |            |
                                 +------------|
                                              ↓
                                            Reduced 
´´´

How it might work in future versions:

´´´
     Mapped #1    Mapped #2    Mapped #3    Mapped #4    Mapped #5    Mapped #6    ...
       |            |            |            |            |            |
       +------------+            +------------+            +------------+
             ↓                         ↓                         ↓
           Reduced                   Reduced                   Reduced
             |                         |                         |
             +-------------------------+                         +--- - - -
                          ↓
                        Reduced
                          |
                          +--- - - -
´´´

Notice that the second method allows for parallelization. But it requires that the `map` function returns items which are "of the same kind" that the reduced items.

```php
$reducer = function ($new_data, $carry) {
	if ( is_null($carry) ) {
		return $new_data;
	}
	
	$animals            = $carry['animals'] + 1;
	$avg_age            = ( $carry['avg_age'] * $carry['animals'] + $new_data['avg_age'] ) / $animals;
	$total_visits       = $carry['total_visits'] + $new_data['total_visits'];
	$avg_visits         = $total_visits / $animals;
	$total_revenue      = $carry['total_revenue'] + $new_data['total_revenue'];
	$avg_revenue_animal = $total_revenue / $animals;
	$avg_revenue_visit  = $total_revenue / $total_visits;
	
	return compact('animals', 'avg_age', 'total_visits', 'avg_visits', 'total_revenue', 'avg_revenue_animal', 'avg_revenue_visit');
};
```

**Set up the output**

The output is an object with a public `send()` method.

This `send()` method is called once for each of the reduced items:

```php
$output->send($reduced);
```

and one last time when there are no more items, with `null` as argument.

```php
$output->send(null);
```

This way, clean-up code can be run (e.g. close file handlers.)

```php
class LogToConsole {
	public function send ($data) {
		if ( !is_null($data) ) {
			print_r($data);
		} else {
			echo "Finished!";
		}
	}
}

$output = new LogToConsole();
```

**Initialize and run** the Map/Reduce algorithm

```php
$mapreducer = new MapReduce($input, $mapper, $reducer, $output);
$mapreducer->run();
```

---

&copy; 2015 José Luis Salinas
