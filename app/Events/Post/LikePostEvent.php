<?php

namespace App\Events\Post;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Type\Integer;

class LikePostEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    protected $user;
    protected $postId;
    /**
     * Create a new event instance.
     */
    public function __construct(int $user, $postId)
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
        return 'like';
    }

    public function broadcastWith(): array
    {
        return [
            'like' => $this->user
        ];
    }
}
