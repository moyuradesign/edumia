<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Identity\RoleManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $fields = [];
        foreach (['name', 'slug', 'description'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $fields[$key] = trim($value);
            }
        }
        if (isset($fields['slug'])) {
            $fields['slug'] = strtolower($fields['slug']);
        }
        $this->merge($fields);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:120',
                Rule::notIn(['admin', 'administrator', 'owner', 'root', 'system', 'super-admin', 'developer']),
                Rule::unique('roles', 'slug')->ignore($this->route('role')?->getKey())],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['sometimes', 'array', 'max:10'],
            'permissions.*' => ['required', 'string', Rule::in(array_keys(RoleManagementService::DELEGATABLE))],
        ];
    }
}
