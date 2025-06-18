<?php

namespace App\Models\dto;

use Illuminate\Support\Collection;

class UnreadMessagesDTO
{
    protected Collection $chats;
    protected int $countMessages;

    /**
     * @param Collection $chats
     * @param int $countMessages
     */
    public function __construct(Collection $chats, int $countMessages)
    {
        $this->chats = $chats;
        $this->countMessages = $countMessages;
    }

    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function getCountMessages(): int
    {
        return $this->countMessages;
    }
}
