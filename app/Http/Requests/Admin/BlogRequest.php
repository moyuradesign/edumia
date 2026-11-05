<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Blog;

final class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (trim((string) $this->input('slug', '')) !== '') {
            return;
        }

        $base = mb_substr(Str::slug((string) $this->input('title', '')), 0, 178);
        if ($base === '') {
            return;
        }
        $slug = $base;
        $number = 2;
        while (Blog::query()->where('slug', $slug)
            ->when($this->route('blog'), fn ($query, $blog) => $query->whereKeyNot($blog->getKey()))->exists()) {
            $slug = mb_substr($base, 0, 178).'-'.$number++;
        }
        $this->merge(['slug' => $slug]);
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
            'category_id' => ['nullable', 'integer', Rule::exists('blog_categories', 'id')],
            'featured_media_id' => ['nullable', 'integer',
                Rule::exists('media_files', 'id')->where('disk', 'local')
                    ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])],
        ];
    }
}
