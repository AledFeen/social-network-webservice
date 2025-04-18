<?php

namespace App\Http\Requests\Post\Comment;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
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
            'post_id' => 'required|integer',
            'reply_id' => 'nullable|integer',
            'text' => 'required|string|max:255',
            'files' => 'nullable|array|max:2',
            'files.*' => 'file|mimes:jpeg,png,jpg,gif,svg|max:15240',
        ];
    }
}
