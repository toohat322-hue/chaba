<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Default driver — no real SMS gateway required. Writes to the log and,
 * if SMS_DEV_INBOX is set, also emails the message so it's readable in
 * Mailhog during local development. Never throws: this driver is meant to
 * always "succeed" so auth flows stay testable with zero external setup.
 */
class LogSmsDriver implements SmsDriver
{
    public function send(string $phone, string $message): void
    {
        Log::info("SMS to {$phone}: {$message}");

        $devInbox = config('services.sms.dev_inbox');

        if ($devInbox) {
            Mail::raw("[SMS to {$phone}] {$message}", function ($mail) use ($devInbox) {
                $mail->to($devInbox)->subject('CHABA SMS (dev)');
            });
        }
    }
}
