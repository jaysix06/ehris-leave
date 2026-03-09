<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * List conversations for the authenticated user with the latest message and unread count.
     */
    public function conversations(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $perPage = max(5, min((int) $request->query('per_page', 25), 100));

        $summaryQuery = Message::query()
            ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as contact_id', [$userId])
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('MAX(id) as last_message_id')
            ->selectRaw(
                'SUM(CASE WHEN receiver_id = ? AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count',
                [$userId],
            )
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('contact_id');

        $conversationPage = DB::query()
            ->fromSub($summaryQuery, 'conversation_summaries')
            ->join('messages as last_message', 'last_message.id', '=', 'conversation_summaries.last_message_id')
            ->select([
                'conversation_summaries.contact_id',
                'conversation_summaries.unread_count',
                'conversation_summaries.last_message_at',
                'last_message.body as last_message_body',
                'last_message.sender_id as last_message_sender_id',
                'last_message.created_at as last_message_created_at',
            ])
            ->orderByRaw('CASE WHEN conversation_summaries.unread_count > 0 THEN 1 ELSE 0 END DESC')
            ->orderByDesc('conversation_summaries.last_message_at')
            ->orderByDesc('conversation_summaries.last_message_id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $request->query('cursor'));

        $contactIds = collect($conversationPage->items())
            ->pluck('contact_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $contacts = User::query()
            ->whereIn('userId', $contactIds)
            ->get([
                'userId',
                'fullname',
                'firstname',
                'middlename',
                'lastname',
                'extname',
                'role',
                'avatar',
                'active',
            ])
            ->keyBy('userId');

        $result = collect($conversationPage->items())->map(function ($row) use ($contacts, $userId) {
            $contact = $contacts->get((int) $row->contact_id);

            $lastMessageCreatedAt = null;
            if ($row->last_message_created_at) {
                $lastMessageCreatedAt = Carbon::parse($row->last_message_created_at)->toISOString();
            }

            return [
                'contact_id' => (int) $row->contact_id,
                'contact' => $contact ? [
                    'id' => (int) $contact->userId,
                    'name' => $contact->name,
                    'firstname' => $contact->firstname,
                    'middlename' => $contact->middlename,
                    'lastname' => $contact->lastname,
                    'extname' => $contact->extname,
                    'role' => $contact->role,
                    'avatar' => $contact->avatar,
                    'active' => (bool) $contact->active,
                ] : null,
                'last_message' => [
                    'body' => (string) $row->last_message_body,
                    'mine' => (int) $row->last_message_sender_id === $userId,
                    'created_at' => $lastMessageCreatedAt,
                ],
                'unread_count' => (int) $row->unread_count,
            ];
        })->values();

        return response()->json([
            'data' => $result,
            'next_cursor' => $conversationPage->nextCursor()?->encode(),
            'has_more' => $conversationPage->hasMorePages(),
        ]);
    }

    /**
     * Load messages between the authenticated user and the given contact.
     */
    public function show(Request $request, int $contactId): JsonResponse
    {
        $userId = Auth::id();

        $messages = Message::query()
            ->where(function ($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })
            ->orWhere(function ($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (Message $msg) => [
                'id' => $msg->id,
                'body' => $msg->body,
                'mine' => $msg->sender_id === $userId,
                'created_at' => $msg->created_at->toISOString(),
            ]);

        Message::query()
            ->where('sender_id', $contactId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a message to another user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:tbl_user,userId'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $userId = Auth::id();

        if ((int) $data['receiver_id'] === $userId) {
            return response()->json(['message' => 'Cannot send messages to yourself.'], 422);
        }

        $message = Message::create([
            'sender_id' => $userId,
            'receiver_id' => $data['receiver_id'],
            'body' => $data['body'],
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'mine' => true,
            'created_at' => $message->created_at->toISOString(),
        ], 201);
    }

    /**
     * Mark all messages from a contact as read.
     */
    public function markRead(Request $request, int $contactId): JsonResponse
    {
        $userId = Auth::id();

        $updated = Message::query()
            ->where('sender_id', $contactId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['marked' => $updated]);
    }
}
