<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Throwable;

class DiagnoseMail extends Command
{
    protected $signature = 'mail:diagnose';

    protected $description = 'Check SMTP authentication and queued email counts without sending emails or exposing credentials';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $this->info('Mailer: '.$mailer.' | Queue: '.config('queue.default'));
        if (config('queue.default') === 'database') {
            $connection = DB::connection(config('queue.connections.database.connection'));
            $this->line('Pending jobs: '.$connection->table(config('queue.connections.database.table', 'jobs'))->count());
        }
        if ($mailer !== 'smtp') {
            $this->error('SMTP is not enabled. Set MAIL_MAILER=smtp.');

            return self::FAILURE;
        }
        foreach (['host', 'username', 'password'] as $key) {
            $value = config('mail.mailers.smtp.'.$key);
            if (! $value || str_contains($value, 'REPLACE_WITH')) {
                $this->error('Missing SMTP '.$key.'. Update your .env file.');

                return self::FAILURE;
            }
        }
        try {
            $transport = Mail::mailer('smtp')->getSymfonyTransport();
            if (! $transport instanceof SmtpTransport) {
                $this->error('The configured transport is not SMTP.');

                return self::FAILURE;
            }
            $transport->start();
            $transport->stop();
            $this->info('SMTP connection and authentication succeeded. No email was sent.');
            $this->line('Run php artisan queue:work to deliver queued emails.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $authenticationFailed = str_contains(strtolower($exception->getMessage()), 'authenticate') || str_contains($exception->getMessage(), '535');
            $this->error($authenticationFailed ? 'SMTP authentication failed. Check MAIL_USERNAME and the provider app password.' : 'SMTP connection failed. Check host, port, TLS, network access, and PHP CA certificates.');

            return self::FAILURE;
        }
    }
}
