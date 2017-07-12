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
    public function hasCounter(string $storageKey): bool
    {
        return isset($this->storage[$storageKey]);
    }

    /**
     * @inheritDoc
     */
    public function getCounter(string $storageKey)
    {
        return $this->storage[$storageKey] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function saveCounter(string $storageKey, Counter $counter, float $ttl = null)
    {
        $this->storage[$storageKey] = $counter;
    }

    /**
     * @inheritDoc
     */
    public function deleteCounter(string $storageKey)
    {
        unset($this->storage[$storageKey]);
    }
}
