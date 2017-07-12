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
                    if (!$this->storage->hasCounter($configuration->getStorageKey())) {
                        $counter = new Counter($configuration->getDuration());
                        $counter->increment();
                        $this->storage->saveCounter($configuration->getStorageKey(), $counter, $configuration->getDuration());
                    } else {
                        $counter = $this->storage->getCounter($configuration->getStorageKey());

                        if ($counter->count() >= $configuration->getMaxRequests()) {
                            usleep($counter->getRemainingTime() * 1000000);
                        } else {
                            $counter->increment();
                            $this->storage->saveCounter($configuration->getStorageKey(), $counter);
                        }
                    }
                    break;
                }
            }
            return $handler($request, $options);
        };
    }
}
