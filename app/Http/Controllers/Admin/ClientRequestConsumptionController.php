<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequestConsumptionRequest;
use App\Services\ClientRequestConsumptionService;
use Illuminate\View\View;

class ClientRequestConsumptionController extends Controller
{
    public function index(ClientRequestConsumptionRequest $request, ClientRequestConsumptionService $service): View
    {
        $report = $service->report($request->user(), $request->selectedMonth(), $request->validated());

        return view('admin.client-consumption.index', compact('report'));
    }

    public function show(ClientRequestConsumptionRequest $request, string $referenceCode, ClientRequestConsumptionService $service): View
    {
        $newsRequest = $service->findForUser($request->user(), $referenceCode);

        return view('admin.client-consumption.show', compact('newsRequest'));
    }
}
