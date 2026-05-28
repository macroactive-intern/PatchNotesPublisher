<?php

namespace App\Http\Requests;

use App\Models\PatchNote;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePatchNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patchNote = $this->route('patch_note');
        $user = $this->user();

        return $user?->isAdmin()
            || ($user?->isEditor() && $patchNote instanceof PatchNote && $patchNote->user_id === $user->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'published' => ['sometimes', 'boolean'],
        ];
    }
}
