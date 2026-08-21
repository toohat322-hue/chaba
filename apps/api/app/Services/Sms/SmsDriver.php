<?php

namespace App\Services\Sms;

interface SmsDriver
{
    public function send(string $phone, string $message): void;
}
