<?php

/**
 *  @API xxx
 */
function apps(Array $args, $session)
{
    if (empty($args['added'])) {
        throw new RuntimeException;
    }
    return array('foo' => 'bar');
}
