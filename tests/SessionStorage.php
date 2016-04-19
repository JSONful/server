<?php

class SessionStorage implements JSONful\Session\Storage
{
    static $xdata = array();
    public function __construct($id)
    {
        $this->id = $id ?: uniqid(true);
        $this->data = array();
        if (!empty(self::$xdata[$this->id])) {
            $this->data = self::$xdata[$this->id];
        }
    }

    public function __destruct()
    {
        self::$xdata[$this->id] = $this->data;
    }
    
    public function get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            return null;
        }
        return $this->data[$name];
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function getAll()
    {
        return $this->data;
    }

    public function destroy()
    {
        $this->data = [];
    }

    public function getSessionId()
    {
        self::$xdata[$this->id] = $this->data;
        return $this->id;
    }
}
