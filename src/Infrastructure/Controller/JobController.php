<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Entity\Job;
use App\Domain\Service\JobServiceInterface;
use App\Domain\Service\JobAlertServiceInterface;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use App\Domain\ValueObject\Salary;
use App\Domain\ValueObject\JobId;
use App\Infrastructure\External\JobSourceClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class JobController
{
    public function __construct(
        private JobServiceInterface $jobService,
        private JobAlertServiceInterface $alertService,
        private JobSourceClientInterface $externalJobClient,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/api/jobs', methods: ['POST'])]
    public function post(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            if (empty($data['title']) || empty($data['company']) || empty($data['description'])) {
                return new JsonResponse([
                    'error' => 'Missing required fields: title, company, description'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create job using domain service
            $job = $this->jobService->createJob(
                new JobTitle($data['title']),
                new CompanyName($data['company']),
                new JobDescription($data['description']),
                isset($data['location']) ? new Location($data['location']) : null,
                isset($data['salary']) ? new Salary($data['salary']) : null,
                $data['type'] ?? 'full-time',
                $data['level'] ?? 'mid',
                $data['tags'] ?? [],
                $data['remote'] ?? false
            );

            // Notify subscribers about new job
            $this->alertService->notifyNewJob($job);

            return new JsonResponse([
                'success' => true,
                'data' => $job->toArray()
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/jobs/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query->get('q');
            $company = $request->query->get('company');
            $location = $request->query->get('location');
            $type = $request->query->get('type');
            $level = $request->query->get('level');
            $remote = $request->query->get('remote');
            $source = $request->query->get('source');
            $limit = (int) ($request->query->get('limit', 50));
            $offset = (int) ($request->query->get('offset', 0));

            // Validate pagination parameters
            if ($limit < 1 || $limit > 100) {
                $limit = 50;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            // Search internal jobs
            $internalJobs = $this->jobService->searchJobs(
                $query,
                $company,
                $location,
                $type,
                $level,
                $remote === 'true' ? true : ($remote === 'false' ? false : null),
                $limit,
                $offset
            );

            // Fetch external jobs if available
            $externalJobs = [];
            if ($this->externalJobClient->isAvailable()) {
                $externalJobsData = $this->externalJobClient->fetchJobs();
                $externalJobs = array_map(fn($jobData) => $jobData, $externalJobsData);
            }

            // Combine and sort results
            $allJobs = array_merge($internalJobs, $externalJobs);
            $allJobs = array_map(
                fn($job) => $job instanceof Job ? $job->toArray() : $job,
                $allJobs
            );
            
            // Apply additional filtering to external jobs if needed
            if ($query || $company || $location || $type || $level || $remote !== null || $source) {
                $allJobs = array_filter($allJobs, function($job) use ($query, $company, $location, $type, $level, $remote, $source) {
                    if ($query && !$this->matchesSearchQuery($job, $query)) {
                        return false;
                    }
                    if ($company && ($job['company'] ?? '') !== $company) {
                        return false;
                    }
                    if ($location && ($job['location'] ?? '') !== $location) {
                        return false;
                    }
                    if ($type && ($job['type'] ?? '') !== $type) {
                        return false;
                    }
                    if ($level && ($job['level'] ?? '') !== $level) {
                        return false;
                    }
                    if ($remote !== null && ($job['remote'] ?? false) !== ($remote === 'true')) {
                        return false;
                    }
                    if ($source === 'internal' && ($job['source'] ?? 'internal') !== 'internal') {
                        return false;
                    }
                    if ($source === 'external' && ($job['source'] ?? 'internal') !== 'external') {
                        return false;
                    }
                    return true;
                });
            }

            // Sort by creation date (newest first)
            usort($allJobs, function($a, $b) {
                $dateA = $a['createdAt'] ?? $a['created_at'] ?? '';
                $dateB = $b['createdAt'] ?? $b['created_at'] ?? '';
                return strcmp($dateB, $dateA);
            });

            // Apply pagination to combined results
            $totalCount = count($allJobs);
            $paginatedJobs = array_slice($allJobs, $offset, $limit);

            return new JsonResponse([
                'success' => true,
                'data' => $paginatedJobs,
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/jobs/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        try {
            $job = $this->jobService->getJob(new JobId($id));

            if ($job === null) {
                return new JsonResponse([
                    'error' => 'Job not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $job->toArray()
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Invalid job ID'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function matchesSearchQuery(array|Job $job, string $query): bool
    {
        $jobData = $job instanceof Job ? $job->toArray() : $job;
        $searchTerms = array_map('strtolower', explode(' ', $query));
        $searchableContent = strtolower(
            ($jobData['title'] ?? '') . ' ' .
            ($jobData['company'] ?? '') . ' ' .
            ($jobData['description'] ?? '') . ' ' .
            ($jobData['location'] ?? '') . ' ' .
            (is_array($jobData['tags'] ?? null) ? implode(' ', $jobData['tags']) : '')
        );

        foreach ($searchTerms as $term) {
            if (str_contains($searchableContent, $term)) {
                return true;
            }
        }

        return false;
    }
}


