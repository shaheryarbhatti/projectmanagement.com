<?php

namespace App\Jobs;

use App\Models\WorkbookUpload;
use App\Services\ExcelInsight\WorkbookImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessWorkbookUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $uploadId, public bool $shouldTruncate = true)
    {
    }

    public function handle(WorkbookImportService $workbookImportService): void
    {
        $upload = WorkbookUpload::query()->findOrFail($this->uploadId);

        try {
            $upload->update([
                'status' => 'processing',
                'failure_message' => null,
            ]);

            $summary = $workbookImportService->process($upload, $this->shouldTruncate);

            $upload->update([
                'status' => 'completed',
                'processed_at' => now(),
                'stats' => $summary,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Workbook processing failed.', [
                'upload_id' => $upload->id,
                'message' => $exception->getMessage(),
            ]);

            $upload->update([
                'status' => 'failed',
                'failure_message' => Str::limit($exception->getMessage(), 60000, '...'),
            ]);
        }
    }
}
