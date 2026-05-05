<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'upload_batch_id',
        'question',
        'answer',
        'provider',
        'source',
        'context_payload',
    ];

    protected $casts = [
        'context_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(WorkbookUpload::class, 'upload_batch_id');
    }
}
