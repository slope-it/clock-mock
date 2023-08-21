<?php
declare(strict_types=1);

namespace SlopeIt\Tests\ClockMock;

use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;

/**
 * @covers \SlopeIt\ClockMock\ClockMock
 */
class ClockMockTest extends TestCase
{
    protected function tearDown(): void
    {
        ClockMock::reset();
    }

    public function test_DateTimeImmutable_constructor_with_absolute_mocked_date()
    {
        ClockMock::freeze($fakeNow = new \DateTimeImmutable('1986-06-05'));

        $this->assertEquals($fakeNow, new \DateTimeImmutable('now'));
    }

    public function test_DateTimeImmutable_constructor_with_relative_mocked_date_with_microseconds()
    {
        $juneFifth1986 = new \DateTime('1986-06-05');

        ClockMock::freeze($fakeNow = new \DateTime('now')); // This uses current time including microseconds

        $this->assertEquals($fakeNow, new \DateTimeImmutable('now'));
        $this->assertEquals($juneFifth1986, new \DateTimeImmutable('1986-06-05'));
    }

    public function test_DateTimeImmutable_constructor_with_relative_mocked_date_without_microseconds()
    {
        $juneFifth1986 = new \DateTimeImmutable('1986-06-05');

        ClockMock::freeze($fakeNow = new \DateTimeImmutable('yesterday')); // Yesterday at midnight, w/o microseconds

        $this->assertEquals($fakeNow, new \DateTimeImmutable('now'));
        $this->assertEquals($juneFifth1986, new \DateTimeImmutable('1986-06-05'));
    }

    public function test_DateTimeImmutable_constructor_with_timezone()
    {
        $dateWithTimezone = new \DateTimeImmutable('1986-06-05 14:41:32+02:00');

        ClockMock::freeze($fakeNow = new \DateTimeImmutable('now'));

        $this->assertEquals($dateWithTimezone, new \DateTimeImmutable('1986-06-05 14:41:32+02:00'));
    }

    public function test_DateTimeImmutable_constructor_with_timezone_respect_zone_type()
    {
        ClockMock::freeze(new \DateTimeImmutable('now'));

        $timezoneType3 = 'Asia/Tokyo';
        $date = new \DateTimeImmutable('1986-06-05', new \DateTimeZone($timezoneType3));

        $this->assertEquals($date->getTimezone()->getName(), $timezoneType3);

        $timezoneType2 = 'CDT';
        $date = new \DateTimeImmutable('1986-06-05', new \DateTimeZone($timezoneType2));

        $this->assertEquals($date->getTimezone()->getName(), $timezoneType2);
    }

    public function test_DateTimeImmutable_constructor_without_timezone()
    {
        $originalTimezone = date_default_timezone_get();
        $defaultTimezone = 'Asia/Tokyo';
        date_default_timezone_set($defaultTimezone);

        try {
            ClockMock::freeze(new \DateTimeImmutable('1986-06-05'));

            $newDate = new \DateTimeImmutable('1986-06-05');

            $this->assertEquals($newDate->getTimezone()->getName(), $defaultTimezone);
        } finally {
            date_default_timezone_set($originalTimezone); // Revert timezone.
        }
    }

