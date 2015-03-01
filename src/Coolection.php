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
        $map = clone $this;
        for ($i = 0; $i < $this->size; $i++) {
            $map[$i] = $func($this->elems[$i]);
        }
        return $map;
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
