<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:20000']];
    }
}
