<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([]);
        }

        $notifications = $user->unreadNotifications()->take(10)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'data' => $notification->data,
                'created_at' => $notification->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
