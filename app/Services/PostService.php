<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\dto\MainPostDTO;
use App\Models\dto\PostDTO;
use App\Models\dto\UserDTO;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostLike;
use App\Models\PostTag;
use App\Models\PreferredTag;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blacklist\checkingBlacklist;
use App\Services\Location\hasLocation;
use App\Services\Location\MustHaveLocation;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ramsey\Collection\Collection;

class PostService implements MustHaveLocation
{
    use hasLocation;
    use checkingBlacklist;

    public function getPost(array $request): ?PostDTO
    {
        $blockedByIds = $this->blockedBy();

        $user = User::where('id', Auth::id())->first();

        $post = Post::where('id', $request['post_id']);

        if ($user->role != 'admin') {
            $post = $post->whereNotIn('user_id', $blockedByIds);
        }

        $post = $post->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->first();

        if (!$post) {
            return null;
        }

        if ($post->repost_id !== null) {
            $post->main_post = $this->getMainPost($post->repost_id);
        }

        $post->is_liked = $post->likes ? $post->likes->contains('user_id', auth()->id()) : false;

        return new PostDTO(
            $post->id,
            new UserDTO($post->user->id, $post->user->name, $post->user->account->image),
            $post->repost_id,
            $post->location,
            $post->text,
            $post->created_at,
            $post->updated_at,
            $post->reposts_count,
            $post->likes_count,
            $post->comments_count,
            $post->tags,
            $post->files,
            $post->main_post,
            $post->is_liked
        );
    }

