<?php

namespace App\Broadcasting;

use App\Models\Post;
use App\Models\User;
use App\Services\PrivacySettings\checkingSettings;

class PostChannel
{
    use checkingSettings;
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
    public function join(User $user, $postId): array|bool
    {
        if (!$user) {
            return false;
        }
        $user_id = Post::find($postId)->user_id;
        $account_type = $this->getSettings($user_id)->account_type;
        if ($account_type === 'public') {
            return true;
        } else if($user->role == 'admin') {
            return true;
        } else {
            if ($this->checkOwner($user_id)) {
                return true;
            } else {
                return (bool) $this->checkSubscribe($user_id);
            }
        }
    }
}
