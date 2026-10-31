<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class LiveClassRequest extends FormRequest
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
            'provider' => ['required', Rule::in(['zoom', 'google_meet', 'teams', 'other'])],
            'meeting_url' => ['required', 'url', 'starts_with:https://', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', Rule::in(['scheduled', 'cancelled', 'completed'])],
        ];
    }
}
