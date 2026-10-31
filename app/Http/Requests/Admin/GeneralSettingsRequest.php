<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class GeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:120'],
            'default_locale' => ['sometimes', 'required', \Illuminate\Validation\Rule::in(array_keys(\App\Helpers\Localization::LANGUAGES))],
            'site_timezone' => ['sometimes', 'required', 'timezone:all'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'allow_registration' => ['sometimes', 'boolean'],
            'homepage_notice' => ['nullable', 'string', 'max:500'],
        ];
    }
}
