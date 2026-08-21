<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeNewsletterRequest;
use App\Mail\NewsletterSubscriptionConfirmedMail;
use App\Models\NewsletterSubscriber;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function store(SubscribeNewsletterRequest $request): JsonResponse
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => $request->validated('email'),
            'locale' => $request->validated('locale'),
            'subscribed_at' => now(),
            // Duplicate emails are already rejected by the unique validation
            // rule before this ever runs, so every successful subscription
            // here is genuinely new — safe to always mail + always mint a
            // fresh unsubscribe token.
            'unsubscribe_token' => Str::uuid(),
        ]);

        Mail::to($subscriber->email)->send(new NewsletterSubscriptionConfirmedMail($subscriber));

        return ApiResponse::success(['id' => $subscriber->id, 'email' => $subscriber->email], 201);
    }

    public function unsubscribe(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            throw new ApiException('not_found', 'This unsubscribe link is invalid or has already been used.', 404);
        }

        $subscriber->delete();

        return ApiResponse::success(['unsubscribed' => true]);
    }
}
