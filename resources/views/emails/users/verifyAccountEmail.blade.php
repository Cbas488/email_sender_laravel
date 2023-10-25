<x-mail::message>
# Hi

To access your account you need to verify it, access the following link please:

<p>Token: {{ $token }}</p>

<x-mail::button :url="'https://endpoint-verify-account/'.$token">
Click me
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
