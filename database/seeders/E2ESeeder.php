<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BannedUser;
use App\Models\BlockedUser;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostTag;
use App\Models\PreferredTag;
use App\Models\PrivacySettings;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserChatLink;
use Illuminate\Database\Seeder;

class E2ESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // clear users
    // clear locations
    public function run(): void
    {
        User::truncate();
        Location::truncate();
        Tag::truncate();
        Chat::truncate();

        $user1 = User::factory()->create([
            'name' => 'user1',
            'email' => 'user1@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $user2 = User::factory()->create([
            'name' => 'user2',
            'email' => 'user2@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $user3 = User::factory()->create([
            'name' => 'user3',
            'email' => 'user3@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $user4= User::factory()->create([
            'name' => 'user4',
            'email' => 'user4@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $userNoVerified = User::factory()->create([
            'name' => 'userNoVerified',
            'email' => 'userNoVerified@gmail.com',
            'password' => '12344321',
            'email_verified_at' => null,
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $userBanned = User::factory()->create([
            'name' => 'userBanned',
            'email' => 'userBanned@gmail.com',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        BannedUser::factory()->create([
           'user_id' => $userBanned->id
        ]);

        $userAdmin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 1,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $userDefault= User::factory()->create([
            'name' => 'userDefault',
            'email' => 'userdefault@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        PrivacySettings::where('user_id', $user2->id)->update([
            'account_type' => 'private',
            'who_can_comment' => 'only_subscribers',
            'who_can_repost' => 'only_subscribers',
            'who_can_message' => 'only_subscribers'
        ]);

        $location = Location::factory()->create([
            'name' => 'location'
        ]);
        $location1 = Location::factory()->create([
            'name' => 'location1',
        ]);
        $location2 = Location::factory()->create([
            'name' => 'location2'
        ]);

        Account::where('user_id', $user1->id)->update([
            'real_name' => 'user1',
            'location' => $location->name,
            'date_of_birth' => '10.10.10',
            'about_me' => 'The first user',
        ]);

        Account::where('user_id', $user2->id)->update([
            'real_name' => 'user2',
            'location' => $location1->name,
            'date_of_birth' => '10.10.10',
            'about_me' => 'The second user',
        ]);

        BlockedUser::factory()->create([
           'user_id' => $user1->id,
           'blocked_id' => $user4->id
        ]);

        Subscription::factory()->create([
            'user_id' => $user1->id,
            'follower_id' => $user2->id
        ]);

        Subscription::factory()->create([
            'user_id' => $user2->id,
            'follower_id' => $user1->id
        ]);

        Subscription::factory()->create([
            'user_id' => $user2->id,
            'follower_id' => $user3->id
        ]);

        SubscriptionRequest::factory()->create([
            'user_id' => $user2->id,
            'follower_id' => $user4->id
        ]);

        $tag = Tag::factory()->create([
            'name' => 'tag1'
        ]);

        $tag1 = Tag::factory()->create([
            'name' => 'tag2'
        ]);

        $tag3 = Tag::factory()->create([
            'name' => 'tag3'
        ]);

        $post = Post::factory()->create([
            'user_id' => $user2->id,
            'location' => $location->name,
            'text' =>  'solo test post',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        PostLike::create([
            'user_id' => $user1->id,
            'post_id' => $post->id
        ]);

        PostLike::create([
            'user_id' => $user2->id,
            'post_id' => $post->id
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
            'text' => 'first comment to post',
        ]);

        $commentReply = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
            'reply_id' => $comment->id,
            'text' => 'reply to first comment',
        ]);

        Post::factory()->create([
            'user_id' => $user2->id,
            'location' => $location1->name,
            'text' =>  'second test post',
            'repost_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $repost = Post::factory()->create([
            'user_id' => $user3->id,
            'location' => $location1->name,
            'text' =>  'post for repost',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Post::factory()->create([
            'user_id' => $userDefault->id,
            'text' => 'repost',
            'repost_id' => $repost->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        /////TEST FEED DATA
        $feedUser = User::factory()->create([
            'name' => 'feeduser',
            'email' => 'feed@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $feedUser1 = User::factory()->create([
            'name' => 'feeduser1',
            'email' => 'feed1@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $feedUser2 = User::factory()->create([
            'name' => 'feeduser2',
            'email' => 'feed2@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        Subscription::factory()->create([
            'user_id' => $feedUser1,
            'follower_id' => $feedUser
        ]);

        Subscription::factory()->create([
            'user_id' => $feedUser2,
            'follower_id' => $feedUser
        ]);

        Post::factory()->create([
            'user_id' => $feedUser1,
            'location' => $location2->name,
            'text' =>  'feed test post',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Post::factory()->create([
            'user_id' => $feedUser2,
            'location' => $location1->name,
            'text' =>  'second feed test post',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        ////TEST RECOMMENDATION DATA
        $recUser = User::factory()->create([
            'name' => 'requser',
            'email' => 'req@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $rectag = Tag::factory()->create([
            'name' => 'rectag'
        ]);

        PreferredTag::factory()->create([
            'user_id' => $recUser->id,
            'tag' => $rectag->name
        ]);

        $recUser1 = User::factory()->create([
            'name' => 'requser1',
            'email' => 'req1@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 1,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        Subscription::factory()->create([
            'user_id' => $recUser1->id,
            'follower_id' => $recUser->id
        ]);

        $recUser2 = User::factory()->create([
            'name' => 'requser2',
            'email' => 'req2@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $reqpost1 = Post::factory()->create([
            'user_id' => $recUser2->id,
            'location' => $location2->name,
            'text' =>  'req test post',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        PostLike::create([
            'user_id' => $recUser1->id,
            'post_id' => $reqpost1->id
        ]);

        $reqpost2 = Post::factory()->create([
            'user_id' => $recUser2->id,
            'location' => $location1->name,
            'text' =>  'second req test post',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        PostTag::factory()->create([
           'post_id'=>$reqpost2->id,
           'tag' =>$rectag->name
        ]);

        $recPrivate = User::factory()->create([
            'name' => 'reqprivate',
            'email' => 'reqpriv@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        PrivacySettings::where('user_id', $recPrivate->id)->update([
            'account_type' => 'private',
            'who_can_comment' => 'only_subscribers',
            'who_can_repost' => 'only_subscribers',
            'who_can_message' => 'only_subscribers'
        ]);

        $reqpost3 = Post::factory()->create([
            'user_id' => $recPrivate->id,
            'location' => $location2->name,
            'text' =>  'third feed test post unvisible',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        PostTag::factory()->create([
            'post_id'=>$reqpost3->id,
            'tag' =>$rectag->name
        ]);

        $chat = Chat::factory()->create([
            'type' => 'personal'
        ]);

        $link = UserChatLink::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user1->id
        ]);

        $link1 = UserChatLink::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user2->id
        ]);

        Message::factory()->create([
            'link_id' => $link->id,
            'text' => 'first message',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(5),
        ]);

        Message::factory()->create([
            'link_id' => $link1->id,
            'text' => 'second message',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $chat1 = Chat::factory()->create([
            'type' => 'personal'
        ]);

        $link3 = UserChatLink::factory()->create([
            'chat_id' => $chat1->id,
            'user_id' => $user1->id
        ]);

        UserChatLink::factory()->create([
            'chat_id' => $chat1->id,
            'user_id' => $user3->id
        ]);

        Message::factory()->create([
            'link_id' => $link3->id,
            'text' => 'third message',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $validateUser = User::factory()->create([
            'name' => 'validateuser',
            'email' => 'validate@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $validateUser1 = User::factory()->create([
            'name' => 'validateuser1',
            'email' => 'validate1@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $validateUser2 = User::factory()->create([
            'name' => 'validateuser2',
            'email' => 'validate2@gmail.com',
            'email_verified_at' => '30.03.2025',
            'password' => '12344321',
            'role' => 0,
            'created_at' => '30.03.2025',
            'updated_at' => '30.03.2025'
        ]);

        $pp = Post::factory()->create([
            'user_id' => $validateUser->id,
            'text' =>  'post for validate testing',
            'location' => $location2->name,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pc = Post::factory()->create([
            'user_id' => $validateUser->id,
            'text' =>  'post for validate update comment testing',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $nt = Tag::factory()->create(
            ['name' => 'new tag tag']);

        PostTag::factory()->create([
            'post_id'=>$pp->id,
            'tag' =>$nt->name
        ]);

        Comment::factory()->create([
            'post_id' => $pc->id,
            'user_id' => $user1->id,
            'text' => 'update',
        ]);

        $chatv = Chat::factory()->create([
            'type' => 'personal'
        ]);

        $linkv = UserChatLink::factory()->create([
            'chat_id' => $chatv->id,
            'user_id' => $validateUser->id
        ]);

        UserChatLink::factory()->create([
            'chat_id' => $chatv->id,
            'user_id' => $validateUser1->id
        ]);

        Message::factory()->create([
            'link_id' => $linkv->id,
            'text' => 'first message',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(5),
        ]);

        $chatv1 = Chat::factory()->create([
            'type' => 'personal'
        ]);

        $linkv1 = UserChatLink::factory()->create([
            'chat_id' => $chatv1->id,
            'user_id' => $validateUser->id
        ]);

        UserChatLink::factory()->create([
            'chat_id' => $chatv1->id,
            'user_id' => $validateUser2->id
        ]);

        Message::factory()->create([
            'link_id' => $linkv1->id,
            'text' => 'first message',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(5),
        ]);

    }
}
