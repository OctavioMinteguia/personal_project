<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Job;
use App\Domain\ValueObject\JobId;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use App\Domain\ValueObject\Salary;

interface JobServiceInterface
{
    public function createJob(
        JobTitle $title,
        CompanyName $company,
        JobDescription $description,
        ?Location $location = null,
        ?Salary $salary = null,
        string $type = 'full-time',
        string $level = 'mid',
        array $tags = [],
        bool $remote = false
    ): Job;

    public function getJob(JobId $id): ?Job;

    public function searchJobs(
        ?string $query = null,
        ?string $company = null,
        ?string $location = null,
        ?string $type = null,
        ?string $level = null,
        ?bool $remote = null,
        int $limit = 50,
        int $offset = 0
    ): array;

    public function getAllJobs(int $limit = 50, int $offset = 0): array;

    public function deleteJob(JobId $id): bool;
}


