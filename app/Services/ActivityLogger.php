<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public static function log(?int $userId, string $actionType, string $description, ?Request $request = null): void
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'description' => $description,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ]);
    }

    public static function logLogin(int $userId, string $userEmail, ?Request $request = null): void
    {
        self::log(
            $userId,
            'LOGIN',
            "User {$userEmail} logged in successfully",
            $request
        );
    }

    public static function logLogout(int $userId, string $userEmail, ?Request $request = null): void
    {
        self::log(
            $userId,
            'LOGOUT',
            "User {$userEmail} logged out",
            $request
        );
    }

    public static function logProfileUpdate(int $userId, string $userEmail, array $changes = [], ?Request $request = null): void
    {
        $changesText = !empty($changes) ? ' (' . implode(', ', $changes) . ')' : '';
        self::log(
            $userId,
            'PROFILE_UPDATE',
            "User {$userEmail} updated profile{$changesText}",
            $request
        );
    }

    public static function logPasswordChange(int $userId, string $userEmail, ?Request $request = null): void
    {
        self::log(
            $userId,
            'PASSWORD_CHANGE',
            "User {$userEmail} changed password",
            $request
        );
    }
}

