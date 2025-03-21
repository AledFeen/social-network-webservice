<?php

namespace App\Services;

use App\Events\Post\DislikePostEvent;
use App\Events\Post\LikePostEvent;
use App\Models\dto\UserDTO;
use App\Models\PostLike;
use App\Services\Blacklist\checkingBlacklist;
use App\Services\Blacklist\MustCheckBlacklist;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Facades\Auth;

class LikeService implements MustCheckBlacklist
{
    use checkingBlacklist;

    public function get(array $request)
    {
        $likes = PostLike::where('post_id', $request['post_id'])
            ->whereNotIn('user_id', $this->blockedBy())
            ->with('user.account')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $users = $likes->map(function ($like) {
            $user = $like->user;
            $account = $user->account;
            return new UserDTO(
                $user->id,
                $user->name,
                $account->image
            );
        });

        return new PaginatedResponse(
            $users,
            $likes->currentPage(),
            $likes->lastPage(),
            $likes->total()
        );
    }

    public function like(array $request): bool
    {
        $like = PostLike::where('user_id', Auth::id())
            ->where('post_id', $request['post_id'])
            ->first();
        if ($like) {
            $dislike = (bool)$like->delete();
            event(new DislikePostEvent(Auth::id(), $request['post_id']));
            return $dislike;
        } else {
            $created = PostLike::create([
                'user_id' => Auth::id(),
                'post_id' => $request['post_id']
            ]);
            if($created) {
                event(new LikePostEvent(Auth::id(), $request['post_id']));
            }
            return (bool)$created;
        }
    }
}
