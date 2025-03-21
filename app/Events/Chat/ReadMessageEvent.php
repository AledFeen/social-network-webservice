<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $messageId;
    private $chatId;
    /**
     * Create a new event instance.
     */
    public function __construct($messageId, $chatId)
    {
        $this->messageId = $messageId;
        $this->chatId = $chatId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'read_message';
    }

    public function broadcastWith(): array
    {
        return [
            'read_message' => $this->messageId
        ];
    }
}
