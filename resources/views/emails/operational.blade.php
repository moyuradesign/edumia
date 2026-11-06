@component('mail::message')
# {{ $headline }}

{{ $summary }}

@component('mail::button', ['url' => $destination])
View in Edumia
@endcomponent

This notice relates to activity on your Edumia account. If you did not expect it, sign in directly to Edumia to review your account.

Thanks,  
{{ config('app.name', 'Edumia') }}
@endcomponent
