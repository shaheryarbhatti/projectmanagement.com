<?php

namespace App\Http\Controllers;

use App\Models\WorkbookUpload;
use Illuminate\View\View;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function index(): View
    {
        return view('history.index', [
            'uploads' => WorkbookUpload::query()->with('user')->latest('processed_at')->latest('id')->paginate(12),
        ]);
    }

    public function download(WorkbookUpload $upload): StreamedResponse
    {
        return Storage::disk(config('excel_insights.upload_disk'))->download(
            $upload->stored_path,
            $upload->original_name
        );
    }
}
