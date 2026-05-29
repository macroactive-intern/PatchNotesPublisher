<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatchNoteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required_without_all:content,published', 'string', 'max:255'],
            'content' => ['required_without_all:title,published', 'string'],
            'published' => ['required_without_all:title,content', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $msg = 'Provide at least one of: title, content, or published.';

        return [
            'title.required_without_all'     => $msg,
            'content.required_without_all'   => $msg,
            'published.required_without_all' => $msg,
        ];
    }
}
