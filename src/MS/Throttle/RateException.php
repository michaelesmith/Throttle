<?php
/**
 * Created by JetBrains PhpStorm.
 * User: msmith
 * Date: 9/5/13
 * Time: 3:49 PM
 * To change this template use File | Settings | File Templates.
 */

namespace MS\Throttle;

class RateException extends \Exception {

    /**
     * @var Interval
     */
    protected $interval;

    public function __construct($identifier, Interval $interval)
    {
        parent::__construct(sprintf('Rate %d in %d seconds was exceeded by "%s"', $interval->count, $interval->seconds, $identifier));
    }

    /**
     * @return Interval
     */
    public function getInterval()
    {
        return $this->interval;
    }


}
