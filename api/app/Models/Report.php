<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'format', 'status', 'filters', 'file_path', 'failure_reason', 'expires_at', 'completed_at'])]
#[Hidden(['user_id', 'file_path', 'failure_reason'])]
class Report extends Model
{
    use HasFactory;

    protected $appends = ['failure_message'];

    protected function casts(): array
    {
        return [
            'filters'      => 'array',
            'expires_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFailureMessageAttribute(): ?string
    {
        if ($this->status !== 'failed') {
            return null;
        }

        return __('messages.report_generation_failed');
    }
}
