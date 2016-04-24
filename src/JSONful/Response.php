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
            'Access-Control-Allow-Origin: ' . $server['public'] ? '*' : $_SERVER['HTTP_HOST'],
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
