<?php

namespace JSONful\Client;

class Requests
{
    protected $requests = array();
    protected $globals = array();

    public function add($name, $args = [])
    {
        $request = new Request($name, $args);
        $this->requests[] = $request;
        return $request;
    }

    public function setSession($session)
    {
        $this->globals['session'] = (string)$session;
        return $this;
    }

    public function toArray()
    {
        $reqs = array();
        foreach ($this->requests as $req) {
            $reqs[] = [$req->getName(), $req->getArguments()];
        }

        return array_merge($this->globals, [
            'requests' => $reqs,
        ]);
    }
}
