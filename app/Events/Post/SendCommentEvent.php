<?php

namespace App\Events\Post;

use App\Models\Comment;
use App\Models\dto\CommentDTO;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendCommentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $comment;
    private $postId;
    /**
     * Create a new event instance.
     */
    public function __construct($comment, $postId)
    {
        $this->comment = $comment;
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
        return 'comment';
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => $this->comment
        ];
    }
}
