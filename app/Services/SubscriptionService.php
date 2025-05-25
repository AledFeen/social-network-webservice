<?php

namespace App\Services;

use App\Models\BlockedUser;
use App\Models\dto\UserDTO;
use App\Models\PrivacySettings;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Services\Blacklist\checkingBlacklist;
use App\Services\Blacklist\MustCheckBlacklist;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Facades\Auth;

class SubscriptionService implements MustCheckBlacklist
{
    use checkingBlacklist;
    public function checkRelations(array $request): object
    {
        $subscription = Subscription::where(['user_id' => $request['user_id'], 'follower_id' => Auth::id()])
            ->first();

        $subscriber = Subscription::where(['user_id' => Auth::id(), 'follower_id' => $request['user_id']])
            ->first();

        $req = SubscriptionRequest::where(['user_id' => $request['user_id'], 'follower_id' => Auth::id()])
            ->first();

        $banned = BlockedUser::where(['user_id' => Auth::id(), 'blocked_id' => $request['user_id']])->first();

        $isBanned = BlockedUser::where(['user_id' => $request['user_id'], 'blocked_id' => Auth::id()])->first();

        return (object)[
            'subscription' => (bool)$subscription,
            'subscriber' => (bool)$subscriber,
            'request' => (bool)$req,
            'banned' => (bool)$banned,
            'isBanned' =>  (bool)$isBanned,
        ];
    }

    public function subscribe(array $request): bool
    {
        $user_id = $request['user_id'];
        $follower_id = Auth::id();

        if(!Subscription::where('user_id', $user_id)->where('follower_id', Auth::id())->first()) {
            $privacy = PrivacySettings::where('user_id', $user_id)->first();

            if($privacy->account_type == 'private') {
                return false;
            }

            $blockedByIds = $this->blockedBy();

            foreach ($blockedByIds as $user) {
                if($user == $user_id) {
                    return false;
                }
            }

            if ($follower_id != $user_id) {
                $created = Subscription::create([
                    'user_id' => $user_id,
                    'follower_id' => $follower_id
                ]);
            } else {
                $created = false;
            }

            return (bool)$created;
        }
        return false;
    }

    public function unsubscribe(array $request): bool
    {
        $deleted = Subscription::where('user_id', $request['user_id'])
            ->where('follower_id', Auth::id())
            ->delete();

        if(!$deleted) {
            $deleted = SubscriptionRequest::where('user_id', $request['user_id'])
                ->where('follower_id', Auth::id())
                ->delete();
        }

        return (bool)$deleted;
    }

    public function deleteSubscriber(array $request): bool
    {
        $deleted = Subscription::where('user_id', Auth::id())
            ->where('follower_id', $request['user_id'])
            ->delete();

        return (bool)$deleted;
    }

    public function subscribers(array $request): PaginatedResponse
    {
        $user = $this->findUser($request);

        $blockedByIds = $this->blockedBy();

        $paginatedFollowers = $user->followers()
            ->whereNotIn('follower_id', $blockedByIds)
            ->with('user.account')
            ->paginate(10, ['*'], 'page', $request['page_id']);

        $followers = $paginatedFollowers->map(function ($subscription) {
            return new UserDTO(
                $subscription->follower->id,
                $subscription->follower->name,
                $subscription->follower->account->image
            );
        });

        return new PaginatedResponse(
            $followers,
            $paginatedFollowers->currentPage(),
            $paginatedFollowers->lastPage(),
            $paginatedFollowers->total()
        );
    }

    public function subscriptions(array $request): PaginatedResponse
    {
        $user = $this->findUser($request);

        $blockedByIds = $this->blockedBy();

        $paginatedFollowings = $user->following()
            ->whereNotIn('user_id', $blockedByIds)
            ->with('user.account')
            ->paginate(10, ['*'], 'page', $request['page_id']);

        $followings = $paginatedFollowings->map(function ($subscription) {
            return new UserDTO($subscription->user->id, $subscription->user->name, $subscription->user->account->image);
        });

        return new PaginatedResponse(
            $followings,
            $paginatedFollowings->currentPage(),
            $paginatedFollowings->lastPage(),
            $paginatedFollowings->total()
        );
    }

    protected function findUser(array $request)
    {
        $user_id = $request['user_id'];
        return User::find($user_id);
    }


}
