<?php

declare(strict_types=1);

namespace App\Tests\Domain\Service;

use App\Domain\Entity\Job;
use App\Domain\Repository\JobRepositoryInterface;
use App\Domain\Service\JobService;
use App\Domain\ValueObject\JobId;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class JobServiceTest extends TestCase
{
    private JobRepositoryInterface|MockObject $jobRepository;
    private JobService $jobService;

    protected function setUp(): void
    {
        $this->jobRepository = $this->createMock(JobRepositoryInterface::class);
        $this->jobService = new JobService($this->jobRepository);
    }

    public function testCanCreateJob(): void
    {
        $this->jobRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Job::class));

        $job = $this->jobService->createJob(
            new JobTitle('PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a PHP developer')
        );

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals('PHP Developer', $job->getTitle());
        $this->assertEquals('Acme Corp', $job->getCompany());
    }

    public function testCanGetJobById(): void
    {
        $jobId = new JobId('test-id');
        $expectedJob = Job::create(
            new JobTitle('PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a PHP developer')
        );

        $this->jobRepository
            ->expects($this->once())
            ->method('findById')
            ->with($jobId)
            ->willReturn($expectedJob);

        $result = $this->jobService->getJob($jobId);

        $this->assertEquals($expectedJob, $result);
    }

    public function testReturnsNullWhenJobNotFound(): void
    {
        $jobId = new JobId('non-existent-id');

        $this->jobRepository
            ->expects($this->once())
            ->method('findById')
            ->with($jobId)
            ->willReturn(null);

        $result = $this->jobService->getJob($jobId);

        $this->assertNull($result);
    }

    public function testCanSearchJobs(): void
    {
        $jobs = [
            Job::create(
                new JobTitle('PHP Developer'),
                new CompanyName('Acme Corp'),
                new JobDescription('PHP developer position'),
                new Location('Buenos Aires')
            ),
            Job::create(
                new JobTitle('Java Developer'),
                new CompanyName('Beta Corp'),
                new JobDescription('Java developer position'),
                new Location('Córdoba')
            )
        ];

        $this->jobRepository
            ->expects($this->once())
            ->method('findByCriteria')
            ->with(['location' => 'Buenos Aires'], 50, 0)
            ->willReturn($jobs);

        $result = $this->jobService->searchJobs(
            location: 'Buenos Aires'
        );

        $this->assertCount(2, $result);
    }

    public function testCanDeleteJob(): void
    {
        $jobId = new JobId('test-id');
        $job = Job::shouldBeDeleted = Job::create(
            new JobTitle('PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a PHP developer')
        );

        $this->jobRepository
            ->expects($this->once())
            ->method('findById')
            ->with($jobId)
            ->willReturn($job);

        $this->jobRepository
            ->expects($this->once())
            ->method('delete')
            ->with($job);

        $result = $this->jobService->deleteJob($jobId);

        $this->assertTrue($result);
    }

    public function testReturnsFalseWhenDeletingNonExistentJob(): void
    {
        $jobId = new JobId('non-existent-id');

        $this->jobRepository
            ->expects($this->once())
            ->method('findById')
            ->with($jobId)
            ->willReturn(null);

        $this->jobRepository
            ->expects($this->never())
            ->method('delete');

        $result = $this->jobService->deleteJob($jobId);

        $this->assertFalse($result);
    }
}


