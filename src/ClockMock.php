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
    /**
     * Used to avoid installing mocks multiple times in case freeze is called multiple times in a row.
     */
    private static bool $areMocksActive = false;

    /**
     * When set, time is currently frozen to this specific date and time.
     */
    private static ?\DateTimeInterface $frozenDateTime = null;

    /**
     * When set, holds the original value from $_SERVER['REQUEST_TIME_FLOAT'] so that it can be used to restore it when
     * resetting after a freeze.
     */
    private static ?float $originalServerRequestTimeFloat = null;

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

        // When freezing, only store $_SERVER['REQUEST_TIME_FLOAT'] the first time (i.e. when the property is null).
        // This way we will restore the actual original value even after freezing multiple times in a row.
        if (self::$originalServerRequestTimeFloat === null) {
            self::$originalServerRequestTimeFloat = $_SERVER['REQUEST_TIME_FLOAT'];
        }

        $_SERVER['REQUEST_TIME'] = self::$frozenDateTime->getTimestamp();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) self::$frozenDateTime->format('U.u');

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
        if (self::$originalServerRequestTimeFloat !== null) {
            $_SERVER['REQUEST_TIME'] = (int) self::$originalServerRequestTimeFloat;
            $_SERVER['REQUEST_TIME_FLOAT'] = self::$originalServerRequestTimeFloat;
            self::$originalServerRequestTimeFloat = null;
        }

        if (!self::$areMocksActive) {
            return;
        }

        uopz_unset_return('date');
        uopz_unset_return('date_create');
        uopz_unset_return('date_create_from_format');
        uopz_unset_return('date_create_immutable');
        uopz_unset_return('date_create_immutable_from_format');
        uopz_unset_return('getdate');
        uopz_unset_return('gettimeofday');
        uopz_unset_return('gmdate');
        uopz_unset_return('gmmktime');
        uopz_unset_return('idate');
        uopz_unset_return('localtime');
        uopz_unset_return('microtime');
        uopz_unset_return('mktime');
        uopz_unset_return('strtotime');
        uopz_unset_return('time');
        uopz_unset_return(\DateTime::class, 'createFromFormat');
        uopz_unset_return(\DateTimeImmutable::class, 'createFromFormat');

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
        uopz_set_return('date_create_from_format', self::mock_date_create_from_format(), true);
        uopz_set_return('date_create_immutable', self::mock_date_create_immutable(), true);
        uopz_set_return('date_create_immutable_from_format', self::mock_date_create_immutable_from_format(), true);
        uopz_set_return('getdate', self::mock_getdate(), true);
        uopz_set_return('gettimeofday', self::mock_gettimeofday(), true);
        uopz_set_return('gmdate', self::mock_gmdate(), true);
        uopz_set_return('gmmktime', self::mock_gmmktime(), true);
        uopz_set_return('idate', self::mock_idate(), true);
        uopz_set_return('localtime', self::mock_localtime(), true);
        uopz_set_return('microtime', self::mock_microtime(), true);
        uopz_set_return('mktime', self::mock_mktime(), true);
        uopz_set_return('strtotime', self::mock_strtotime(), true);
        uopz_set_return('time', self::mock_time(), true);

        if (extension_loaded('calendar')) {
            uopz_set_return('unixtojd', self::mock_unixtojd(), true);
        }

        uopz_set_mock(\DateTime::class, DateTimeMock::class);
        uopz_set_return(\DateTime::class, 'createFromFormat', self::mock_date_create_from_format(), true);
        uopz_set_mock(\DateTimeImmutable::class, DateTimeImmutableMock::class);
        uopz_set_return(\DateTimeImmutable::class, 'createFromFormat', self::mock_date_create_immutable_from_format(), true);

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
     * @see https://www.php.net/manual/en/function.date-create-from-format.php
     */
    private static function mock_date_create_from_format(): callable
    {
        return function ($format, $datetime, DateTimeZone $timezone = null) {
            $dateTimeObject = \DateTime::createFromFormat($format, (string) $datetime, $timezone);

            // If the format doesn't include any time parts `createFromFormat` uses the current time, not all zeroes.
            // In that case the un-mocked call above will use the real time, not the mocked time.
            // This uses `date_parse_from_format` to detect whether the format includes time parts and,
            // if so, replaces the time of the result with the frozen time.
            // `date_parse_from_format` returns false for all time parts if no time parts are included in the format,
            // but if the format includes at least one time part all time parts not included become zero instead.
            $parsedDate = date_parse_from_format($format, (string) $datetime);
            if ($dateTimeObject !== false && $parsedDate['hour'] === false) {
                $frozen = ClockMock::getFrozenDateTime();
                return $dateTimeObject->setTime(
                    (int) $frozen->format('H'),
                    (int) $frozen->format('i'),
                    (int) $frozen->format('s'),
                    (int) $frozen->format('u'),
                );
            } else {
                return $dateTimeObject;
            }
        };
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
     * @see https://www.php.net/manual/en/function.date-create-immutable-from-format.php
     */
    public static function mock_date_create_immutable_from_format(): callable
    {
        return function ($format, $datetime, DateTimeZone $timezone = null) {
            // Create an immutable instance starting from the mutable mock, so we don't have to replicate mocking logic.
            $mutableDateTime = date_create_from_format($format, $datetime, $timezone);

            return new \DateTimeImmutable($mutableDateTime->format('Y-m-d\TH:i:s.uT'), $timezone);
        };
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
     * @see https://www.php.net/manual/en/function.gmmktime.php
     */
    private static function mock_gmmktime(): callable
    {
        $gmmktime_mock = function (int $hour, ?int $minute, ?int $second, ?int $month, ?int $day, ?int $year) {
            /** @var \DateTime $gmtDateTime */
            $gmtDateTime = self::mock_date_create()(self::mock_gmdate()('Y-m-d H:i:s.u'));

            return self::mock_mktime()($hour + self::$frozenDateTime->getOffset() / 3600,
                $minute ?? (int) $gmtDateTime->format('i'),
                $second ?? (int) $gmtDateTime->format('s'),
                $month ?? (int) $gmtDateTime->format('m'),
                $day ?? (int) $gmtDateTime->format('j'),
                $year ?? (int) $gmtDateTime->format('Y'));
        };

        return fn (int $hour, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null) => $gmmktime_mock($hour, $minute, $second, $month, $day, $year);
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
            return self::$frozenDateTime->format('0.u00 U');
        };

        return fn (bool $as_float = false) => $microtime_mock($as_float);
    }

    /**
     * @see https://www.php.net/manual/en/function.mktime.php
     */
    private static function mock_mktime(): callable
    {
        $mktime_mock = function (int $hour, ?int $minute, ?int $second, ?int $month, ?int $day, ?int $year) {
            return mktime($hour,
                $minute ?? (int) self::$frozenDateTime->format('i'),
                $second ?? (int) self::$frozenDateTime->format('s'),
                $month ?? (int) self::$frozenDateTime->format('m'),
                $day ?? (int) self::$frozenDateTime->format('j'),
                $year ?? (int) self::$frozenDateTime->format('Y'));
        };

        return fn (int $hour, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null) => $mktime_mock($hour, $minute, $second, $month, $day, $year);
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
