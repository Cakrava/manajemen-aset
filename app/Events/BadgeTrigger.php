<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeTrigger implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $sender_id;
    public string $type;
    public string $sender_name;
    public string $message_preview;

    public function __construct(int $sender_id, string $type = 'message', string $sender_name = '', string $message_preview = '')
    {
        $this->sender_id      = $sender_id;
        $this->type           = $type;
        $this->sender_name    = $sender_name;
        $this->message_preview = $message_preview;
    }

    public function broadcastOn(): array
    {
        return [new Channel('badgeticket')];
    }

    public function broadcastAs(): string
    {
        return 'triger';
    }
}
