<?php

class Server extends JSONful\Server {
    public function handle(Array $request = []) {
        return $this->process($request);
    }
}

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public static function requests()
    {
        $args = array();
        foreach (glob(__DIR__ . '/features/*.json') as $file) {
            $arg = json_decode(file_get_contents($file), true);
            $args[] = [$arg['request'], $arg['response']];
        }
        return $args;
    }

    /**
     *  @dataProvider requests
     */
    public function testRouting(Array $request, $response)
    {
        $server = new Server(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals($response, $server->handle($request)->getResponses()['responses']);
    }

    /**
     *  @dataProvider requests
     */
    public function testPreresponse(Array $request, $response)
    {
        $server = new Server(__DIR__ . '/apps');
        $GLOBALS['encrypt'] = true;
        $response = ['responses' => $response];
        do_encrypt($response);
        $this->assertEquals($response, $server->handle($request)->getResponses());
    }

    protected function hasSessionHeader(Array $headers)
    {
        foreach ($headers as $header) {
            if (preg_match("/X-Session-Id/", $header)) {
                return true;
            }
        }

        return false;
    }

    public function testSessionNoSession()
    {
        $GLOBALS['encrypt'] = false;

        $requests = new JSONful\Client\Requests;
        $requests->add('xxx', []);

        /** server instance */
        $server = new Server(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';


        $response = $server->handle($requests->toArray())->getResponses();
        $this->assertEquals([['foo' => 'bar']], $response['responses']);
        $this->assertTrue(empty($response['session']));

        $requests = new JSONful\Client\Requests;
        $requests->add('session', ['remember' => 1]);

        $response = $server->handle($requests->toArray())->getResponses();
        $this->assertEquals([null], $response['responses']);
        $this->assertFalse(empty($response['session']));
        define('X_SESSION', $response['session']);

    }

    /**
     *  @dependsOn testSessionNoSession
     */
    public function testSessionFirst()
    {
        $server = new Server(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';

        $requests = new JSONful\Client\Requests;
        $requests->add('session', ['remember' => 2]);
        $requests->setSession(X_SESSION);

        $response = $server->handle($requests->toArray())->getResponses();
        $this->assertEquals([1], $response['responses']);
        $this->assertEquals(X_SESSION, $response['session']);
    }

    /**
     *  @dependsOn testSessionNoSession
     */
    public function testSessionSession()
    {
        /** new - server instance */
        $server = new Server;
        $server->addDirectory(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';

        $requests = new JSONful\Client\Requests;
        $requests->add('session', ['remember' => 3]);
        $requests->setSession(X_SESSION);

        $response = $server->handle($requests->toArray())->getResponses();
        $this->assertEquals([2], $response['responses']);
    }

    /** @expectedException RuntimeException */
    public function testAddDirectoryException()
    {
        $server = new Server(__DIR__ . '/apps');
        $server->handle([]);

        $server->addDirectory(__DIR__);
    }
}
