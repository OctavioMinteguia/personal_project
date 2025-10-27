<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class CompanyName
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmedValue = trim($value);
        
        if (empty($trimmedValue)) {
            throw new \InvalidArgumentException('Company name cannot be empty');
        }

        if (strlen($trimmedValue) > 255) {
            throw new \InvalidArgumentException('Company name cannot exceed 255 characters');
        }

        $this->value = $trimmedValue;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(CompanyName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


