<?php

namespace App\ValueObjects;

use App\Models\Currency;
use App\Models\Location;
use InvalidArgumentException;

class Money
{
    private int $amount; // Stored in smallest unit
    private Currency $currency;
    private ?Location $location;

    public function __construct(float|int|string $amount, Currency $currency, ?Location $location = null)
    {
        $this->currency = $currency;
        $this->location = $location;
        $this->amount = $this->toCents($amount);
    }

    /**
     * Create from cents (for database retrieval)
     */
    public static function fromCents(int $cents, Currency $currency, ?Location $location = null): self
    {
        $instance = new self(0, $currency, $location);
        $instance->amount = $cents;
        return $instance;
    }

    /**
     * Convert decimal amount to cents (smallest unit)
     */
    private function toCents(float|int|string $amount): int
    {
        if (is_string($amount)) {
            $amount = (float) preg_replace('/[^\d.-]/', '', $amount);
        }
        
        return (int) round((float) $amount * $this->currency->getMultiplier());
    }

    /**
     * Convert cents to decimal amount
     */
    private function toDecimal(): float
    {
        return $this->amount / $this->currency->getMultiplier();
    }

    /**
     * Get amount in decimal format
     */
    public function getAmount(): float
    {
        return $this->toDecimal();
    }

    /**
     * Get amount in cents for storage
     */
    public function getCents(): int
    {
        return $this->amount;
    }

    /**
     * Get currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Get location
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * Convert to another currency using integer math to prevent rounding errors
     */
    public function convertTo(Currency $targetCurrency): self
    {
        if ($this->currency->id === $targetCurrency->id) {
            return $this;
        }

        // Get base currency for the tenant
        $baseCurrency = Currency::where('tenant_id', $this->currency->tenant_id)
            ->where('is_base_currency', true)
            ->first();

        if (!$baseCurrency) {
            throw new InvalidArgumentException('Base currency not found for tenant');
        }

        // Use high precision integer math
        $precision = 1000000; // 1 million for high precision
        
        if ($this->currency->is_base_currency) {
            // Direct conversion from base to target
            // target = base * exchange_rate
            $targetCents = (int) round(
                $this->amount * 
                $targetCurrency->exchange_rate * 
                ($targetCurrency->getMultiplier() / $baseCurrency->getMultiplier())
            );
        } 
        elseif ($targetCurrency->is_base_currency) {
            // Direct conversion to base
            // base = source / exchange_rate
            $targetCents = (int) round(
                $this->amount / 
                $this->currency->exchange_rate * 
                ($baseCurrency->getMultiplier() / $this->currency->getMultiplier())
            );
        }
        else {
            // Convert via base currency: source -> base -> target
            // First to base
            $baseAmount = $this->amount / $this->currency->exchange_rate;
            
            // Then to target
            $targetCents = (int) round(
                $baseAmount * 
                $targetCurrency->exchange_rate * 
                ($targetCurrency->getMultiplier() / $baseCurrency->getMultiplier())
            );
        }

        return self::fromCents($targetCents, $targetCurrency, $this->location);
    }

    /**
     * Add another money object
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        
        return new self(
            $this->getAmount() + $other->getAmount(),
            $this->currency,
            $this->location
        );
    }

    /**
     * Subtract another money object
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        
        return new self(
            $this->getAmount() - $other->getAmount(),
            $this->currency,
            $this->location
        );
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $factor): self
    {
        return new self(
            $this->getAmount() * $factor,
            $this->currency,
            $this->location
        );
    }

    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw new InvalidArgumentException('Division by zero');
        }
        
        return new self(
            $this->getAmount() / $divisor,
            $this->currency,
            $this->location
        );
    }

    /**
     * Allocate amount across n targets
     */
    public function allocate(int $n): array
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of targets must be positive');
        }

        $low = (int) floor($this->amount / $n);
        $high = $low + 1;
        $remainder = $this->amount % $n;
        
        $results = [];
        for ($i = 0; $i < $n; $i++) {
            $cents = $i < $remainder ? $high : $low;
            $results[] = self::fromCents($cents, $this->currency, $this->location);
        }
        
        return $results;
    }

    /**
     * Check if amounts are equal
     */
    public function equals(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount === $other->amount;
    }

    /**
     * Check if greater than another
     */
    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    /**
     * Check if greater than or equal to another
     */
    public function greaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    /**
     * Check if less than another
     */
    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    /**
     * Check if less than or equal to another
     */
    public function lessThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount <= $other->amount;
    }

    /**
     * Check if amount is zero
     */
    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Check if amount is positive
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if amount is negative
     */
    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Assert both money objects have same currency
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency->id !== $other->currency->id) {
            throw new InvalidArgumentException('Currency mismatch');
        }
    }

    /**
     * Format with currency symbol
     */
    public function format(): string
    {
        return $this->currency->format($this->toDecimal());
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->toDecimal(),
            'cents' => $this->amount,
            'currency_code' => $this->currency->code,
            'currency_id' => $this->currency->id,
            'formatted' => $this->format(),
        ];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->format();
    }
}