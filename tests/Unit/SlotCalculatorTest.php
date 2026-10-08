<?php

namespace Tests\Unit;

use App\Booking\SlotCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class SlotCalculatorTest extends TestCase
{
    private function utcTimes(array $slots): array
    {
        return array_map(fn (CarbonImmutable $s) => $s->format('H:i'), $slots);
    }

    public function test_converts_local_working_hours_to_utc_slots(): void
    {
        $now = CarbonImmutable::parse('2026-10-01 00:00', 'UTC');
        $slots = (new SlotCalculator)->freeSlots('2026-10-08', 'Europe/Kyiv', [['16:00', '19:00']], [], $now);

        $this->assertSame(['13:00', '14:00', '15:00'], $this->utcTimes($slots));
    }

    public function test_skips_taken_slots_and_a_partial_last_hour(): void
    {
        $now = CarbonImmutable::parse('2026-10-01 00:00', 'UTC');
        $taken = [CarbonImmutable::parse('2026-10-08 14:00', 'UTC')];
        $slots = (new SlotCalculator)->freeSlots('2026-10-08', 'Europe/Kyiv', [['16:00', '18:30']], $taken, $now);

        $this->assertSame(['13:00'], $this->utcTimes($slots));
    }

    public function test_respects_minimum_lead_time(): void
    {
        $now = CarbonImmutable::parse('2026-10-08 13:30', 'UTC');
        $slots = (new SlotCalculator(60, 60))->freeSlots('2026-10-08', 'Europe/Kyiv', [['16:00', '19:00']], [], $now);

        $this->assertSame(['15:00'], $this->utcTimes($slots));
    }

    public function test_merges_several_windows_in_order(): void
    {
        $now = CarbonImmutable::parse('2026-10-01 00:00', 'UTC');
        $slots = (new SlotCalculator)->freeSlots(
            '2026-10-10',
            'Europe/Kyiv',
            [['18:00', '19:00'], ['10:00', '11:00']],
            [],
            $now,
        );

        $this->assertSame(['07:00', '15:00'], $this->utcTimes($slots));
    }

    public function test_handles_daylight_saving_change(): void
    {
        $now = CarbonImmutable::parse('2026-10-01 00:00', 'UTC');
        $slots = (new SlotCalculator)->freeSlots('2026-10-26', 'Europe/Kyiv', [['16:00', '17:00']], [], $now);

        $this->assertSame(['14:00'], $this->utcTimes($slots));
    }
}
