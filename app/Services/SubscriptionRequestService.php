<?php

namespace App\Services;

use App\Http\Resources\SubscriptionRequest\SubscriptionRequestDTOResource;
use App\Models\dto\SubscriptionRequestDTO;
use App\Models\dto\UserDTO;
use App\Models\PrivacySettings;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionRequestService
{
    public function subscribe(array $request): bool
    {
        $user_id = $request['user_id'];
        $follower_id = Auth::id();

        if(!SubscriptionRequest::where('user_id', $user_id)->where('follower_id', Auth::id())->first()) {
            $privacy = PrivacySettings::where('user_id', $user_id)->first();
            if ($privacy->account_type == 'public') {
                return false;
            }

            if ($follower_id != $user_id) {
                $created = SubscriptionRequest::create([
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

    public function accept(array $request): bool
    {
        DB::beginTransaction();
        try {

            $subRequest = SubscriptionRequest::where('id', $request['id'])->first();
            SubscriptionRequest::where('id', $request['id'])->delete();
            Subscription::create([
                'user_id' => Auth::id(),
                'follower_id' => $subRequest->follower_id
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            logger($e);
            return false;
        }
    }

    public function decline(array $request): bool
    {
        return (bool)SubscriptionRequest::where('id', $request['id'])->delete();
    }

    public function get(array $request): PaginatedResponse
    {
        $paginatedRequests = SubscriptionRequest::where('user_id', Auth::id())
            ->with('follower.account')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $requests = $paginatedRequests->getCollection()->map(function ($request) {
            return new SubscriptionRequestDTO(
               $request->id,
                new UserDTO(
                    $request->follower->id,
                    $request->follower->name,
                    $request->follower->account->image
                ),
               $request->created_at
            );
        });

        return new PaginatedResponse(
            $requests,
            $paginatedRequests->currentPage(),
            $paginatedRequests->lastPage(),
            $paginatedRequests->total()
        );
    }

    public function getCount() {
        return SubscriptionRequest::where('user_id', Auth::id())->count();
    }

}
