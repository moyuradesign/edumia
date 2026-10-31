<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BootcampRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $bootcamp = $this->route('bootcamp');

        return $actor?->canTeach() &&
            ($bootcamp === null || $actor->isAdmin() || (int) $bootcamp->instructor_id === (int) $actor->getKey());
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:190',
                Rule::unique('bootcamps', 'slug')->ignore($this->route('bootcamp')?->getKey())],
            'description' => ['nullable', 'string', 'max:30000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999', 'decimal:0,2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
