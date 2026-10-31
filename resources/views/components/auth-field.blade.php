@props(['name', 'label', 'icon' => 'user', 'type' => 'text', 'value' => '', 'help' => null])
@php
    $fieldId = 'auth-'.str_replace('_', '-', $name);
    $hasError = $errors->has($name);
    $describedBy = implode(' ', array_filter([
        $help ? $fieldId.'-help' : null,
        $hasError ? $fieldId.'-error' : null,
    ]));
@endphp
<div class="auth-field {{ $hasError ? 'auth-field--invalid' : '' }} {{ $type === 'select' ? 'auth-field--select' : '' }}">
    <div class="auth-field-control">
        <svg class="auth-field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"
             fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            @switch($icon)
                @case('mail')
                    <rect x="3" y="5" width="18" height="14" rx="3"/>
                    <path d="m4.5 7 7.5 6 7.5-6"/>
                    @break
                @case('lock')
                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>
                    @break
                @case('globe')
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18M12 3c-2.7 2.5-4 5.5-4 9s1.3 6.5 4 9c2.7-2.5 4-5.5 4-9s-1.3-6.5-4-9Z"/>
                    @break
                @default
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4.5 21c.5-4 3-6 7.5-6s7 2 7.5 6"/>
            @endswitch
        </svg>
        @if($type === 'select')
            <select id="{{ $fieldId }}" name="{{ $name }}"
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->merge(['class' => 'auth-field-input']) }}>
                {{ $slot }}
            </select>
        @else
            <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
                value="{{ $type === 'password' ? '' : $value }}" placeholder=" "
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                {{ $attributes->merge(['class' => 'auth-field-input']) }}>
        @endif
        <label class="auth-field-label" for="{{ $fieldId }}">{{ $label }}</label>
        @if($type === 'password')
            <button class="auth-password-toggle" type="button" data-password-toggle
                aria-controls="{{ $fieldId }}" aria-label="Show {{ strtolower($label) }}"
                aria-pressed="false" title="Show {{ strtolower($label) }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/>
                    <circle cx="12" cy="12" r="2.7"/>
                </svg>
            </button>
        @endif
    </div>
    @if($help)
        <p class="auth-field-help" id="{{ $fieldId }}-help">{{ $help }}</p>
    @endif
    @error($name)
        <p class="auth-field-error" id="{{ $fieldId }}-error">{{ $message }}</p>
    @enderror
</div>
