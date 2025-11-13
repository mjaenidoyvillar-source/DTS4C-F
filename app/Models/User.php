<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'is_active',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function ownedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    public function handlingDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'current_handler_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function documentLogs(): HasMany
    {
        return $this->hasMany(DocumentLog::class);
    }

    /**
     * Get the profile picture URL
     */
    public function getProfilePictureUrlAttribute()
    {
        if (!$this->profile_picture) {
            return null;
        }

        try {
            $path = $this->profile_picture;
            
            // The path from storage is already 'profile-pictures/filename.ext'
            // So we can use it directly
            
            // Check if file exists
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                // Always use the route method for API consistency
                // This works regardless of symlink status
                $filename = basename($path);
                // Use url() helper instead of route() to avoid route resolution issues
                return url('/profile-picture/' . urlencode($filename));
            }
        } catch (\Exception $e) {
            // Log error but don't throw - just return null
            \Log::warning('Error generating profile picture URL: ' . $e->getMessage());
        }

        return null;
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
     * Get the user's name with UTF-8 sanitization
     */
    public function getNameAttribute($value)
    {
        $rawValue = $this->attributes['name'] ?? $value ?? null;
        return $this->sanitizeUtf8($rawValue);
    }

    /**
     * Get the user's email with UTF-8 sanitization
     */
    public function getEmailAttribute($value)
    {
        $rawValue = $this->attributes['email'] ?? $value ?? null;
        return $this->sanitizeUtf8($rawValue);
    }
}
