<?php

declare(strict_types=1);

namespace App\Infrastructure\External;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class JobberwockyExtraSourceClient implements JobSourceClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $externalJobSourceUrl,
        private LoggerInterface $logger
    ) {
    }

    public function fetchJobs(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->externalJobSourceUrl);
            $data = $response->toArray();

            return $this->normalizeJobs($data);
        } catch (HttpExceptionInterface $e) {
            $this->logger->error('Failed to fetch jobs from external source', [
                'url' => $this->externalJobSourceUrl,
                'error' => $e->getMessage()
            ]);
            
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while fetching external jobs', [
                'url' => $this->externalJobSourceUrl,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->externalJobSourceUrl, [
                'timeout' => 5
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function normalizeJobs(array $data): array
    {
        $normalizedJobs = [];

        foreach ($data as $jobData) {
            try {
                $normalizedJob = $this->normalizeJob($jobData);
                if ($normalizedJob !== null) {
                    $normalizedJobs[] = $normalizedJob;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to normalize job data', [
                    'job_data' => $jobData,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $normalizedJobs;
    }

    private function normalizeJob(array $jobData): ?array
    {
        // Handle the messy response format from the external source
        // This is where we clean up and standardize the data structure
        
        $normalized = [
            'title' => $this->extractTitle($jobData),
            'company' => $this->extractCompany($jobData),
            'description' => $this->extractDescription($jobData),
            'location' => $this->extractLocation($jobData),
            'salary' => $this->extractSalary($jobData),
            'type' => $this->extractType($jobData),
            'level' => $this->extractLevel($jobData),
            'tags' => $this->extractTags($jobData),
            'remote' => $this->extractRemote($jobData),
            'source' => 'external'
        ];

        // Validate required fields
        if (empty($normalized['title']) || empty($normalized['company']) || empty($normalized['description'])) {
            return null;
        }

        return $normalized;
    }

    private function extractTitle(array $jobData): string
    {
        return $jobData['title'] ?? 
               $jobData['job_title'] ?? 
               $jobData['position'] ?? 
               $jobData['role'] ?? 
               '';
    }

    private function extractCompany(array $jobData): string
    {
        return $jobData['company'] ?? 
               $jobData['company_name'] ?? 
               $jobData['employer'] ?? 
               '';
    }

    private function extractDescription(array $jobData): string
    {
        return $jobData['description'] ?? 
               $jobData['job_description'] ?? 
               $jobData['summary'] ?? 
               $jobData['details'] ?? 
               '';
    }

    private function extractLocation(array $jobData): ?string
    {
        return $jobData['location'] ?? 
               $jobData['city'] ?? 
               $jobData['address'] ?? 
               null;
    }

    private function extractSalary(array $jobData): ?string
    {
        return $jobData['salary'] ?? 
               $jobData['compensation'] ?? 
               $jobData['pay'] ?? 
               null;
    }

    private function extractType(array $jobData): string
    {
        $type = $jobData['type'] ?? 
                $jobData['employment_type'] ?? 
                $jobData['job_type'] ?? 
                'full-time';

        // Normalize common variations
        $type = strtolower($type);
        if (in_array($type, ['full-time', 'fulltime', 'full_time', 'permanent'])) {
            return 'full-time';
        }
        if (in_array($type, ['part-time', 'parttime', 'part_time'])) {
            return 'part-time';
        }
        if (in_array($type, ['contract', 'contractor', 'freelance'])) {
            return 'contract';
        }
        if (in_array($type, ['internship', 'intern'])) {
            return 'internship';
        }

        return 'full-time'; // default
    }

    private function extractLevel(array $jobData): string
    {
        $level = $jobData['level'] ?? 
                 $jobData['seniority'] ?? 
                 $jobData['experience_level'] ?? 
                 'mid';

        // Normalize common variations
        $level = strtolower($level);
        if (in_array($level, ['junior', 'entry', 'entry-level', 'entry_level'])) {
            return 'junior';
        }
        if (in_array($level, ['senior', 'sr', 'lead', 'principal'])) {
            return 'senior';
        }
        if (in_array($level, ['mid', 'middle', 'intermediate', 'medior'])) {
            return 'mid';
        }

        return 'mid'; // default
    }

    private function extractTags(array $jobData): array
    {
        $tags = $jobData['tags'] ?? 
                $jobData['skills'] ?? 
                $jobData['technologies'] ?? 
                [];

        if (is_string($tags)) {
            return array_map('trim', explode(',', $tags));
        }

        if (!is_array($tags)) {
            return [];
        }

        return $tags;
    }

    private function extractRemote(array $jobData): bool
    {
        $remote = $jobData['remote'] ?? 
                  $jobData['work_from_home'] ?? 
                  $jobData['telecommute'] ?? 
                  false;

        if (is_string($remote)) {
            $remote = strtolower($remote);
            return in_array($remote, ['true', 'yes', '1', 'remote', 'wfh']);
        }

        return (bool) $remote;
    }
}


