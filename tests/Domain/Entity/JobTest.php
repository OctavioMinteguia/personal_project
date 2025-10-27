<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Job;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use App\Domain\ValueObject\Salary;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testCanCreateJobWithRequiredFields(): void
    {
        $job = Job::create(
            new JobTitle('Senior PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a senior PHP developer...')
        );

        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals('Senior PHP Developer', $job->getTitle());
        $this->assertEquals('Acme Corp', $job->getCompany());
        $this->assertEquals('We are looking for a senior PHP developer...', $job->getDescription());
        $this->assertEquals('full-time', $job->getType());
        $this->assertEquals('mid', $job->getLevel());
        $this->assertFalse($job->isRemote());
        $this->assertEquals('internal', $job->getSource());
        $this->assertNotNull($job->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCreatedAt());
    }

    public function testCanCreateJobWithAllFields(): void
    {
        $job = Job::create(
            new JobTitle('Senior PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a senior PHP developer...'),
            new Location('Buenos Aires'),
            new Salary('$5000 - $7000'),
            'full-time',
            'senior',
            ['PHP', 'Symfony', 'MySQL'],
            true
        );

        $this->assertEquals('Buenos Aires', $job->getLocation());
        $this->assertEquals('$5000 - $7000', $job->getSalary());
        $this->assertEquals('senior', $job->getLevel());
        $this->assertEquals(['PHP', 'Symfony', 'MySQL'], $job->getTags());
        $this->assertTrue($job->isRemote());
    }

    public function testCanCreateJobFromExternalSource(): void
    {
        $job = Job::fromExternalSource(
            'Frontend Developer',
            'Tech Startup',
            'Join our amazing team...',
            'Remote',
            '$4000 - $6000',
            'contract',
            'mid',
            ['React', 'TypeScript'],
            true
        );

        $this->assertEquals('Frontend Developer', $job->getTitle());
        $this->assertEquals('Tech Startup', $job->getCompany());
        $this->assertEquals('external', $job->getSource());
        $this->assertTrue($job->isRemote());
    }

    public function testCanUpdateJob(): void
    {
        $job = Job::create(
            new JobTitle('PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('Original description')
        );

        $job->update(
            new JobTitle('Senior PHP Developer'),
            null,
            new JobDescription('Updated description'),
            new Location('Buenos Aires')
        );

        $this->assertEquals('Senior PHP Developer', $job->getTitle());
        $this->assertEquals('Updated description', $job->getDescription());
        $this->assertEquals('Buenos Aires', $job->getLocation());
        $this->assertGreaterThan($job->getCreatedAt(), $job->getUpdatedAt());
    }

    public function testMatchesSearchCriteria(): void
    {
        $job = Job::create(
            new JobTitle('Senior PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('We are looking for a senior PHP developer with Symfony experience'),
            new Location('Buenos Aires'),
            null,
            'full-time',
            'senior',
            ['PHP', 'Symfony', 'MySQL']
        );

        $this->assertTrue($job->matchesSearchCriteria('PHP'));
        $this->assertTrue($job->matchesSearchCriteria('Symfony'));
        $this->assertTrue($job->matchesSearchCriteria('Acme'));
        $this->assertTrue($job->matchesSearchCriteria('Buenos Aires'));
        $this->assertTrue($job->matchesSearchCriteria('senior developer'));
        $this->assertFalse($job->matchesSearchCriteria('Python'));
        $this->assertFalse($job->matchesSearchCriteria('Java'));
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $job = Job::create(
            new JobTitle('PHP Developer'),
            new CompanyName('Acme Corp'),
            new JobDescription('Job description'),
            new Location('Buenos Aires'),
            new Salary('$3000'),
            'full-time',
            'mid',
            ['PHP', 'MySQL'],
            true
        );

        $array = $job->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('company', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('location', $array);
        $this->assertArrayHasKey('salary', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('level', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('remote', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
        $this->assertArrayHasKey('source', $array);
    }
}


