@component('mail::message')
# Temporary Account Created

Hello {{ $user->name }},

A temporary account has been created for you. Here are your login details:

**Email:** {{ $user->email }}
**Password:** {{ $password }}

Please log in and change your password as soon as possible.

@component('mail::button', ['url' => config('app.url') . '/login'])
Login
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent