<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewInstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
