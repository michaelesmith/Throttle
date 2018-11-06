<?php
declare(strict_types = 1);

namespace MS\Throttle;

class Interval
{
    /**
     * @var int
     */
    private $expiresAt;

    /**
     * @var int
     */
    public $count;

    /**
     * @param int $expiresAt
     * @param int $count
     */
    public function __construct(int $expiresAt, int $count = 0)
    {
        $this->expiresAt = time() + $expiresAt;
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }
}
