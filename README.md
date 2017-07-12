[![Latest Stable Version](https://poser.pugx.org/bentools/guzzle-throttle-middleware/v/stable)](https://packagist.org/packages/bentools/guzzle-throttle-middleware)
[![License](https://poser.pugx.org/bentools/guzzle-throttle-middleware/license)](https://packagist.org/packages/bentools/guzzle-throttle-middleware)
[![Build Status](https://img.shields.io/travis/bpolaszek/guzzle-throttle-middleware/master.svg?style=flat-square)](https://travis-ci.org/bpolaszek/guzzle-throttle-middleware)
[![Coverage Status](https://coveralls.io/repos/github/bpolaszek/guzzle-throttle-middleware/badge.svg?branch=master)](https://coveralls.io/github/bpolaszek/guzzle-throttle-middleware?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/bpolaszek/guzzle-throttle-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/bpolaszek/guzzle-throttle-middleware)
[![Total Downloads](https://poser.pugx.org/bentools/guzzle-throttle-middleware/downloads)](https://packagist.org/packages/bentools/guzzle-throttle-middleware)

# Guzzle Throttle Middleware

This middleware adds throttling capabilities to your [Guzzle](https://github.com/guzzle/guzzle) client.

This can be useful when some hosts limits your number of requests per second / per minute.

Installation
------------

> composer require bentools/guzzle-throttle-middleware


Counter storage
---------------

By default, request counters are stored in an array. 

But you can use the `PSR6Adapter` to store your counters within a [psr/cache](http://www.php-fig.org/psr/psr-6/) implementation,
such as [symfony/cache](https://symfony.com/doc/current/components/cache.html), and use shared storages like Redis, APCu, Memcached, ...

Usage
-----

For this middleware to work, you need to register some configurations.

A configuration is composed of:
* A Request matcher (to trigger or not the throttler, depending on the request content)
* A maximum number of requests
* The period, in seconds, during which the maximum number of requests apply.
* A storage key.

You can register as many configurations as you need. The 1st request matcher wins.

Example
-------

```php
namespace App\RequestMatcher;

use BenTools\Psr7\RequestMatcherInterface;
use Psr\Http\Message\RequestInterface;

class ExampleOrgRequestMatcher implements RequestMatcherInterface
{
    /**
     * @inheritDoc
     */
    public function matchRequest(RequestInterface $request)
    {
        return false !== strpos($request->getUri()->getHost(), 'example.org');
    }
}
```
```php
use App\RequestMatcher\ExampleOrgRequestMatcher;
use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\ThrottleConfiguration;
use BenTools\GuzzleHttp\Middleware\ThrottleMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

require_once __DIR__ . '/vendor/autoload.php';

$stack = HandlerStack::create();
$middleware = new ThrottleMiddleware(new ArrayAdapter());

// Max 1 request per second
$maxRequests = 1;
$durationInSeconds = 1;
$middleware->registerConfiguration(
    new ThrottleConfiguration(new ExampleOrgRequestMatcher(), $maxRequests, $durationInSeconds, 'example')
);

$stack->push($middleware, 'throttle');
$client = new Client([
    'handler' => $stack,
]);

$client->get('http://www.example.org'); // Will be executed immediately
$client->get('http://www.example.org'); // Will be executed in 1 second
```

Tests
-----

> ./vendor/bin/phpunit


Known issues
------------
Due to PHP's synchronous behaviour, remember that throttling means calling `sleep()` or `usleep()` functions, which will delay your entire script, and not only the current request.

This means throttling will also block Guzzle's asynchronous requests when using `CurlMultiHandler`.

To prevent this, you may have a look at [bentools/guzzle-queue-handler](https://github.com/bpolaszek/guzzle-queue-handler), a handler that delegates asynchronous requests to PHP workers (Beanstalk, RabbitMQ, Redis, ...).

You can then enable throttling only on workers.


See also
--------

* [bentools/guzzle-queue-handler](https://github.com/bpolaszek/guzzle-queue-handler) - A queue handler to process Guzzle 6+ requests within a work queue.
* [kevinrob/guzzle-cache-middleware](https://github.com/Kevinrob/guzzle-cache-middleware) - A HTTP Cache for Guzzle 6. It's a simple Middleware to be added in the HandlerStack.
* [bentools/guzzle-duration-middleware](https://github.com/bpolaszek/guzzle-duration-middleware) - A Guzzle 6+ Middleware that adds a X-Request-Duration header to all responses to monitor response times.