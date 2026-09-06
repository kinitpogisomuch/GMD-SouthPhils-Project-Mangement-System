<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $client,
    ) {
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        // Public: every admin viewing the Projects client list should see the
        // re-sort live, and the payload carries nothing sensitive (a client name).
        return [
            new Channel('admin-projects-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.status.changed';
    }

    public function broadcastWith(): array
    {
        return ['client' => $this->client];
    }
}
