<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkbookUpload extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'stored_name',
        'stored_path',
        'selected_tabs',
        'status',
        'stats',
        'failure_message',
        'processed_at',
    ];

    protected $casts = [
        'selected_tabs' => 'array',
        'stats' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'upload_batch_id');
    }
}
