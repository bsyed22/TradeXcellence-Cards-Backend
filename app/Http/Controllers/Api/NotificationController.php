<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = DB::table('notifications')
//            ->where('notifiable_type', \App\Models\User::class)
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = DB::table('notifications')
//            ->where('notifiable_type', \App\Models\User::class)
            ->whereNull('read_at')
            ->count();


        return response()->success(compact('notifications', 'unreadCount'),"List of notifications",200);
    }

    public function getNotificationsByUserId(int $id)
    {
        $notifications = DB::table('notifications')
//            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $id)
            ->orderBy('created_at', 'desc')->
            get();

        return response()->success($notifications,"List of notifications",200);

    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'string|exists:notifications,id',
        ]);

        DB::table('notifications')
            ->whereIn('id', $request->notification_ids)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        DB::table('notifications')
//            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }
}
