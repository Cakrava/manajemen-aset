<?php

namespace App\Events;

use App\Models\Letters;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LetterStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $clientId;
    public int $letterId;
    public string $status;
    public string $letterNumber;

    public function __construct(Letters $letter)
    {
        $this->clientId = (int) $letter->client_id;
        $this->letterId = (int) $letter->id;
        $this->status = (string) $letter->status;
        $this->letterNumber = (string) $letter->letter_number;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->clientId),
            new PrivateChannel('user-channel.' . $this->clientId),
            new PrivateChannel('admin-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'letter-updated';
    }
}
