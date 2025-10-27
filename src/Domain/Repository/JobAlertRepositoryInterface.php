<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\JobAlert;

interface JobAlertRepositoryInterface
{
    public function save(JobAlert $alert): void;

    public function findById(string $id): ?JobAlert;

    public function findByEmail(string $email): array;

    public function findActiveAlerts(): array;

    public function delete(JobAlert $alert): void;
}


