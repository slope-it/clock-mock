<?php
declare(strict_types=1);

namespace SlopeIt\Tests\ClockMock;

use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;

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

    public function test_DateTime_constructor_with_absolute_mocked_date()
    {
        ClockMock::freeze($fakeNow = new \DateTime('1986-06-05'));

        $this->assertEquals($fakeNow, new \DateTime('now'));
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

    public function test_idate()
    {
        ClockMock::freeze(new \DateTime('1986-06-05'));

        $this->assertSame(1986, idate('Y'));
        $this->assertSame(2010, idate('Y', (new \DateTime('2010-05-22'))->getTimestamp()));
    }

    public function test_microtime()
    {
        ClockMock::freeze(new \DateTime('@1619000631.123456'));

        $this->assertEquals('0.123456 1619000631', microtime());
        $this->assertSame(1619000631.123456, microtime(true));
    }

    public function test_time()
    {
        ClockMock::freeze($fakeNow = new \DateTime('yesterday'));

        $this->assertEquals($fakeNow->getTimestamp(), time());
    }
}
