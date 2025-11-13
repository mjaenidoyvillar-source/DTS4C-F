<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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
     * Get the department's name with UTF-8 sanitization
     */
    public function getNameAttribute($value)
    {
        $rawValue = $this->attributes['name'] ?? $value ?? null;
        return $this->sanitizeUtf8($rawValue);
    }
}


