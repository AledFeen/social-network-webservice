<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $message;
    private $chatId;
    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, $chatId)
    {
        $this->message = $message;
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
        return 'edited_message';
    }

    public function broadcastWith(): array
    {
        return [
            'edited_message' => $this->message
        ];
    }
}
