<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\MediaApiCredential;
use App\Models\MediaOutlet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiUsageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role?->code === 'CLIENTE' || $user->hasPermission('API_CONSUMOS.VER'), 403);
        $filters = $request->validate(['media_outlet_id' => ['nullable', 'integer'], 'endpoint' => ['nullable', 'string', 'max:120'], 'status_code' => ['nullable', 'integer', 'min:100', 'max:599'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $allowedMedia = MediaOutlet::query()->when($user->role?->code === 'CLIENTE', fn ($query) => $query->where('organization_id', $user->organization_id));
        $mediaIds = (clone $allowedMedia)->pluck('id');
        $query = ApiUsageLog::query()->whereIn('media_outlet_id', $mediaIds)
            ->when($filters['media_outlet_id'] ?? null, fn ($q, $id) => $q->where('media_outlet_id', $id))
            ->when($filters['endpoint'] ?? null, fn ($q, $endpoint) => $q->where('endpoint', $endpoint))
            ->when($filters['status_code'] ?? null, fn ($q, $status) => $q->where('status_code', $status))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
        $stats = ['requests' => (clone $query)->count(), 'records' => (clone $query)->sum('records_returned'), 'errors' => (clone $query)->where('status_code', '>=', 400)->count(), 'average_ms' => (int) round((clone $query)->avg('duration_ms') ?? 0)];
        $logs = $query->with(['mediaOutlet:id,name', 'credential:id,name,token_prefix'])->latest()->paginate(25)->withQueryString();
        $media = $allowedMedia->orderBy('name')->get(['id', 'name']);
        $activeCredentials = MediaApiCredential::query()->whereIn('media_outlet_id', $mediaIds)->whereNull('revoked_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count();

        return view('admin.api-usage.index', compact('logs', 'media', 'filters', 'stats', 'activeCredentials'));
    }
}
