<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public static function log(?int $documentId, ?int $userId, string $actionType, string $description): void
    {
        AuditLog::create([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'description' => $description,
        ]);
    }
}


