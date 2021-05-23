<?php
declare(strict_types=1);

namespace SlopeIt\ClockMock;

/**
 * Class providing a static utility to freeze the current system time using the php-uopz extension.
 *
 * Currently mocked functions/methods:
 *
 * time()
 * microtime()
 * date()
 * idate()
 * DateTime::__construct
 * DateTimeImmutable::__construct
 *
 * Currently MISSING functions/methods/globals:
 *
 * strtotime(),
 * mktime()
 * gmmktime()
 * gmdate()
 * getdate()
 * localtime()
 * strftime()
 * gmstrftime()
 * gettimeofday()
 * unixtojd()
 * date_create()
 * date_create_from_format()
 * date_create_immutable()
 * date_create_immutable_from_format()
 * DateTime::createFromFormat
 * DateTimeImmutable::createFromFormat
 * $_SERVER['REQUEST_TIME']
 */
final class ClockMock
{
    private static bool $areMocksActive = false;

    private static ?\DateTimeInterface $frozenDateTime = null;

    /**
     * @return mixed
     */
    public static function executeWithFrozenTime(\DateTimeInterface $dateTime, \Closure $callable)
    {
        try {
            self::freeze($dateTime);
            return $callable();
        } finally {
            self::reset();
        }
    }

    public static function freeze(\DateTimeInterface $dateTime): void
    {
        self::$frozenDateTime = clone $dateTime;

        self::activateMocksIfNeeded();
    }

    public static function getFrozenDateTime(): ?\DateTimeInterface
    {
        return self::$frozenDateTime;
    }

    /**
     * Removes any mocks on time (i.e. the ones installed by `ClockMock::freeze`).
     */
    public static function reset(): void
    {
        if (!self::$areMocksActive) {
            return;
        }

        \uopz_unset_return('time');
        \uopz_unset_return('microtime');
        \uopz_unset_return('date');
        \uopz_unset_return('idate');

        \uopz_unset_mock(\DateTime::class);
        \uopz_unset_mock(\DateTimeImmutable::class);

        self::$areMocksActive = false;
        self::$frozenDateTime = null;
    }

    private static function activateMocksIfNeeded(): void
    {
        if (self::$areMocksActive) {
            return;
        }

        $time_mock = function () {
            return self::$frozenDateTime->getTimestamp();
        };
        uopz_set_return(
            'time',
            fn () => $time_mock(),
            true
        );

        $microtime_mock = function (bool $as_float) {
            // @see https://www.php.net/manual/en/function.microtime.php
            if ($as_float) {
                return (float) self::$frozenDateTime->format('U.u');
            }

            return self::$frozenDateTime->format('0.u U');
        };
        uopz_set_return(
            'microtime',
            fn (bool $as_float = false) => $microtime_mock($as_float),
            true
        );

        $date_mock = function (string $format, ?int $timestamp) {
            return date($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };
        uopz_set_return(
            'date',
            fn (string $format, ?int $timestamp = null) => $date_mock($format, $timestamp),
            true,
        );

        $idate_mock = function (string $format, ?int $timestamp) {
            return idate($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };
        uopz_set_return(
            'idate',
            fn (string $format, ?int $timestamp = null) => $idate_mock($format, $timestamp),
            true,
        );

        uopz_set_mock(\DateTime::class, DateTimeMock::class);
        uopz_set_mock(\DateTimeImmutable::class, DateTimeImmutableMock::class);

        self::$areMocksActive = true;
    }
}
