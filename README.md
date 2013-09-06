[![Build Status](https://travis-ci.org/michaelesmith/Throttle.png?branch=master)](https://travis-ci.org/michaelesmith/Throttle)

README
======

What is it?
-------------------

A basic throttling implementation

Installation
------------

### Use Composer (*recommended*)

The recommended way to install msDateTime is through composer.

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Just create a `composer.json` file for your project:

``` json
{
    "require": {
        "michaelesmith/throttle": "*"
    }
}
```

For more info on composer see https://github.com/composer/composer

Examples
--------

###Basic

    $throttle = new Throttle(new \Doctrine\Common\Cache\ApcCache(), new \MS\Throttle\Time());
    $throttle->addInterval(60, 2); // adds an interval where 2 increments are allowed in 60 seconds
    $throttle->addInterval(600, 5); // adds an interval where 5 increments are allowed in 10 minutes

    // in each action you want to limit
    try {
        $throttle->increment($request->getClientIp());
    } catch(\MS\Throttle\RateException $e) {
        $e->getInterval(); // the interval that hit the rate limit
        printf('You can only make %d requests in %d seconds', $e->getInterval()->count, $e->getInterval()->seconds);
    }

You can use any Doctrine\Common\Cache adapter but you need to use one that is persistent across requests for most cases.
