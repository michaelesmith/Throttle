<?php

namespace MS\Throttle\Tests\Unit;

use MS\Throttle\Condition;
use MS\Throttle\Interval;
use MS\Throttle\RateException;
use MS\Throttle\Tests\Common\TestCase;
use MS\Throttle\Throttle;
use phpmock\mockery\PHPMockery;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ThrottleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockTime();
    }

    private function mockTime()
    {
        // We need to mock time() here since it is used in the constructor on Interval. We would probably be safe
        // not to but if the test ever spanned more than 1 second it would fail so avoiding hard to find bugs here.
        $namespace = substr(Interval::class, 0, strrpos(Interval::class, '\\'));
        $time = PHPMockery::mock($namespace, 'time')->andReturn(strtotime('1980-02-05 06:45:00'));
    }

    private function unMockTime()
    {
        \Mockery::close();
    }

    public function dpTestAdd()
    {
        return [
            0 => [
                [
                    new Condition(100, 2),
                    new Condition(200, 3),
                ], // $conditions
            ],
            1 => [
                [
                    new Condition(100, 2),
                    new Condition(100, 3),
                ], // $conditions
                '/^This instance already has a condition with a ttl of 100$/', // $exceptionMessage
            ],
            2 => [
                [
                    new Condition(100, 3),
                    new Condition(200, 2),
                ], // $conditions
                '/^Adding a condition of ttl 200, limit 2 will never be reached due to existing condition of ttl 100, limit 3$/', // $exceptionMessage
            ],
            3 => [
                [
                    new Condition(200, 2),
                    new Condition(100, 3),
                ], // $conditions
                '/^Adding a condition of ttl 100, limit 3 will prevent existing condition of ttl 200, limit 2 from being reached$/', // $exceptionMessage
            ],
        ];
    }

    /**
     * @dataProvider dpTestAdd
     */
    public function testAdd($conditions, $exceptionMessage = '')
    {
        $cache = \Mockery::mock(CacheItemPoolInterface::class);
        $sut = new Throttle($cache);

        if ($exceptionMessage) {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessageRegExp($exceptionMessage);
        }

        foreach ($conditions as $condition) {
            $this->assertInstanceOf(Throttle::class, $sut->add($condition)); // We need to make at least 1 assertion to prevent being marked as risky
        }
    }

    const TEST_INCREMENT_TTL = 10;
    const TEST_INCREMENT_LIMIT = 2;
    const TEST_INCREMENT_IDENTIFIER = 'S123';

    public function dpTestIncrement()
    {
        $this->mockTime();

        $ret = [
            0 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(false);
                    $item->expects('get')->never();
                    $item->expects('expiresAfter')->once()->with(self::TEST_INCREMENT_TTL);
                    $item->expects('set')->once();
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);
                    $cache->expects('save')->once()->with($item);

                    return $cache;
                }, // $cache
                1, // $count
            ], // cache miss
            1 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(true);
                    $item->expects('get')->once()->andReturn(new Interval(self::TEST_INCREMENT_TTL - 1, 1));
                    $item->expects('expiresAfter')->never();
                    $item->expects('set')->once();
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);
                    $cache->expects('save')->once()->with($item);

                    return $cache;
                }, // $cache
                1, // $count
            ], // cache hit
            2 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(false);
                    $item->expects('get')->never();
                    $item->expects('expiresAfter')->once()->with(self::TEST_INCREMENT_TTL);
                    $item->expects('set')->once();
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);
                    $cache->expects('save')->once()->with($item);

                    return $cache;
                }, // $cache
                3, // $count
                true, // $expectException
            ], // cache miss
            3 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(true);
                    $item->expects('get')->once()->andReturn(new Interval(self::TEST_INCREMENT_TTL - 1, 1));
                    $item->expects('expiresAfter')->never();
                    $item->expects('set')->once();
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);
                    $cache->expects('save')->once()->with($item);

                    return $cache;
                }, // $cache
                2, // $count
                true, // $expectException
            ], // cache hit
        ];

        $this->unMockTime();

        return $ret;
    }

    /**
     * @dataProvider dpTestIncrement
     */
    public function testIncrement($cache, $count, $expectException = false)
    {
        $cache = $cache();
        $sut = new Throttle($cache);
        $sut->add(new Condition(self::TEST_INCREMENT_TTL, self::TEST_INCREMENT_LIMIT));

        if ($expectException) {
            $this->expectException(RateException::class);
            $this->expectExceptionMessage(sprintf(
                'Rate %d in %d seconds was exceeded by "%s"',
                self::TEST_INCREMENT_LIMIT, self::TEST_INCREMENT_TTL, self::TEST_INCREMENT_IDENTIFIER
            ));
        }

        $this->assertInstanceOf(Throttle::class, $sut->increment(self::TEST_INCREMENT_IDENTIFIER, $count));
    }

    const TEST_GET_INTERVALS_TTL = 10;
    const TEST_GET_INTERVALS_LIMIT = 2;
    const TEST_GET_INTERVALS_IDENTIFIER = 'S123';

    public function dpTestGetIntervals()
    {
        $this->mockTime();

        $ret = [
            0 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(false);
                    $item->expects('get')->never();
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);

                    return $cache;
                }, // $cache
                [
                    new Interval(self::TEST_GET_INTERVALS_TTL, 0),
                ], // $intervals
            ], // cache miss
            1 => [
                function() {
                    $item = \Mockery::mock(CacheItemInterface::class);
                    $item->expects('isHit')->once()->andReturn(true);
                    $item->expects('get')->once()->andReturn(new Interval(self::TEST_GET_INTERVALS_TTL - 1, 1));
                    $cache = \Mockery::mock(CacheItemPoolInterface::class);
                    $cache->expects('getItem')->once()->andReturn($item);

                    return $cache;
                }, // $cache
                [
                    new Interval(self::TEST_GET_INTERVALS_TTL - 1, 1),
                ], // $intervals
            ], // cache hit
        ];

        $this->unMockTime();

        return $ret;
    }

    /**
     * @dataProvider dpTestGetIntervals
     */
    public function testGetIntervals($cache, $intervals)
    {
        $cache = $cache();
        $sut = new Throttle($cache);
        $sut->add(new Condition(self::TEST_GET_INTERVALS_TTL, self::TEST_GET_INTERVALS_LIMIT));

        $this->assertEquals($intervals, $sut->getIntervals(self::TEST_GET_INTERVALS_IDENTIFIER));
    }
}
