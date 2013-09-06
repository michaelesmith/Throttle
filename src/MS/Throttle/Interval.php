<?php
/**
 * Created by JetBrains PhpStorm.
 * User: msmith
 * Date: 9/5/13
 * Time: 3:49 PM
 * To change this template use File | Settings | File Templates.
 */

namespace MS\Throttle;


class Interval {

    public $seconds;

    public $count;

    function __construct($seconds, $count)
    {
        $this->seconds = $seconds;
        $this->count = $count;
    }

}
