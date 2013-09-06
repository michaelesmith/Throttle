<?php

/**
 * @version 0.2
 *
 * @author msmith
 */

namespace MS\Throttle\Test;

use Doctrine\Common\Cache\ArrayCache;
use MS\Throttle\Throttle;

class ThrottleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $ts = 0;

        $time = $this->getMock('\MS\Throttle\Time');
        $time->expects($this->any())
            ->method('getTimestamp')
            ->will($this->returnCallback(function() use (&$ts) {
                return $ts;
            }));

        $cache = new ArrayCache();
        $throttle = new Throttle($cache, $time);
        $throttle->addInterval(100, 2);

        $ts = 100;
        $throttle->increment('id1');
        $ts = 120;
        $this->assertNull($throttle->getThrottledInterval('id1'));

        $ts = 150;
        $throttle->increment('id1');
        $throttle->increment('id2');
        $ts = 220;
        $throttle->increment('id1');
        $this->assertNull($throttle->getThrottledInterval('id2'));

        $ts = 235;
        $interval = $throttle->getThrottledInterval('id1');
        $this->assertInstanceOf('\MS\Throttle\Interval', $interval);

        $this->setExpectedException('\MS\Throttle\RateException');
        $ts = 240;
        $throttle->increment('id1');
    }

    public function testGarbageCollectIdentifier()
    {
        $ts = 0;

        $time = $this->getMock('\MS\Throttle\Time');
        $time->expects($this->any())
            ->method('getTimestamp')
            ->will($this->returnCallback(function() use (&$ts) {
                return $ts;
            }));

        $cache = new ArrayCache();
        $throttle = new Throttle($cache, $time);
        $throttle->addInterval(100, 25);

        $ts = 100;
        $throttle->increment('id1');
        $throttle->increment('id2');
        $ts = 150;
        $throttle->increment('id1');
        $ts = 220;
        $throttle->increment('id1');
        $ts = 240;
        $throttle->increment('id1');
        $throttle->increment('id2');

        $throttle->garbageCollect('id1');
        $this->assertCount(3, $cache->fetch('id1'));
        $this->assertCount(2, $cache->fetch('id2'));
    }

    public function testGarbageCollectAll()
    {
        $ts = 0;

        $time = $this->getMock('\MS\Throttle\Time');
        $time->expects($this->any())
            ->method('getTimestamp')
            ->will($this->returnCallback(function() use (&$ts) {
                return $ts;
            }));

        $cache = new ArrayCache();
        $throttle = new Throttle($cache, $time);
        $throttle->addInterval(100, 25);

        $ts = 100;
        $throttle->increment('id1');
        $throttle->increment('id2');
        $ts = 150;
        $throttle->increment('id1');
        $ts = 220;
        $throttle->increment('id1');
        $ts = 240;
        $throttle->increment('id1');
        $throttle->increment('id2');

        $throttle->garbageCollect();
        $this->assertCount(3, $cache->fetch('id1'));
        $this->assertCount(1, $cache->fetch('id2'));
    }

}
