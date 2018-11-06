<?php
declare(strict_types = 1);

namespace MS\Throttle;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;

class Throttle
{
    /**
     * @var Condition[]
     */
    private $conditions = [];

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Condition $condition
     * @return $this
     * @throws \LogicException
     */
    public function add(Condition $condition): Throttle
    {
        foreach ($this->conditions as $existing) {
            if ($condition->getTtl() === $existing->getTtl()) {
                throw new \LogicException(sprintf('This instance already has a condition with a ttl of %d', $existing->getTtl()));
            } elseif ($condition->getTtl() > $existing->getTtl() && $condition->getLimit() <= $existing->getLimit()) {
                throw new \LogicException(sprintf('Adding a condition of ttl %d, limit %d will never be reached due to existing condition of ttl %d, limit %d',
                    $condition->getTtl(), $condition->getLimit(), $existing->getTtl(), $existing->getLimit()
                ));
            } elseif ($condition->getTtl() < $existing->getTtl() && $condition->getLimit() >= $existing->getLimit()) {
                throw new \LogicException(sprintf('Adding a condition of ttl %d, limit %d will prevent existing condition of ttl %d, limit %d from being reached',
                    $condition->getTtl(), $condition->getLimit(), $existing->getTtl(), $existing->getLimit()
                ));
            }
        }
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @param string $identifier
     * @param int $count
     * @return $this
     * @throws RateException
     * @throws CacheInvalidArgumentException
     */
    public function increment(string $identifier, int $count = 1): Throttle
    {
        foreach ($this->conditions as $condition) {
            $item = $this->getItem($identifier, $condition);
            if ($item->isHit()) {
                /** @var Interval $interval */
                $interval = $item->get();
            } else {
                $interval = new Interval($condition->getTtl());
                $item->expiresAfter($condition->getTtl());
            }
            $interval->count += $count;
            $item->set($interval);
            $this->cache->save($item);

            if ($interval->count > $condition->getLimit()) {
                throw new RateException($identifier, $condition);
            }
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @return Interval[]
     * @throws CacheInvalidArgumentException
     */
    public function getIntervals(string $identifier): array
    {
        $intervals = [];
        foreach ($this->conditions as $condition) {
            $item = $this->getItem($identifier, $condition);
            $interval = $item->isHit() ? $item->get() : new Interval($condition->getTtl());
            $intervals[] = $interval;
        }

        return $intervals;
    }

    /**
     * @param string $identifier
     * @param Condition $condition
     * @return CacheItemInterface
     * @throws CacheInvalidArgumentException
     */
    private function getItem(string $identifier, Condition $condition): CacheItemInterface
    {
        return $this->cache->getItem(sprintf('%s-%s', $identifier, $condition));
    }
}
