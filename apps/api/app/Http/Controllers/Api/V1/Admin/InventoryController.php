<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    /**
     * PRD's literal `/admin/inventory/{variant_id}/adjust` — first real use
     * of the Phase 1 inventory_adjustments audit table. Locks the inventory
     * row (same pattern as CartService) so a negative adjustment can never
     * take stock_quantity below the currently-reserved amount.
     */
    public function adjust(AdjustInventoryRequest $request, string $variantId): JsonResponse
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            throw new ApiException('not_found', 'Product variant not found.', 404);
        }

        $delta = (int) $request->input('delta');
        $actorId = $request->user()->id;

        $inventory = DB::transaction(function () use ($variantId, $delta, $request, $actorId, $variant) {
            $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();

            if (! $inventory) {
                throw new ApiException('not_found', 'Inventory record not found for this variant.', 404);
            }

            $oldStock = $inventory->stock_quantity;
            $newStock = $oldStock + $delta;

            if ($newStock < $inventory->reserved_quantity) {
                throw ApiException::conflict(
                    'stock_below_reserved',
                    'This adjustment would take stock below what is already reserved in active carts.',
                );
            }

            $inventory->update(['stock_quantity' => $newStock]);

            DB::table('inventory_adjustments')->insert([
                'id' => (string) Str::orderedUuid(),
                'variant_id' => $variantId,
                'delta' => $delta,
                'reason' => $request->input('reason'),
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            AuditLogger::log(
                $request->user(),
                'inventory.adjusted',
                $variant,
                ['stock_quantity' => [$oldStock, $newStock], 'reason' => [null, $request->input('reason')]],
            );

            return $inventory;
        });

        return ApiResponse::success([
            'variant_id' => $variantId,
            'stock_quantity' => $inventory->stock_quantity,
            'reserved_quantity' => $inventory->reserved_quantity,
            'available_quantity' => $inventory->fresh()->available_quantity,
        ]);
    }
}
