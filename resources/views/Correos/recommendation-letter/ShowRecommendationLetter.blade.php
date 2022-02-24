@component('mail::message')
# Introduccion

Accede a la carta de recomendacion

@component('mail::button', ['url' => route('recommendationLetter.show',$user_id)])
Ver carta de recomendacion
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent