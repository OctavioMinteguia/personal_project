<?php

declare(strict_types=1);

namespace App\Application\Service;

interface EmailServiceInterface
{
    public function sendJobAlert(
        string $toEmail,
        string $subject,
        array $jobs
    ): void;
}


