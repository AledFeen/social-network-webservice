<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostTag;
use App\Models\PreferredTag;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreferredTagService
{
    public function ignore(array $request) : bool
    {
        $tags = PostTag::where('post_id', $request['post_id'])->get();

        DB::beginTransaction();
        try {

            foreach ($tags as $tag) {
                PreferredTag::where('tag', $tag->tag)
                    ->where('user_id', Auth::id())
                    ->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            logger($e);
            return false;
        }
    }

    public function delete(array $request): bool
    {
        return (bool) PreferredTag::where('id', $request['id'])
            ->delete();
    }

    public function getTags() {
        return PreferredTag::where('user_id', Auth::id())->get();
    }

    public function addTag(array $request) : bool {
        if($this->checkTagExistence($request['text'])) {
            if(!PreferredTag::where('user_id', Auth::id())->where('tag', $request['text'])->exists()) {
                return (bool) PreferredTag::create([
                    'user_id' => Auth::id(),
                    'tag' => $request['text']
                ]);
            } else return false;
        } else return false;
    }

    protected function checkTagExistence(string $tag): bool
    {
        return (bool) Tag::where('name', $tag)->exists();
    }
}
