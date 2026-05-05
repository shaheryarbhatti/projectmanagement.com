<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspensionResumptionRecord extends Model
{
    protected $fillable = [
        'upload_batch_id',
        'source_row',
        'project_name',
        'contractor_designer_name',
        'actual_pct',
        'type_of_suspension',
        'po',
        'project_start_date',
        'suspension_date',
        'suspension_reason',
        'resumption_date',
        'revised_finish_date',
        'suspension_duration_days',
        'status_of_resumption',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'actual_pct' => 'decimal:6',
            'project_start_date' => 'date',
            'suspension_date' => 'date',
            'resumption_date' => 'date',
            'revised_finish_date' => 'date',
        ];
    }

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(WorkbookUpload::class, 'upload_batch_id');
    }
}
