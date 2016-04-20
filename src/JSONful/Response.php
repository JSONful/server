<?php

namespace JSONful;

class Response
{
    protected $server;
    protected $httpHeaders = array();
    protected $responses;

    public function __construct(Server $server, $responses)
    {
        $this->server      = $server;
        $this->responses   = $responses; 
        $this->httpHeaders = array(
            'Access-Control-Allow-Origin: *',
            'Content-Type: application/json',
            'Access-Control-Allow-Credentials: false',
            'Access-Control-Allow-Methods: POST, OPTIONS',
            'Access-Control-Allow-Headers: DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Accept-Encoding,Accept,Authorization,Origin',
        );

        $keys = array();
        foreach (array_merge($this->httpHeaders, headers_list()) as $header) {
            list($key, ) = explode(":", $header);
            $keys[] = $key;
        }
        $this->httpHeaders[] = 'Access-Control-Expose-Headers: ' . implode(",", array_unique($keys));
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getHeaders()
    {
        return $this->httpHeaders;
    }

    public function send()
    {
        foreach ($this->httpHeaders as $header) {
            header($header);
        }
        echo json_encode($this->responses);
    }
}
