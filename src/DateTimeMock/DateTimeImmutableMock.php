<?php
declare(strict_types=1);

namespace Slope\Test\Clock;

use DateTimeZone;

/**
 * "Inner" class used as a mock for DateTimeImmutable
 *
 * @deprecated This must not be used in production code!
 */
class DateTimeImmutableMock extends \DateTimeImmutable
{
    public function __construct(?string $datetime = 'now', DateTimeZone $timezone = null)
    {
        // Just use the other class to not replicate freezing logic.
        $otherDateTime = new DateTimeMock($datetime, $timezone);

        parent::__construct($otherDateTime->format('Y-m-d H:i:s.u'), $timezone);
    }
}
