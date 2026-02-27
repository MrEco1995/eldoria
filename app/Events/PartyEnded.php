<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyEnded implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $partyId,
        public ?string $reason = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('party.'.$this->partyId);
    }

    public function broadcastAs(): string
    {
        return 'party.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'partyId' => $this->partyId,
            'reason' => $this->reason,
        ];
    }
}
