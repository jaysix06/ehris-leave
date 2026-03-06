<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notification = $user->notifications()->whereKey($id)->first();
        if ($notification === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'ok' => true,
            'id' => $notification->id,
            'read_at' => $notification->read_at,
        ]);
    }
}

