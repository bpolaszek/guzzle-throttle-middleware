<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

class Counter implements \Serializable, \JsonSerializable, \Countable
{
    /**
     * @var float
     */
    private $expiresIn;

    /**
     * @var float
     */
    private $expiresAt;

    /**
     * @var int
     */
    private $counter;

    /**
     * Counter constructor.
     * @param float $startTime
     * @param int $nbRequests
     */
    public function __construct(float $expiresIn)
    {
        $this->expiresIn = $expiresIn;
        $this->reset();
    }

    private function reset()
    {
        $this->counter = 0;
        $this->expiresAt = microtime(true) + $this->expiresIn;
    }

    /**
     * Increment counter.
     */
    public function increment()
    {
        if ($this->isExpired()) {
            $this->reset();
        } else {
            $this->counter++;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        if ($this->isExpired()) {
            $this->reset();
        }
        return $this->counter;
    }

    /**
     * @return float
     */
    public function getRemainingTime()
    {
        return (float) max(0, $this->expiresAt - microtime(true));
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return 0.0 === $this->getRemainingTime();
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
        $this->expiresAt = $data['e'];
        $this->expiresIn = $data['i'];
        $this->counter = $data['n'];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'i' => $this->expiresIn,
            'e' => $this->expiresAt,
            'n' => $this->counter,
        ];
    }
}
