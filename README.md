# ClockMock

[Slope s.r.l.](https://www.slope.it)

[![Latest Stable Version](https://poser.pugx.org/slope-it/clock-mock/v/stable)](https://packagist.org/packages/slope-it/clock-mock)
[![Total Downloads](https://poser.pugx.org/slope-it/clock-mock/downloads)](https://packagist.org/packages/slope-it/clock-mock)
[![License](https://poser.pugx.org/slope-it/clock-mock/license)](https://packagist.org/packages/slope-it/clock-mock)

ClockMock provides a way for mocking the current timestamp used by PHP for \DateTime(Immutable) objects and date/time
functions. It requires the uopz extension (at least version 6.1.1).

Credits to:
- [timecop](https://github.com/hnw/php-timecop), as ClockMock was inspired by it.
- [ext-uopz](https://github.com/krakjoe/uopz), as ClockMock is just a very thin layer on top of the amazing uopz
extension, which provides a very convenient way to mock any function or method, including the ones of the php stdlib, 
at runtime.

## Why we built it

- We were looking for a way to mock the native php date and time functions and classes without being forced to use a
  third party library (e.g. [nesbot/carbon](https://github.com/briannesbitt/carbon))
- For this purpose, we were previously using the `php-timecop` extension. Problem was that the extension never
  implemented support for PHP 7.4 onward. That extension currently does not build at all for PHP 8.0.

## Installation

Composer is the only supported installation method. Run the following to install the latest version from Packagist:

``` bash
composer require --dev slope-it/clock-mock
```

Note that, as this is not a tool intended for production, it should be required only for development (`--dev` flag).

## Mocked functions/methods

- time()
- microtime()
- date()
- idate()
- strtotime()
- getdate()
- localtime()
- date_create()
- date_create_immutable()
- DateTime::__construct
- DateTimeImmutable::__construct

## Functions/methods with missing mocks (HELP NEEDED!)

- mktime()
- gmmktime()
- gmdate()
- strftime()
- gmstrftime()
- gettimeofday()
- unixtojd()
- date_create_from_format()
- date_create_immutable_from_format()
- DateTime::createFromFormat
- DateTimeImmutable::createFromFormat
- $_SERVER['REQUEST_TIME']

## Usage

### 1. Stateful API

You can call `ClockMock::freeze` with a \DateTime or \DateTimeImmutable. Any code executed after it will use that
specific date and time as the current timestamp.
Call `ClockMock::reset` when done to restore real, current time.

Example:

``` php
// ...
class MyTestCase extends TestCase
{
    public function test_something_using_stateful_mocking_api()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));
        
        // Code executed in here, until ::reset is called, will use the above date and time as "current"
        $nowYmd = date('Y-m-d');
        
        ClockMock::reset();
        
        $this->assertEquals('1986-06-05', $nowYmd);
    }
}
```

### 2. Stateless API

The library also provides a closure-based API that will execute the provided code at a specific point in time. This API
does not need manually freezing or re-setting time, so it can be less error prone in some circumstances.

Example:

``` php
// ...
class MyTestCase extends TestCase
{
    public function test_something_using_stateless_mocking_api()
    {
        $nowYmd = ClockMock::executeAtFrozenDateTime(new \DateTime('1986-06-05'), function () {
            // Code executed in here will use the above date and time as "current"
            return date('Y-m-d');
        });
        
        $this->assertEquals('1986-06-05', $nowYmd);
    }
}
```

## How to contribute

* Did you find and fix any bugs in the existing code?
* Do you want to contribute a new feature, or a missing mock?
* Do you think documentation can be improved?

Under any of these circumstances, please fork this repo and create a pull request. We are more than happy to accept
contributions!

## Maintainer

[@andreasprega](https://twitter.com/andreasprega)
