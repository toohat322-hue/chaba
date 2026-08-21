<?php

namespace App\Http\Resources\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AdminAuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_name' => $this->actor_name,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'subject_label' => $this->subject_label,
            'before' => $this->before,
            'after' => $this->after,
            'created_at' => $this->created_at,
        ];
    }
}
