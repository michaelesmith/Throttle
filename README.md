[![Build Status](https://travis-ci.org/michaelesmith/Throttle.png?branch=master)](https://travis-ci.org/michaelesmith/Throttle)

# What is it?
A basic throttling implementation

# Versions
If you are looking for the old version compatible with PHP 5.3 try the [v0.1 branch](https://github.com/michaelesmith/Throttle/tree/v0.1) and update your composer requirement to `"michaelesmith/throttle": "^0.1"`

# Installation
`composer require "michaelesmith/throttle"`

You will also need a PSR-6 compatible cache library such as `"cache/cache"` or `"symfony/cache"`.

# Examples
```php
$throttle = new Throttle(new \Cache\Adapter\PHPArray\ArrayCachePool());
$throttle->add(new Condition(60, 2)); // adds an interval where 2 increments are allowed in 60 seconds
$throttle->add(new Condition(600, 5)); // adds an interval where 5 increments are allowed in 10 minutes

// in each action you want to limit
try {
    $throttle->increment($_SERVER['REMOTE_ADDR']); // some client identifier like an ip or session id
    // NOTE: $_SERVER['REMOTE_ADDR'] may not gove you the actual client IP if you are behind a reverse proxy
    // Here is how Symfony finds the client IP
    // @link: https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/Request.php#L786-L805
} catch(RateException $e) {
    $condition = $e->getCondition(); // the condition that hit the rate limit
    printf('You can only make %d requests in %d seconds', $condition->getLimit(), $condition->getTtl());
}
```

You can use any PSR-6 compatible cache pool, but you need to use one that is persistent across requests for most cases unlike this example.
