<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPaymentResource;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PRD §7.9/§19: admin visibility into transactions (payments.view) and COD
// reconciliation (payments.reconcile) — see routes/api.php for the
// permission-gated route groups.
class PaymentController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('order')->orderByDesc('created_at');

        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('reconciliation_status')) {
            $query->where('reconciliation_status', $request->string('reconciliation_status'));
        }

        $paginator = $query->paginate(self::PER_PAGE, page: (int) $request->input('page', 1));

        return ApiResponse::success([
            'items' => AdminPaymentResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $payment): JsonResponse
    {
        $model = Payment::with(['order', 'refunds', 'reconciler'])->find($payment);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Payment not found.');
        }

        return ApiResponse::success(new AdminPaymentResource($model));
    }

    public function reconcile(Request $request, string $payment): JsonResponse
    {
        $model = Payment::find($payment);

        if (! $model) {
            throw ApiException::notFound('not_found', 'Payment not found.');
        }

        $status = $request->string('reconciliation_status')->toString();

        if (! in_array($status, ['reconciled', 'disputed', 'unreconciled'], true)) {
            throw ApiException::businessRule(
                'invalid_reconciliation_status',
                'reconciliation_status must be reconciled, disputed, or unreconciled.',
            );
        }

        $model->update([
            'reconciliation_status' => $status,
            'reconciled_at' => $status === 'unreconciled' ? null : now(),
            'reconciled_by' => $status === 'unreconciled' ? null : $request->user()->id,
            'reconciliation_notes' => $request->input('notes'),
        ]);

        return ApiResponse::success(new AdminPaymentResource($model->fresh(['order', 'reconciler'])));
    }
}
