<x-mail::message>
@php
    $url = str_replace('://', '://' . $subdomain . '.', config('app.url'));
@endphp

# Registo recebido com sucesso

Olá, **{{ $nomeUser }}**!

O pedido de registo da instituição **{{ $nomeInstituicao }}** foi recebido e está a aguardar aprovação.

Pode acompanhar o estado através do link:

<x-mail::button :url="$url">
Ver Estado da Conta
</x-mail::button>

Receberá um email assim que for aprovada.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>