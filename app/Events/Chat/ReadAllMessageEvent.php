<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadAllMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $linkId;
    private $chatId;
    private $countMessages;
    private $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($linkId, $chatId, $userId, $countMessages)
    {
        $this->linkId = $linkId;
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->countMessages = $countMessages;
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
        return 'read_messages';
    }

    public function broadcastWith(): array
    {
        return [
            'data' => ['link_messages' => $this->linkId, 'user_id' => $this->userId, 'count' => $this->countMessages]
        ];
    }
}
