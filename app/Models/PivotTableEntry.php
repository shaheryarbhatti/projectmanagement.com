<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PivotTableEntry extends Model
{
    protected $fillable = [
        'upload_batch_id',
        'source_row',
        'source_column',
        'cell_reference',
        'section_title',
        'metric_title',
        'row_label',
        'column_label',
        'value_numeric',
        'value_text',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:2',
        ];
    }

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(WorkbookUpload::class, 'upload_batch_id');
    }
}
