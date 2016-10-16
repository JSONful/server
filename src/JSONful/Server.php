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

use FunctionDiscovery;
use FunctionDiscovery\TFunction;
use RuntimeException;
use Exception;
use Pimple;

class Server extends Pimple
{
    const WRONG_REQ_METHOD  = -1;
    const INTERNAL_ERROR    = -2;
    const RETRY_LATER       = -3;

    protected $dirs = array(__DIR__);
    protected $functions;
    protected $events;

    public function __construct($dirs = null)
    {
        $this->dirs = $dirs ? (Array)$dirs : [];
        $this['session_storage'] = __NAMESPACE__ . '\Session\Native';
        $this['session_id']   = null;
        $this['public']       = false;
        $this['_has_session'] = false;
        $this['session'] = $this->share(function($service) {
            $service['_has_session'] = true;
            return new $service['session_storage']($service['session_id']);
        });
    }

    /**
     * Add another directory where the framework will be looking for 
     * functions to export as an API.
     *
     * @param String $dir   Directory
     *
     * @return Server
     */
    public function addDirectory($dir)
    {
        if (!empty($this->functions)) {
            throw new RuntimeException("You cannot add any directory, the server has booted already. You need to create another server instance");
        }
        $this->dirs[] = $dir;
        return $this;
    }

    /**
     *  Initialise the framework, it happens at most once before running routing the framework
     */
    protected function initialize()
    {
        if (!empty($this->functions)) {
            return;
        }

        $loader = new FunctionDiscovery($this->dirs);
        $this->functions  = $loader->getFunctions('api');
        $this->events = array(
            'initRequest'   => $loader->getFunctions('initRequest'),
            'preRoute'      => $loader->getFunctions('preRoute'),
            'postRoute'     => $loader->getFunctions('postRoute'),
            'preExec'      => $loader->getFunctions('preExec'),
            'postExec'     => $loader->getFunctions('postExec'),
            'preExecute'      => $loader->getFunctions('preExecute'),
            'postExecute'     => $loader->getFunctions('postExecute'),
            'preResponse'   => $loader->getFunctions('preResponse'),
        );
    }

    /**
     *  Run custom initRequest when they have targeted a function name.
     *
     *  @param string       $processName     processr name the initRequest is listening to
     *  @param mixed        &$argument      Arguments  
     *  @param TFunction    $event          Event function object
     *  @param TFunction    $processr        API call processr
     */
    protected function runInitRequest($processName, &$argument, TFunction $event, TFunction $processr = null)
    {
        $arguments = array();
        foreach ($argument['requests'] as $id => $request) {
            if ($request[0] === $processName) {
                $arguments[] = &$argument['requests'][$id][1];
            }
        }

        if (!empty($arguments)) {
            $event->call(array(&$arguments, $this));
        }
    }

    /**
     *  Run a given event
     *  
     *  @param string       $eventName      Event name to run
     *  @param mixed        &$argument      Arguments  
     *  @param TFunction    $processr        API call processr
     *
     *  @return {crodas\ApiServer\Response}     Response object
     */
    protected function runEvent($events, &$argument, TFunction $processr = null)
    {
        foreach (explode(",", $events) as $eventName) {
            foreach ($this->events[$eventName] as $name => $event) {
                if ($eventName === 'initRequest' && is_string($name) ) {
                    $this->runInitRequest($name, $argument, $event, $processr);
                } else if (!$processr || (is_numeric($name) || $processr->hasAnnotation($name))) {
                    $event->call(array(&$argument, $this, $processr ? $processr->getAnnotation($name) : null));
                }
            }
        }
    }

    /**
     *  Prepare the response and send it back to the client.
     *
     *  @param mixed $responses Response for the client
     *  @return {Response}
     */
    protected function send($responses)
    {
        $responses = compact('responses');
        $this->runEvent('preResponse', $responses);
        $headers = array();
        if ($this['_has_session']) {
            $responses['session'] = $this['session']->getSessionId();
        }
        return new Response($this, $responses);
    }

    /**
     * Process the requests. It makes sure the Request is POST and that it 
     * contains a valid JSON object. It also ensures the format is of the request
     * is valid. After all the validations it executes all the requests
     * and return a Response Object.
     *
     * @param Array $requests       Request Body
     * @return JSONful\Response     Response object
     */
    protected function process(Array $request = array())
    {
        $this->initialize();

        $request = $request ?: json_decode(file_get_contents('php://input'), true);
        if (empty($request) || empty($request['requests'])) {
            return $this->send(self::WRONG_REQ_METHOD);
        }

        if (!empty($request['session']) && is_scalar($request['session'])) {
            $this['session_id'] = (string)$request['session'];
        }

        try {
            $this->runEvent('initRequest', $request);

            $responses = array();
            foreach ($request['requests'] as $id => $request) {
                if (is_string($request)) {
                    $request = [$request, []];
                }
                if (empty($request[1])) {
                    $request[1] = [];
                }
                $responses[$id] = $this->execRequest($request[0], $request[1]);
            }
        } catch (RetryException $e) {
            $responses = self::RETRY_LATER;
        } catch (Exception $e) {
            $responses = self::INTERNAL_ERROR;
        }
        
        return $this->send($responses);
    }

    /**
     * Executes a request. It will run all the validations (preRoute/postRoute)
     * and executes the function.
     *
     * @param String $name      Function name
     * @param $args      Function arguments
     *
     * @return 
     */
    protected function execRequest($name, $args)
    {
        if (empty($this->functions[$name])) {
            return ['error' => true, 'text' => $name . ' is not a valid function'];
        }

        try {
            $function = $this->functions[$name];
            $this->runEvent('preRoute,preExecute,preExec', $args, $function);
            $response = $function->call(array(&$args, $this));
            $this->runEvent('postRoute,postExecute,postExec', $response, $function);
        } catch (Exception $e) {
            return ['error' => true, 'text' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Runs the framework
     *
     * @param Array $requests       Request Body
     * @return JSONful\Response     Response object
     */
    public function run()
    {
        if (!empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === 'OPTIONS') {
            $response = new Response($this, []);
        } else {
            $response = $this->process();
        }
        return $response->send();
    }

}
