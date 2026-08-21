<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminAuditLogResource;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->string('actor_id'));
        }

        // Prefix match — "product" finds product.created/updated/archived
        // without the caller needing the exact action string.
        if ($request->filled('action')) {
            $query->where('action', 'ILIKE', $request->string('action').'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => AdminAuditLogResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
