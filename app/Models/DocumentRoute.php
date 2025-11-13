<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRoute extends Model
{
    use HasFactory;

    protected $table = 'routes';

    protected $fillable = [
        'document_id',
        'from_department_id',
        'to_department_id',
        'from_user_id',
        'to_user_id',
        'target_owner_id',
        'new_status',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function targetOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_owner_id');
    }
}


