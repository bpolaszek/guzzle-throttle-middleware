<?php

namespace BenTools\GuzzleHttp\Middleware;

use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;
use Psr\Http\Message\RequestInterface;

class ThrottleMiddleware
{
    /**
     * @var ThrottleConfiguration[]
     */
    private $configurations = [];
    /**
     * @var ThrottleStorageInterface
     */
    private $storage;

    /**
     * ThrottleMiddleware constructor.
     * @param ThrottleStorageInterface $storage
     */
    public function __construct(ThrottleStorageInterface $storage = null)
    {
        $this->storage = $storage ?? new ArrayAdapter();
    }

    /**
     * @param ThrottleConfiguration $configuration
     */
    public function registerConfiguration(ThrottleConfiguration $configuration)
    {
        $this->configurations[$configuration->getStorageKey()] = $configuration;
    }

    /**
     * @param ThrottleConfiguration $configuration
     * @param Counter               $counter
     * @return bool
     */
    private function shouldThrottle(ThrottleConfiguration $configuration, Counter $counter): bool
    {
        return $counter->getNbRequests() >= $configuration->getMaxRequests();
    }

    /**
     * @param ThrottleConfiguration $configuration
     * @param Counter               $counter
     * @return float
     */
    public function getRemainingTime(ThrottleConfiguration $configuration, Counter $counter): float
    {
        $remaining = ($counter->getStartTime() + $configuration->getDuration()) - microtime(true);
        return (float) max(0, $remaining);
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            foreach ($this->configurations as $configuration) {
                // Request match - Check if we need to throttle
                if ($configuration->matchRequest($request)) {
                    $counter = $this->storage->getCounter($configuration->getStorageKey());
                    $remainingTime = $this->getRemainingTime($configuration, $counter);

                    if ($this->shouldThrottle($configuration, $counter)) {
                        usleep($remainingTime * 1000000);
                        $this->storage->resetCounter($configuration->getStorageKey());
                    } else {
                        $counter->increment();
                        $this->storage->saveCounter($configuration->getStorageKey(), $counter, ceil($remainingTime));
                    }
                    break;
                }
            }
            return $handler($request, $options);
        };
    }
}
