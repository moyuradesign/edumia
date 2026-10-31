<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(UserRole::values())],
            'status' => ['required', Rule::in(AccountStatus::values())],
        ];
    }
}
