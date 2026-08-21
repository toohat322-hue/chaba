<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * PRD §12/§7.15/§25 Phase 6: plain Eloquent-backed notification log — not
 * Laravel's built-in Notification system, whose default table shape
 * (type/notifiable_type/notifiable_id/data/read_at) doesn't match this
 * project's already-migrated custom schema (channel/event_type/payload/
 * status). 'in_app' is the only channel actually dispatched — it's
 * "delivered" the instant the row exists, no external gateway involved.
 * email/sms/whatsapp/push stay valid enum values for later; nothing sends
 * through them yet (same single-channel reality as OtpService today).
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function notify(User $user, string $eventType, array $payload = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'channel' => 'in_app',
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsRead(Notification $notification): Notification
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    }
}
