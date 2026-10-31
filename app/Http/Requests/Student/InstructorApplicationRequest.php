<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class InstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && ! $this->user()->canTeach();
    }

    public function rules(): array
    {
        return [
            'specialization' => ['required', 'string', 'max:190'],
            'experience' => ['required', 'string', 'min:30', 'max:8000'],
            'motivation' => ['required', 'string', 'min:30', 'max:8000'],
            'portfolio_url' => ['nullable', 'url', 'max:255', 'starts_with:https://'],
        ];
    }
}
