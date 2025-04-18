<?php

namespace App\Services;

use App\Events\Post\DeleteCommentEvent;
use App\Events\Post\EditCommentEvent;
use App\Events\Post\SendCommentEvent;
use App\Models\Comment;
use App\Models\CommentFile;
use App\Models\dto\CommentDTO;
use App\Models\dto\CommentWithReplyDTO;
use App\Models\dto\UserDTO;
use App\Models\Post;
use App\Services\Blacklist\checkingBlacklist;
use App\Services\Paginate\PaginatedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentService
{
    use checkingBlacklist;

    public function getCommentForAdmin(array $request): ?CommentDTO
    {
        if(Auth::user()->role == 'admin') {
            $comment = Comment::where('id', $request['comment_id'])
                ->with('files')
                ->with('user.account')
                ->withCount('replies')
                ->first();

            return new CommentDTO(
                $comment->id,
                $comment->post_id,
                new UserDTO(
                    $comment->user->id,
                    $comment->user->name,
                    $comment->user->account->image,
                ),
                $comment->text,
                $comment->created_at,
                $comment->updated_at,
                $comment->replies_count,
                $comment->files
            );
        } else return null;
    }

    public function get(array $request): PaginatedResponse
    {
        $blockedByIds = $this->blockedBy();

        $paginatedComments = Comment::where('post_id', $request['post_id'])
            ->whereNotIn('user_id', $blockedByIds)
            ->where('reply_id', null)
            ->with('files')
            ->with('user.account')
            ->withCount('replies')
            ->paginate(15, ['*'], 'page', $request['page_id']);


        $data = $this->getCommentsDTOs($paginatedComments);

        return new PaginatedResponse(
            $data,
            $paginatedComments->currentPage(),
            $paginatedComments->lastPage(),
            $paginatedComments->total()
        );
    }

    public function getReplies(array $request): PaginatedResponse
    {
        $blockedByIds = $this->blockedBy();

        $paginatedComments = Comment::where('reply_id', $request['reply_id'])
            ->whereNotIn('user_id', $blockedByIds)
            ->with('files')
            ->with('user.account')
            ->withCount('replies')
            ->paginate(5, ['*'], 'page', $request['page_id']);

        $data = $this->getCommentsDTOs($paginatedComments);

        return new PaginatedResponse(
            $data,
            $paginatedComments->currentPage(),
            $paginatedComments->lastPage(),
            $paginatedComments->total()
        );
    }

    protected function getCommentsDTOs($paginatedComments) {
        return $paginatedComments->getCollection()->map(function ($comment) {
            return new CommentDTO(
                $comment->id,
                $comment->post_id,
                new UserDTO(
                    $comment->user->id,
                    $comment->user->name,
                    $comment->user->account->image,
                ),
                $comment->text,
                $comment->created_at,
                $comment->updated_at,
                $comment->replies_count,
                $comment->files
            );
        });
    }

    public function create(array $request): bool
    {
        if ($request['text']) {
            DB::beginTransaction();
            if (!array_key_exists('files', $request)) {
                $request['files'] = [];
            }
            $files = $request['files'];
            $images = [];
            try {
                $createdComment = Comment::create([
                    'post_id' => $request['post_id'],
                    'user_id' => Auth::id(),
                    'reply_id' => $request['reply_id'],
                    'text' => $request['text']
                ]);
                if($createdComment) {
                    foreach ($files as $file) {
                        $images[] = $this->addImage($file, $createdComment->id);
                    }
                    event(new SendCommentEvent($createdComment->id, $request['post_id']));
                    DB::commit();
                    return true;
                }
                return false;
            } catch (\Throwable $e) {
                DB::rollBack();
                if ($files) {
                    $this->clearStorage($images);
                }
                report($e);
                return false;
            }
        } else return false;
    }

    public function getComment(array $request): CommentWithReplyDTO
    {
        $blockedByIds = $this->blockedBy();

        $comment = Comment::where('id', $request['comment_id'])
            ->with('files')
            ->whereNotIn('user_id', $blockedByIds)
            ->with('user.account')
            ->withCount('replies')
            ->first();

        return new CommentWithReplyDTO(
            $comment->id,
            $comment->post_id,
            $comment->reply_id,
            new UserDTO(
                $comment->user->id,
                $comment->user->name,
                $comment->user->account->image,
            ),
            $comment->text,
            $comment->created_at,
            $comment->updated_at,
            $comment->replies_count,
            $comment->files
        );
    }

    public function update(array $request) {
        $comment = Comment::where('id', $request['comment_id'])->first();
        if($comment->user_id == Auth::id()) {
            $updated = $comment->update([
                'text' => $request['text']
            ]);

            return (bool)$updated;
        }
        return false;
    }

    public function delete(array $request): bool
    {
        $comment = Comment::where('id', $request['comment_id'])->first();
        if($comment) {
            $post = Post::where('id', $comment->post_id)->first();
            if($post->user_id == Auth::id() || $comment->user_id == Auth::id() || Auth::user()->role == 'admin') {
                $files = CommentFile::where('comment_id', $request['comment_id'])->get();
                if($comment->delete()) {
                    foreach ($files as $file) {
                        $this->deleteImage($file->filename);
                    }
                    event(new DeleteCommentEvent(['id' => $request['comment_id'], 'reply' => $comment->reply_id], $post->id));
                    return true;
                } else {
                    return false;
                }
            }
            else {
                return false;
            }
        } else return false;
    }

    protected function addImage($file, int $commentId): string
    {
        $fileName = basename(Storage::put('/private/images/comments', $file));
        CommentFile::create([
            'comment_id' => $commentId,
            'filename' => $fileName,
        ]);
        return $fileName;
    }

    protected function deleteImage($name): void
    {
        Storage::delete('/private/images/comments/' . $name);
    }

    protected function clearStorage(array $images): void
    {
        foreach ($images as $image) {
            $this->deleteImage($image);
        }
    }
}
