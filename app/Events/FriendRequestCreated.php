<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $recipientUserId,
        public int $friendshipId,
        public array $requester,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->recipientUserId);
    }

    public function broadcastAs(): string
    {
        return 'friend.request.created';
    }

    public function broadcastWith(): array
    {
        return [
            'friendshipId' => $this->friendshipId,
            'requester' => $this->requester,
        ];
    }
}
