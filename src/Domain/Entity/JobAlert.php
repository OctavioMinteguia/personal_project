<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'job_alerts')]
class JobAlert
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $searchPattern;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $filters;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct()
    {
        // Private constructor to enforce use of factory methods
    }

    public static function create(
        Email $email,
        ?string $searchPattern = null,
        ?array $filters = null
    ): self {
        $alert = new self();
        $alert->id = Uuid::uuid4()->toString();
        $alert->email = $email->value();
        $alert->searchPattern = $searchPattern;
        $alert->filters = $filters;
        $alert->active = true;
        $alert->createdAt = new \DateTimeImmutable();
        $alert->updatedAt = new \DateTimeImmutable();

        return $alert;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSearchPattern(): ?string
    {
        return $this->searchPattern;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        ?string $searchPattern = null,
        ?array $filters = null
    ): void {
        if ($searchPattern !== null) {
            $this->searchPattern = $searchPattern;
        }
        if ($filters !== null) {
            $this->filters = $filters;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->active = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function matchesJob(Job $job): bool
    {
        // Check search pattern
        if ($this->searchPattern && !$job->matchesSearchCriteria($this->searchPattern)) {
            return false;
        }

        // Check filters
        if ($this->filters) {
            foreach ($this->filters as $filterKey => $filterValue) {
                switch ($filterKey) {
                    case 'company':
                        if ($job->getCompany() !== $filterValue) {
                            return false;
                        }
                        break;
                    case 'location':
                        if ($job->getLocation() !== $filterValue) {
                            return false;
                        }
                        break;
                    case 'type':
                        if ($job->getType() !== $filterValue) {
                            return false;
                        }
                        break;
                    case 'level':
                        if ($job->getLevel() !== $filterValue) {
                            return false;
                        }
                        break;
                    case 'remote':
                        if ($job->isRemote() !== (bool)$filterValue) {
                            return false;
                        }
                        break;
                }
            }
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'searchPattern' => $this->searchPattern,
            'filters' => $this->filters,
            'active' => $this->active,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}


