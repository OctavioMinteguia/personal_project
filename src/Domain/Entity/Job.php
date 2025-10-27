<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\JobId;
use App\Domain\ValueObject\JobTitle;
use App\Domain\ValueObject\CompanyName;
use App\Domain\ValueObject\JobDescription;
use App\Domain\ValueObject\Location;
use App\Domain\ValueObject\Salary;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'jobs')]
class Job
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    private string $company;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $salary;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 50)]
    private string $level;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags;

    #[ORM\Column(type: 'boolean')]
    private bool $remote;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 50, default: 'internal')]
    private string $source;

    private function __construct()
    {
        // Private constructor to enforce use of factory methods
    }

    public static function create(
        JobTitle $title,
        CompanyName $company,
        JobDescription $description,
        ?Location $location = null,
        ?Salary $salary = null,
        string $type = 'full-time',
        string $level = 'mid',
        array $tags = [],
        bool $remote = false,
        string $source = 'internal'
    ): self {
        $job = new self();
        $job->id = Uuid::uuid4()->toString();
        $job->title = $title->value();
        $job->company = $company->value();
        $job->description = $description->value();
        $job->location = $location?->value();
        $job->salary = $salary?->value();
        $job->type = $type;
        $job->level = $level;
        $job->tags = $tags;
        $job->remote = $remote;
        $job->source = $source;
        $job->createdAt = new \DateTimeImmutable();
        $job->updatedAt = new \DateTimeImmutable();

        return $job;
    }

    public static function fromExternalSource(
        string $title,
        string $company,
        string $description,
        ?string $location = null,
        ?string $salary = null,
        string $type = 'full-time',
        string $level = 'mid',
        array $tags = [],
        bool $remote = false
    ): self {
        return self::create(
            new JobTitle($title),
            new CompanyName($company),
            new JobDescription($description),
            $location ? new Location($location) : null,
            $salary ? new Salary($salary) : null,
            $type,
            $level,
            $tags,
            $remote,
            'external'
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function isRemote(): bool
    {
        return $this->remote;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function update(
        ?JobTitle $title = null,
        ?CompanyName $company = null,
        ?JobDescription $description = null,
        ?Location $location = null,
        ?Salary $salary = null,
        ?string $type = null,
        ?string $level = null,
        ?array $tags = null,
        ?bool $remote = null
    ): void {
        if ($title !== null) {
            $this->title = $title->value();
        }
        if ($company !== null) {
            $this->company = $company->value();
        }
        if ($description !== null) {
            $this->description = $description->value();
        }
        if ($location !== null) {
            $this->location = $location->value();
        }
        if ($salary !== null) {
            $this->salary = $salary->value();
        }
        if ($type !== null) {
            $this->type = $type;
        }
        if ($level !== null) {
            $this->level = $level;
        }
        if ($tags !== null) {
            $this->tags = $tags;
        }
        if ($remote !== null) {
            $this->remote = $remote;
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function matchesSearchCriteria(string $query): bool
    {
        $searchTerms = array_map('strtolower', explode(' ', $query));
        
        $searchableContent = strtolower(
            $this->title . ' ' . 
            $this->company . ' ' . 
            $this->description . ' ' . 
            ($this->location ?? '') . ' ' .
            ($this->tags ? implode(' ', $this->tags) : '')
        );

        foreach ($searchTerms as $term) {
            if (str_contains($searchableContent, $term)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'company' => $this->company,
            'description' => $this->description,
            'location' => $this->location,
            'salary' => $this->salary,
            'type' => $this->type,
            'level' => $this->level,
            'tags' => $this->tags,
            'remote' => $this->remote,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'source' => $this->source,
        ];
    }
}


