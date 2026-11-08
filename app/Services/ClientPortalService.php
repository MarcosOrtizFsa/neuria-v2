<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\ApiUsageLog;
use App\Models\AutomationLedgerEntry;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use App\Models\ContentDistributionEvent;
use App\Models\GeneratedDraft;
use App\Models\MediaApiCredential;
use App\Models\MediaChannel;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ClientPortalService
{
    /** @return array<string, mixed> */
    public function summary(User $user, CarbonImmutable $month): array
    {
        $organization = $user->organization;

        if ($organization === null) {
            return ['organization' => null, 'month' => $month];
        }

        $organizationId = $organization->id;
        $periodStart = $month->utc();
        $periodEnd = $month->addMonth()->utc();
        $mediaIds = MediaOutlet::query()->where('organization_id', $organizationId)->select('id');
        $outlets = MediaOutlet::query()->where('organization_id', $organizationId);
        $channels = MediaChannel::query()->whereIn('media_outlet_id', clone $mediaIds);
        $activeCredentials = MediaApiCredential::query()
            ->whereIn('media_outlet_id', clone $mediaIds)
            ->whereNull('revoked_at')
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $apiQuery = ApiUsageLog::query()
            ->whereIn('media_outlet_id', clone $mediaIds)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);
        $api = [
            'requests' => (clone $apiQuery)->count(),
            'records' => (int) (clone $apiQuery)->sum('records_returned'),
            'errors' => (clone $apiQuery)->where('status_code', '>=', 400)->count(),
            'average_ms' => (int) round((clone $apiQuery)->avg('duration_ms') ?? 0),
        ];

        $requestQuery = NewsRequest::query()
            ->whereHas('destinations.mediaOutlet', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);
        $contentQuery = GeneratedDraft::query()
            ->whereHas('destination.mediaOutlet', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);
        $distributionQuery = ContentDistributionEvent::query()
            ->whereIn('media_outlet_id', clone $mediaIds)
            ->where('occurred_at', '>=', $periodStart)
            ->where('occurred_at', '<', $periodEnd);
        $aiUsageQuery = AiUsage::query()
            ->whereHas('newsRequest.destinations.mediaOutlet', fn (Builder $query) => $query->where('organization_id', $organizationId))
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);

        $taskQuery = AutomationTask::query()->where('organization_id', $organizationId);
        $runQuery = AutomationRun::query()
            ->where('organization_id', $organizationId)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);
        $runStatuses = (clone $runQuery)
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COUNT(CASE WHEN status = 'COMPLETADA' THEN 1 END) as completed")
            ->selectRaw("COUNT(CASE WHEN status = 'SIN_NOVEDADES' THEN 1 END) as no_news")
            ->selectRaw("COUNT(CASE WHEN status IN ('ERROR', 'INVALIDA', 'BLOQUEADA', 'ESTADO_DESCONOCIDO') THEN 1 END) as incidents")
            ->first();

        $ledgerQuery = AutomationLedgerEntry::query()
            ->where('organization_id', $organizationId)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd);
        $ledgerTotals = (clone $ledgerQuery)->toBase()->selectRaw('type, SUM(amount_minor) as total')->groupBy('type')->pluck('total', 'type');
        $budget = $organization->automationBudget;
        $budgetMatchesPeriod = $budget?->period_start?->format('Y-m') === $month->format('Y-m');

        return [
            'organization' => $organization,
            'month' => $month,
            'services' => [
                'media' => ['total' => (clone $outlets)->count(), 'active' => (clone $outlets)->where('status', 'ACTIVO')->count(), 'channels' => (clone $channels)->count()],
                'api' => ['enabled' => $activeCredentials > 0, 'active_credentials' => $activeCredentials],
                'automation' => [
                    'enabled' => (clone $taskQuery)->exists(),
                    'total' => (clone $taskQuery)->count(),
                    'active' => (clone $taskQuery)->where('status', 'ACTIVA')->count(),
                    'paused' => (clone $taskQuery)->where('status', 'PAUSADA')->count(),
                    'next_run_at' => (clone $taskQuery)->where('status', 'ACTIVA')->whereNotNull('next_run_at')->min('next_run_at'),
                ],
                'content' => ['available' => GeneratedDraft::query()->whereHas('destination.mediaOutlet', fn (Builder $query) => $query->where('organization_id', $organizationId))->where('status', 'DISPONIBLE')->count()],
            ],
            'monthly' => [
                'api' => $api,
                'news_requests' => (clone $requestQuery)->count(),
                'contents_generated' => (clone $contentQuery)->count(),
                'contents_available' => (clone $contentQuery)->where('status', 'DISPONIBLE')->count(),
                'distribution_events' => (clone $distributionQuery)->count(),
                'ai_tokens' => (int) (clone $aiUsageQuery)->sum('total_tokens'),
                'automation' => [
                    'runs' => (int) ($runStatuses->total ?? 0),
                    'completed' => (int) ($runStatuses->completed ?? 0),
                    'no_news' => (int) ($runStatuses->no_news ?? 0),
                    'incidents' => (int) ($runStatuses->incidents ?? 0),
                ],
                'commercial' => [
                    'currency' => $budget?->currency ?? 'ARS',
                    'reserved_minor' => (int) ($ledgerTotals['RESERVA'] ?? 0),
                    'settled_minor' => (int) ($ledgerTotals['LIQUIDACION'] ?? 0),
                    'released_minor' => (int) ($ledgerTotals['LIBERACION'] ?? 0),
                    'monthly_limit_minor' => $budgetMatchesPeriod ? (int) $budget->monthly_limit_minor : null,
                    'available_minor' => $budgetMatchesPeriod ? max(0, (int) $budget->monthly_limit_minor - (int) $budget->reserved_minor - (int) $budget->settled_minor) : null,
                ],
            ],
            'recentRuns' => (clone $runQuery)->with(['task:id,name', 'result:id,automation_run_id,title,status'])->latest('created_at')->latest('id')->limit(8)->get(),
            'recentMovements' => (clone $ledgerQuery)->with(['run:id,automation_task_id,public_id,status,created_at', 'run.task:id,name'])->latest('created_at')->latest('id')->limit(10)->get(),
        ];
    }
}
