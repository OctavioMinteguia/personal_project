<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Controller;

use App\Domain\Entity\Job;
use App\Domain\Service\JobServiceInterface;
use App\Domain\Service\JobAlertServiceInterface;
use App\Domain\ValueObject\JobId;
use App\Infrastructure\Controller\JobController;
use App\Infrastructure\External\JobSourceClientInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class JobControllerTest extends TestCase
{
    private JobServiceInterface|MockObject $jobService;
    private JobAlertServiceInterface|MockObject $alertService;
    private JobSourceClientInterface|MockObject $externalJobClient;
    private SerializerInterface|MockObject $serializer;
    private ValidatorInterface|MockObject $validator;
    private JobController $controller;

    protected function setUp(): void
    {
        $this->jobService = $this->createMock(JobServiceInterface::class);
        $this->alertService = $this->createMock(JobAlertServiceInterface::class);
        $this->externalJobClient = $this->createMock(JobSourceClientInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        
        $this->controller = new JobController(
            $this->jobService,
            $this->alertService,
            $this->externalJobClient,
            $this->serializer,
            $this->validator
        );
    }

    public function testCanCreateJob(): void
    {
        $jobData = [
            'title' => 'PHP Developer',
            'company' => 'Acme Corp',
            'description' => 'We are looking for a PHP developer',
            'location' => 'Buenos Aires',
            'type' => 'full-time',
            'level' => 'mid'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($jobData));

        $job = Job::create(
            new \App\Domain\ValueObject\JobTitle($jobData['title']),
            new \App\Domain\ValueObject\CompanyName($jobData['company']),
            new \App\Domain\ValueObject\JobDescription($jobData['description']),
            new \App\Domain\ValueObject\Location($jobData['location'])
        );

        $this->jobService
            ->expects($this->once())
            ->method('createJob')
            ->willReturn($job);

        $this->alertService
            ->expects($this->once())
            ->method('notifyNewJob')
            ->with($job);

        $response = $this->controller->post($request);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('PHP Developer', $responseData['data']['title']);
    }

    public function testReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->post($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function testReturnsBadRequestForMissingRequiredFields(): void
    {
        $jobData = [
            'title' => 'PHP Developer',
            // Missing company and description
        ];

        $request = new Request([], [], [], [], [], [], json_encode($jobData));

        $response = $this->controller->post($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Missing required fields', $responseData['error']);
    }

    public function testCanSearchJobs(): void
    {
        $request = new Request(['q' => 'PHP', 'limit' => '10']);

        $jobs = [
            Job::create(
                new \App\Domain\ValueObject\JobTitle('PHP Developer'),
                new \App\Domain\ValueObject\CompanyName('Acme Corp'),
                new \App\Domain\ValueObject\JobDescription('PHP developer position')
            )
        ];

        $this->jobService
            ->expects($this->once())
            ->method('searchJobs')
            ->with('PHP', null, null, null, null, null, 10, 0)
            ->willReturn($jobs);

        $this->externalJobClient
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $response = $this->controller->search($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
    }

    public function testCanGetJobById(): void
    {
        $jobId = 'test-job-id';
        $job = Job::create(
            new \App\Domain\ValueObject\JobTitle('PHP Developer'),
            new \App\Domain\ValueObject\CompanyName('Acme Corp'),
            new \App\Domain\ValueObject\JobDescription('PHP developer position')
        );

        $this->jobService
            ->expects($this->once())
            ->method('getJob')
            ->with(new JobId($jobId))
            ->willReturn($job);

        $response = $this->controller->get($jobId);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('PHP Developer', $responseData['data']['title']);
    }

    public function testReturnsNotFoundForNonExistentJob(): void
    {
        $jobId = 'non-existent-id';

        $this->jobService
            ->expects($this->once())
            ->method('getJob')
            ->with(new JobId($jobId))
            ->willReturn(null);

        $response = $this->controller->get($jobId);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Job not found', $responseData['error']);
    }
}


