<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealtimeBadgeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $targetUserId;
    public string $badgeType;
    public int $count;

    /**
     * @param int $targetUserId
     * @param string $badgeType 'unread_messages', 'pending_tickets', 'needed_letters'
     * @param int $count
     */
    public function __construct(int $targetUserId, string $badgeType, int $count)
    {
        $this->targetUserId = $targetUserId;
        $this->badgeType = $badgeType;
        $this->count = $count;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->targetUserId),
            new PrivateChannel('user-channel.' . $this->targetUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'badge-updated';
    }
}
