<?php

namespace JSONful\Client;

class Request
{
    protected $name;

    protected $args;

    protected $response;

    public function __construct($name, $args)
    {
        $this->name = $name;
        $this->args = $args;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getArguments()
    {
        return $this->args;
    }
}
