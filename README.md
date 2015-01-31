# php-mapreduce

Implementation of the map/reduce algorithm in PHP.

## Usage

##### 1. Include `MapReduce.php` file in your code

```php
require_once 'MapReduce.php';
```

##### 2. Set up the input

The input has to be an implementation of `Traversable`. Classes implementing `Iterator` or `IteratorAggregate`, like `Generator`, work as well. `array`, not being a class, does not work. But fear not, `ArrayIterator` can be used to get the iterator of the array. 

In this example, a veterinarian has a list of all the pets they have treated, with many fields for each one:

```php
$pets = [
	  [ 'name' => 'Bono',  'spices' => 'dog',     'birthday' => '2010-01-01', 'visits' => '3', 'revenue' =>  98.00 ]
	, [ 'name' => 'Lenny', 'spices' => 'cat',     'birthday' => '2005-02-12', 'visits' => '2', 'revenue' => 128.00 ]
	, [ 'name' => 'Bruce', 'spices' => 'dog',     'birthday' => '2008-03-31', 'visits' => '3', 'revenue' => 155.00 ]
	, [ 'name' => 'Sting', 'spices' => 'turtle',  'birthday' => '2010-04-06', 'visits' => '2', 'revenue' =>  58.00 ]
	, [ 'name' => 'Jay',   'spices' => 'papagay', 'birthday' => '2012-05-16', 'visits' => '1', 'revenue' =>  19.00 ]
	, [ 'name' => 'Steve', 'spices' => 'cat',     'birthday' => '2005-06-22', 'visits' => '3', 'revenue' =>  68.00 ]
	, [ 'name' => 'Mike',  'spices' => 'dog',     'birthday' => '2008-07-21', 'visits' => '2', 'revenue' =>  55.00 ]
	, [ 'name' => 'Ben',   'spices' => 'dog',     'birthday' => '2009-08-16', 'visits' => '2', 'revenue' =>  71.00 ]
	, [ 'name' => 'Miles', 'spices' => 'cat',     'birthday' => '2011-09-14', 'visits' => '4', 'revenue' => 346.00 ]
	, [ 'name' => 'Jack',  'spices' => 'dog',     'birthday' => '2009-10-03', 'visits' => '6', 'revenue' => 244.00 ]
];

$input = new ArrayIterator($pets);
```

Or using the included `CsvReader` class:

```php
$pets = new CsvReader('pet_data.csv');
```

##### 3. Create the mapping function

This function transforms each item in the input into another item, more suitable to be processed with the `reduce` function.

```
     Input #1     Input #2     Input #3     Input #4     Input #5     ...
       |            |            |            |            |
       ↓            ↓            ↓            ↓            ↓
     Mapped #1    Mapped #2    Mapped #3    Mapped #4    Mapped #5    ...
```

Basically, this function is used to pick only the values we are going to use and transform them, if needed, so that they are easily aggregable (or reducible). I.e. convert a date in format YYYY-MM-DD into a decimal number, an address into lat/lng coordinates, etc.

In this example, we want to get: number of animals, average age, total number of visits, average number of visits, total revenue, average revenue per animal and average revenue per visit.

```php
$mapper = function ($pet) {
	return [
		  'species'            => $pet['species']
		, 'animals'            => 1
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

##### 4. Create the reducing function

This function takes two results of the mapping function and creates a new result. This new result will be merged with the next mapped item, and so on until there are no more mapped items and the last result is the output of the algorithm.

How it works now:

```
null         Mapped #1    Mapped #2    Mapped #3    Mapped #4    Mapped #5    ...
  |            |            |            |
  +------------|            |            |
               ↓            |            |
            Reduced         |            |
               |            |            |
               +------------|            |
                            ↓            |
                         Reduced         |
                            |            |
                            +------------|
                                         ↓
                                      Reduced 
