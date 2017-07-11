<?php

namespace BenTools\GuzzleHttp\Middleware\Tests;

use BenTools\GuzzleHttp\Middleware\DurationHeaderMiddleware;
use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\ThrottleConfiguration;
use BenTools\GuzzleHttp\Middleware\ThrottleMiddleware;
use BenTools\Psr7\RequestMatcherInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ThrottleMiddlewareTest extends TestCase
{

    public function testMiddleware()
    {
        $maxRequests = 1;
        $durationInSeconds = 0.5;
        $client = $this->createConfiguredClient($maxRequests, $durationInSeconds);

        // The counter should not exist
        $response = $client->get('/foo');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block
        $response = $client->get('/bar');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));

        // The counter should have been reset
        $response = $client->get('/baz');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));
    }

    public function testMiddlewareWithMultipleRequests()
    {
        $maxRequests = 3;
        $durationInSeconds = 0.5;
        $client = $this->createConfiguredClient($maxRequests, $durationInSeconds);

        // The counter should not exist: 0/3
        $response = $client->get('/php');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 1/3
        $response = $client->get('/javascript');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 2/3
        $response = $client->get('/html');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block: 3/3
        $response = $client->get('/css');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));

        // The counter should have been reset: 0/3
        $response = $client->get('/python');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 1/3
        $response = $client->get('/java');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 2/3
        $response = $client->get('/go');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block: 3/3
        $response = $client->get('/ruby');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));
    }

    /**
     * @param float $durationInSeconds
     * @return float
     */
    private function getExpectedDuration(float $durationInSeconds)
    {
        return $durationInSeconds - 0.03; // We have to minus 0.03 because sometimes PHP is a little faster :)
    }

    /**
     * @param ResponseInterface $response
     * @return float
     */
    private function getRequestDuration(ResponseInterface $response)
    {
        return (float) $response->getHeaderLine('X-Request-Duration');
    }

    /**
     * @param int    $maxRequests
     * @param float  $duration
     * @param string $storageKey
     * @return Client
     */
    private function createConfiguredClient(int $maxRequests, float $duration, string $storageKey = 'foo')
    {
        $stack = HandlerStack::create(function (RequestInterface $request, array $options) {
            return new FulfilledPromise(new Response());
        });
        $middleware = new ThrottleMiddleware(new ArrayAdapter());
        $stack->push(new DurationHeaderMiddleware(), 'duration');
        $stack->push($middleware, 'throttle');
        $client = new Client([
            'handler' => $stack,
        ]);

        $middleware->registerConfiguration(new ThrottleConfiguration($this->createRequestMatcher(function () {
            return true;
        }), $maxRequests, $duration, $storageKey));
        return $client;
    }

    /**
     * @param callable $requestMatcher
     * @return RequestMatcherInterface
     */
    private function createRequestMatcher(callable $requestMatcher)
    {
        return new class($requestMatcher) implements RequestMatcherInterface
        {

            private $requestMatcher;

            public function __construct($requestMatcher)
            {
                $this->requestMatcher = $requestMatcher;
            }

            public function matchRequest(RequestInterface $request)
            {
                $callable = $this->requestMatcher;
                return $callable($request);
            }

        };
    }

}
