<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\BannedUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function checkAuth(): array
    {
        if (Auth::id()) return ['token' => true];
        return ['token' => false];
    }

    public function user(): UserResource
    {
       return new UserResource(Auth::user());
    }

    public function checkVerified()
    {
        return new JsonResponse((bool)User::where('id', Auth::id())->value('email_verified_at'));
    }

    public function checkBanned() {
        return new JsonResponse((bool)BannedUser::where('user_id', Auth::id())->value('created_at'));
    }
}
