[![Build Status](https://travis-ci.org/colindecarlo/collection.svg?branch=master)](https://travis-ci.org/colindecarlo/collection)

# Collection

Collection is a library geared towards delivering a fast and intuitive interface over a group of related elements.

## Defining a Collection

Collections can be constructed in multiple ways by passing:
* an integer to the constructor indicating its capacity
* an array or [SplFixedArray](splfixedarray) containing the elements of the collection

### Defining an Empty Collection

Empty collections are created by passing an integer to the Collection constructor indicating its
capacity. All indexes of the collection are initalized to `null` and the size of the collection
is reported as `0`.

```php
$imEmpty = new Collection(10);

count($imEmpty);
// 0
```

### Defining a Collection Derived From An `array` or `SplFixedArray`

Collections can be defined using a populated array (or SplFixedArray) simply by passing the array
to Collection constructor. The size of the Collection is determined by finding the last non-null
index of the array.

```php
$daysOfTheWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$fromArray = new Collection($daysOfTheWeek);

count($fromArray);
// 7
```

```php
$occupations = new \SplFixedArray(10);
$occupations[0] = 'Botanist';

$occupations = new Collection($occupations);

count($occupations);
// 1
```

## Using Collection

### map($func)

Use `map` to create a new instance of Collection which contains a projection of each element of the
mapped collection. The projection is created by applying the function contained in `$func` to each
element of the original collection.

#### Parameters

<dl>
  <dt>`$func`</dt>
  <dd>The function which is applied to each element of the collection. `$func` can be any
      [callable](callable) function.
  </dd>
</dl>

#### Example

```php
// using a function name
$words = new Collection(['Lorem', 'ipsum', 'dolor', 'sit' 'amet']);
$shouty = $words->map('strtoupper');
// ['LOREM', 'IPSUM', 'DOLOR', 'SIT' 'AMET'];

// using a callable array
$classesToMock = new Collection(['SomeClass', 'SomeOtherClass', 'YetAnotherClass']);
$mocks = $classesToMack->map(['Mockery', 'mock']);
//[object(Mockery\Mock), object(Mockery\Mock), object(Mockery\Mock)]

// using an anonymous function
$stringyDates = new Collection(['2007-06-08', '2009-05-11', '2014-02-19']);
$dates = $stringyDates->map(function ($date) {
  return DateTime::createFromFormat('Y-m-d', $date);
});
// [object(DateTime), object(DateTime), object(DateTime)]
```

### each($func)

Apply `$func` to each element of the collection. `each` returns the original collection so you can
chain other methods off of it.

#### Parameters

<dl>
  <dt>`$func`</dt>
  <dd>The function which is applied to each element of the collection. `$func` can be any
      [callable](callable) function.
  </dd>
</dl>

#### Example

```php
$queueEmail = function ($address) use ($message, $emailQueue) {
    $emailQueue->publish(['to' => $address, 'message' => $message]);
};

$adminEmails->each($queueEmail);
```

### reduce($func, $intial)

Generate a single aggregate value derived from applying `$func` to each element of the collection.

#### Parameters

<dl>
  <dt>`$func`</dt>
  <dd>The reducer function, this function accepts two parameters, `$carry` and `$elem` (in that
      order) where `$carry` is the current value of the reduction and `$elem` is the current
      element in the collection being reduced. `$func` can be any [callable](callable) function.
  </dd>
  <dt>`$initial`</dt>
  <dd>The initial value to be used in the reduction</dd>
</dl>

#### Example

```php
$workSchedule = new Collection([
     ['date' => '2015/04/20', 'start' => '08:00', 'end' => '12:00'],
     ['date' => '2015/04/21', 'start' => '12:00', 'end' => '17:00'],
     ['date' => '2015/04/23', 'start' => '08:00', 'end' => '17:00'],
     ['date' => '2015/04/24', 'start' => '10:00', 'end' => '15:00']
]);

$totalHours = $workSchedule->reduce(function($total, $schedule) {
    $start = DateTime::createFromFormat('Y/m/d H:i', $schedule['date'] . ' ' . $schedule['start']);
    $end = DateTime::createFromFormat('Y/m/d H:i', $schedule['date'] . ' ' . $schedule['end']);
    $hours = $end->diff($start)->h;
    return $total + $hours;
});
// 25
```

### filter($func = null)

Return a new Collection containing the elements for which `$func` returns `true`. If `$func` is
not passed to `filter` then only truthy values contained in the original collection will be
present in the result.

#### Parameters

<dl>
  <dt>`$func`</dt>
  <dd>The filter function for which each element of the collection is passed through. `$func`
      returns `true` if the element should be kept and `false` if not.
  </dd>
</dl>

#### Example

```php
$workSchedule = new Collection([
     ['date' => '2015/04/20', 'start' => '08:00', 'end' => '12:00'],
     ['date' => '2015/04/21', 'start' => '12:00', 'end' => '17:00'],
     ['date' => '2015/04/23', 'start' => '08:00', 'end' => '17:00'],
     ['date' => '2015/04/24', 'start' => '10:00', 'end' => '15:00']
]);

$packALunch = $workSchedule->filter(function($schedule) {
    $start = DateTime::createFromFormat('Y/m/d H:i', $schedule['date'] . ' ' . $schedule['start']);
    $end = DateTime::createFromFormat('Y/m/d H:i', $schedule['date'] . ' ' . $schedule['end']);
    $hours = $end->diff($start)->h;
    return $hours > 4;
});
// object(Collection)(
     ['date' => '2015/04/21', 'start' => '12:00', 'end' => '17:00'],
     ['date' => '2015/04/23', 'start' => '08:00', 'end' => '17:00'],
     ['date' => '2015/04/24', 'start' => '10:00', 'end' => '15:00']
)
```

### slice($offset, $length = null)
### flatten($flattenWith = null)

Transform a multi-dimensional collection into a one dimensional collection. The responsibilty of
`$flattenWith` is to accept an element of the collection and return an arrray (or an object that
implements both [Countable](countable) and [ArrayAccess](arrayaccess)) of the elements contained
within that element. If a `flattenWith` function is not provided to `flatten` then the method
will attempt to flatten the collection using a generic `flattenWith` function, this is useful
for flattening a 2 dimensional collection.

#### Parameters

<dl>
  <dt>`$func`</dt>
  <dd>This function is used to flatten individual elements into single dimensional array.
  </dd>
</dl>

#### Example





### contains($value)
### first($matching = null)
### last($matching = null)
### reverse()
### groupBy($getGroupKey)
### prepend($elem)
### append($elem)
### push($elem)
### pop()
### toArray()
### count()

## Author

Colin DeCarlo, colin@thedecarlos.ca

## License

Collection is licensed under the MIT License - see the LICENSE file for details

[splfixedarray]: http://php.net/manual/en/class.splfixedarray.php
[callable]: http://php.net/manual/en/language.types.callable.php
