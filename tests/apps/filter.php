<?php

/**
 *  @preRoute xxx
 */
function filter_xx(Array $args)
{
    throw new RuntimeException("Failed exception");
}

function is_prime($x) {
    $middle = ceil($x/2);
    for ($i = 2; $i <= $middle; ++$i) {
        if ($x % $i === 0) {
            return false;
        }
    }

    return true;
}

/**
 *  @initRequest do_fail
 */
function do_fail_initreq()
{
    throw new \Exception;
}

/**
 *  @initRequest retry_later
 */
function retry_later_init(Array $requests, $server) {
    throw new JSONful\RetryException;
}

/**
 *    @initRequest is_prime3
 */
function filter_prime_x(Array $requests, $server)
{
    foreach ($requests as &$request) {
        $request['result'] = is_prime($request['q']);
    }
}

/**
 *    @initRequest
 */
function filter_prime_1(Array $requests, $server)
{
    $is_prime = array();
    foreach ($requests['requests'] as $request) {
        if ($request[0] === 'is_prime') {
            $is_prime[$request[1]['q']] = is_prime($request[1]['q']);
        }
    }
    $server['is_prime'] = $is_prime;
}

/**
 *    @initRequest
 */
function filter_prime_2(Array &$requests, $server)
{
    foreach ($requests['requests'] as &$request) {
        if ($request[0] === 'is_prime_2') {
            $request[1]['result'] = is_prime($request[1]['q']);
        }
    }
}
