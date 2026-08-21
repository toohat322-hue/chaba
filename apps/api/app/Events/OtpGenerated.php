<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever OtpService generates a code — never exposed via the API
 * response itself. Lets tests assert on the real code (Event::fake()) without
 * parsing logs/mail, and gives production a hook for additional delivery
 * channels later without changing OtpService's public contract.
 */
class OtpGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly string $phone,
        public readonly string $purpose,
        public readonly string $code,
    ) {}
}
