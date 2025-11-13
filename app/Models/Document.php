<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'document_type',
        'description',
        'purpose',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'file_data',
        'qr_path',
        'department_id',
        'receiving_department_id',
        'target_owner_id',
        'owner_id',
        'current_handler_id',
        'current_owner_id',
        'current_status',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function receivingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'receiving_department_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function currentHandler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_handler_id');
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    public function targetOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_owner_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Sanitize UTF-8 string to prevent encoding errors
     */
    private function sanitizeUtf8($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        
        // Remove control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        
        // Check and fix UTF-8 encoding
        if (!mb_check_encoding($value, 'UTF-8')) {
            // Try to convert from various encodings
            $detected = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $detected);
            } else {
                // If detection fails, use mb_scrub to remove invalid bytes
                $value = mb_scrub($value, 'UTF-8');
            }
        }
        
        return $value;
    }

    /**
     * Get the document's title with UTF-8 sanitization
     */
    public function getTitleAttribute($value)
    {
        $rawValue = $this->attributes['title'] ?? $value ?? null;
        return $this->sanitizeUtf8($rawValue);
    }

    /**
     * Get the document's description with UTF-8 sanitization
     */
    public function getDescriptionAttribute($value)
    {
        $rawValue = $this->attributes['description'] ?? $value ?? null;
        return $this->sanitizeUtf8($rawValue);
    }
}


