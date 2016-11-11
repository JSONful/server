<?php
/*
 * Copyright (c) 2016 JSONful
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace JSONful\Session;

class Native implements Storage
{
    public function __construct($id)
    {
        ini_set("session.use_cookies", 0);
        ini_set("session.use_only_cookies", 0);
        ini_set("session.cache_limiter", "");
        ini_set("session.use_trans_sid", 0);
        ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 180);

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
