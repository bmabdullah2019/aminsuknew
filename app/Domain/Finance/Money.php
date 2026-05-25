<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use InvalidArgumentException;

final class Money
{
    private const SCALE = 100;

    public function __construct(
        private readonly int $minor,
        private readonly string $currency = 'BDT'
    ) {}

    public static function fromMinor(int $minor, string $currency = 'BDT'): self
    {
        return new self($minor, strtoupper(trim($currency)) ?: 'BDT');
    }

    public static function fromMajor(int|float|string $major, string $currency = 'BDT'): self
    {
        $value = (float) $major;

        return new self((int) round($value * self::SCALE, 0, PHP_ROUND_HALF_UP), strtoupper(trim($currency)) ?: 'BDT');
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function major(): float
    {
        return round($this->minor / self::SCALE, 2);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor + $other->minor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor - $other->minor, $this->currency);
    }

    public function multiply(int|float $multiplier): self
    {
        return new self(
            (int) round($this->minor * (float) $multiplier, 0, PHP_ROUND_HALF_UP),
            $this->currency
        );
    }

    public function max(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->minor >= $other->minor ? $this : $other;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function clampNonNegative(): self
    {
        return new self(max(0, $this->minor), $this->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Money currency mismatch: [%s] vs [%s].',
                $this->currency,
                $other->currency
            ));
        }
    }
}
