<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterSubscriberController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $paginator = NewsletterSubscriber::query()
            ->orderByDesc('subscribed_at')
            ->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => collect($paginator->items())->map(fn ($subscriber) => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'locale' => $subscriber->locale,
                'subscribed_at' => $subscriber->subscribed_at,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