    public function getPostsByTag(array $request): PaginatedResponse
    {
        $blockedByIds = $this->blockedBy();

        $paginatedPosts = Post::whereHas('tags', function ($query) use ($request) {
            $query->where('name', $request['tag']);
        })
            ->whereNotIn('user_id', $blockedByIds)
            ->whereHas('user.privacy', function ($query) {
                $query->where('account_type', 'public');
            })
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function getRecommendationPosts(array $request): PaginatedResponse
    {
        $likedTags = PreferredTag::where('user_id', Auth::id())->pluck('tag');
        $followedUserIds = Subscription::where('follower_id', Auth::id())->pluck('user_id');
        $likedPosts = PostLike::whereIn('user_id', $followedUserIds)->pluck('post_id');

        $blockedByIds = $this->blockedBy();
        $blockedByIds[] = Auth::id();

        $paginatedPosts = Post::where(function ($query) use ($likedTags, $likedPosts, $blockedByIds) {
            $query->whereHas('tags', function ($query) use ($likedTags) {
                $query->whereIn('name', $likedTags);
            })
                ->orWhereIn('id', $likedPosts);
        })
            ->whereNotIn('user_id', $blockedByIds)
            ->whereHas('user.privacy', function ($query) {
                $query->where('account_type', 'public');
            })
            ->where('created_at', '>=', now()->subWeeks(2))
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('likes_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function getFeedPosts(array $request): PaginatedResponse
    {
        $followingUserIds = Subscription::where('follower_id', Auth::id())
            ->pluck('user_id')
            ->toArray();
        $blockedByIds = $this->blockedBy();

        $paginatedPosts = Post::whereIn('user_id', $followingUserIds)
            ->whereNotIn('user_id', $blockedByIds)
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function getUserPosts(array $request): PaginatedResponse
    {
        $blockedByIds = $this->blockedBy();

        $paginatedPosts = Post::where('user_id', $request['user_id'])
            ->whereNotIn('user_id', $blockedByIds)
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);


        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function getSearchPost(array $request): PaginatedResponse
    {
        $posts = Post::query();

        if (array_key_exists('text', $request)) {
            $posts = $posts->where('text', 'like', '%' . $request['text'] . '%');
        }

        if (array_key_exists('tags', $request)) {
            $posts = $posts->whereHas('tags', function ($query) use ($request) {
                $query->whereIn('name', $request['tags']);
            });
        }

        if (array_key_exists('location', $request)) {
            $posts = $posts->where('location', 'like', '%' . $request['location'] . '%');
        }

        if (array_key_exists('user', $request)) {
            $posts = $posts->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request['user'] . '%');
            });
        }

        $blockedByIds = $this->blockedBy();

        $paginatedPosts = $posts->whereNotIn('user_id', $blockedByIds)
            ->whereHas('user.privacy', function ($query) {
            $query->where('account_type', 'public');
        })
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function getReposts(array $request): PaginatedResponse
    {
        $blockedByIds = $this->blockedBy();

        $paginatedPosts = Post::where('repost_id', $request['post_id'])
            ->whereNotIn('user_id', $blockedByIds)
            ->withCount('reposts')
            ->withCount('likes')
            ->withCount('comments')
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->with('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $request['page_id']);

        $postsWithMainPost = $this->getPostsWithMainPosts($paginatedPosts);

        $postsWithLikedProperty = $this->getPostsWithIsLikedProperty($postsWithMainPost);

        $data = $this->getPostDTOs($postsWithLikedProperty);

        return new PaginatedResponse(
            $data,
            $paginatedPosts->currentPage(),
            $paginatedPosts->lastPage(),
            $paginatedPosts->total()
        );
    }

    public function delete(array $request): bool
    {
        $post = Post::where('id', $request['post_id'])->first();

        if ($post && ($post->user_id == Auth::id() || Auth::user()->role == 'admin')) {
            $comments = Comment::where('post_id', $request['post_id'])
                ->with('files')
                ->get();

            $commentFiles = $comments->flatMap(function ($comment) {
                return $comment->files;
            });

            $this->deleteCommentsImage($commentFiles);

            $postFiles = PostFile::where('post_id', $request['post_id'])->get();
            $this->deleteFiles($postFiles);

            $deleted = Post::where('id', $request['post_id'])
                ->delete();

            return (bool)$deleted;
        } else return false;
    }

    public function updateTags(array $request): bool
    {
        $post = Post::where('id', $request['post_id'])->first();

        if ($post->user_id == Auth::id()) {
            DB::beginTransaction();

            try {
                $tags = PostTag::where('post_id', $request['post_id']);
                if ($tags) {
                    $tags->delete();
                }

                if ($request['tags']) {
                    $this->checkTagExistence($request['tags']);
                    foreach ($request['tags'] as $tag) {
                        PostTag::create([
                            'post_id' => $request['post_id'],
                            'tag' => $tag
                        ]);
                    }
                }

                DB::commit();
                return true;
            } catch (\Exception $e) {
                DB::rollBack();
                logger($e);
                return false;
            }
        }
        return false;
    }

    public function updateLocation(array $request): bool
    {
        $post = Post::where('id', $request['post_id'])->first();
        if ($post->user_id == Auth::id()) {

            $updated = $post->update([
                'location' => $request['location'] ? $this->checkLocation($request['location']) : null
            ]);
            return (bool)$updated;

        } else return false;

    }

    public function updateText(array $request): bool
    {
        $post = Post::where('id', $request['post_id'])->first();

        if ($post->user_id == Auth::id()) {

            $files = $post->files()->get();

            if ($request['text'] !== null) {
                $updated = $post->update([
                    'text' => $request['text']
                ]);

                return (bool)$updated;
            } else {
                if ($files->count() > 0) {
                    $updated = $post->update([
                        'text' => $request['text']
                    ]);

                    return (bool)$updated;
                } else return false;
            }
        }
        return false;
    }

    public function updateFiles(array $request): bool
    {
        $files = PostFile::where('post_id', $request['post_id'])->get();
        $post = Post::where('id', $request['post_id'])->first();
        if ($post->user_id == Auth::id()) {
            if (array_key_exists('files', $request) && $request['files']) {
                DB::beginTransaction();
                $images = [];
                $videos = [];
                try {
                    PostFile::where('post_id', $request['post_id'])->delete();
                    foreach ($request['files'] as $file) {
                        $extension = $file->getClientOriginalExtension();
                        if (in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'svg'])) {
                            $images[] = $this->addImage($file, $request['post_id']);
                        } else $videos[] = $this->addVideo($file, $request['post_id']);
                    }
                    DB::commit();
                    $this->deleteFiles($files);
                    return true;
                } catch (\Exception $e) {
                    DB::rollBack();
                    if ($files) {
                        $this->clearStorage($images, $videos);
                    }
                    logger($e);
                    return false;
                }
            } else {
                if ($post->text != null) {
                    PostFile::where('post_id', $request['post_id'])->delete();
                    $this->deleteFiles($files);
                    return true;
                } else return false;
            }
        }
        return false;
    }

    public function create(array $request): bool
    {
        if (!array_key_exists('files', $request)) {
            $request['files'] = null;
        }
        $files = $request['files'];

        if (!array_key_exists('tags', $request)) {
            $request['tags'] = null;
        }

        if ($request['text'] || $files) {
            DB::beginTransaction();
            $images = [];
            $videos = [];
            try {
                $location = $request['location'] ? $this->checkLocation($request['location']) : null;
                $createdPost = $this->createPost($request['repost_id'], $location, $request['text']);
                if ($createdPost) {
                    if ($request['tags'] != null) {
                        $this->checkTagExistence($request['tags']);
                        foreach ($request['tags'] as $tag) {
                            PostTag::create([
                                'post_id' => $createdPost->id,
                                'tag' => $tag
                            ]);
                        }
                    }

                    if ($request['files'] != null) {
                        foreach ($files as $file) {
                            $extension = $file->getClientOriginalExtension();
                            if (in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'svg'])) {
                                $images[] = $this->addImage($file, $createdPost->id);
                            } else $videos[] = $this->addVideo($file, $createdPost->id);
                        }
                    }
                    DB::commit();
                    return true;
                }
                return false;
            } catch (\Throwable $e) {
                DB::rollBack();
                if ($files) {
                    $this->clearStorage($images, $videos);
                }
                report($e);
                return false;
            }
        } else return false;
    }

    public function getSearchTags(array $request)
    {
        return Tag::where('name', 'like', '%' . $request['search-text'] . '%')
            ->withCount('posts')
            ->take(10)
            ->get();
    }

    public function getLocations(array $request)
    {
        return Location::where('name', 'like', '%' . $request['search-text'] . '%')
            ->take(10)
            ->get();
    }

    protected function getPostsWithMainPosts($paginatedPosts)
    {
        return $paginatedPosts->getCollection()->map(function ($post) {
            if ($post->repost_id !== null) {
                $post->main_post = $this->getMainPost($post->repost_id);
            }
            return $post;
        });
    }

    protected function getPostsWithIsLikedProperty($paginatedPosts)
    {
        $authUserId = auth()->id();

        return $paginatedPosts->map(function ($post) use ($authUserId) {
            $post->is_liked = $post->likes ? $post->likes->contains('user_id', $authUserId) : false;
            return $post;
        });
    }

    protected function getMainPost(int $post_id): MainPostDTO
    {
        $post = Post::where('id', $post_id)
            ->with('user.account')
            ->with('files')
            ->with('tags')
            ->first();

        return new MainPostDTO(
            $post->id,
            new UserDTO($post->user->id, $post->user->name, $post->user->account->image),
            $post->location,
            $post->text,
            $post->created_at,
            $post->updated_at,
            $post->tags,
            $post->files
        );
    }

    protected function getPostDTOs($paginatedPosts)
    {
        return $paginatedPosts->map(function ($post) {
            return new PostDTO(
                $post->id,
                new UserDTO($post->user->id, $post->user->name, $post->user->account->image),
                $post->repost_id,
                $post->location,
                $post->text,
                $post->created_at,
                $post->updated_at,
                $post->reposts_count,
                $post->likes_count,
                $post->comments_count,
                $post->tags,
                $post->files,
                $post->main_post,
                $post->is_liked
            );
        });
    }

    protected function deleteCommentsImage($files): void
    {
        foreach ($files as $file) {
            Storage::delete('/private/images/comments/' . $file->filename);
        }
    }

    protected function checkTagExistence(array $tags): void
    {
        if ($tags) {
            foreach ($tags as $tag) {
                if (!Tag::where('name', $tag)->first()) {
                    Tag::create([
                        'name' => $tag
                    ]);
                }
            }
        }
    }

    protected function createPost(?int $repost_id, ?string $location, ?string $text)
    {
        return Post::create([
            'user_id' => Auth::id(),
            'repost_id' => $repost_id,
            'location' => $location,
            'text' => $text
        ]);
    }

    protected function clearStorage(array $images, array $videos): void
    {
        foreach ($images as $image) {
            $this->deleteImage($image);
        }

        foreach ($videos as $video) {
            $this->deleteVideo($video);
        }
    }

    protected function deleteFiles(\Illuminate\Database\Eloquent\Collection $files): void
    {
        foreach ($files as $file) {
            if ($file->type == 'image') {
                $this->deleteImage($file->filename);
            } else {
                $this->deleteVideo($file->filename);
            }
        }
    }

    protected function addImage($file, int $postId): string
    {
        $fileName = basename(Storage::put('/private/images/posts', $file));
        $file = PostFile::create([
            'post_id' => $postId,
            'type' => 'image',
            'filename' => $fileName,
        ]);
        return $file->filename;
    }

    protected function addVideo($file, int $postId): string
    {
        $fileName = basename(Storage::put('/private/videos/posts', $file));
        $file = PostFile::create([
            'post_id' => $postId,
            'type' => 'video',
            'filename' => $fileName,
        ]);
        return $file->filename;
    }

    protected function deleteImage($name): void
    {
        Storage::delete('/private/images/posts/' . $name);
    }

    protected function deleteVideo($name): void
    {
        Storage::delete('/private/videos/posts/' . $name);
    }
}
