<?php

namespace App\Http\Controllers;

use App\Events\FriendRequestCreated;
use App\Models\Friendship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FriendshipController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'recipient_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $recipientUserId = (int) $data['recipient_user_id'];
        if ((int) $user->id === $recipientUserId) {
            return response()->json([
                'message' => 'Du kannst dir selbst keine Freundschaftsanfrage senden.',
            ], 422);
        }

        $existing = Friendship::query()
            ->where(function ($query) use ($user, $recipientUserId) {
                $query
                    ->where('requester_id', $user->id)
                    ->where('recipient_id', $recipientUserId)
                    ->orWhere(function ($nested) use ($user, $recipientUserId) {
                        $nested
                            ->where('requester_id', $recipientUserId)
                            ->where('recipient_id', $user->id);
                    });
            })
            ->whereIn('status', [Friendship::STATUS_PENDING, Friendship::STATUS_ACCEPTED])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Zwischen euch gibt es bereits eine offene oder angenommene Anfrage.',
            ], 422);
        }

        $friendship = Friendship::query()->create([
            'requester_id' => $user->id,
            'recipient_id' => $recipientUserId,
            'status' => Friendship::STATUS_PENDING,
        ]);

        try {
            broadcast(new FriendRequestCreated(
                recipientUserId: $recipientUserId,
                friendshipId: (int) $friendship->id,
                requester: [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ));
        } catch (\Throwable) {
            // Keep friend request endpoint functional even without realtime.
        }

        return response()->json([
            'status' => 'sent',
            'friendshipId' => (int) $friendship->id,
            'recipientUserId' => $recipientUserId,
        ]);
    }

    public function accept(Request $request, Friendship $friendship): JsonResponse
    {
        $user = $request->user();
        abort_unless((int) $friendship->recipient_id === (int) $user->id, 403);

        if ($friendship->status !== Friendship::STATUS_PENDING) {
            return response()->json(['message' => 'Diese Anfrage ist nicht mehr offen.'], 422);
        }

        $friendship->forceFill([
            'status' => Friendship::STATUS_ACCEPTED,
            'responded_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'accepted',
            'friendshipId' => (int) $friendship->id,
            'friend' => [
                'id' => (int) $friendship->requester_id,
                'name' => $friendship->requester?->name,
                'email' => $friendship->requester?->email,
            ],
        ]);
    }

    public function reject(Request $request, Friendship $friendship): JsonResponse
    {
        $user = $request->user();
        abort_unless((int) $friendship->recipient_id === (int) $user->id, 403);

        if ($friendship->status !== Friendship::STATUS_PENDING) {
            return response()->json(['message' => 'Diese Anfrage ist nicht mehr offen.'], 422);
        }

        $friendship->forceFill([
            'status' => Friendship::STATUS_REJECTED,
            'responded_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'rejected',
            'friendshipId' => (int) $friendship->id,
        ]);
    }

    public function destroy(Request $request, Friendship $friendship): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            (int) $friendship->requester_id === (int) $user->id
            || (int) $friendship->recipient_id === (int) $user->id,
            403
        );

        $friendshipId = (int) $friendship->id;
        $friendId = (int) (
            (int) $friendship->requester_id === (int) $user->id
                ? $friendship->recipient_id
                : $friendship->requester_id
        );

        $friendship->delete();

        return response()->json([
            'status' => 'removed',
            'friendshipId' => $friendshipId,
            'friendId' => $friendId,
        ]);
    }
}
