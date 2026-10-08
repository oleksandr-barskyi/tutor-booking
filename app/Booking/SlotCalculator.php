<?php

namespace App\Booking;

use Carbon\CarbonImmutable;

final class SlotCalculator
{
    public function __construct(
        private readonly int $lessonMinutes = 60,
        private readonly int $minLeadMinutes = 60,
    ) {}

    public function freeSlots(string $date, string $timezone, array $windows, array $takenStarts, CarbonImmutable $now): array
    {
        $taken = array_map(fn (CarbonImmutable $t) => $t->utc()->getTimestamp(), $takenStarts);
        $earliest = $now->utc()->addMinutes($this->minLeadMinutes);
        $slots = [];

        foreach ($windows as [$from, $to]) {
            $cursor = CarbonImmutable::parse("{$date} {$from}", $timezone);
            $end = CarbonImmutable::parse("{$date} {$to}", $timezone);

            while ($cursor->addMinutes($this->lessonMinutes)->lte($end)) {
                $utc = $cursor->utc();
                if ($utc->gte($earliest) && ! in_array($utc->getTimestamp(), $taken, true)) {
                    $slots[] = $utc;
                }
                $cursor = $cursor->addMinutes($this->lessonMinutes);
            }
        }

        usort($slots, fn ($a, $b) => $a <=> $b);

        return $slots;
    }

    public function lessonMinutes(): int
    {
        return $this->lessonMinutes;
    }
}
