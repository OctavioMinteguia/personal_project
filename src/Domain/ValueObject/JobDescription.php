<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class JobDescription
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmedValue = trim($value);
        
        if (empty($trimmedValue)) {
            throw new \InvalidArgumentException('Job description cannot be empty');
        }

        $this->value = $trimmedValue;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(JobDescription $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}