```

How it might work in future versions:

```
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
```

Notice that the second method allows for parallelization. But it requires that the `map` function returns items which are "of the same kind" that the reduced items.

```php
$reducer = function ($new_data, $carry) {
	if ( is_null($carry) ) {
		return $new_data;
	}
	
	$species            = $carry['species'];
	$animals            = $carry['animals'] + 1;
	$avg_age            = ( $carry['avg_age'] * $carry['animals'] + $new_data['avg_age'] ) / $animals;
	$total_visits       = $carry['total_visits'] + $new_data['total_visits'];
	$avg_visits         = $total_visits / $animals;
	$total_revenue      = $carry['total_revenue'] + $new_data['total_revenue'];
	$avg_revenue_animal = $total_revenue / $animals;
	$avg_revenue_visit  = $total_revenue / $total_visits;
	
	return compact('species', 'animals', 'avg_age', 'total_visits', 'avg_visits', 'total_revenue', 'avg_revenue_animal', 'avg_revenue_visit');
};
```

##### 5. Set up the output

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

##### 6. Initialize and run the Map/Reduce algorithm

If no options are passed to the constructor, only one reduced item is returned... a global aggregator.

```php
echo "Getting global data:\n";
$mapreducer = new MapReduce($input, $mapper, $reducer, $output);
$mapreducer->run();
echo "\n";
```

Output is:

```
Getting global data:
Array
(
    [species] => dog
    [animals] => 10
    [avg_age] => 5.9726447828732
    [total_visits] => 28
    [avg_visits] => 2.8
    [total_revenue] => 1242
    [avg_revenue_animal] => 124.2
    [avg_revenue_visit] => 44.357142857143
)
Finished!
```

Please, note that there is a strange output value in the first run, `[species] => dog`. This is because we used the same mapping function for both the ungrouped and grouped runs. This should usually not be done.

If options `grouped` is set to true, one reduced item is returned per group.

Groups are defined by the first value of each mapped item (in this case, `species`.)

In the future, this options might change to `group_by`, which will be either `true` for the first value -just like now,- the key of the value to use as group ID or a closure that accepts the mapped item and returns the group ID.

Also, `$options` argument will probably be moved from constructor to method `run()`.

```php
echo "Getting grouped data:\n";
$mapreducer = new MapReduce($input, $mapper, $reducer, $output, ['grouped' => true]);
$mapreducer->run();
echo "\n";
```

Output is:

```
Getting grouped data:
Array
(
    [species] => dog
    [animals] => 5
    [avg_age] => 5.8472512168226
    [total_visits] => 16
    [avg_visits] => 3.2
    [total_revenue] => 623
    [avg_revenue_animal] => 124.6
    [avg_revenue_visit] => 38.9375
)
Array
(
    [species] => cat
    [animals] => 3
    [avg_age] => 7.6525748155753
    [total_visits] => 9
    [avg_visits] => 3
    [total_revenue] => 542
    [avg_revenue_animal] => 180.66666666667
    [avg_revenue_visit] => 60.222222222222
)
Array
(
    [species] => turtle
    [animals] => 1
    [avg_age] => 4.8216751273861
    [total_visits] => 2
    [avg_visits] => 2
    [total_revenue] => 58
    [avg_revenue_animal] => 58
    [avg_revenue_visit] => 29
)
Array
(
    [species] => papagay
    [animals] => 1
    [avg_age] => 2.7107921705073
    [total_visits] => 1
    [avg_visits] => 1
    [total_revenue] => 19
    [avg_revenue_animal] => 19
    [avg_revenue_visit] => 19
)
Finished!
```

---

## To do

- [ ] document progress callbacks
- [ ] insurance example: add insured values
- [ ] insurance example: improve kml output (info, markers)
- [ ] implement a not-in-memory solution for very big datasets when using groups -optional-
- [ ] move `$map`, `$reduce`, `$output` and `$options` arguments from constructor to `run()`
- [ ] add callback to kml writer to get point data (lat/lng, name, description, icon, etc)
- [ ] make it easy to merge already reduced files -- allow `map` function to be `null`?
- [ ] accept an array of inputs and process them all in the same batch, one after the other
- [x] create a kml writer
- [x] accept an array of outputs
- [x] create a csv writer
- [x] create a csv reader

---

&copy; 2015 José Luis Salinas
