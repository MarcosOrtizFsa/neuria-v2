<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsRequestRequest;
use App\Models\MediaChannel;
use App\Models\NewsRequest;
use App\Services\AuditService;
use App\Services\NewsWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NewsRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', NewsRequest::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:40']]);
        $requests = NewsRequest::query()
            ->with('requester:id,name,last_name')
            ->withCount(['destinations', 'drafts'])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.news-requests.index', compact('requests', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', NewsRequest::class);
        $channels = MediaChannel::query()
            ->with(['mediaOutlet.organization', 'mediaOutlet.editorialProfile'])
            ->where('status', 'ACTIVO')
            ->whereHas('mediaOutlet', fn ($query) => $query->where('status', 'ACTIVO')->whereHas('editorialProfile'))
            ->orderBy('media_outlet_id')->orderBy('name')->get();

        return view('admin.news-requests.create', compact('channels'));
    }

    public function store(StoreNewsRequestRequest $request, NewsWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $newsRequest = $workflow->createAndDispatch($request->validated(), $request->user());
        $audit->record($request, 'CREAR_SOLICITUD', 'AGENCIA_NOTICIAS', $newsRequest, ['destinations' => count($request->validated('channel_ids'))]);

        return redirect()->route('admin.agencia.show', $newsRequest)->with('success', 'Solicitud creada. La investigación fue enviada a la cola.');
    }

    public function show(NewsRequest $newsRequest): View
    {
        Gate::authorize('view', $newsRequest);
        $newsRequest->load(['requester:id,name,last_name', 'destinations.mediaOutlet:id,name,organization_id', 'destinations.mediaChannel:id,name,type', 'destinations.draft', 'researches.sources', 'jobRuns', 'usages', 'statusHistory.user:id,name,last_name']);

        return view('admin.news-requests.show', compact('newsRequest'));
    }

    public function retry(Request $request, NewsRequest $newsRequest, NewsWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        Gate::authorize('retry', $newsRequest);
        $workflow->retry($newsRequest, $request->user());
        $audit->record($request, 'REINTENTAR', 'AGENCIA_NOTICIAS', $newsRequest);

        return back()->with('success', 'El reintento fue enviado a la cola.');
    }
}
