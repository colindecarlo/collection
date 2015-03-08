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
    protected $size;
    protected $reversed;

    public function __construct($size, $reversed = false)
    {
        $this->reversed = $reversed;

        if (is_array($size)) {
            $this->fromArray($size);
            return;
        }

        if ($size instanceof SplFixedArray) {
            $this->fromFixedArray($size);
            return;
        }

        $this->elems = new SplFixedArray($size);
        $this->size = $size;
    }

    protected function fromArray($array)
    {
        $this->elems = SplFixedArray::fromArray($array, false);
        $this->size = $this->elems->count();
    }

    protected function fromFixedArray($fixedArray)
    {
        $this->elems = $fixedArray;
        $this->size = $this->elems->count();
    }

    public function map($func)
    {
        $mapped = new static($this->size);
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
            $offset = $this->size + $offset;
        }

        if ($length === null) {
            $length = $this->size - $offset;
        }

        if ($length < 0) {
            $length = $this->size - $offset + $length;
        }

        $length = $offset + $length < $this->size ? $length : $this->size - $offset;

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
        return new static(clone $this->elems, true);
    }

    public function toArray()
    {
        $asArray = $this->elems->toArray();
        return $this->reversed ? array_reverse($asArray) : $asArray;
    }

    public function offsetGet($index)
    {
        $this->assertBoundaries($index);

        return $this->elems[$this->getIndex($index)];
    }

    public function offsetSet($index, $value)
    {
        $this->assertBoundaries($index);

        $this->elems[$this->getIndex($index)] = $value;
    }

    public function offsetExists($index)
    {
        $this->assertBoundaries($index);

        return $index < $this->size;
    }

    public function offsetUnset($index)
    {
        $this->assertBoundaries($index);

        unset($this->elems[$this->getIndex($index)]);
    }

    protected function getIndex($index)
    {
        return $this->reversed ? ($this->size - 1) - $index : $index;
    }

    protected function assertBoundaries($index)
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException('Invalid index ' . $index);
        }
    }

    public function count()
    {
        return $this->elems->count();
    }
}
