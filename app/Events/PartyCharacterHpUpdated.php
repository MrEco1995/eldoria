<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyCharacterHpUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $partyId,
        public int $partyCharacterId,
        public array $hp,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('party.'.$this->partyId);
    }

    public function broadcastAs(): string
    {
        return 'party.character-hp.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'partyId' => $this->partyId,
            'partyCharacterId' => $this->partyCharacterId,
            'hp' => $this->hp,
        ];
    }
}
