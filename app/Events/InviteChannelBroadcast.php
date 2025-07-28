<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InviteChannelBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $channel;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data, $channel)
    {
        $this->data = $data;
        $this->channel = $channel;
    }
    public function broadcastOn(): Channel
    {
        return new Channel($this->channel);
    }
    public function broadcastAs(): string
    {
        return 'CpsNotification';
    }
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
