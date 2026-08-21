<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    public function test_log_driver_logs_the_message(): void
    {
        config(['services.sms.driver' => 'log', 'services.sms.dev_inbox' => null]);
        Log::spy();

        app(SmsService::class)->send('+213555000111', 'hello');

        Log::shouldHaveReceived('info')->once()->with('SMS to +213555000111: hello');
    }

    public function test_twilio_driver_posts_to_the_twilio_api_when_configured(): void
    {
        config([
            'services.sms.driver' => 'twilio',
            'services.sms.twilio.sid' => 'ACtest',
            'services.sms.twilio.token' => 'tokentest',
            'services.sms.twilio.from' => '+15005550006',
        ]);

        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201)]);

        app(SmsService::class)->send('+213555000111', 'hello');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ACtest/Messages.json')
                && $request['To'] === '+213555000111'
                && $request['Body'] === 'hello';
        });
    }

    public function test_twilio_driver_without_credentials_throws_a_clear_error(): void
    {
        config(['services.sms.driver' => 'twilio', 'services.sms.twilio.sid' => null]);

        $this->expectExceptionMessage('SMS delivery is not configured.');

        app(SmsService::class)->send('+213555000111', 'hello');
    }
}
