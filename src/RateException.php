<?php

namespace MS\Throttle;

class RateException extends \RuntimeException {

    /**
     * @var Condition
     */
    protected $condition;

    /**
     * @param string $identifier
     * @param Condition $condition
     */
    public function __construct(string $identifier, Condition $condition)
    {
        $this->condition = $condition;
        parent::__construct(sprintf('Rate %d in %d seconds was exceeded by "%s"', $condition->getLimit(), $condition->getTtl(), $identifier));
    }

    /**
     * @return Condition
     */
    public function getCondition()
    {
        return $this->condition;
    }


}
