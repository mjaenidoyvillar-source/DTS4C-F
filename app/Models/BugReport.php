<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_type',
        'severity',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'user_id',
        'ip_address',
        'user_agent',
        'request_data',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'occurrence_count',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'request_data' => 'array',
        'occurrence_count' => 'integer',
        'line' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by')->withTrashed();
    }

    /**
     * Increment occurrence count for duplicate errors
     */
    public function incrementOccurrence(): void
    {
        $this->increment('occurrence_count');
        $this->touch(); // Update updated_at
    }
}
