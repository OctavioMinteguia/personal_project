<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Job;
use App\Domain\Repository\JobRepositoryInterface;
use App\Domain\ValueObject\JobId;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineJobRepository implements JobRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(Job $job): void
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function findById(JobId $id): ?Job
    {
        return $this->entityManager->getRepository(Job::class)->find($id->value());
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Job::class)->findAll();
    }

    public function findByCriteria(array $criteria, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->entityManager->getRepository(Job::class)->createQueryBuilder('j');

        foreach ($criteria as $field => $value) {
            if ($field === 'remote') {
                $qb->andWhere("j.{$field} = :{$field}");
            } else {
                $qb->andWhere("j.{$field} = :{$field}");
            }
            $qb->setParameter($field, $value);
        }

        $qb->setMaxResults($limit)
           ->setFirstResult($offset)
           ->orderBy('j.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countByCriteria(array $criteria): int
    {
        $qb = $this->entityManager->getRepository(Job::class)->createQueryBuilder('j')
            ->select('COUNT(j.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("j.{$field} = :{$field}")
               ->setParameter($field, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function delete(Job $job): void
    {
        $this->entityManager->remove($job);
        $this->entityManager->flush();
    }
}


