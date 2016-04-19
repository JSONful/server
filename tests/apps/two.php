<?php

/**
 *  @preResponse
 */
function do_encrypt(&$response)
{
    if (!empty($GLOBALS['encrypt'])) {
        $response['responses'] = base64_encode(json_encode($response['responses']));
    }
}

/** @API do_fail */
function do_fail()
{
}

/**
 *  @preRoute yyy
 *  @xxx
 */
function do_it()
{
}

/**
 *  @preRoute
 */
function all_request(Array & $args)
{
    $args['added'] = 1;
}
