<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\ApiUsageLog;
use App\Models\AutomationLedgerEntry;
use App\Models\GeneratedDraft;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ClientRequestConsumptionService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(User $user, CarbonImmutable $month, array $filters): array
    {
        $periodStart = $month->utc();
        $periodEnd = $month->addMonth()->utc();
        $query = $this->visibleRequests($user)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd)
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(fn (Builder $nested) => $nested
                    ->where('reference_code', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%'));
            });
        $requestIds = (clone $query)->select('news_requests.id');

        $stats = [
            'requests' => (clone $query)->count(),
            'tokens' => (int) AiUsage::query()->whereIn('news_request_id', clone $requestIds)->sum('total_tokens'),
            'api_calls' => ApiUsageLog::query()->whereIn('news_request_id', clone $requestIds)->count(),
            'contents' => GeneratedDraft::query()->whereHas('destination', fn (Builder $builder) => $builder->whereIn('news_request_id', clone $requestIds))->count(),
        ];

        $organization = $user->role?->code === 'CLIENTE' ? $user->organization : null;
        $commercial = ['currency' => 'ARS', 'monthly_limit_minor' => null, 'settled_minor' => 0, 'reserved_minor' => 0, 'released_minor' => 0, 'available_minor' => null];
        if ($organization !== null) {
            $budget = $organization->automationBudget;
            $ledger = AutomationLedgerEntry::query()
                ->where('organization_id', $organization->id)
                ->where('created_at', '>=', $periodStart)
                ->where('created_at', '<', $periodEnd)
                ->toBase()
                ->selectRaw('type, SUM(amount_minor) as total')
                ->groupBy('type')
                ->pluck('total', 'type');
            $budgetMatchesPeriod = $budget?->period_start?->format('Y-m') === $month->format('Y-m');
            $commercial = [
                'currency' => $budget?->currency ?? 'ARS',
                'monthly_limit_minor' => $budgetMatchesPeriod ? (int) $budget->monthly_limit_minor : null,
                'settled_minor' => (int) ($ledger['LIQUIDACION'] ?? 0),
                'reserved_minor' => (int) ($ledger['RESERVA'] ?? 0),
                'released_minor' => (int) ($ledger['LIBERACION'] ?? 0),
                'available_minor' => $budgetMatchesPeriod ? max(0, (int) $budget->monthly_limit_minor - (int) $budget->reserved_minor - (int) $budget->settled_minor) : null,
            ];
        }

        return [
            'month' => $month,
            'filters' => $filters,
            'stats' => $stats,
            'commercial' => $commercial,
            'requests' => $this->paginate($query),
        ];
    }

    public function findForUser(User $user, string $referenceCode): NewsRequest
    {
        $newsRequest = $this->visibleRequests($user)
            ->where('reference_code', $referenceCode)
            ->with([
                'requester:id,name,last_name',
                'usages' => fn ($query) => $query->latest('created_at'),
                'apiUsageLogs' => fn ($query) => $query->with(['mediaOutlet:id,name', 'credential:id,name,token_prefix'])->latest('created_at'),
                'jobRuns' => fn ($query) => $query->latest('created_at'),
                'researches.sources',
                'destinations' => function ($query) use ($user): void {
                    $query->when($user->role?->code === 'CLIENTE', fn ($builder) => $builder->whereHas('mediaOutlet', fn ($media) => $media->where('organization_id', $user->organization_id)))
                        ->with(['mediaOutlet:id,name', 'mediaChannel:id,name,type', 'draft:id,news_request_destination_id,public_id,title,status']);
                },
            ])
            ->firstOrFail();

        return $newsRequest;
    }

    private function visibleRequests(User $user): Builder
    {
        $query = NewsRequest::query()->whereNotNull('api_media_outlet_id');

        if ($user->role?->code === 'CLIENTE') {
            $mediaIds = MediaOutlet::query()->where('organization_id', $user->organization_id)->select('id');
            $query->whereIn('api_media_outlet_id', $mediaIds);
        }

        return $query;
    }

    private function paginate(Builder $query): LengthAwarePaginator
    {
        return $query
            ->withSum('usages as total_tokens', 'total_tokens')
            ->withSum('usages as input_tokens', 'input_tokens')
            ->withSum('usages as output_tokens', 'output_tokens')
            ->withCount(['apiUsageLogs as api_calls_count', 'drafts as contents_count'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }
}
