<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaOutlet;
use App\Services\EditorialProfileWordReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EditorialProfileReportController extends Controller
{
    public function __invoke(Request $request, MediaOutlet $mediaOutlet, EditorialProfileWordReport $report): BinaryFileResponse
    {
        Gate::authorize('editEditorial', $mediaOutlet);
        $profile = $mediaOutlet->editorialProfile()->with('sources')->firstOrFail();
        $path = $report->create($mediaOutlet, $profile);
        $filename = 'linea-editorial-'.Str::slug($mediaOutlet->name).'-v'.$profile->current_version.'.docx';

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }
}
