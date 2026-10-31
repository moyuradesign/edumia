<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class KnowledgeBaseTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $base = $this->route('knowledgeBase');

        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:190',
                Rule::unique('knowledge_base_topics', 'slug')
                    ->where('knowledge_base_id', $base?->getKey())
                    ->ignore($this->route('topic')?->getKey())],
            'body' => ['required', 'string', 'max:50000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
