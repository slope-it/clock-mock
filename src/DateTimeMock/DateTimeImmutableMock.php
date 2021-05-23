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
        // Just use the mutable version of the mock, so we don't have to replicate freezing logic.
        $otherDateTime = new DateTimeMock($datetime, $timezone);

        parent::__construct($otherDateTime->format('Y-m-d H:i:s.u'), $timezone);
    }
}
