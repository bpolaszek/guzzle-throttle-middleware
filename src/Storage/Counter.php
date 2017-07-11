<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

class Counter implements \Serializable, \JsonSerializable
{
    /**
     * @var float
     */
    private $startTime;

    /**
     * @var int
     */
    private $nbRequests = 0;

    /**
     * Counter constructor.
     * @param float $startTime
     * @param int $nbRequests
     */
    public function __construct(float $startTime = null, int $nbRequests = 0)
    {
        $this->startTime = $startTime ?? microtime(true);
        $this->nbRequests = $nbRequests;
    }

    /**
     * @return int
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * @return int
     */
    public function getNbRequests(): int
    {
        return $this->nbRequests;
    }

    public function increment()
    {
        $this->nbRequests++;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return json_encode($this);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->startTime = $data['t'];
        $this->nbRequests = $data['n'];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            't' => $this->startTime,
            'n' => $this->nbRequests,
        ];
    }
}
