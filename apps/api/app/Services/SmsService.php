<?php

namespace App\Services;

use App\Services\Sms\LogSmsDriver;
use App\Services\Sms\SmsDriver;
use App\Services\Sms\TwilioSmsDriver;

/**
 * Resolves the configured SMS_DRIVER to a concrete driver. Adding a new
 * gateway later means adding one more `match` arm + driver class, not
 * touching any of this service's callers (OtpService, OrderService, ...).
 */
class SmsService
{
    public function send(string $phone, string $message): void
    {
        $this->driver()->send($phone, $message);
    }

    private function driver(): SmsDriver
    {
        return match (config('services.sms.driver', 'log')) {
            'twilio' => new TwilioSmsDriver,
            default => new LogSmsDriver,
        };
    }
}
