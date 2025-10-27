<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class Salary
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmedValue = trim($value);
        
        if (empty($trimmedValue)) {
            throw new \InvalidArgumentException('Salary cannot be empty');
        }

        if (strlen($trimmedValue) > 100) {
            throw new \InvalidArgumentException('Salary cannot exceed 100 characters');
        }

        $this->value = $trimmedValue;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Salary $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


