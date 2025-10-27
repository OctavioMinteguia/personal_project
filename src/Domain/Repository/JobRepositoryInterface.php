<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Job;
use App\Domain\ValueObject\JobId;

interface JobRepositoryInterface
{
    public function save(Job $job): void;

    public function findById(JobId $id): ?Job;

    public function findAll(): array;

    public function findByCriteria(array $criteria, int $limit = 50, int $offset = 0): array;

    public function countByCriteria(array $criteria): int;

    public function delete(Job $job): void;
}


