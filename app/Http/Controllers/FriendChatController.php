<?php

namespace App\Http\Controllers;

use App\Events\FriendChatMessageCreated;
use App\Models\ChatMessage;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $friends = $this->resolveFriends($user->id);

        $payload = $friends->map(function (User $friend) use ($user) {
            $latestMessage = ChatMessage::query()
                ->where(function ($query) use ($user, $friend) {
                    $query
                        ->where('sender_id', $user->id)
                        ->where('recipient_id', $friend->id)
                        ->orWhere(function ($nested) use ($user, $friend) {
                            $nested
                                ->where('sender_id', $friend->id)
                                ->where('recipient_id', $user->id);
                        });
                })
                ->orderByDesc('id')
                ->first();

            $unreadCount = ChatMessage::query()
                ->where('sender_id', $friend->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return [
                'id' => (int) $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
                'latestMessage' => $latestMessage ? [
                    'id' => (int) $latestMessage->id,
                    'body' => (string) $latestMessage->body,
                    'senderId' => (int) $latestMessage->sender_id,
                    'recipientId' => (int) $latestMessage->recipient_id,
                    'createdAt' => optional($latestMessage->created_at)?->toISOString(),
                ] : null,
                'unreadCount' => $unreadCount,
            ];
        })->values();

        return response()->json(['friends' => $payload]);
    }

    public function messages(Request $request, User $friend): JsonResponse
    {
        $user = $request->user();
        $this->assertFriends($user->id, (int) $friend->id);

        $messages = ChatMessage::query()
            ->where(function ($query) use ($user, $friend) {
                $query
                    ->where('sender_id', $user->id)
                    ->where('recipient_id', $friend->id)
                    ->orWhere(function ($nested) use ($user, $friend) {
                        $nested
                            ->where('sender_id', $friend->id)
                            ->where('recipient_id', $user->id);
                    });
            })
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message) => [
                'id' => (int) $message->id,
                'senderId' => (int) $message->sender_id,
                'recipientId' => (int) $message->recipient_id,
                'body' => (string) $message->body,
                'createdAt' => optional($message->created_at)?->toISOString(),
                'readAt' => optional($message->read_at)?->toISOString(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request, User $friend): JsonResponse
    {
        $user = $request->user();
        $this->assertFriends($user->id, (int) $friend->id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ChatMessage::query()->create([
            'sender_id' => $user->id,
            'recipient_id' => $friend->id,
            'body' => trim((string) $data['body']),
        ]);

        $messagePayload = [
            'id' => (int) $message->id,
            'senderId' => (int) $message->sender_id,
            'recipientId' => (int) $message->recipient_id,
            'body' => (string) $message->body,
            'createdAt' => optional($message->created_at)?->toISOString(),
            'readAt' => optional($message->read_at)?->toISOString(),
        ];

        try {
            broadcast(new FriendChatMessageCreated(
                recipientUserId: (int) $friend->id,
                message: $messagePayload,
                sender: [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ));
        } catch (\Throwable) {
            // Keep chat functional without realtime.
        }

        return response()->json([
            'message' => $messagePayload,
        ]);
    }

    public function markRead(Request $request, User $friend): JsonResponse
    {
        $user = $request->user();
        $this->assertFriends($user->id, (int) $friend->id);

        ChatMessage::query()
            ->where('sender_id', $friend->id)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    private function resolveFriends(int $userId)
    {
        $friendships = Friendship::query()
            ->with(['requester:id,name,email', 'recipient:id,name,email'])
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                    ->orWhere('recipient_id', $userId);
            })
            ->get();

        return $friendships->map(function (Friendship $entry) use ($userId) {
            return (int) $entry->requester_id === $userId ? $entry->recipient : $entry->requester;
        })->filter();
    }

    private function assertFriends(int $userId, int $friendId): void
    {
        abort_if($userId === $friendId, 422);

        $isFriend = Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($query) use ($userId, $friendId) {
                $query
                    ->where('requester_id', $userId)
                    ->where('recipient_id', $friendId)
                    ->orWhere(function ($nested) use ($userId, $friendId) {
                        $nested
                            ->where('requester_id', $friendId)
                            ->where('recipient_id', $userId);
                    });
            })
            ->exists();

        abort_unless($isFriend, 403);
    }
}
