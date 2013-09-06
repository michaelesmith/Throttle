<?php

namespace MS\Throttle;

use Doctrine\Common\Cache\Cache;

/**
 * A basic throttling implementation
 *
 * @version 0.1
 *
 * @author msmith
 */
class Throttle
{
    protected $intervals = array();

    protected $cache;

    protected $time;

    public function __construct(Cache $cache, Time $time)
    {
        $this->cache = $cache;
        $this->time = $time;
    }

    public function add(Interval $interval)
    {
        $this->intervals[] = $interval;
    }

    public function addInterval($seconds, $count)
    {
        $this->add(new Interval($seconds, $count));
    }

    public function increment($identifier)
    {
        if($interval = $this->getThrottledInterval($identifier)){
            throw new RateException($identifier, $interval);
        }

        if(!$this->cache->contains($identifier)){
            $this->cacheAdd('_identifiers', $identifier);
        }
        $this->cacheAdd($identifier, $this->time->getTimestamp());
    }

    protected function cacheAdd($identifier, $datum)
    {
        if(!$data = $this->cache->fetch($identifier)){
            $data = array();
        }
        $data[] = $datum;
        $this->cache->save($identifier, $data);
    }

    protected function cacheRemove($identifier, $datum)
    {
        if(!$data = $this->cache->fetch($identifier)){
            $data = array();
        }

        $key = array_search($datum, $data);

        if($key){
            unset($data[$key]);
            $this->cache->save($identifier, array_merge($data));
        }
    }

    public function getThrottledInterval($identifier)
    {
        $data = $this->cache->fetch($identifier);
        if(!$data){
            return false;
        }

        foreach($this->intervals as $interval){
            /** @var $interval Interval */
            if(count($data) < $interval->count){
                continue;
            }

            if($data[count($data) - $interval->count] > ($this->time->getTimestamp() - $interval->seconds)){
                return $interval;
            }
        }
    }

    public function garbageCollect($identifier = null)
    {
        $maxSeconds = 0;
        foreach($this->intervals as $interval){
            /** @var $interval Interval */
            if($interval->seconds > $maxSeconds){
                $maxSeconds = $interval->seconds;
            }
        }

        if($identifier){
            $this->garbageCollectIdentifier($identifier, $maxSeconds);
        }else{
            $this->garbageCollectAll($maxSeconds);
        }
    }

    protected function garbageCollectIdentifier($identifier, $maxSeconds)
    {
        if(!$data = $this->cache->fetch($identifier)){
            $data = array();
        }

        $ts = $this->time->getTimestamp() - $maxSeconds;

        foreach($data as $key => $datum){
            if($datum > $ts){
                break;
            }
            unset($data[$key]);
        }

        if(!count($data)){
            $this->cacheRemove('_identifiers', $identifier);
        }

        $this->cache->save($identifier, array_merge($data));
    }

    protected function garbageCollectAll($maxSeconds)
    {
        if(!$identifiers = $this->cache->fetch('_identifiers')){
            $identifiers = array();
        }

        foreach($identifiers as $identifier){
            $this->garbageCollectIdentifier($identifier, $maxSeconds);
        }
    }
}
