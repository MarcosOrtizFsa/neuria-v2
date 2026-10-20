<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDraft;
use App\Models\MediaOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', GeneratedDraft::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:GENERANDO,DISPONIBLE,ERROR,SUSPENDIDO,RETIRADO'], 'media_outlet_id' => ['nullable', 'integer'], 'sort' => ['nullable', 'in:newest,oldest,title']]);
        $query = GeneratedDraft::query()->with(['destination.mediaOutlet:id,name,organization_id', 'destination.mediaChannel:id,name,type', 'destination.newsRequest:id,title']);

        if ($request->user()->role?->code === 'CLIENTE') {
            $query->whereHas('destination.mediaOutlet', fn ($media) => $media->where('organization_id', $request->user()->organization_id));
        }

        $contents = $query
            ->when($filters['q'] ?? null, fn ($builder, $search) => $builder->where(fn ($nested) => $nested->where('title', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['media_outlet_id'] ?? null, fn ($builder, $mediaId) => $builder->whereHas('destination', fn ($destination) => $destination->where('media_outlet_id', $mediaId)))
            ->when(($filters['sort'] ?? 'newest') === 'oldest', fn ($builder) => $builder->orderBy('generated_at')->orderBy('id'))
            ->when(($filters['sort'] ?? 'newest') === 'title', fn ($builder) => $builder->orderBy('title')->orderBy('id'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn ($builder) => $builder->orderByDesc('generated_at')->orderByDesc('id'))
            ->paginate(15)->withQueryString();

        $media = MediaOutlet::query()
            ->when($request->user()->role?->code === 'CLIENTE', fn ($builder) => $builder->where('organization_id', $request->user()->organization_id))
            ->whereHas('channels')->orderBy('name')->get(['id', 'name']);

        return view('admin.contents.index', compact('contents', 'media', 'filters'));
    }

    public function show(Request $request, GeneratedDraft $content): View
    {
        $content->load(['destination.mediaOutlet.organization', 'destination.mediaChannel', 'destination.newsRequest', 'research.sources', 'research.newsRequest', 'editorialProfileVersion', 'statusHistory.user']);
        if ($request->user()->role?->code === 'CLIENTE' && $request->user()->organization_id !== $content->destination->mediaOutlet->organization_id) {
            abort(404);
        }
        Gate::authorize('view', $content);

        return view('admin.contents.show', compact('content'));
    }
}
