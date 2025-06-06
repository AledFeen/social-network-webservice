<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostTag;
use App\Models\PreferredTag;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserChatLink;
use Illuminate\Database\Seeder;

class RandomDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    private array $users = [];
    private array $tags = [];
    private array $posts = [];
    private array $locations = [];

    public function run(): void
    {
        User::truncate();
        Location::truncate();
        Tag::truncate();
        Chat::truncate();

        $this->createUsers(10);
        $this->createLocations(10);
        $this->createSubscribes(10);
        $this->createTags(10);
        $this->createPreferredTags(10);
        $this->createPosts(15);
        $this->createPersonalChats(15, 20);
    }

    private function createUsers(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'password' => '12344321',
            ]);

            $this->users[] = $user;
        }

        foreach ($this->users as $user) {
            logger($user);
        }
    }

    private function createLocations(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $location = Location::factory()->create();
            $this->locations[] = $location;
        }
    }

    private function createSubscribes(int $maxCountSubscribes): void
    {
        foreach ($this->users as $user) {
            $countSubscribes = rand(0, $maxCountSubscribes);

            for ($i = 0; $i < $countSubscribes; $i++) {
                $usedNumbers = [];

                $number = rand(0, $maxCountSubscribes - 1);

                while ($this->users[$number]->id === $user->id || in_array($number, $usedNumbers)) {
                    $number = rand(0, $maxCountSubscribes - 1);
                }

                Subscription::factory()->create([
                    'user_id' => $user->id,
                    'follower_id' => $this->users[$number]->id
                ]);
            }
        }
    }

    private function createTags(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $tag = Tag::factory()->create();
            $this->tags[] = $tag;
        }
    }

    private function createPreferredTags(int $maxCount): void
    {
        foreach ($this->users as $user) {
            foreach (array_rand($this->tags, rand(2, $maxCount)) as $tag) {
                PreferredTag::factory()->create([
                    'user_id' => $user->id,
                    'tag' => $this->tags[$tag]->name
                ]);
            }
        }
    }

    private function createPosts(int $count): void
    {
        foreach ($this->users as $user) {
            $countPosts = rand(0, $count);
            for ($i = 0; $i < $countPosts; $i++) {
                if (empty($this->posts)) {
                    $this->createPost($user->id);
                } else {
                    if (rand(0, 1) === 1) {
                        $this->createPost($user->id);
                    } else {
                        $this->createRepost($user->id);
                    }
                }
            }
        }
    }

    private function createPost(int $userId): void
    {
        $post = Post::factory()->create([
            'user_id' => $userId,
            'location' => $this->locations[array_rand($this->locations, 1)],
        ]);
        $this->posts[] = $post;
        $this->createPostTags($post->id);
        $this->createPostComments($post->id);
    }

    private function createRepost(int $userId): void
    {
        $post = Post::factory()->create([
            'user_id' => $userId,
            'location' => $this->locations[array_rand($this->locations, 1)],
            'repost_id' => $this->posts[array_rand($this->posts, 1)],
        ]);
        $this->posts[] = $post;
        $this->createPostTags($post->id);
        $this->createPostComments($post->id);
        $this->createLikes($post->id);
    }

    private function createPostTags(int $postId): void
    {
        foreach (array_rand($this->tags, rand(2, 5)) as $tag) {
            PostTag::factory()->create([
                'post_id' => $postId,
                'tag' => $this->tags[$tag]->name
            ]);
        }
    }

    private function createPostComments(int $postId): void
    {
        foreach (array_rand($this->users, rand(2, 10)) as $user) {
            $comment = Comment::factory()->create([
                'post_id' => $postId,
                'user_id' => $this->users[$user]->id,
            ]);

            if (rand(0, 1) === 0) {
                $this->createPostCommentsReplies($postId, $comment->id);
            }
        }
    }

    private function createPostCommentsReplies(int $postId, int $commentId): void
    {
        foreach (array_rand($this->users, rand(2, 10)) as $user) {
            Comment::factory()->create([
                'post_id' => $postId,
                'user_id' => $this->users[$user]->id,
                'reply_id' => $commentId,
            ]);
        }
    }

    private function createLikes(int $postId): void
    {
        foreach (array_rand($this->users, rand(2, 10)) as $user) {
            PostLike::create([
                'user_id' => $this->users[$user]->id,
                'post_id' => $postId
            ]);
        }
    }

    private function createPersonalChats(int $count, $maxCountMessages): void
    {
        $pairs = [];

        for ($i = 0; $i < $count; $i++) {

            do {
                $pair = array_rand($this->users, 2);
            } while (in_array($pair, $pairs) || $pair[0] === $pair[1]);

            $pairs[] = $pair;
            $replace = $pair[0];
            $pair[0] = $pair[1];
            $pair[1] = $replace;
            $pairs[] = $pair;

            $chat = Chat::factory()->create([
                'type' => 'personal'
            ]);
            $chatUsers = [];
            foreach ($pair as $el) {
                $chatUsers[] = UserChatLink::factory()->create([
                    'chat_id' => $chat->id,
                    'user_id' => $this->users[$el]->id
                ]);
            }
            $this->createMessages($chatUsers, 20);
        }
    }

    private function createMessages(array $pair, int $maxCount): void
    {
        for ($i = 0; $i < rand(2, $maxCount); $i++ ) {
            $user = rand(0,1);
            Message::factory()->create([
                'link_id' => $pair[$user]->id,
            ]);
        }
    }

}
