# JSONful

JSONful is a framework which helps creating API servers.

## Features

1. Ease of use
  * It exposes regular PHP functions to the API, making it easy to integrate with any other framework and existing code.
  * It is easy to make cross domain requests (`$server['public'] = true;`)
  * The [javascript client](https://github.com/JSONful/client-js) works out of the box and it's optimized for performance
    * The client concatenates many requests and sending it in a single HTTP request.
    * It has no dependency, and it can be used with WebPack or directly. 

## Installation

```bash
composer require jsonful/server
```

## Usage

### `api.php`
```php
require __DIR__ . '/vendor/autoload.php';

$server new JSONful\Server(__DIR__ . '/apps');
$server->run();
```

### `apps/prime.php`

```php
/** @API("prime") */
function is_prime($number)
{
    if ($number <= 0) {
        return false;
    }
    $middle = ceil($number/2);
    for ($i = 2; $i <= $middle; ++$i) {
        if ($number % $i === 0) {
            return false;
        }
    }
    return true;
}

/** @API("ping") */
function ping() {
    return ['pong' => time()];
}
```

### `client.js`

```javascript
var client = new JSONful("https://api.myapp.net/");

client.exec("ping", function(err, response) {
  console.log(response); // {"pong": xxxx}
}
client.exec("prime", 99).then(function(response) {
  console.error(response); // false
});
```
