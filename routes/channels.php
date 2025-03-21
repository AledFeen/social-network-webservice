<?php

use App\Models\Post;
use App\Models\User;
use App\Models\UserChatLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    if (!$user) {
        return false;
    }

    return UserChatLink::where('chat_id', $chatId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('post.{postId}', function ($user, $postId) {
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
});

/*
Broadcast::channel('profile.{username}', function ($user, $username) {
    if (!$user) {
        return false;
    }
    $u = User::where('name', $username)->first();
    $userId = $u->id;
    $account_type = $this->getSettings($userId)->account_type;
    if ($account_type === 'public') {
        return true;
    } else if($user->role == 'admin') {
        return true;
    } else {
        if ($this->checkOwner($userId)) {
            return true;
        } else {
            return (bool)$this->checkSubscribe($userId);
        }
    }
});*/
