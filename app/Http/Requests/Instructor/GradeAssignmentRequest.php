<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class GradeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && Gate::allows('update', $course);
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'feedback' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
