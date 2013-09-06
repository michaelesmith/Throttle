<?php
/**
 * Created by JetBrains PhpStorm.
 * User: msmith
 * Date: 9/5/13
 * Time: 5:09 PM
 * To change this template use File | Settings | File Templates.
 */

namespace MS\Throttle;


class Time {

    public function getTimestamp()
    {
        return time();
    }

    public function time()
    {
        return time();
    }

    public function strtotime($str, $now = null)
    {
        return strtotime($str, $now);
    }
}
