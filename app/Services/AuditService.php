<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function record(Request $request, string $action, string $module, ?Model $record = null, array $metadata = []): void
    {
        AuditLog::create(['user_id' => $request->user()?->id, 'action' => $action, 'module' => $module, 'auditable_type' => $record?->getMorphClass(), 'auditable_id' => $record?->getKey(), 'ip_address' => $request->ip(), 'result' => 'SUCCESS', 'metadata' => $metadata ?: null]);
    }
}
