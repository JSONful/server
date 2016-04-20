<?php

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
        $this['_has_session'] = false;
        $this['session'] = $this->share(function($service) {
            $service['_has_session'] = true;
            return new $service['session_storage']($service['session_id']);
        });
    }

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
            'preResponse'   => $loader->getFunctions('preResponse'),
        );
    }

    /**
     *  Run custom initRequest when they have targeted a function name.
     *
     *  @param string       $handleName     handler name the initRequest is listening to
     *  @param mixed        &$argument      Arguments  
     *  @param TFunction    $event          Event function object
     *  @param TFunction    $handler        API call handler
     */
    protected function runInitRequest($handleName, &$argument, TFunction $event, TFunction $handler = null)
    {
        $arguments = array();
        foreach ($argument['requests'] as $id => $request) {
            if ($request[0] === $handleName) {
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
     *  @param TFunction    $handler        API call handler
     *
     *  @return {crodas\ApiServer\Response}     Response object
     */
    protected function runEvent($eventName, &$argument, TFunction $handler = null)
    {
        foreach ($this->events[$eventName] as $name => $event) {
            if ($eventName === 'initRequest' && is_string($name) ) {
                $this->runInitRequest($name, $argument, $event, $handler);
            } else if (!$handler || (is_numeric($name) || $handler->hasAnnotation($name))) {
                $event->call(array(&$argument, $this, $handler ? $handler->getAnnotation($name) : null));
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

    protected function processRequest($name, $args)
    {
        if (empty($this->functions[$name])) {
            return ['error' => true, 'text' => $name . ' is not a valid function'];
        }

        try {
            $function = $this->functions[$name];
            $this->runEvent('preRoute', $args, $function);
            $response = $function->call(array(&$args, $this));
            $this->runEvent('postRoute', $response, $function);
        } catch (Exception $e) {
            return ['error' => true, 'text' => $e->getMessage()];
        }

        return $response;
    }

    public function run()
    {
        if ($_SERVER["REQUEST_METHOD"] === 'OPTIONS') {
            $response = new Response($this, []);
        } else {
            $response = $this->handle();
        }
        return $response->send();
    }

    public function handle(Array $request = array())
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
                $responses[$id] = $this->processRequest($request[0], $request[1]);
            }
        } catch (RetryException $e) {
            $responses = self::RETRY_LATER;
        } catch (Exception $e) {
            $responses = self::INTERNAL_ERROR;
        }
        
        return $this->send($responses);
    }

}
