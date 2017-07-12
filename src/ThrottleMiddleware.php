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


    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            foreach ($this->configurations as $configuration) {
                if ($configuration->matchRequest($request)) {
                    $this->processConfiguration($configuration);
                    break;
                }
            }
            return $handler($request, $options);
        };
    }

    private function processConfiguration(ThrottleConfiguration $configuration)
    {
        try {
            $counter = $this->storage->getCounter($configuration->getStorageKey());
        } catch (\TypeError $e) {
            $counter = new Counter($configuration->getDuration());
        }

        if (!$counter->isExpired()) {
            if ($counter->count() >= $configuration->getMaxRequests()) {
                $microDuration = $configuration->getDuration() * 1000000;
                usleep(random_int(min(50000, $microDuration), ($microDuration))); // Add some randomness to help shared storage
                $this->processConfiguration($configuration);
                return;
            }
        } else {
            $counter->reset();
        }

        $counter->increment();
        $this->storage->saveCounter($configuration->getStorageKey(), $counter, $configuration->getDuration());
    }
}
