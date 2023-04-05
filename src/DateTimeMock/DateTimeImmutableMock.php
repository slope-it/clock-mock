<?php
declare(strict_types=1);

namespace SlopeIt\ClockMock\DateTimeMock;

use DateTimeZone;

/**
 * Class used by ClockMock as a mock for DateTimeImmutable.
 *
 * @internal Do not use directly
 */
class DateTimeImmutableMock extends \DateTimeImmutable
{
    public function __construct(?string $datetime = 'now', ?DateTimeZone $timezone = null)
    {
        // Create an immutable instance starting from the mutable mock, so we don't have to replicate mocking logic.
        $mutableDateTime = new DateTimeMock($datetime, $timezone);

        parent::__construct($mutableDateTime->format('Y-m-d\TH:i:s.u'), $mutableDateTime->getTimezone());
    }
}
