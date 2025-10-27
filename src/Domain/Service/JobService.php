<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Job;
use App\Domain\Repository\JobRepositoryInterface;
use App\Domain\ValueObject\JobId;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use App\Domain\ValueObject\Salary;

class JobService implements JobServiceInterface
{
    public function __construct(
        private JobRepositoryInterface $jobRepository
    ) {
    }

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
    ): Job {
        $job = Job::create(
            $title,
            $company,
            $description,
            $location,
            $salary,
            $type,
            $level,
            $tags,
            $remote
        );

        $this->jobRepository->save($job);

        return $job;
    }

    public function getJob(JobId $id): ?Job
    {
        return $this->jobRepository->findById($id);
    }

    public function searchJobs(
        ?string $query = null,
        ?string $company = null,
        ?string $location = null,
        ?string $type = null,
        ?string $level = null,
        ?bool $remote = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $criteria = [];

        if ($company !== null) {
            $criteria['company'] = $company;
        }
        if ($location !== null) {
            $criteria['location'] = $location;
        }
        if ($type !== null) {
            $criteria['type'] = $type;
        }
        if ($level !== null) {
            $criteria['level'] = $level;
        }
        if ($remote !== null) {
            $criteria['remote'] = $remote;
        }

        $jobs = $this->jobRepository->findByCriteria($criteria, $limit, $offset);

        // If there's a query, filter by text search
        if ($query !== null && !empty(trim($query))) {
            $jobs = array_filter($jobs, function (Job $job) use ($query) {
                return $job->matchesSearchCriteria($query);
            });
        }

        return array_values($jobs);
    }

    public function getAllJobs(int $limit = 50, int $offset = 0): array
    {
        return $this->jobRepository->findByCriteria([], $limit, $offset);
    }

    public function deleteJob(JobId $id): bool
    {
        $job = $this->jobRepository->findById($id);
        
        if ($job === null) {
            return false;
        }

        $this->jobRepository->delete($job);
        
        return true;
    }
}


