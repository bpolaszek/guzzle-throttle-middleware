<?php

namespace BenTools\GuzzleHttp\Middleware\Tests;

use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;
use PHPUnit\Framework\TestCase;

class ArrayAdapterTest extends TestCase
{

    public function testCreateCounter()
    {
        $storage = new ArrayAdapter();
        $counter = $storage->getCounter('foo');
        $this->assertEquals(0, $counter->getNbRequests());
        $this->assertInternalType('float', $counter->getStartTime());
        $counter->increment();
        $this->assertEquals(1, $counter->getNbRequests());
        return $storage;
    }

    /**
     * @param ThrottleStorageInterface $storage
     * @depends testCreateCounter
     */
    public function testRetrieveCounter(ThrottleStorageInterface $storage)
    {
        $counter = $storage->getCounter('foo');
        $this->assertEquals(1, $counter->getNbRequests());
        $counter = $storage->getCounter('bar');
        $this->assertEquals(0, $counter->getNbRequests());
        return $storage;
    }

    /**
     * @param ThrottleStorageInterface $storage
     * @depends testRetrieveCounter
     */
    public function testResetCounter(ThrottleStorageInterface $storage)
    {
        $counter = $storage->getCounter('foo');
        $this->assertEquals(1, $counter->getNbRequests());
        $storage->resetCounter('foo');

        $counter = $storage->getCounter('foo');
        $this->assertEquals(0, $counter->getNbRequests());
    }
}
