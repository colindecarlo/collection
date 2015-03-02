<?php

namespace Coolection;

use ArrayAccess;
use Countable;
use SplFixedArray;

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

    public function filter($func)
    {
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
            $offset = $this->size - $offset - 1;
        }

        if ($length === null) {
            $length = $this->size - $offset;
        }

        if ($length < 0) {
            $length = $this->size - $offset - $length;
        }

        $length = min($offset + $lenth, $this->size);

        $sliced = new SplFixedArray($length);
        for ($i = 0; $i < $length; $i++) {
            $sliced[$i] = $this->elems[$offset + $i];
        }
        return $sliced;
    }

    public function asPlainArray()
    {
        return $this->elems->toArray();
    }

    public function offsetGet($index)
    {
        return $index < $this->size ? $this->elems[$index] : null;
    }

    public function offsetSet($index, $value)
    {
        if ($index >= $this->size) {
            return;
        }

        $this->elems[$index] = $value;
    }

    public function offsetExists($index)
    {
        return $index < $this->size;
    }

    public function offsetUnset($index)
    {
        if ($index < $this->size) {
            return;
        }

        unset($this->elems[$index]);
    }

    public function count()
    {
        return count($this->elems);
    }
}
