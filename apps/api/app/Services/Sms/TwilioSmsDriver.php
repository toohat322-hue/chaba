<?php

namespace App\Services\Sms;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio REST API over Laravel's HTTP client — no twilio/sdk composer
 * dependency needed, this is a single documented POST call
 * (https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource).
 * Inert until TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN/TWILIO_FROM_NUMBER are
 * set and SMS_DRIVER=twilio — same "ready but not activated until real
 * credentials exist" pattern as the CIB/eDahabia payment gateways.
 */
class TwilioSmsDriver implements SmsDriver
{
    public function send(string $phone, string $message): void
    {
        $sid = config('services.sms.twilio.sid');
        $token = config('services.sms.twilio.token');
        $from = config('services.sms.twilio.from');

        if (! $sid || ! $token || ! $from) {
            throw new ApiException('sms_provider_not_configured', 'SMS delivery is not configured.', 500);
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $phone,
                'From' => $from,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            Log::error("Twilio SMS to {$phone} failed: {$response->body()}");

            throw new ApiException('sms_delivery_failed', 'Unable to send the SMS.', 502);
        }
    }
}
