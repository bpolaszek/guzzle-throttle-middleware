<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

interface ThrottleStorageInterface
{

    /**
     * @param string $storageKey
     * @return Counter
     */
    public function getCounter(string $storageKey): Counter;

    /**
     * @param string  $storageKey
     * @param Counter $counter
     * @param int     $ttl
     */
    public function saveCounter(string $storageKey, Counter $counter, int $ttl);

    /**
     * @param string $storageKey
     */
    public function resetCounter(string $storageKey);
}
