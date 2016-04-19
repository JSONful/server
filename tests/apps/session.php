<?php

/**
 *  @API session
 */
function session(Array $args, $server)
{
    $ret = $server['session']->get('remember');
    $server['session']->set('remember', $args['remember']);
    return $ret;
}
