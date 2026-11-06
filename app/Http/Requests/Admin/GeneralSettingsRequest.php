<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Normalize optional text, without changing the meaning of submitted
        // registration flags, locale, or timezone.
        $fields = [];
        foreach (['site_name', 'support_email', 'homepage_notice', 'site_timezone'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $fields[$key] = trim($value);
            }
        }
        if ($fields !== []) {
            $this->merge($fields);
        }
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:120'],
            'default_locale' => ['sometimes', 'required', Rule::in(array_keys(\App\Helpers\Localization::LANGUAGES))],
            'site_timezone' => ['sometimes', 'required', 'timezone:all'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'allow_registration' => ['sometimes', 'boolean'],
            'homepage_notice' => ['nullable', 'string', 'max:500'],
        ];
    }
}
