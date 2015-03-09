<?php

namespace SpeedBag;

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

class SpeedBag implements ArrayAccess, Countable
{
    protected $elems;
    protected $capacity;
    protected $size;

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
            if ($this->elems[$i]) {
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

    public function append($elem) {
        $this->offsetSet($this->size, $elem);
    }

    public function push($elem) {
        $this->append($elem);
    }

    public function pop()
    {
        return $this->size > 0 ? $this->elems[--$this->size] : null;
    }

    public function toArray()
    {
        return array_filter($this->elems->toArray());
    }

    public function offsetGet($index)
    {
        $this->assertBoundaries($index);

        return $this->elems[$index];
    }

    public function offsetSet($index, $value)
    {
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
}
