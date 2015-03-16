<?php

namespace SpeedBag;

use Iterator;
use Countable;
use ArrayAccess;
use SplFixedArray;
use OutOfBoundsException;
use InvalidArgumentException;

/*
 * (c) Colin DeCarlo <colin@thedecarlos.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

class SpeedBag implements ArrayAccess, Countable, Iterator
{
    protected $elems;
    protected $capacity;
    protected $size;

    protected $iteratorPosition = 0;

    public function __construct($capacity)
    {
        $this->elems = $this->buildElems($capacity);
        $this->capacity = $this->elems->count();
        $this->size = $this->lastNonNullIndex();
    }

    protected function buildElems($arg)
    {
        if (is_numeric($arg)) {
            return new SplFixedArray($arg);
        }

        if (is_array($arg)) {
            return SplFixedArray::fromArray($arg, false);
        }

        if ($arg instanceof SplFixedArray) {
            return $arg;
        }

        throw new InvalidArgumentException(
            'Invalid argument supplied to SpeedBag::__construct. (required: numeric|array|SplFixedArray)'
        );
    }

    protected function lastNonNullIndex()
    {
        for ($i = $this->capacity - 1; $i >= 0; $i--) {
            if ($this->elems[$i] !== null) {
                break;
            }
        }

        return $i >= 0 ? $i+1 : 0;
    }

    public function map($func)
    {
        $mapped = new static($this->capacity);
        for ($i = 0; $i < $this->size; $i++) {
            $mapped[$i] = $func($this->elems[$i]);
        }
        return $mapped;
    }

    public function each($func)
    {
        for ($i = 0; $i < $this->size; $i++) {
            $func($this->elems[$i]);
        }
        return $this;
    }

    public function reduce($func, $carry)
    {
        for ($i = 0; $i < $this->size; $i++) {
            $carry = $func($carry, $this->elems[$i]);
        }
        return $carry;
    }

    public function filter($func = null)
    {
        $identity = function ($elem) {
            return $elem;
        };

        $func = $func ?: $identity;

        $filtered = [];
        for ($i = 0, $k = 0; $i < $this->size; $i++) {
            if (! $func($this->elems[$i])) {
                continue;
            }
            $filtered[] = $this->elems[$i];
        }
        return new static($filtered);
    }

    public function slice($offset, $length = null)
    {
        if ($offset < 0) {
            $offset = $this->capacity + $offset;
        }

        if ($length === null) {
            $length = $this->capacity - $offset;
        }

        if ($length < 0) {
            $length = $this->capacity - $offset + $length;
        }

        $length = $offset + $length < $this->capacity ? $length : $this->size - $offset;

        if ($length < 0) {
            throw new InvalidArgumentException(
                'This slice would have a negative length, refer to the documentation for usage.'
            );
        }

        $sliced = new SplFixedArray($length);
        for ($i = 0; $i < $length; $i++) {
            $sliced[$i] = $this->elems[$offset + $i];
        }
        return $sliced;
    }

    public function flatten($flattenWith = null)
    {
        $flattenWith = $flattenWith ?: function ($elem) {
            if (is_array($elem) || $elem instanceof Iterator) {
                return $elem;
            }

            if ($elem instanceof Traversable) {
                $bits = [];
                foreach ($elem as $bit) {
                    $bits[] = $bit;
                }
                return $bits;
            }

            return [$elem];
        };

        $mapped = $this->map($flattenWith);

        $totalElements = $mapped->reduce(function ($sum, $elem) {
            return $sum + count($elem);
        }, 0);

        $index = 0;
        return $mapped->reduce(function ($flattened, $elem) use (&$index) {
            for ($i = 0; $i < count($elem); $i++) {
                $flattened[$index++] = $elem[$i];
            }
            return $flattened;
        }, new static($totalElements));
    }

    public function contains($value)
    {
        return !! $this->first($value);
    }

    public function first($matching = null)
    {
        if (null === $matching) {
            return $this->elems[0];
        }

        $matching = $this->getMatchingFunction($matching);

        for ($i = 0; $i < $this->size; $i++) {
            if ($matching($this->elems[$i])) {
                return $this->elems[$i];
            }
        }

        return null;
    }

    public function last($matching = null)
    {
        if (null === $matching) {
            return $this->elems[$this->size - 1];
        }

        $matching = $this->getMatchingFunction($matching);

        for ($i = $this->size - 1; $i >= 0; $i--) {
            if ($matching($this->elems[$i])) {
                return $this->elems[$i];
            }
        }

        return null;
    }

    protected function getMatchingFunction($matching)
    {
        return is_callable($matching)
            ? $matching
            : function ($elem) use ($matching) {
                return $elem == $matching;
            };
    }

    public function reverse()
    {
        return new static(array_reverse($this->toArray()));
    }

    public function groupBy($getGroupKey)
    {
        $groups = [];
        for ($i = 0; $i < $this->size; $i++) {
            $groups[$getGroupKey($this->elems[$i])][] = $this->elems[$i];
        }

        return array_reduce($groups, function ($grouped, $group) {
            $grouped->append(new static($group));
            return $grouped;
        }, new static(count($groups)));
    }

    public function prepend($elem)
    {
        for ($i = $this->size - 1; $i >= 0; $i--) {
            $this->offsetSet($i+1, $this->elems[$i]);
        }
        $this->elems[0] = $elem;
    }

    public function append($elem)
    {
        $this->offsetSet($this->size, $elem);
    }

    public function push($elem)
    {
        $this->append($elem);
    }

    public function pop()
    {
        if ($this->size == 0) {
            return null;
        }

        $elem = $this->elems[--$this->size];
        $this->elems[$this->size] = null;

        return $elem;
    }

    public function toArray()
    {
        return array_filter($this->elems->toArray());
    }

    public function offsetGet($index)
    {
        if (is_int($index)) {
            return $this->getElementAt($index);
        }

        if ($this->isSlice($index)) {
            list($offset, $length) = $this->getSliceOffsetAndLength($index);
            return $this->slice($offset, $length);
        }

        throw new IndexOutOfBoundsException('Unknown or invalid index: ' . $index);
    }

    protected function isSlice($index)
    {
        if (strpos($index, ':') !== false) {
            return true;
        }

        if (strpos($index, ',') !== false) {
            return true;
        }

        return false;
    }

    protected function getSliceOffsetAndLength($slice)
    {
        return strpos($slice, ':') !== false
            ? $this->getSliceOffsetAndLengthByRange($slice)
            : $this->getSliceOffsetAndLengthByTake($slice);
    }

    protected function getSliceOffsetAndLengthByRange($range)
    {
        $split = explode(':', $range, 2);

        $start = trim($split[0]) ?: 0;
        $end = trim($split[1]) ?: null;

        if ($this->isInvalidRange($start, $end)) {
            throw new InvalidArgumentException('Invalid slice range: ' . $range);
        }

        if (is_int($end)) {
            $end = $end < 0 ? $end : $end - ($this->size - 1);
        }

        return [$start, $end];
    }

    protected function isInvalidRange($start, $end) {
        if (! is_int($start)) {
            return true;
        }

        if (! is_int($end) && $end !== null) {
            return true;
        }

        return false;
    }

    protected function getSliceOffsetAndLengthByTake($take)
    {
        $split = explode(',', $take);

        if (count($split) !== 2) {
            throw new InvalidArgumentException('Invalid take notation: ' . $take);
        }

        $offset = trim($split[0]);
        $length = trim($split[1]);

        if (is_int($offset) && is_int($length) && $offset > 0 && $length > 0) {
            return [$offset, $length];
        }

        throw new InvalidArgumentException('Invalid take notation: ' . $take);
    }

    protected function getElementAt($index)
    {
        $this->assertBoundaries($index);

        return $this->elems[$index];
    }

    public function offsetSet($index, $value)
    {
        if ($index >= $this->capacity) {
            do {
                $this->capacity = floor(1.5 * $this->capacity) + 1;
            } while ($index >= $this->capacity);
            $this->elems->setSize($this->capacity);
        }

        $this->assertBoundaries($index);

        $this->elems[$index] = $value;
        $this->size = $index >= $this->size ? $index + 1 : $this->size;
    }

    public function offsetExists($index)
    {
        $this->assertBoundaries($index);

        return true;
    }

    public function offsetUnset($index)
    {
        $this->assertBoundaries($index);

        unset($this->elems[$index]);
    }

    protected function assertBoundaries($index)
    {
        if ($index < 0 || $index >= $this->capacity) {
            throw new OutOfBoundsException('Invalid index ' . $index);
        }
    }

    public function count()
    {
        return $this->size;
    }

    public function rewind()
    {
        $this->iteratorPosition = 0;
    }

    public function current()
    {
        return $this->offsetGet($this->iteratorPosition);
    }

    public function key()
    {
        return $this->iteratorPosition;
    }

    public function next()
    {
        $this->iteratorPosition++;
    }

    public function valid()
    {
        return $this->iteratorPosition < $this->size;
    }
}

/**
 * As far as I'm concerned, if it looks like an integer, it is.
 */
function is_int($int) {
    return (string)(int)$int === (string)$int;
}
