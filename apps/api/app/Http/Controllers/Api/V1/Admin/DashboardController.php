<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'total_products' => Product::where('status', 'active')->count(),
            'total_categories' => Category::count(),
            'total_customers' => User::whereNull('role_id')->count(),
            'low_stock_count' => Inventory::whereColumn('available_quantity', '<=', 'low_stock_threshold')
                ->where('available_quantity', '>', 0)
                ->count(),
            // Orders/Payments were built in a later phase but this dashboard
            // was never revisited to reflect it — this was reporting a
            // permanent false/null even after orders existed and worked.
            'orders_available' => true,
            'total_orders' => Order::count(),
            // Revenue from every order that isn't cancelled/failed, in
            // centimes (same unit as every other money field in the API) —
            // matches how AdminOrderController surfaces orders elsewhere.
            'total_sales' => (int) Order::whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total'),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
        ]);
    }

    /**
     * Charts for the admin dashboard — the plain /dashboard endpoint above
     * only ever exposed raw point-in-time counts, no trend or breakdown
     * data at all. 5-minute cache: these are aggregate queries over the
     * whole orders table, and a dashboard doesn't need per-request freshness.
     */
    public function analytics(): JsonResponse
    {
        return ApiResponse::success(Cache::remember('admin.dashboard.analytics', now()->addMinutes(5), function () {
            $salesByDay = Order::query()
                ->whereNotIn('order_status', ['cancelled', 'failed'])
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')
                ->groupBy('day')
                ->get()
                ->keyBy(fn ($row) => (string) $row->day);

            $salesLast30Days = collect(range(0, 29))->map(function ($i) use ($salesByDay) {
                $date = now()->subDays(29 - $i)->toDateString();

                return ['date' => $date, 'total' => (int) ($salesByDay[$date]->total ?? 0)];
            })->values();

            $topProducts = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNotIn('orders.order_status', ['cancelled', 'failed'])
                ->selectRaw('order_items.product_name_snapshot as name, SUM(order_items.quantity) as quantity_sold')
                ->groupBy('order_items.product_name_snapshot')
                ->orderByDesc('quantity_sold')
                ->limit(5)
                ->get()
                ->map(fn ($row) => ['name' => $row->name, 'quantity_sold' => (int) $row->quantity_sold]);

            $ordersByStatus = Order::query()
                ->selectRaw('order_status as status, COUNT(*) as count')
                ->groupBy('order_status')
                ->get()
                ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->count]);

            return [
                'sales_last_30_days' => $salesLast30Days,
                'top_products' => $topProducts,
                'orders_by_status' => $ordersByStatus,
            ];
        }));
    }
}
