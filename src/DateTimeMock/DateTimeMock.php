<?php
declare(strict_types=1);

namespace SlopeIt\ClockMock\DateTimeMock;

use DateTimeZone;
use SlopeIt\ClockMock\ClockMock;

/**
 * Class used by ClockMock as a mock for DateTime.
 *
 * @internal Do not use directly
 */
class DateTimeMock extends \DateTime
{
    public function __construct(?string $datetime = 'now', ?DateTimeZone $timezone = null)
    {
        $datetime = $datetime ?? 'now';

        parent::__construct($datetime, $timezone);

        $this->setTimestamp(strtotime($datetime, ClockMock::getFrozenDateTime()->getTimestamp()));

        if ($this->shouldUseMicrosecondsOfFrozenDate($datetime)) {
            $this->setTime(
                (int) $this->format('H'),
                (int) $this->format('i'),
                (int) $this->format('s'),
                (int) ClockMock::getFrozenDateTime()->format('u')
            );
        }
    }

    private function shouldUseMicrosecondsOfFrozenDate(string $datetime): bool
    {
        // After some empirical tests, we've seen that microseconds are set to the current actual ones only when all of
        // these variables are false (i.e. when an absolute date or time is not provided).
        $parsedDate = date_parse($datetime);
        return $parsedDate['year'] === false
            && $parsedDate['month'] === false
            && $parsedDate['day'] === false
            && $parsedDate['hour'] === false
            && $parsedDate['minute'] === false
            && $parsedDate['second'] === false;
    }
}
