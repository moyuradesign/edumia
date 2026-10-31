<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && Gate::allows('update', $course);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'instructions' => ['required', 'string', 'max:30000'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'published' => ['sometimes', 'boolean'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
        ];
    }
}
