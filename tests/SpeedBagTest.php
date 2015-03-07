<?php

/*
 * (c) Colin DeCarlo <colin@thedecarlos.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

use SpeedBag\SpeedBag;

class SpeedBagTest extends PHPUnit_Framework_TestCase
{
    public function test_that_the_each_method_visits_every_element_of_the_collection()
    {
        $speedBag = new SpeedBag([
            (object)['value' => true],
            (object)['value' => true],
            (object)['value' => true]
        ]);

        $negate = function ($elem) {
            $elem->value = ! $elem->value;
        };

        $speedBag->each($negate);

        $this->assertFalse($speedBag[0]->value);
        $this->assertFalse($speedBag[1]->value);
        $this->assertFalse($speedBag[2]->value);
    }

    public function test_that_the_map_method_returns_a_new_SpeedBag_of_mapped_elements()
    {
        $speedBag = new SpeedBag(['foo', 'bar', 'baz']);
        $mapped = $speedBag->map('strtoupper');

        $this->assertNotSame($speedBag, $mapped);

        $this->assertEquals('foo', $speedBag[0]);
        $this->assertEquals('bar', $speedBag[1]);
        $this->assertEquals('baz', $speedBag[2]);

        $this->assertEquals('FOO', $mapped[0]);
        $this->assertEquals('BAR', $mapped[1]);
        $this->assertEquals('BAZ', $mapped[2]);
    }

    public function test_that_the_reduce_method_returns_a_reduced_value_of_the_collection()
    {
        $speedBag = new SpeedBag([1, 2, 3]);

        $total = $speedBag->reduce(function ($sum, $num) {
            return $sum + $num;
        }, 0);

        $this->assertEquals(6, $total);
    }

    public function test_that_the_filter_method_removes_expected_elements_from_the_collection()
    {
        $speedBag = new SpeedBag([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $odds = $speedBag->filter(function ($elem) {
            return $elem % 2;
        });

        $this->assertNotSame($speedBag, $odds);

        $this->assertCount(5, $odds);

        $this->assertEquals(1, $odds[0]);
        $this->assertEquals(3, $odds[1]);
        $this->assertEquals(5, $odds[2]);
        $this->assertEquals(7, $odds[3]);
        $this->assertEquals(9, $odds[4]);
    }

    public function test_that_the_filter_method_removes_falsey_elements_from_the_collection_by_default()
    {
        $speedBag = new SpeedBag([
            false,
            0,
            0.0,
            '',
            '0',
            [],
            null
        ]);

        $shouldBeEmpty = $speedBag->filter();

        $this->assertCount(0, $shouldBeEmpty);
    }

    /**
     * @dataProvider sliceProvider
     */
    public function test_that_the_slice_method_returns_the_expected_portion_of_the_collection($offset, $length, $expected)
    {
        $speedBag = new SpeedBag([
            'lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $slice = $speedBag->slice($offset, $length);

        $this->assertNotSame($speedBag, $slice);

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
        $speedBag = new SpeedBag([
            'lorem', 'ipsum', 'dolor', 'sit', 'amet',
            'consectetur', 'adipiscing', 'elit', 'sed', 'do'
        ]);

        $speedBag->slice(9, -2);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_accessing_invalid_indexes_in_the_collection_causes_an_exception($index)
    {
        $speedBag = new SpeedBag(['foo', 'bar', 'baz']);
        $speedBag[$index];
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_setting_invalid_indexes_in_the_collection_causes_an_exception($index)
    {
        $speedBag = new SpeedBag(['foo', 'bar', 'baz']);
        $speedBag[$index] = 'wat';
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_checking_the_existence_of_an_element_at_invalid_indexes_in_the_collection_causes_and_exception($index)
    {
        $speedBag = new SpeedBag(['foo', 'bar', 'baz']);
        isset($speedBag[$index]);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Invalid index
     * @dataProvider invalidIndexProvider
     */
    public function test_that_unsetting_an_element_at_invalid_indexes_in_the_collection_causes_and_exception($index)
    {
        $speedBag = new SpeedBag(['foo', 'bar', 'baz']);
        unset($speedBag[$index]);
    }

    public function invalidIndexProvider()
    {
        return [
            [-1],
            [3]
        ];
    }

    public function test_that_flattening_a_collection_using_the_default_flatten_function_flattens_a_two_dimensional_array()
    {
        $arrayOfArrays = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
            [10]
        ];

        $speedBag = new SpeedBag($arrayOfArrays);
        $flattened = $speedBag->flatten();

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

        $speedBag = new SpeedBag($arrayOfArrays);
        $flattened = $speedBag->flatten($dontFlatten);

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

        $speedBag = new SpeedBag($deepNest);
        $flattened = $speedBag->flatten($recursiveFlatten);

        $this->assertCount(10, $flattened);
        $this->assertEquals([1,2, 3, 4, 5, 6, 7, 8, 9, 10], $flattened->toArray());
    }
}
