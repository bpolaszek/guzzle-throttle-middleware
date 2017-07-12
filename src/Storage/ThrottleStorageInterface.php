<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

interface ThrottleStorageInterface
{
    /**
     * @param string $storageKey
     * @return bool
     */
    public function hasCounter(string $storageKey): bool;

    /**
     * @param string $storageKey
     * @return Counter
     */
    public function getCounter(string $storageKey): Counter;

    /**
     * @param string  $storageKey
     * @param Counter $counter
     * @param float   $ttl
     */
    public function saveCounter(string $storageKey, Counter $counter, float $ttl = null);

    /**
     * @param string $storageKey
     */
    public function deleteCounter(string $storageKey);
}
