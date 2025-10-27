<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\Service\JobAlertServiceInterface;
use App\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JobAlertController
{
    public function __construct(
        private JobAlertServiceInterface $alertService
    ) {
    }

    #[Route('/api/job-alerts', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            if (empty($data['email'])) {
                return new JsonResponse([
                    'error' => 'Email is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $alert = $this->alertService->subscribe(
                new Email($data['email']),
                $data['searchPattern'] ?? null,
                $data['filters'] ?? null
            );

            return new JsonResponse([
                'success' => true,
                'data' => $alert->toArray()
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

    #[Route('/api/job-alerts/{id}', methods: ['DELETE'])]
    public function unsubscribe(string $id): JsonResponse
    {
        try {
            $success = $this->alertService->unsubscribe($id);

            if (!$success) {
                return new JsonResponse([
                    'error' => 'Job alert not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Job alert unsubscribed successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
