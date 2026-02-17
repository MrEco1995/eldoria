<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyStarted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $partyId,
        public string $startedAt,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('party.'.$this->partyId);
    }

    public function broadcastAs(): string
    {
        return 'party.started';
    }

    public function broadcastWith(): array
    {
        return [
            'partyId' => $this->partyId,
            'startedAt' => $this->startedAt,
        ];
    }
}

