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
        parent::__construct($datetime, $timezone);

        $isDateTimeStringRelative = $this->isRelativeDateString($datetime);

        // Empty string is not accepted by strtotime, which we use below, so normalize to 'now'. By the way, this is
        // also equivalent to how original \DateTime treats it.
        if ($datetime === '') {
            $datetime = 'now';
        }

        if ($timezone !== null && !$isDateTimeStringRelative) {
            // When there's a timezone and the provided date is absolute, the timestamp must be calculated with that
            // specific timezone in order to mimic behavior of the original \DateTime (which does not modify time).
            $this->setTimestamp(
                strtotime(
                    "$datetime {$timezone->getName()}",
                    ClockMock::getFrozenDateTime()->getTimestamp()
                )
            );
        } else {
            $this->setTimestamp(strtotime($datetime, ClockMock::getFrozenDateTime()->getTimestamp()));
        }

        // After some empirical tests, we've seen that microseconds are set to the current actual ones only when an
        // absolute date or time is not provided.
        if ($isDateTimeStringRelative) {
            $this->setTime(
                (int) $this->format('H'),
                (int) $this->format('i'),
                (int) $this->format('s'),
                (int) ClockMock::getFrozenDateTime()->format('u')
            );
        }
    }

    /**
     * Returns whether the provided one is a relative date (e.g. "now", "yesterday", "tomorrow", etc...).
     */
    private function isRelativeDateString(string $datetime): bool
    {
        $parsedDate = date_parse($datetime);
        return $parsedDate['year'] === false
            && $parsedDate['month'] === false
            && $parsedDate['day'] === false
            && $parsedDate['hour'] === false
            && $parsedDate['minute'] === false
            && $parsedDate['second'] === false;
    }
}
