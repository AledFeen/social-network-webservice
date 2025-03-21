<?php

namespace App\Http\Resources\CommentDTO;

use App\Http\Resources\UserDTO\UserDTOResource;
use App\Models\CommentFile;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentWithReplyDTOResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getId(),
            'post_id' => $this->getPostId(),
            'reply_id' => $this->getReplyId(),
            'user' => new UserDTOResource($this->getUser()),
            'text' => $this->getText(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
            'hasReplies' => $this->hasReplies(),
            'files' => CommentFileResource::collection($this->getFiles()),
        ];
    }
}
