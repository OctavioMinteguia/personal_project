<?php

declare(strict_types=1);

namespace App\Infrastructure\External;

interface JobSourceClientInterface
{
    public function fetchJobs(): array;

    public function isAvailable(): bool;
}


