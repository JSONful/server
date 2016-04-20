<?php

namespace JSONful\Session;

class Native implements Storage
{
    public function __construct($id)
    {
        ini_set("session.use_cookies", 0);
        ini_set("session.use_only_cookies", 0);
        ini_set("session.cache_limiter", "");
        ini_set('session.gc_maxlifetime', 60 * 60 * 30);

        if ($id) {
            session_id($id);
        }
        session_start();
        $this->sessionId = session_id();
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
        return $this;
    }
    
    public function destroy()
    {
        session_destroy();
        $this->sessionId = null;
    }

    public function getAll()
    {
        return $_SESSION;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $_SESSION)) {
            return null;
        }
        return $_SESSION[$name];
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }
}
