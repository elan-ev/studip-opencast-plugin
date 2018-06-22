<?php
/**
 * OCJsonFile - handles json files
 */

class OCJsonFile implements ArrayAccess
{

    private $path = '';
    public $content = array();

    public function __construct($path)
    {
        $this->path = $path;
        if (!file_exists($path)) {
            $this->save();
        }
        $this->load();
    }

    public function is_empty()
    {
        return count($this->content) == 0;
    }

    public function load()
    {
        $this->content = json_decode(file_get_contents($this->path), TRUE);
    }

    public function save()
    {
        file_put_contents($this->path, json_encode($this->content));
    }

    public function offsetExists($offset)
    {
        $this->load();

        return array_key_exists($offset, $this->content);
    }

    public function &offsetGet($offset)
    {
        $this->load();

        return $this->content[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->load();
        $this->content[$offset] = $value;
        $this->save();
    }

    public function offsetUnset($offset)
    {
        $this->load();
        unset($this->content[$offset]);
        $this->save();
    }
}
