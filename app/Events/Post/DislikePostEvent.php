<?php

namespace App\Events\Post;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DislikePostEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $user;
    private $postId;
    /**
     * Create a new event instance.
     */
    public function __construct($user, $postId)
    {
        $this->user = $user;
        $this->postId = $postId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('post.' . $this->postId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'dislike';
    }

    public function broadcastWith(): array
    {
        return [
            'dislike' => $this->user
        ];
    }
}
