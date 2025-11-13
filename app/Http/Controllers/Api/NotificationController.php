<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->with('document')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'document_id' => $notification->document_id,
                    'document_title' => $notification->document->title ?? null,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'unread_count' => Notification::where('user_id', $user->id)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Mark a notification as read
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => [
                'id' => $notification->id,
                'is_read' => $notification->is_read,
            ],
        ]);
    }
}
