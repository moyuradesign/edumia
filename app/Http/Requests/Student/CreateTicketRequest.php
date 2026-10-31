<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:10', 'max:20000'],
        ];
    }
}
