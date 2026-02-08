<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyReadyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $partyId,
        public int $userId,
        public bool $isReady,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('party.'.$this->partyId);
    }

    public function broadcastAs(): string
    {
        return 'party.ready.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'partyId' => $this->partyId,
            'userId' => $this->userId,
            'isReady' => $this->isReady,
        ];
    }
}
