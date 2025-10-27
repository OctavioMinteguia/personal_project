<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Job;
use App\Domain\Entity\JobAlert;
use App\Domain\Repository\JobAlertRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Application\Service\EmailServiceInterface;

class JobAlertService implements JobAlertServiceInterface
{
    public function __construct(
        private JobAlertRepositoryInterface $alertRepository,
        private EmailServiceInterface $emailService
    ) {
    }

    public function subscribe(
        Email $email,
        ?string $searchPattern = null,
        ?array $filters = null
    ): JobAlert {
        $alert = JobAlert::create($email, $searchPattern, $filters);
        $this->alertRepository->save($alert);

        return $alert;
    }

    public function unsubscribe(string $alertId): bool
    {
        $alert = $this->alertRepository->findById($alertId);
        
        if ($alert === null) {
            return false;
        }

        $alert->deactivate();
        $this->alertRepository->save($alert);
        
        return true;
    }

    public function getAlertsByEmail(Email $email): array
    {
        return $this->alertRepository->findByEmail($email->value());
    }

    public function notifyNewJob(Job $job): void
    {
        $alerts = $this->alertRepository->findActiveAlerts();
        
        foreach ($alerts as $alert) {
            if ($alert->matchesJob($job)) {
                $this->emailService->sendJobAlert(
                    $alert->getEmail(),
                    'New Job Alert: ' . $job->getTitle(),
                    [$job->toArray()]
                );
            }
        }
    }

    public function getActiveAlerts(): array
    {
        return $this->alertRepository->findActiveAlerts();
    }
}


