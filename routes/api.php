<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/checkAuth', [\App\Http\Controllers\Auth\AuthController::class, 'checkAuth']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', [\App\Http\Controllers\Auth\AuthController::class, 'user']);
});

Route::group(['middleware' => ['auth:sanctum', 'verified', 'check_ban']], function () {
    Route::get('/privacy-settings', [\App\Http\Controllers\PrivacySettingsController::class, 'getSettings']);
    Route::put('/privacy-settings', [\App\Http\Controllers\PrivacySettingsController::class, 'updateSettings']);

    Route::get('/my-account', [\App\Http\Controllers\AccountController::class, 'getMyAccount']);
    Route::put('/my-account', [\App\Http\Controllers\AccountController::class, 'updateAccount']);
    Route::get('/my-avatar', [\App\Http\Controllers\AccountController::class, 'getMyAvatar']);
    Route::get('/profile-image/{filename}', [\App\Http\Controllers\FileController::class, 'getAccountImage']);
    Route::post('/profile-image', [\App\Http\Controllers\AccountController::class, 'updateImage']);
    Route::delete('/profile-image', [\App\Http\Controllers\AccountController::class, 'deleteImage']);

    Route::get('/profile', [\App\Http\Controllers\AccountController::class, 'getProfile']);

    Route::get('/search-profile', [\App\Http\Controllers\AccountController::class, 'getSearchProfiles']);

    Route::get('/check-relations', [\App\Http\Controllers\SubscriptionController::class, 'checkRelations']);

    Route::group(['middleware' => ['account_type']], function () {
        Route::get('/subscribers', [\App\Http\Controllers\SubscriptionController::class, 'getSubscribers']);
        Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'getSubscriptions']);
        Route::get('/posts', [\App\Http\Controllers\PostController::class, 'getPosts']);
    });

    Route::group(['middleware' => ['account_type_post']], function () {
        Route::get('/post', [\App\Http\Controllers\PostController::class, 'getPost']);
        Route::get('/comments', [\App\Http\Controllers\PostController::class, 'getComments']);
        Route::get('/likes', [\App\Http\Controllers\PostController::class, 'getPostLikes']);
        Route::get('/reposts', [\App\Http\Controllers\PostController::class, 'getReposts']);
    });

    Route::get('/comment-replies',  [\App\Http\Controllers\PostController::class, 'getCommentReplies']);

    Route::get('/post-image/{filename}', [\App\Http\Controllers\FileController::class, 'getPostImage']);
    Route::get('/post-video/{filename}', [\App\Http\Controllers\FileController::class, 'getPostVideo']);
    Route::get('/comment-image/{filename}', [\App\Http\Controllers\FileController::class, 'getCommentImage']);

    Route::get('/feed-posts', [\App\Http\Controllers\PostController::class, 'getFeedPosts']);

    //only with public accounts
    Route::get('/posts-by-tag', [\App\Http\Controllers\PostController::class, 'getPostsByTag']);
    Route::get('/recommended-posts', [\App\Http\Controllers\PostController::class, 'getRecommendedPosts']);
    Route::get('/search-posts', [\App\Http\Controllers\PostController::class, 'getSearchPosts']);

    Route::get('/preferredTags', [\App\Http\Controllers\PreferredTagController::class, 'get']);
    Route::post('/preferredTag', [\App\Http\Controllers\PreferredTagController::class, 'add']);
    Route::delete('/preferredTag', [\App\Http\Controllers\PreferredTagController::class, 'delete']);
    Route::delete('/ignoreTag', [\App\Http\Controllers\PreferredTagController::class, 'ignore']);

    Route::get('/blocked-users', [\App\Http\Controllers\BlacklistController::class, 'getBlockedUsers']);
    Route::post('/block-user', [\App\Http\Controllers\BlacklistController::class, 'block']);
    Route::delete('/unblock-user', [\App\Http\Controllers\BlacklistController::class, 'unblock']);

    Route::post('/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribeUser']);
    Route::delete('/unsubscribe', [\App\Http\Controllers\SubscriptionController::class, 'unsubscribeUser']);
    Route::delete('/delete-subscriber', [\App\Http\Controllers\SubscriptionController::class, 'deleteSubscriber']);

    Route::post('subscribe-request', [\App\Http\Controllers\SubscriptionRequestController::class, 'subscribe']);
    Route::post('accept-request', [\App\Http\Controllers\SubscriptionRequestController::class, 'acceptRequest']);
    Route::delete('decline-request', [\App\Http\Controllers\SubscriptionRequestController::class, 'declineRequest']);
    Route::get('subscription-requests', [\App\Http\Controllers\SubscriptionRequestController::class, 'getRequests']);
    Route::get('subscription-requests-count', [\App\Http\Controllers\SubscriptionRequestController::class, 'getRequestCount']);

    Route::group(['middleware' => ['can_repost']], function () {
        Route::post('/post', [\App\Http\Controllers\PostController::class, 'createPost']);
    });

    Route::delete('/post', [\App\Http\Controllers\PostController::class, 'deletePost']);
    Route::put('/post', [\App\Http\Controllers\PostController::class, 'updatePostText']);
    Route::put('/post-location', [\App\Http\Controllers\PostController::class, 'updatePostLocation']);
    Route::post('/post-files', [\App\Http\Controllers\PostController::class, 'updatePostFiles']);
    Route::put('/post-tags', [\App\Http\Controllers\PostController::class, 'updatePostTags']);

    Route::get('/search-tags', [\App\Http\Controllers\PostController::class, 'getSearchTags']);
    Route::get('/locations', [\App\Http\Controllers\PostController::class, 'getLocations']);

    Route::group(['middleware' => ['can_comment']], function () {
        Route::post('/comment', [\App\Http\Controllers\PostController::class, 'leaveComment']);
    });
    Route::put('/comment', [\App\Http\Controllers\PostController::class, 'updateComment']);
    Route::delete('/comment', [\App\Http\Controllers\PostController::class, 'deleteComment']);

    Route::post('/like', [\App\Http\Controllers\PostController::class, 'likePost']);

    Route::get('/notification/followers', [\App\Http\Controllers\NotificationController::class, 'getFollowers']);
    Route::get('/notification/likes', [\App\Http\Controllers\NotificationController::class, 'getLikes']);
    Route::get('/notification/comments', [\App\Http\Controllers\NotificationController::class, 'getComments']);
    Route::get('/notification/comment-replies', [\App\Http\Controllers\NotificationController::class, 'getCommentReplies']);
    Route::get('/notification/reposts', [\App\Http\Controllers\NotificationController::class, 'getReposts']);

    Route::delete('/notification/followers', [\App\Http\Controllers\NotificationController::class, 'deleteFollowers']);
    Route::delete('/notification/likes', [\App\Http\Controllers\NotificationController::class, 'deleteLikes']);
    Route::delete('/notification/comments', [\App\Http\Controllers\NotificationController::class, 'deleteComment']);
    Route::delete('/notification/comment-replies', [\App\Http\Controllers\NotificationController::class, 'deleteReplies']);
    Route::delete('/notification/reposts', [\App\Http\Controllers\NotificationController::class, 'deleteReposts']);

    Route::group(['middleware' => ['can_message']], function () {
        Route::post('/personal-chat', [\App\Http\Controllers\ChatController::class, 'createPersonalChat']);
    });

    Route::get('/chats', [\App\Http\Controllers\ChatController::class, 'getChats']);
    Route::get('/chat-users', [\App\Http\Controllers\ChatController::class, 'getChatUsers']);
    Route::post('/chat', [\App\Http\Controllers\ChatController::class, 'createPersonalChat']);
    Route::get('/chatId', [\App\Http\Controllers\ChatController::class, 'getChatId']);
    Route::delete('/chat', [\App\Http\Controllers\ChatController::class, 'deletePersonalChat']);
    Route::get('/messages', [\App\Http\Controllers\ChatController::class, 'getMessages']);
    Route::post('/message', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::put('/message', [\App\Http\Controllers\ChatController::class, 'updateMessageText']);
    Route::delete('/message', [\App\Http\Controllers\ChatController::class, 'deleteMessage']);
    Route::put('/messages-read', [\App\Http\Controllers\ChatController::class, 'updateReadProperties']);
    Route::put('/message-read', [\App\Http\Controllers\ChatController::class, 'updateReadProperty']);
    Route::get('/last-message', [\App\Http\Controllers\ChatController::class, 'getLastMessage']);

    Route::get('/message-image/{filename}', [\App\Http\Controllers\FileController::class, 'getMessageImage']);
    Route::get('/message-video/{filename}', [\App\Http\Controllers\FileController::class, 'getMessageVideo']);
    Route::get('/message-audio/{filename}', [\App\Http\Controllers\FileController::class, 'getMessageAudio']);
    Route::get('/message-file/{filename}', [\App\Http\Controllers\FileController::class, 'getMessageFile']);

    Route::get('/complaint', [\App\Http\Controllers\ComplaintController::class, 'getComplaint']);
    Route::get('/complaints', [\App\Http\Controllers\ComplaintController::class, 'getComplaints']);
    Route::post('/complaint', [\App\Http\Controllers\ComplaintController::class, 'createComplaint']);
    Route::put('/complaint', [\App\Http\Controllers\ComplaintController::class, 'updateComplaint']);
    Route::get('/comment-admin', [\App\Http\Controllers\PostController::class, 'getCommentForAdmin']);
    Route::get('/message-admin', [\App\Http\Controllers\ChatController::class, 'getMessageForAdmin']);
    Route::get('/profile-admin', [\App\Http\Controllers\AccountController::class, 'getProfileForAdmin']);

    Route::post('/ban', [\App\Http\Controllers\ComplaintController::class, 'banUser']);
    Route::put('/account-admin', [\App\Http\Controllers\AccountController::class, 'updateAccountForAdmin']);
    Route::delete('/profile-image-admin', [\App\Http\Controllers\AccountController::class, 'deleteImageForAdmin']);
});
