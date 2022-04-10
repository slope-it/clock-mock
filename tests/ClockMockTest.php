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
    
    public function test_DateTime_constructor_with_absolute_mocked_date()
    {
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05'));

        $this->assertEquals($fakeNow, new \DateTime('now'));
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

    public function test_date_create_immutable()
    {
        ClockMock::freeze($fakeNow = new \DateTimeImmutable('1986-06-05'));

        $this->assertEquals($fakeNow, date_create_immutable());
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

    public function test_gmdate()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));

        $this->assertEquals('1986-06-05', gmdate('Y-m-d'));
        $this->assertEquals('2010-05-22', gmdate('Y-m-d', (new \DateTime('2010-05-22'))->getTimestamp()));
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

        $this->assertEquals('0.123456 1619000631', microtime());
        $this->assertSame(1619000631.123456, microtime(true));
    }

    public function test_server()
    {
        $serverRequestTime      = $_SERVER['REQUEST_TIME'];
        $serverRequestTimeFloat = $_SERVER['REQUEST_TIME_FLOAT'];

        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05'));

        $this->assertEquals($fakeNow->getTimestamp(), $_SERVER['REQUEST_TIME']);
        $this->assertEquals((float) $fakeNow->format('U.u'), $_SERVER['REQUEST_TIME_FLOAT']);

        ClockMock::reset();

        $this->assertEquals($serverRequestTime, $_SERVER['REQUEST_TIME']);
        $this->assertEquals($serverRequestTimeFloat, $_SERVER['REQUEST_TIME_FLOAT']);
    }

    public function test_server_freeze_twice()
    {
        $serverRequestTime      = $_SERVER['REQUEST_TIME'];
        $serverRequestTimeFloat = $_SERVER['REQUEST_TIME_FLOAT'];

        ClockMock::freeze(new \DateTime('1986-06-05'));
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-06'));

        $this->assertEquals($fakeNow->getTimestamp(), $_SERVER['REQUEST_TIME']);
        $this->assertEquals((float) $fakeNow->format('U.u'), $_SERVER['REQUEST_TIME_FLOAT']);

        ClockMock::reset();

        $this->assertEquals($serverRequestTime, $_SERVER['REQUEST_TIME']);
        $this->assertEquals($serverRequestTimeFloat, $_SERVER['REQUEST_TIME_FLOAT']);
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
}
