<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => [
                'required', 'string', 'max:190', 'alpha_dash:ascii',
                Rule::unique('blogs', 'slug')->ignore($this->route('blog')?->getKey()),
            ],
            'excerpt' => ['nullable', 'string', 'max:1500'],
            'body' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
