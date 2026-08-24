<x-mail::message>
@php
    $url = str_replace('://', '://' . $subdomain . '.', config('app.url'));
@endphp

# Conta activada com sucesso!

Olá, **{{ $nomeUser }}**!

A conta da instituição **{{ $nomeInstituicao }}** foi activada.

<x-mail::button :url="$url">
Aceder à Plataforma
</x-mail::button>

As suas credenciais:
- **Email:** {{ $email }}
- **Password temporária:** `12345678`

> Altere a password após o primeiro acesso.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>