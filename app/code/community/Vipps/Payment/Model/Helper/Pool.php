<?php
/**
 * Copyright 2019 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *    documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

/**
 * Class Pool
 */
class Vipps_Payment_Model_Helper_Pool
{
    /**
     * @var \ArrayObject
     */
    private $pool;

    /**
     * Pool constructor.
     * @param array $array
     */
    public function __construct($array = [])
    {
        $this->pool = new \ArrayObject();

        foreach ($array as $key => $value) {
            $this->add($key, $value);
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function add($key, $value)
    {
        $this->pool->offsetSet($key, $value);

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->pool->offsetGet($key);
    }

    /**
     * @return \ArrayObject
     */
    public function getAll()
    {
        return $this->pool;
    }
}
