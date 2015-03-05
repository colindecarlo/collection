<?php

namespace Coolection;

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

class Coolection implements ArrayAccess, Countable
{
    protected $elems;
    protected $size;

    public function __construct($size)
    {
        if (is_array($size)) {
            $this->fromArray($size);
            return;
        }

        $this->elems = new SplFixedArray($size);
        $this->size = $size;
    }

    protected function fromArray($array)
    {
        $this->elems = SplFixedArray::fromArray($array, false);
        $this->size = count($this);
    }

    public function map($func)
    {
        $mapped = new SplFixedArray($this->size);
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

    public function toArray()
    {
        return $this->elems->toArray();
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
    }

    public function offsetExists($index)
    {
        $this->assertBoundaries($index);

        return $index < $this->size;
    }

    public function offsetUnset($index)
    {
        $this->assertBoundaries($index);

        unset($this->elems[$index]);
    }

    protected function assertBoundaries($index)
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException('Invalid index' . $index);
        }
    }

    public function count()
    {
        return count($this->elems);
    }
}
