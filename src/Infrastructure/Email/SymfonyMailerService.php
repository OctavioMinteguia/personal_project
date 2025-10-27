<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

use App\Application\Service\EmailServiceInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Psr\Log\LoggerInterface;

class SymfonyMailerService implements EmailServiceInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function sendJobAlert(
        string $toEmail,
        string $subject,
        array $jobs
    ): void {
        try {
            $htmlContent = $this->generateHtmlContent($jobs);
            $textContent = $this->generateTextContent($jobs);

            $email = (new SymfonyEmail())
                ->from('noreply@jobberwocky.com')
                ->to($toEmail)
                ->subject($subject)
                ->text($textContent)
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Job alert email sent successfully', [
                'to' => $toEmail,
                'subject' => $subject,
                'jobs_count' => count($jobs)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send job alert email', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function generateHtmlContent(array $jobs): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Job Alerts</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .job { border: 1px solid #ddd; margin: 20px 0; padding: 20px; border-radius: 5px; }
        .job-title { font-size: 1.2em; font-weight: bold; color: #2c3e50; }
        .company { color: #7f8c8d; font-weight: bold; }
        .description { margin: 10px 0; }
        .meta { font-size: 0.9em; color: #7f8c8d; }
        .tags { margin-top: 10px; }
        .tag { background: #ecf0f1; padding: 2px 8px; margin: 2px; border-radius: 3px; display: inline-block; font-size: 0.8em; }
    </style>
</head>
<body>
    <h1>New Job Alerts</h1>
    <p>We found new job opportunities that match your criteria!</p>';

        foreach ($jobs as $job) {
            $html .= '<div class="job">';
            $html .= '<div class="job-title">' . htmlspecialchars($job['title']) . '</div>';
            $html .= '<div class="company">' . htmlspecialchars($job['company']) . '</div>';
            
            if (!empty($job['location'])) {
                $html .= '<div class="meta">📍 ' . htmlspecialchars($job['location']) . '</div>';
            }
            
            if (!empty($job['salary'])) {
                $html .= '<div class="meta">💰 ' . htmlspecialchars($job['salary']) . '</div>';
            }
            
            $html .= '<div class="meta">' . ucfirst($job['type']) . ' • ' . ucfirst($job['level']) . '</div>';
            
            if (!empty($job['remote']) && $job['remote']) {
                $html .= '<div class="meta">🏠 Remote</div>';
            }
            
            $html .= '<div class="description">' . htmlspecialchars(substr($job['description'], 0, 300)) . '...</div>';
            
            if (!empty($job['tags'])) {
                $html .= '<div class="tags">';
                foreach ($job['tags'] as $tag) {
                    $html .= '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        $html .= '<p><small>This is an automated email from Jobberwocky. You can unsubscribe at any time.</small></p>';
        $html .= '</body></html>';

        return $html;
    }

    private function generateTextContent(array $jobs): string
    {
        $text = "New Job Alerts\n\n";
        $text .= "We found new job opportunities that match your criteria!\n\n";

        foreach ($jobs as $job) {
            $text .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $text .= "Title: " . $job['title'] . "\n";
            $text .= "Company: " . $job['company'] . "\n";
            
            if (!empty($job['location'])) {
                $text .= "Location: " . $job['location'] . "\n";
            }
            
            if (!empty($job['salary'])) {
                $text .= "Salary: " . $job['salary'] . "\n";
            }
            
            $text .= "Type: " . ucfirst($job['type']) . " • Level: " . ucfirst($job['level']) . "\n";
            
            if (!empty($job['remote']) && $job['remote']) {
                $text .= "Remote: Yes\n";
            }
            
            $text .= "\nDescription:\n" . substr($job['description'], 0, 300) . "...\n";
            
            if (!empty($job['tags'])) {
                $text .= "\nTags: " . implode(', ', $job['tags']) . "\n";
            }
            
            $text .= "\n";
        }

        $text .= "\nThis is an automated email from Jobberwocky. You can unsubscribe at any time.\n";

        return $text;
    }
}


