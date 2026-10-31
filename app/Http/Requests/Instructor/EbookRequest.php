<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $ebook = $this->route('ebook');

        return $actor?->canTeach()
            && ($ebook === null || $actor->isAdmin() || (int) $ebook->author_id === (int) $actor->getKey());
    }

    public function rules(): array
    {
        $ebook = $this->route('ebook');

        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:190', Rule::unique('ebooks', 'slug')->ignore($ebook?->getKey())],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,2'],
            'file' => [$ebook ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
