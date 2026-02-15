<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyRollCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $partyId,
        public int $userId,
        public string $userName,
        public string $die,
        public int $result,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('party.'.$this->partyId);
    }

    public function broadcastAs(): string
    {
        return 'party.roll.created';
    }

    public function broadcastWith(): array
    {
        return [
            'partyId' => $this->partyId,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'die' => $this->die,
            'result' => $this->result,
            'rolledAt' => now()->toIso8601String(),
        ];
    }
}

