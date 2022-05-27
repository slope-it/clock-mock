<?php
declare(strict_types=1);

namespace SlopeIt\ClockMock;

use DateTimeZone;
use SlopeIt\ClockMock\DateTimeMock\DateTimeImmutableMock;
use SlopeIt\ClockMock\DateTimeMock\DateTimeMock;

/**
 * Class that provides static utilities to freeze the current system time using the php-uopz extension.
 */
final class ClockMock
{
    private static bool $areMocksActive = false;

    private static ?\DateTimeInterface $frozenDateTime = null;

    /**
     * @return mixed Anything the provided `$callable` returns.
     */
    public static function executeAtFrozenDateTime(\DateTimeInterface $dateTime, \Closure $callable)
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

        uopz_unset_return('date');
        uopz_unset_return('date_create');
        uopz_unset_return('date_create_immutable');
        uopz_unset_return('getdate');
        uopz_unset_return('gettimeofday');
        uopz_unset_return('gmdate');
        uopz_unset_return('gmstrftime');
        uopz_unset_return('idate');
        uopz_unset_return('localtime');
        uopz_unset_return('microtime');
        uopz_unset_return('strftime');
        uopz_unset_return('strtotime');
        uopz_unset_return('time');

        if (extension_loaded('calendar')) {
            uopz_unset_return('unixtojd');
        }

        uopz_unset_mock(\DateTime::class);
        uopz_unset_mock(\DateTimeImmutable::class);

        self::$areMocksActive = false;
        self::$frozenDateTime = null;
    }

    private static function activateMocksIfNeeded(): void
    {
        if (self::$areMocksActive) {
            return;
        }

        uopz_set_return('date', self::mock_date(), true);
        uopz_set_return('date_create', self::mock_date_create(), true);
        uopz_set_return('date_create_immutable', self::mock_date_create_immutable(), true);
        uopz_set_return('getdate', self::mock_getdate(), true);
        uopz_set_return('gettimeofday', self::mock_gettimeofday(), true);
        uopz_set_return('gmdate', self::mock_gmdate(), true);
        uopz_set_return('gmstrftime', self::mock_gmstrftime(), true);
        uopz_set_return('idate', self::mock_idate(), true);
        uopz_set_return('localtime', self::mock_localtime(), true);
        uopz_set_return('microtime', self::mock_microtime(), true);
        uopz_set_return('strftime', self::mock_strftime(), true);
        uopz_set_return('strtotime', self::mock_strtotime(), true);
        uopz_set_return('time', self::mock_time(), true);

        if (extension_loaded('calendar')) {
            uopz_set_return('unixtojd', self::mock_unixtojd(), true);
        }

        uopz_set_mock(\DateTime::class, DateTimeMock::class);
        uopz_set_mock(\DateTimeImmutable::class, DateTimeImmutableMock::class);

        self::$areMocksActive = true;
    }

    /**
     * @see https://www.php.net/manual/en/function.date.php
     */
    private static function mock_date(): callable
    {
        $date_mock = function (string $format, ?int $timestamp) {
            return date($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $format, ?int $timestamp = null) => $date_mock($format, $timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.date-create.php
     */
    private static function mock_date_create(): callable
    {
        return fn (?string $datetime = 'now', ?DateTimeZone $timezone = null) => new \DateTime($datetime, $timezone);
    }

    /**
     * @see https://www.php.net/manual/en/function.date-create-immutable.php
     */
    private static function mock_date_create_immutable(): callable
    {
        return fn (?string $datetime = 'now', ?DateTimeZone $timezone = null)
            => new \DateTimeImmutable($datetime, $timezone);
    }

    /**
     * @see https://www.php.net/manual/en/function.getdate.php
     */
    private static function mock_getdate(): callable
    {
        $getdate_mock = function (?int $timestamp) {
            return getdate($timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (?int $timestamp = null) => $getdate_mock($timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.gettimeofday.php
     */
    private static function mock_gettimeofday(): callable
    {
        $gettimeofday_mock = function (bool $as_float) {
            if ($as_float) {
                return (float) self::$frozenDateTime->format('U.u');
            }
            return [
                'sec'         => self::$frozenDateTime->getTimestamp(),
                'usec'        => (int) self::$frozenDateTime->format('u'),
                'minuteswest' => (int) self::$frozenDateTime->format('Z') / -60,
                'dsttime'     => (int) self::$frozenDateTime->format('I'),
            ];
        };

        return fn (bool $as_float = false) => $gettimeofday_mock($as_float);
    }

    /**
     * @see https://www.php.net/manual/en/function.gmdate.php
     */
    private static function mock_gmdate(): callable
    {
        $gmdate_mock = function (string $format, ?int $timestamp) {
            return gmdate($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $format, ?int $timestamp = null) => $gmdate_mock($format, $timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.gmstrftime.php
     */
    private static function mock_gmstrftime(): callable
    {
        $gmstrftime_mock = function (string $format, ?int $timestamp) {
            return gmstrftime($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $format, ?int $timestamp = null) => $gmstrftime_mock($format, $timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.idate.php
     */
    private static function mock_idate(): callable
    {
        $idate_mock = function (string $format, ?int $timestamp) {
            return idate($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $format, ?int $timestamp = null) => $idate_mock($format, $timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.localtime.php
     */
    private static function mock_localtime(): callable
    {
        $localtime_mock = function (?int $timestamp, bool $associative) {
            return localtime($timestamp ?? self::$frozenDateTime->getTimestamp(), $associative);
        };

        return fn (?int $timestamp = null, bool $associative = false) => $localtime_mock($timestamp, $associative);
    }

    /**
     * @see https://www.php.net/manual/en/function.microtime.php
     */
    private static function mock_microtime(): callable
    {
        $microtime_mock = function (bool $as_float) {
            if ($as_float) {
                return (float) self::$frozenDateTime->format('U.u');
            }

            return self::$frozenDateTime->format('0.u U');
        };

        return fn (bool $as_float = false) => $microtime_mock($as_float);
    }

    /**
     * @see https://www.php.net/manual/en/function.strftime.php
     */
    private static function mock_strftime(): callable
    {
        $strftime_mock = function (string $format, ?int $timestamp) {
            return strftime($format, $timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $format, ?int $timestamp = null) => $strftime_mock($format, $timestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.strtotime.php
     */
    private static function mock_strtotime(): callable
    {
        $strtotime_mock = function (string $datetime, ?int $baseTimestamp) {
            return strtotime($datetime, $baseTimestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (string $datetime, ?int $baseTimestamp = null) => $strtotime_mock($datetime, $baseTimestamp);
    }

    /**
     * @see https://www.php.net/manual/en/function.time.php
     */
    private static function mock_time(): callable
    {
        $time_mock = function () {
            return self::$frozenDateTime->getTimestamp();
        };

        return fn () => $time_mock();
    }

    /**
     * @see https://www.php.net/manual/en/function.time.php
     */
    private static function mock_unixtojd(): callable
    {
        $unixtojd_mock = function (?int $timestamp) {
            return unixtojd($timestamp ?? self::$frozenDateTime->getTimestamp());
        };

        return fn (?int $timestamp = null) => $unixtojd_mock($timestamp);
    }
}
