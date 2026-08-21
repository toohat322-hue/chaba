<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryFee;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin accountability trail (PRD §12/§15's audit_logs table — scaffolded
 * early but never wired to any controller until now; see the call sites in
 * Admin\* controllers). Scoped to the highest-stakes admin write actions,
 * not every mutation in the app. `actor_name`/`subject_label` are resolved
 * once, at write time, and snapshotted (2026_08_20_000001 migration) — the
 * table's original `entity_type`/`entity_id` alone would go stale once a
 * subject is later renamed or hard-deleted, exactly when the log matters
 * most.
 */
class AuditLogger
{
    /**
     * @param  array<string, array{0: mixed, 1: mixed}>|null  $changes  field => [old, new]
     */
    public static function log(User $actor, string $action, Model $subject, ?array $changes = null): void
    {
        $before = null;
        $after = null;

        if ($changes !== null) {
            $before = array_map(fn ($pair) => $pair[0], $changes);
            $after = array_map(fn ($pair) => $pair[1], $changes);
        }

        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->full_name,
            'action' => $action,
            'entity_type' => class_basename($subject),
            'entity_id' => $subject->getKey(),
            'subject_label' => self::labelFor($subject),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Builds a `{field: [old, new]}` diff — call after ->fill($data) but
     * BEFORE ->save(). save() calls syncOriginal() once it commits, so
     * getOriginal() after save() would already reflect the *new* value, not
     * the prior one; getDirty()/getOriginal() are only reliable while the
     * unsaved change is still pending. Timestamps are excluded; a diff with
     * nothing left is returned as null rather than an empty array (nothing
     * meaningful changed).
     */
    public static function diff(Model $subject): ?array
    {
        $changed = collect($subject->getDirty())
            ->except(['updated_at', 'created_at'])
            ->mapWithKeys(fn ($newValue, $key) => [$key => [$subject->getOriginal($key), $newValue]])
            ->all();

        return $changed === [] ? null : $changed;
    }

    private static function labelFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof Product => $subject->name_en,
            $subject instanceof ProductVariant => $subject->sku,
            $subject instanceof Category => $subject->name_en,
            $subject instanceof Order => $subject->order_number,
            $subject instanceof Coupon => $subject->code,
            $subject instanceof User => $subject->full_name,
            $subject instanceof DeliveryFee => "Wilaya {$subject->wilaya_code}",
            $subject instanceof StoreSetting => 'Store Settings',
            default => (string) $subject->getKey(),
        };
    }
}
