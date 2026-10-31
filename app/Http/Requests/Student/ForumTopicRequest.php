<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class ForumTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }
}
