<?php

/*
 * (c) Colin DeCarlo <colin@thedecarlos.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace ColinDeCarlo\Collection;

use PHPUnit_Framework_TestCase;
use SplFixedArray;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function test_that_a_Collection_can_be_constructed_using_a_numeric_argument()
    {
        $collection = new Collection(10);
        $this->assertInstanceOf('ColinDeCarlo\\Collection\\Collection', $collection);
        $this->assertCount(0, $collection);
    }

    public function test_that_a_Collection_can_be_constructed_using_an_array_argument()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $this->assertInstanceOf('ColinDeCarlo\\Collection\\Collection', $collection);
        $this->assertCount(3, $collection);
    }

    public function test_that_a_Collection_can_be_constructed_using_an_SplFixedArray_argument()
    {
        $collection = new Collection(new SplFixedArray(10));
        $this->assertInstanceOf('ColinDeCarlo\\Collection\\Collection', $collection);
        $this->assertCount(0, $collection);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid argument supplied to ColinDeCarlo\Collection\Collection::__construct
     */
    public function test_that_an_excpetion_is_thrown_when_instantiating_a_Collection_with_an_invalid_argument()
    {
        new Collection('foo');
    }

    public function test_that_the_each_method_visits_every_element_of_the_collection()
    {
        $collection = new Collection([
            (object)['value' => true],
            (object)['value' => true],
            (object)['value' => true]
        ]);

        $negate = function ($elem) {
            $elem->value = ! $elem->value;
        };

        $collection->each($negate);

        $this->assertFalse($collection[0]->value);
        $this->assertFalse($collection[1]->value);
        $this->assertFalse($collection[2]->value);
    }

    public function test_that_the_map_method_returns_a_new_Collection_of_mapped_elements()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $mapped = $collection->map('strtoupper');

        $this->assertNotSame($collection, $mapped);

        $this->assertEquals('foo', $collection[0]);
        $this->assertEquals('bar', $collection[1]);
        $this->assertEquals('baz', $collection[2]);

        $this->assertEquals('FOO', $mapped[0]);
        $this->assertEquals('BAR', $mapped[1]);
        $this->assertEquals('BAZ', $mapped[2]);
    }

    public function test_that_the_reduce_method_returns_a_reduced_value_of_the_collection()
    {
        $collection = new Collection([1, 2, 3]);

        $total = $collection->reduce(function ($sum, $num) {
            return $sum + $num;
        }, 0);

        $this->assertEquals(6, $total);
    }

    public function test_that_the_filter_method_removes_expected_elements_from_the_collection()
    {
        $collection = new Collection([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $odds = $collection->filter(function ($elem) {
            return $elem % 2;
        });

        $this->assertNotSame($collection, $odds);

        $this->assertCount(5, $odds);

        $this->assertEquals(1, $odds[0]);
        $this->assertEquals(3, $odds[1]);
        $this->assertEquals(5, $odds[2]);
        $this->assertEquals(7, $odds[3]);
        $this->assertEquals(9, $odds[4]);
    }

    public function test_that_the_filter_method_removes_falsey_elements_from_the_collection_by_default()
    {
        $collection = new Collection([
            false,
            0,
            0.0,
            '',
            '0',
            [],
            null
        ]);

        $shouldBeEmpty = $collection->filter();

        $this->assertCount(0, $shouldBeEmpty);
    }

    /**
     * @dataProvider sliceProvider
     */
    public function test_that_the_slice_method_returns_the_expected_portion_of_the_collection($offset, $length, $expected)
    {
        $collection = new Collection([
            'lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $slice = $collection->slice($offset, $length);

        $this->assertNotSame($collection, $slice);

        $this->assertEquals($expected, $slice->toArray());
    }

    public function sliceProvider()
    {
        return [
            [0, 1, ['lorem']],
            [1, 1, ['ipsum']],
            [2, 4, ['dolor', 'sit', 'amet', 'consectetur']],
            [8, 3, ['sed', 'do']],
            [8, 2, ['sed', 'do']],
            [8, null, ['sed', 'do']],
            [-3, 3, ['elit', 'sed', 'do']],
            [5, -3, ['consectetur', 'adipiscing']],
            [-5, -5, []],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage This slice would have a negative length, refer to the documentation for usage.
     */
    public function test_that_the_slice_method_throws_an_exception_when_the_resulting_slice_would_contain_less_than_zero_elements()
    {
        $collection = new Collection([
            'lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $collection->slice(9, -2);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Unknown or invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_accessing_invalid_indexes_in_the_collection_causes_an_exception($index)
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection[$index];
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Unknown or invalid index
     */
    public function test_that_setting_invalid_indexes_in_the_collection_causes_an_exception()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection[-1] = 'wat';
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Unknown or invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_checking_the_existence_of_an_element_at_invalid_indexes_in_the_collection_causes_and_exception($index)
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        isset($collection[$index]);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Unknown or invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_unsetting_an_element_at_invalid_indexes_in_the_collection_causes_and_exception($index)
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        unset($collection[$index]);
    }

    public function invalidIndexProvider()
    {
        return [
            [-1],
            [3],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unknown or invalid index
     */
    public function test_that_accessing_an_element_at_a_invalid_string_index_throws_an_exception()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection['foo'];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid index
     */
    public function test_that_setting_an_element_at_a_invalid_string_index_throws_an_exception()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection['foo'] = 'bar';
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid index
     */
    public function test_that_unsetting_an_element_at_a_invalid_string_index_throws_an_exception()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        unset($collection['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid index
     */
    public function test_that_checking_the_existence_of_an_element_at_a_invalid_string_index_throws_an_exception()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        isset($collection['foo']);
    }

    public function test_that_flattening_a_collection_using_the_default_flatten_function_flattens_a_two_dimensional_array()
    {
        $arrayOfArrays = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
            [10]
        ];

        $collection = new Collection($arrayOfArrays);
        $flattened = $collection->flatten();

        $this->assertCount(10, $flattened);
        $this->assertEquals([1,2, 3, 4, 5, 6, 7, 8, 9, 10], $flattened->toArray());
    }

    public function test_that_flattening_a_collection_using_a_custom_function_flattens_the_collection_as_expected()
    {
        $arrayOfArrays = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
            [10]
        ];

        $dontFlatten = function ($elem) {
            return [$elem];
        };

        $collection = new Collection($arrayOfArrays);
        $flattened = $collection->flatten($dontFlatten);

        $this->assertCount(4, $flattened);
        $this->assertEquals($arrayOfArrays, $flattened->toArray());
    }

    public function test_that_flattening_a_collection_using_a_recursive_function_flattens_the_collection_as_expected()
    {
        $deepNest = [
            [[1, 2], [3, 4]],
            [[5, 6], [7, 8]],
            [[9, 10]]
        ];

        $recursiveFlatten = function ($elem) {
            $flattened = [];
            array_walk_recursive($elem, function ($elem) use (&$flattened) {
                $flattened[] = $elem;
            });
            return $flattened;
        };

        $collection = new Collection($deepNest);
        $flattened = $collection->flatten($recursiveFlatten);

        $this->assertCount(10, $flattened);
        $this->assertEquals([1,2, 3, 4, 5, 6, 7, 8, 9, 10], $flattened->toArray());
    }

    public function test_that_the_contains_method_returns_correctly_when_looking_for_scalar_values()
    {
        $collection = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertTrue($collection->contains(1));
        $this->assertTrue($collection->contains(5));
        $this->assertTrue($collection->contains(10));

        $this->assertFalse($collection->contains(42));
    }

    public function test_that_the_contains_method_returns_correctly_when_using_a_custom_equality_function()
    {
        $collection = new Collection([
            'Lorem ipsum dolor',
            'sit amet consectetur',
            'adipiscing elit sed',
            'do'
        ]);

        $hasWord = function ($searchWord) {
            return function ($elem) use ($searchWord) {
                return !! strstr($elem, $searchWord);
            };
        };

        $this->assertTrue($collection->contains($hasWord('elit')));
        $this->assertFalse($collection->contains($hasWord('foo')));
    }

    public function test_that_the_first_method_returns_the_first_element_of_the_collection_when_called_without_an_argument()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $collection->first());
    }

    public function test_that_the_first_method_returns_the_first_matching_element_of_the_collection_when_called_with_a_scalar_argument()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $collection->first(5));
    }

    public function test_that_the_first_method_returns_null_when_no_matching_scalar_element_is_found_in_the_collection()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertNull($collection->first(42));
    }

    public function test_that_the_first_method_returns_the_first_matching_element_of_the_collection_when_called_with_a_custom_function()
    {
        $collection = new Collection([
            'Lorem ipsum dolor',
            'sit amet consectetur',
            'adipiscing elit sed',
            'do'
        ]);

        $matcher = function ($elem) {
            return false !== strpos($elem, 'sit amet');
        };

        $this->assertEquals('sit amet consectetur', $collection->first($matcher));
    }

    public function test_that_the_first_method_returns_null_when_no_matching_element_is_found_in_the_collection_using_a_custom_function()
    {
        $collection = new Collection([
            'Lorem ipsum dolor',
            'sit amet consectetur',
            'adipiscing elit sed',
            'do'
        ]);

        $matcher = function ($elem) {
            return false !== strpos($elem, 'foo bar baz');
        };

        $this->assertNull($collection->first($matcher));
    }

    public function test_that_the_last_method_returns_the_last_element_of_the_collection_when_called_without_an_argument()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $collection->last());
    }

    public function test_that_the_last_method_returns_the_last_matching_element_of_the_collection_when_called_with_a_scalar_argument()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $collection->last(1));
    }

    public function test_that_the_last_method_returns_null_when_no_matching_scalar_element_is_found_in_the_collection()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertNull($collection->last(42));
    }

    public function test_that_the_last_method_returns_the_last_matching_element_of_the_collection_when_called_with_a_custom_function()
    {
        $collection = new Collection([
            'Lorem ipsum dolor',
            'sit amet consectetur',
            'adipiscing elit sed',
            'do'
        ]);

        $matcher = function ($elem) {
            return false !== strpos($elem, 'sit amet');
        };

        $this->assertEquals('sit amet consectetur', $collection->last($matcher));
    }

    public function test_that_the_last_method_returns_null_when_no_matching_element_is_found_in_the_collection_using_a_custom_function()
    {
        $collection = new Collection([
            'Lorem ipsum dolor',
            'sit amet consectetur',
            'adipiscing elit sed',
            'do'
        ]);

        $matcher = function ($elem) {
            return false !== strpos($elem, 'foo bar baz');
        };

        $this->assertNull($collection->last($matcher));
    }

    /**
     * @dataProvider reverseProvider
     */
    public function test_that_the_reverse_method_returns_a_new_Collection_with_reversed_elements($original, $reversed)
    {
        $collection = new Collection($original);
        $this->assertEquals($reversed, $collection->reverse()->toArray());
    }

    public function reverseProvider()
    {
        return [
            [[1, 2, 3, 4, 5], [5, 4, 3, 2, 1]],
            [[1, 2, 3, 4], [ 4, 3, 2, 1]],
        ];
    }

    public function test_that_the_append_method_adds_an_element_to_the_end_of_the_collection()
    {
        $collection = new Collection(1);

        $this->assertCount(0, $collection);

        $collection->append('foo');

        $this->assertCount(1, $collection);
        $this->assertEquals('foo', $collection[0]);
    }

    public function test_that_the_appending_to_a_full_Collection_causes_the_collection_to_grow()
    {
        $collection = new Collection(['foo']);

        $this->assertCount(1, $collection);

        $collection->append('bar');

        $this->assertCount(2, $collection);
    }

    public function test_that_the_groupBy_method_correctly_groups_elements_of_the_collection()
    {
        $collection = new Collection([
            'Lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $grouped = $collection->groupBy(function ($elem) {
            return strlen($elem);
        });

        $expected = [
            ['Lorem', 'ipsum', 'dolor'],
            ['sit', 'sed'],
            ['amet', 'elit'],
            ['consectetur'],
            ['adipiscing'],
            ['do']
        ];

        $this->assertCount(6, $grouped);
        for ($i = 0; $i < 6; $i++) {
            $this->assertEquals($expected[$i], $grouped[$i]->toArray());
        }
    }

    public function test_that_the_pop_method_removes_and_returns_the_last_element_of_the_collection()
    {
        $collection = new Collection(['foo', 'bar', 'baz']);

        $this->assertCount(3, $collection);
        $this->assertEquals('baz', $collection->pop());

        $this->assertCount(2, $collection);
        $this->assertNull($collection[2]);
    }

    public function test_that_the_pop_method_returns_null_when_the_collection_is_empty()
    {
        $collection = new Collection(1);

        $this->assertCount(0, $collection);
        $this->assertNull($collection->pop());
    }

    public function test_that_the_push_method_adds_an_element_to_the_end_of_the_collection()
    {
        $collection = new Collection(1);

        $this->assertCount(0, $collection);

        $collection->push('foo');

        $this->assertCount(1, $collection);
        $this->assertEquals('foo', $collection[0]);
    }

    public function test_that_pushing_onto_a_full_Collection_causes_the_collection_to_grow()
    {
        $collection = new Collection(['foo']);

        $this->assertCount(1, $collection);

        $collection->push('bar');

        $this->assertCount(2, $collection);
    }

    public function test_that_adding_an_element_past_the_end_of_the_collection_grows_the_collection_to_accomodate_the_element()
    {
        $collection = new Collection(['foo', 'bar']);
        $collection->append('baz');

        $this->assertCount(3, $collection);
        $this->assertEquals('baz', $collection[2]);
    }

    public function test_that_the_prepend_method_adds_the_element_to_the_start_of_the_collection()
    {
        $collection = new Collection(['bar']);

        $this->assertCount(1, $collection);
        $this->assertEquals('bar', $collection[0]);

        $collection->prepend('foo');

        $this->assertCount(2, $collection);
        $this->assertEquals('foo', $collection[0]);
        $this->assertEquals('bar', $collection[1]);
    }

    public function test_that_a_Collection_is_iterable()
    {
        $collection = new Collection(['foo', 'bar', 'baz', null, null]);

        $elems = [];
        foreach ($collection as $index => $elem) {
            $elems[$index] = $elem;
        }

        $this->assertEquals($elems, $collection->toArray());
    }

    /**
     * @dataProvider arrayIndexSliceProvider
     */
    public function test_that_slices_can_be_taken_using_array_indexing($index, $expected)
    {
        $collection = new Collection([
            'Lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $this->assertEquals($expected, $collection[$index]->toArray());
    }

    public function arrayIndexSliceProvider()
    {
        return [
            ['1:3', ['ipsum', 'dolor', 'sit']],
            [':2', ['Lorem', 'ipsum', 'dolor']],
            [':-6', ['Lorem', 'ipsum', 'dolor', 'sit']],
            ['7:', ['elit', 'sed', 'do']],
            ['-3:', ['elit', 'sed', 'do']],
            [':-6', ['Lorem', 'ipsum', 'dolor', 'sit']],
            ['-3:8', ['elit', 'sed']],
            ['-5:-1', ['consectetur', 'adipiscing', 'elit', 'sed']],
            ['7:-1', ['elit', 'sed']],
            ['5,3', ['consectetur', 'adipiscing', 'elit']],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid slice range
     * @dataProvider invalidSliceIndexProvider
     */
    public function test_that_an_exception_is_thrown_when_indexing_with_invalid_slice_notation($index)
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection[$index];
    }

    public function invalidSliceIndexProvider()
    {
        return [
            ['foo:1'],
            ['1:foo']
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid take notation
     * @dataProvider invalidTakeIndexProvider
     */
    public function test_that_an_exception_is_thrown_when_indexing_with_invalid_take_notation($index)
    {
        $collection = new Collection(['foo', 'bar', 'baz']);
        $collection[$index];
    }

    public function invalidTakeIndexProvider()
    {
        return [
            [','],
            ['1,2,3']
        ];
    }

    public function test_that_indexes_containing_false_are_considered_populated()
    {
        $collection = new Collection([true, false, null]);

        $this->assertCount(2, $collection);
    }
}
