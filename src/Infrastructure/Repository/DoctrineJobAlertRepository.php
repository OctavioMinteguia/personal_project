<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\JobAlert;
use App\Domain\Repository\JobAlertRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineJobAlertRepository implements JobAlertRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(JobAlert $alert): void
    {
        $this->entityManager->persist($alert);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?JobAlert
    {
        return $this->entityManager->getRepository(JobAlert::class)->find($id);
    }

    public function findByEmail(string $email): array
    {
        return $this->entityManager->getRepository(JobAlert::class)
            ->createQueryBuilder('ja')
            ->where('ja.email = :email')
            ->setParameter('email', $email)
            ->orderBy('ja.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveAlerts(): array
    {
        return $this->entityManager->getRepository(JobAlert::class)
            ->createQueryBuilder('ja')
            ->where('ja.active = :active')
            ->setParameter('active', true)
            ->orderBy('ja.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function delete(JobAlert $alert): void
    {
        $this->entityManager->remove($alert);
        $this->entityManager->flush();
    }
}


