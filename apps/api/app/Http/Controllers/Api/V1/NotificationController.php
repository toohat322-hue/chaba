<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PRD §25 Phase 6. Every lookup is scoped by the authenticated user's own
// id (matches OrderController's $request->user()->orders() pattern) so a
// guessed notification id can never read/mutate someone else's inbox.
class NotificationController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $paginator = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => NotificationResource::collection($paginator->getCollection()),
            'unread_count' => $this->notifications->unreadCount($user),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $model = Notification::where('user_id', $request->user()->id)->find($notification);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Notification not found.');
        }

        return ApiResponse::success(new NotificationResource($this->notifications->markAsRead($model)));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllAsRead($request->user());

        return ApiResponse::success(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $model = Notification::where('user_id', $request->user()->id)->find($notification);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Notification not found.');
        }

        $model->delete();

        return ApiResponse::success(['message' => 'Notification deleted.']);
    }
}
