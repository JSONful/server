<?php

/**
 *    @API("is_prime")
 *
 *    The "bulk" calculation is done in a @initRequest, in this tests
 *    it makes no difference but it coves the following scenarios:
 *
 *        1. Imagine we have 5 calls to `is_prime`, and the datasource is a DB
 *            - It's much faster to query `IN` 
 *            - Having a single db query is faster (instead of 5)
 *        2. The @initRequest handler passes datas back to the `is_prime` through the server instance
 */
function is_prime_service(Array $args, $server)
{
    return $server['is_prime'][$args['q']];
}

/**
 *  @API("is_prime_2")
 *  @API("is_prime3")
 *
 *  Same as is_prime but the data exchange between @initRequest
 *  and the service is done throught the args
 */
function is_prime_service_2(Array $args, $server)
{
    return $args['result'];
}
