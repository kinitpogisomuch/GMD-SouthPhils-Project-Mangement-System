<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $recipientType,
        public int $recipientId,
        public array $payload,
    ) {
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("inbox.{$this->recipientType}.{$this->recipientId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
