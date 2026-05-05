<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExcelInsightUploadRequest;
use App\Jobs\ProcessWorkbookUpload;
use App\Models\WorkbookUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function index(): View
    {
        return view('upload.index', [
            'latestUpload' => WorkbookUpload::query()->latest('processed_at')->latest('id')->first(),
            'allowedSheets' => config('excel_insights.allowed_sheets'),
        ]);
    }

    public function store(StoreExcelInsightUploadRequest $request): RedirectResponse
    {
        $files = $request->file('files');
        $tabs = array_values(array_unique($request->validated('tabs')));
        $successCount = 0;
        $failCount = 0;

        foreach ($files as $index => $file) {
            $isFirst = ($index === 0);
            $storedPath = $file->store(
                config('excel_insights.upload_directory'),
                config('excel_insights.upload_disk')
            );

            $upload = WorkbookUpload::query()->create([
                'user_id' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => basename($storedPath),
                'stored_path' => $storedPath,
                'selected_tabs' => $tabs,
                'status' => 'pending',
            ]);

            try {
                ProcessWorkbookUpload::dispatchSync($upload->id, $isFirst);
                $upload->refresh();
                if ($upload->status === 'completed') {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\Exception $e) {
                $upload->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
                $failCount++;
            }
        }

        if ($failCount > 0 && $successCount === 0) {
            return back()->withInput()->with('error', "All {$failCount} workbooks failed to process.");
        }

        $message = "Successfully processed {$successCount} workbooks.";
        if ($failCount > 0) $message .= " ({$failCount} failed)";

        return redirect()->route('dashboard.index')->with('success', $message);
    }
}
