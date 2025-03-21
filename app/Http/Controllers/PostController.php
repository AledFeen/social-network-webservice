<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\Comment\AddCommentRequest;
use App\Http\Requests\Post\Comment\DeleteCommentRequest;
use App\Http\Requests\Post\Comment\GetCommentByIdRequest;
use App\Http\Requests\Post\Comment\GetCommentForAdminRequest;
use App\Http\Requests\Post\Comment\GetCommentRepliesRequest;
use App\Http\Requests\Post\Comment\GetCommentRequest;
use App\Http\Requests\Post\Comment\UpdateCommentRequest;
use App\Http\Requests\Post\CreatePostRequest;
use App\Http\Requests\Post\DeletePostRequest;
use App\Http\Requests\Post\GetFeedPostsRequest;
use App\Http\Requests\Post\GetPostRequest;
use App\Http\Requests\Post\GetPostsByTagRequest;
use App\Http\Requests\Post\GetPostsRequest;
use App\Http\Requests\Post\GetRepostsRequest;
use App\Http\Requests\Post\GetSearchTagsRequest;
use App\Http\Requests\Post\Like\GetLikesRequest;
use App\Http\Requests\Post\Like\LikeRequest;
use App\Http\Requests\Post\GetSearchPostRequest;
use App\Http\Requests\Post\UpdateFilesRequest;
use App\Http\Requests\Post\UpdateLocationRequest;
use App\Http\Requests\Post\UpdateTagsRequest;
use App\Http\Requests\Post\UpdateTextRequest;
use App\Http\Resources\CommentDTO\CommentDTOResource;
use App\Http\Resources\CommentDTO\CommentWithReplyDTOResource;
use App\Http\Resources\CommentDTO\PaginatedCommentDTOResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PostDTO\PaginatedPostDTOResource;
use App\Http\Resources\PostDTO\PostDTOResource;
use App\Http\Resources\TagDtoResource;
use App\Http\Resources\UserDTO\PaginatedUserDTOResource;
use App\Services\CommentService;
use App\Services\LikeService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    protected PostService $postService;
    protected CommentService $commentService;
    protected LikeService $likeService;

    public function __construct(PostService $postService, CommentService $commentService, LikeService $likeService)
    {
        $this->postService = $postService;
        $this->commentService = $commentService;
        $this->likeService = $likeService;
    }

    public function getLocations(GetSearchTagsRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $request = $request->validated();

        $result = $this->postService->getLocations($request);

        return LocationResource::collection($result);
    }

    public function getSearchTags(GetSearchTagsRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $request = $request->validated();

        $result = $this->postService->getSearchTags($request);

        return TagDtoResource::collection($result);
    }

    public function getPost(GetPostRequest $request): PostDTOResource|JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->getPost($request);

        if (!$result) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        return new PostDTOResource($result);
    }

    public function getPostsByTag(GetPostsByTagRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getPostsByTag($request);

        return new PaginatedPostDTOResource($result);
    }

    public function getRecommendedPosts(GetFeedPostsRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getRecommendationPosts($request);

        return new PaginatedPostDTOResource($result);
    }

    public function getFeedPosts(GetFeedPostsRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getFeedPosts($request);

        return new PaginatedPostDTOResource($result);
    }

    public function getPosts(GetPostsRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getUserPosts($request);

        return new PaginatedPostDTOResource($result);
    }

    public function getSearchPosts(GetSearchPostRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getSearchPost($request);

        return new PaginatedPostDTOResource($result);
    }

    public function getReposts(GetRepostsRequest $request): PaginatedPostDTOResource
    {
        $request = $request->validated();

        $result = $this->postService->getReposts($request);

        return new PaginatedPostDTOResource($result);
    }

    public function createPost(CreatePostRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->create($request);

        return response()->json(['success' => $result], $result ? 201 : 400);
    }

    public function updatePostText(UpdateTextRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->updateText($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function updatePostLocation(UpdateLocationRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->updateLocation($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function updatePostTags(UpdateTagsRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->updateTags($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function updatePostFiles(UpdateFilesRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->updateFiles($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function deletePost(DeletePostRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->postService->delete($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function getComments(GetCommentRequest $request): PaginatedCommentDTOResource
    {
        $request = $request->validated();

        $result = $this->commentService->get($request);

        return new PaginatedCommentDTOResource($result);
    }

    public function getComment(GetCommentByIdRequest $request): CommentWithReplyDTOResource
    {
        $request = $request->validated();

        $result = $this->commentService->getComment($request);

        return new CommentWithReplyDTOResource($result);
    }

    public function getCommentReplies(GetCommentRepliesRequest $request): PaginatedCommentDTOResource
    {
        $request = $request->validated();

        $result = $this->commentService->getReplies($request);

        return new PaginatedCommentDTOResource($result);
    }

    public function getCommentForAdmin(GetCommentForAdminRequest $request): CommentDTOResource|JsonResponse
    {
        $request = $request->validated();

        $result = $this->commentService->getCommentForAdmin($request);

        if (!$result) {
            return response()->json(['error' => 'No rights'], 405);
        }

        return new CommentDTOResource($result);
    }

    public function leaveComment(AddCommentRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->commentService->create($request);

        return response()->json(['success' => $result], $result ? 201 : 400);
    }

    public function updateComment(UpdateCommentRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->commentService->update($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function deleteComment(DeleteCommentRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->commentService->delete($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function likePost(LikeRequest $request): JsonResponse
    {
        $request = $request->validated();

        $result = $this->likeService->like($request);

        return response()->json(['success' => $result], $result ? 200 : 400);
    }

    public function getPostLikes(GetLikesRequest $request): PaginatedUserDTOResource
    {
        $request = $request->validated();

        $result = $this->likeService->get($request);

        return new PaginatedUserDTOResource($result);
    }

}
