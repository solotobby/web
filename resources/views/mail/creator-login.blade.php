<x-mail::message>
# Open your studio

Hello {{ $creator->name }},

Use this one-time link to open your creator studio. It expires in 30 minutes.

<x-mail::button :url="$loginUrl">
Open studio
</x-mail::button>

Your door: {{ url('/with/'.$creator->slug) }}

If you did not ask for this, you can ignore the letter.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
