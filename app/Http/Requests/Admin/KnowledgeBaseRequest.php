<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class KnowledgeBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:190',
                Rule::unique('knowledge_bases', 'slug')->ignore($this->route('knowledgeBase')?->getKey())],
            'category' => ['nullable', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:50000'],
            'published' => ['sometimes', 'boolean'],
        ];
    }
}