    public function test_DateTimeImmutable_createFromFormat()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));

        $dateTimeFromFormat = \DateTimeImmutable::createFromFormat('Y-m-d', '2022-05-28');

        // Verification: when not provided with a time, createFromFormat should use current time.
        $this->assertSame('2022-05-28 12:13:14', $dateTimeFromFormat->format('Y-m-d H:i:s'));
    }

    public function test_DateTime_constructor_with_absolute_mocked_date()
    {
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05'));

        $this->assertEquals($fakeNow, new \DateTime('now'));
    }

    /**
     * @see https://github.com/slope-it/clock-mock/issues/26
     */
    public function test_DateTime_constructor_with_absolute_date_and_canonical_timezone()
    {
        // The mocked date, either aboslute or relative, is irrelevant for this test. Having a mocked date is enough.
        ClockMock::freeze(new \DateTime('now'));

        $absoluteDateTimeWithTimezone = new \DateTime(
            '1986-06-05 12:13:14',
            $japanTimezone = new \DateTimeZone('Asia/Tokyo')
        );

        // Verification: when date is absolute and timezone is specified, the mocked clock should have no effect.
        $this->assertEquals($japanTimezone, $absoluteDateTimeWithTimezone->getTimezone());
        $this->assertSame('1986-06-05 12:13:14', $absoluteDateTimeWithTimezone->format('Y-m-d H:i:s'));
    }

    /**
     * @see https://github.com/slope-it/clock-mock/issues/37
     * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
     */
    public function test_DateTime_constructor_with_absolute_date_and_non_canonical_timezone()
    {
        // The mocked date, either aboslute or relative, is irrelevant for this test. Having a mocked date is enough.
        ClockMock::freeze(new \DateTime('now'));

        $absoluteDateTimeWithTimezone = new \DateTime(
            '1986-06-05 12:13:14',
            $usEasternTimezone = new \DateTimeZone('US/Eastern')
        );

        // Verification: when date is absolute and timezone is specified, the mocked clock should have no effect.
        $this->assertEquals($usEasternTimezone, $absoluteDateTimeWithTimezone->getTimezone());
        $this->assertSame('1986-06-05 12:13:14', $absoluteDateTimeWithTimezone->format('Y-m-d H:i:s'));
    }

    /**
     * @see https://github.com/slope-it/clock-mock/issues/31
     */
    public function test_DateTime_constructor_with_empty_string()
    {
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05 14:26:29.123456'));

        $this->assertEquals($fakeNow, new \DateTime('')); // Empty string should behave exactly as "now"
    }

    /**
     * @see https://github.com/slope-it/clock-mock/issues/7
     */
    public function test_DateTime_constructor_with_microseconds_and_specific_timezone()
    {
        ClockMock::freeze(new \DateTime('2022-04-04 14:26:29.123456')); // UTC, +00:00

        // Reconstruct the current date (which is now based on the one mocked above) but apply a specific timezone. The
        // resulting date should have its time modified accordingly to the timezone.
        $nowWithIndiaTimezone = new \DateTime('now', $indiaTimezone = new \DateTimeZone('+05:30'));

        $this->assertEquals($indiaTimezone, $nowWithIndiaTimezone->getTimezone());
        $this->assertSame('19', $nowWithIndiaTimezone->format('H')); // 14 plus 5
        $this->assertSame('56', $nowWithIndiaTimezone->format('i')); // 26 plus 30
        $this->assertSame('29', $nowWithIndiaTimezone->format('s')); // does not vary
        $this->assertSame('123456', $nowWithIndiaTimezone->format('u')); // does not vary
    }

    public function test_DateTime_constructor_with_relative_mocked_date_with_microseconds()
    {
        $juneFifth1986 = new \DateTime('1986-06-05');

        ClockMock::freeze($fakeNow = new \DateTime('now')); // This uses current time including microseconds

        $this->assertEquals($fakeNow, new \DateTime('now'));
        $this->assertEquals($juneFifth1986, new \DateTime('1986-06-05'));
    }

    public function test_DateTime_constructor_with_relative_mocked_date_without_microseconds()
    {
        $juneFifth1986 = new \DateTime('1986-06-05');

        ClockMock::freeze($fakeNow = new \DateTime('yesterday')); // Yesterday at midnight, without microseconds

        $this->assertEquals($fakeNow, new \DateTime('now'));
        $this->assertEquals($juneFifth1986, new \DateTime('1986-06-05'));
    }

    public function test_DateTime_createFromFormat_with_time()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14.168432'));

        $dateTimeFromFormat = \DateTime::createFromFormat('Y-m-d i', '2022-05-28 24');

        $this->assertSame('2022-05-28 00:24:00.000000', $dateTimeFromFormat->format('Y-m-d H:i:s.u'));
    }

    public function test_DateTime_createFromFormat_without_time()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14.168432'));

        $dateTimeFromFormat = \DateTime::createFromFormat('Y-m-d', '2022-05-28');

        // Verification: when not provided with a time, createFromFormat should use current time.
        $this->assertSame('2022-05-28 12:13:14.168432', $dateTimeFromFormat->format('Y-m-d H:i:s.u'));
    }

    public function test_DateTime_createFromFormat_unix()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14.168432'));

        // this strange pattern occurs in Symfony 4.4.42
        // Symfony\Component\HttpFoundation\ResponseHeaderBag.php:306
        $dateTimeFromFormat = \DateTime::createFromFormat('U', time());

        // time() is integer seconds, so the microseconds get rounded off
        $this->assertSame('1986-06-05 12:13:14.000000', $dateTimeFromFormat->format('Y-m-d H:i:s.u'));
    }

    public function test_date()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));

        $this->assertEquals('1986-06-05', date('Y-m-d'));
        $this->assertEquals('2010-05-22', date('Y-m-d', (new \DateTime('2010-05-22'))->getTimestamp()));
    }

    public function test_date_create()
    {
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05'));

        $this->assertEquals($fakeNow, date_create());
    }

    public function test_date_create_from_format()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));

        $dateTimeFromFormat = date_create_from_format('Y-m-d', '2022-05-28');

        // Verification: when not provided with a time, createFromFormat should use current time.
        $this->assertInstanceOf(\DateTime::class, $dateTimeFromFormat);
        $this->assertSame('2022-05-28 12:13:14', $dateTimeFromFormat->format('Y-m-d H:i:s'));
    }

    public function test_date_create_from_format_must_return_false_on_error()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));

        $dateTime = \DateTime::createFromFormat('\n\o\w', '2022-01-01');
        $this->assertFalse($dateTime);
    }

    public function test_date_create_immutable()
    {
        ClockMock::freeze($fakeNow = new \DateTimeImmutable('1986-06-05'));

        $this->assertEquals($fakeNow, date_create_immutable());
    }

    public function test_date_create_immutable_from_format()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));

        $dateTimeFromFormat = date_create_immutable_from_format('Y-m-d', '2022-05-28');

        // Verification: when not provided with a time, createFromFormat should use current time.
        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTimeFromFormat);
        $this->assertSame('2022-05-28 12:13:14', $dateTimeFromFormat->format('Y-m-d H:i:s'));
    }

    public function test_date_create_immutable_from_format_with_invalid_format()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));

        $dateTimeFromFormat = date_create_immutable_from_format('d/m/Y', '2022-05-28');

        $this->assertFalse($dateTimeFromFormat);
    }

    public function test_date_create_from_format_freeze_reset_must_not_use_mock()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05 12:13:14'));
        ClockMock::reset();

        $dateTime = \DateTime::createFromFormat('\n\o\w', 'now');

        $this->assertInstanceOf(\DateTime::class, $dateTime);
    }

    public function test_getdate()
    {
        ClockMock::freeze(new \DateTime('@518306400'));

        $this->assertEquals(
            [
                'seconds' => 0,
                'minutes' => 0,
                'hours' => 22,
                'mday' => 4,
                'wday' => 3,
                'mon' => 6,
                'year' => 1986,
                'yday' => 154,
                'weekday' => 'Wednesday',
                'month' => 'June',
                0 => 518306400,
            ],
            getdate()
        );
    }

    public static function dataProvider_gettimeofday(): array
    {
        return [
            ['2022-04-04 14:26:29.123456', 'UTC', [1649082389, 123456, 0, 0]],
            ['2022-03-04 14:26:29.123456', 'Europe/Kiev', [1646396789, 123456, -120, 0]],
            ['2022-04-04 14:26:29.123456', 'Europe/Kiev', [1649071589, 123456, -180, 1]],
        ];
    }

    /**
     * @dataProvider dataProvider_gettimeofday
     */
    public function test_gettimeofday(string $freezeDateTime, string $freezeTimeZone, array $expectedResult)
    {
        ClockMock::freeze(new \DateTime($freezeDateTime, new \DateTimeZone($freezeTimeZone)));

        $this->assertEquals(array_combine(['sec', 'usec', 'minuteswest', 'dsttime'], $expectedResult), gettimeofday());
    }

    public function test_gettimeofday_as_float()
    {
        ClockMock::freeze(new \DateTime('2022-04-04 14:26:29.123456')); // UTC, +00:00

        $this->assertEquals(1649082389.123456, gettimeofday(true));
    }

    public function test_gmdate()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));

        $this->assertEquals('1986-06-05', gmdate('Y-m-d'));
        $this->assertEquals('2010-05-22', gmdate('Y-m-d', (new \DateTime('2010-05-22'))->getTimestamp()));
    }

    public function test_gmstrftime()
    {
        ClockMock::freeze($fakeNow = new \DateTime('2022-04-04 14:26:29', new \DateTimeZone('Europe/Kiev')));

        $this->assertEquals('2022-04-04 11:26:29', gmstrftime('%F %T'));
    }

    public static function dataProvider_gmmktime(): array
    {
        // NOTE: for all datasets, hour in freezeDateTime is completely irrelevant because always overridden by $hour
        // parameter provided to gmmktime. Also, in expectedDateTime hour is always "13" because hour 10 in GMT
        // corresponds to hour 13 when we are in +03:00 offset.
        return [
            [
                '2022-04-04T05:26:29+03:00',
                [10],
                '2022-04-04T13:26:29+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, 10],
                '2022-04-04T13:10:29+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, null, 10],
                '2022-04-04T13:26:10+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, null, null, 10],
                '2022-10-04T13:26:29+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, null, null, null, 10],
                '2022-04-10T13:26:29+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, null, null, null, null, 10],
                '2010-04-04T13:26:29+00:00'
            ],
            [
                '2022-04-04T05:26:29+03:00',
                [10, 10, 10, 10, 10, 10],
                '2010-10-10T13:10:10+00:00'
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_gmmktime
     */
    public function test_gmmktime(string $freezeDateTime, array $mktimeArgs, string $expectedDateTime)
    {
        ClockMock::freeze(new \DateTime($freezeDateTime));

        $this->assertEquals($expectedDateTime, date(DATE_ATOM, gmmktime(...$mktimeArgs)));
    }

    public function test_idate()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));

        $this->assertSame(1986, idate('Y'));
        $this->assertSame(2010, idate('Y', (new \DateTime('2010-05-22'))->getTimestamp()));
    }

    public function test_localtime()
    {
        ClockMock::freeze(new \DateTimeImmutable('1986-06-05'));

        $this->assertEquals(
            [
                0 => 0,
                1 => 0,
                2 => 0,
                3 => 5,
                4 => 5,
                5 => 86,
                6 => 4,
                7 => 155,
                8 => 0,
            ],
            localtime()
        );
    }

    public function test_microtime()
    {
        ClockMock::freeze(new \DateTime('@1619000631.123456'));

        $this->assertEquals('0.12345600 1619000631', microtime());
        $this->assertSame(1619000631.123456, microtime(true));
    }

    public function test_server_superglobal_request_time()
    {
        $originalServerRequestTime = $_SERVER['REQUEST_TIME'];
        $originalServerRequestTimeFloat = $_SERVER['REQUEST_TIME_FLOAT'];

        ClockMock::freeze($fakeNow1 = new \DateTime('1986-06-05'));

        $this->assertSame($fakeNow1->getTimestamp(), $_SERVER['REQUEST_TIME']);
        $this->assertSame((float) $fakeNow1->format('U.u'), $_SERVER['REQUEST_TIME_FLOAT']);

        // Freeze twice to make sure original values are still preserved even when freezing multiple times in a row.
        ClockMock::freeze($fakeNow2 = new \DateTime('2022-05-28'));

        $this->assertSame($fakeNow2->getTimestamp(), $_SERVER['REQUEST_TIME']);
        $this->assertSame((float) $fakeNow2->format('U.u'), $_SERVER['REQUEST_TIME_FLOAT']);

        ClockMock::reset();

        // Verify that original values are restored when mocks are reset
        $this->assertSame($originalServerRequestTime, $_SERVER['REQUEST_TIME']);
        $this->assertSame($originalServerRequestTimeFloat, $_SERVER['REQUEST_TIME_FLOAT']);
    }

    public function test_strftime()
    {
        ClockMock::freeze($fakeNow = new \DateTimeImmutable('2022-04-04 14:26:29'));

        $this->assertEquals('2022-04-04 14:26:29', strftime('%F %T'));
    }

    public static function dataProvider_mktime(): array
    {
        return [
            [
                '2022-04-04T14:26:29+00:00',
                [10],
                '2022-04-04T10:26:29+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, 10],
                '2022-04-04T10:10:29+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, null, 10],
                '2022-04-04T10:26:10+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, null, null, 10],
                '2022-10-04T10:26:29+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, null, null, null, 10],
                '2022-04-10T10:26:29+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, null, null, null, null, 10],
                '2010-04-04T10:26:29+00:00'
            ],
            [
                '2022-04-04T14:26:29+00:00',
                [10, 10, 10, 10, 10, 10],
                '2010-10-10T10:10:10+00:00'
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_mktime
     */
    public function test_mktime(string $freezeDateTime, array $mktimeArgs, string $expectedDateTime)
    {
        ClockMock::freeze(new \DateTime($freezeDateTime));

        $this->assertEquals($expectedDateTime, date(DATE_ATOM, mktime(...$mktimeArgs)));
    }

    public function test_strtotime()
    {
        ClockMock::freeze($fakeNow = new \DateTimeImmutable('1986-06-05'));

        $this->assertEquals($fakeNow->getTimestamp(), strtotime('now'));
    }

    public function test_time()
    {
        ClockMock::freeze($fakeNow = new \DateTime('yesterday'));

        $this->assertEquals($fakeNow->getTimestamp(), time());
    }

    public function test_unixtojd()
    {
        ClockMock::freeze(new \DateTime('January 1, 1970 GMT'));

        $this->assertEquals(2440588, unixtojd());

        ClockMock::freeze(new \DateTime('January 3, 1970 GMT'));

        $this->assertEquals(2440590, unixtojd());
    }
}
