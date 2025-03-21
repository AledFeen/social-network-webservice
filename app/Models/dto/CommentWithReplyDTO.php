<?php

namespace App\Models\dto;

use App\Models\CommentFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CommentWithReplyDTO extends \App\Models\Comment
{
    protected int $id;
    protected int $postId;
    protected ?int $replyId;
    protected UserDTO $user;
    protected string $text;
    protected Carbon $createdAt;
    protected Carbon $updatedAt;
    protected int $hasReplies;
    protected Collection $files;

    /**
     * @param int $id
     * @param int $postId
     * @param int|null $replyId
     * @param UserDTO $user
     * @param string $text
     * @param Carbon $createdAt
     * @param Carbon $updatedAt
     * @param int $hasReplies
     * @param Collection $files
     */
    public function __construct(int $id, int $postId, ?int $replyId, UserDTO $user, string $text, Carbon $createdAt, Carbon $updatedAt, int $hasReplies, Collection $files)
    {
        $this->id = $id;
        $this->postId = $postId;
        $this->replyId = $replyId;
        $this->user = $user;
        $this->text = $text;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->hasReplies = $hasReplies;
        $this->files = $files;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getUser(): UserDTO
    {
        return $this->user;
    }

    public function hasReplies(): int
    {
        return $this->hasReplies;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getReplyId(): ?int
    {
        return $this->replyId;
    }

    public function getFiles(): Collection
    {
        return $this->files;
    }
}
