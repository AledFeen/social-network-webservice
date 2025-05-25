<?php

namespace App\Observers;

use App\Models\NotificationLike;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostTag;
use App\Models\PreferredTag;

class PostLikeObserver
{
    /**
     * Handle the PostLike "created" event.
     */
    public function created(PostLike $postLike): void
    {
        $post = $this->getPost($postLike);
        $this->createNotification($post, $postLike);
        $this->addPreferredTags($post, $postLike);
    }

    protected function getPost(PostLike $postLike)
    {
        return Post::where('id', $postLike->post_id)->first();
    }

    protected function createNotification(Post $post, PostLike $postLike): void
    {
        if ($post->user_id != $postLike->user_id) {
            NotificationLike::create([
                'user_id' => $post->user_id,
                'like_id' => $postLike->id
            ]);
        }
    }

    protected function addPreferredTags(Post $post, PostLike $postLike): void
    {
        $tags = PostTag::where('post_id', $post->id)->get();
        foreach ($tags as $tag) {
            if($this->checkPreferredTagExistence($tag->tag, $postLike->user_id)) {
                PreferredTag::where('user_id', $postLike->user_id)
                    ->where('tag', $tag->tag)
                    ->increment('count');
            } else {
                PreferredTag::create([
                   'user_id' => $postLike->user_id,
                   'tag' => $tag->tag
                ]);
            }
        }
    }

    protected function checkPreferredTagExistence(string $tag, int $user) : bool
    {
        return (bool)PreferredTag::where('user_id', $user)->where('tag', $tag)->first();
    }

}
