<?php

namespace MS\Throttle\Tests\Common;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function tearDown() {
        parent::tearDown();

        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        \Mockery::close();
    }
}
