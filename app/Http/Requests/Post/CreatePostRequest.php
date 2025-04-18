<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'repost_id' => 'nullable|integer',
            'location' => 'nullable|string|max:64',
            'text' => 'nullable|string|max:512',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:32',
            'files' => 'nullable|array|max:4',
            'files.*' => 'file|mimes:jpeg,png,jpg,gif,svg,mp4,webm|max:25600',
        ];
    }
}
