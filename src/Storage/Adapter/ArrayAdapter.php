<?php

namespace BenTools\GuzzleHttp\Middleware\Storage\Adapter;

use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;

class ArrayAdapter implements ThrottleStorageInterface
{

    private $storage = [];

    /**
     * @inheritDoc
     */
    public function getCounter(string $storageKey): Counter
    {
        if (!isset($this->storage[$storageKey])) {
            $counter = $this->storage[$storageKey] = new Counter(microtime(true));
        } else {
            $counter = $this->storage[$storageKey];
        }

        return $counter;
    }

    /**
     * @inheritDoc
     */
    public function saveCounter(string $storageKey, Counter $counter, int $ttl)
    {
        $this->storage[$storageKey] = $counter;
    }

    /**
     * @inheritDoc
     */
    public function resetCounter(string $storageKey)
    {
        unset($this->storage[$storageKey]);
    }
}
