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

    /**
     * @return string
     */
    public function getHumanReadableTime()
    {
        if($this->seconds < 60){
            return sprintf('%d seconds', $this->seconds);
        }elseif($this->seconds < 3600){
            return sprintf('%d minutes', $this->seconds / 60);
        }elseif($this->seconds < 86400){
            return sprintf('%d hours', $this->seconds / 3600);
        }elseif($this->seconds < 2592000){
            return sprintf('%d days', $this->seconds / 86400);
        }elseif($this->seconds < 31536000){
            return sprintf('%d months', $this->seconds / 2592000);
        }else{
            return sprintf('%d years', $this->seconds / 31536000);
        }
    }

}
