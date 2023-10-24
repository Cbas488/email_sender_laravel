<x-mail::message>
# Hi

We have received a mail change request from an Email Sender account to this recipient, please access the following URL. <br>
If you are unaware of this message, please disregard it.

<p>Token: {{ $token }}</p>

<x-mail::button :url="'https://endpoint-change/'.$token">
Click me
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
