<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Models\UserChatLink;

class ChatChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, $chatId): array|bool
    {
        if (!$user) {
            return false;
        }

        return UserChatLink::where('chat_id', $chatId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
