<?php

class scholar_cached_array implements ArrayAccess, Iterator
{
    protected $_cid;
    protected $_data  = array();
    protected $_dirty = false;

    public function __construct($id)
    {
        $this->_cid = 'scholar_cached_array:' . $id;

        if ($data = cache_get($this->_cid)) {
            $this->_data = (array) $data->data;
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    public function clear()
    {
        if ($this->_data) {
            $this->_data = array();
            $this->_dirty = true;
        }
    }

    // Podczas przekroczenia limitu czasu zostaja wywolane funkcje zamykające.
    // http://www.php.net/manual/en/function.set-time-limit.php#69957
    public function shutdown()
    {
        if ($this->_dirty) {
            ksort($this->_data);
            cache_set($this->_cid, $this->_data);
        }
    }

    public function offsetExists($key)
    {
        return isset($this->_data[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function offsetSet($key, $value)
    {
        $this->_data[$key] = $value;
        $this->_dirty = true;
    }

    public function offsetUnset($key)
    {
        if (isset($this->_data[$key])) {
            unset($this->_data[$key]);
            $this->_dirty = true;
        }        
    }

    public function rewind() {
        reset($this->_data);
    }

    public function current() {
        return current($this->_data);
    }

    public function key() {
        return key($this->_data);
    }

    public function next() {
        return next($this->_data);
    }

    public function valid() {
        return null !== key($this->_data);
    }
}

