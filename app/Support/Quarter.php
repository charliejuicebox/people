<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Calendar quarters in the group's timezone (Perth and Bali are both UTC+8).
 */
class Quarter
{
    public function __construct(public readonly int $year, public readonly int $quarter) {}

    public static function current(?CarbonImmutable $now = null): self
    {
        $now ??= CarbonImmutable::now(config('people.timezone'));

        return new self($now->year, intdiv($now->month - 1, 3) + 1);
    }

    public function previous(): self
    {
        return $this->quarter === 1 ? new self($this->year - 1, 4) : new self($this->year, $this->quarter - 1);
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, ($this->quarter - 1) * 3 + 1, 1, 0, 0, 0, config('people.timezone'));
    }

    public function end(): CarbonImmutable
    {
        return $this->start()->addMonths(3)->subSecond();
    }

    public function label(): string
    {
        return "Q{$this->quarter} {$this->year}";
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->quarter === $other->quarter;
    }
}
