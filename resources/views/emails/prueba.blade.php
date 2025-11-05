@component('mail::message')
# Hola {{ $usuario }}

{{ $mensaje }}

@component('mail::button', ['url' => 'https://appsescollanos.com'])
Ir al sitio
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
