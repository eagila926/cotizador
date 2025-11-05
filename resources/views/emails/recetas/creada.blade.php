@component('mail::message')
# Nueva receta generada

**Fórmula:** {{ $receta->codigo_formula }}  
**SO:** {{ $receta->so }}  
**Fecha:** {{ \Carbon\Carbon::parse($receta->fecha)->format('d/m/Y') }}

@isset($medico)
**Médico:** {{ trim(($medico->nombres ?? '').' '.($medico->apellidos ?? '')) }} (C.I. {{ $medico->cedula }})
@endisset

@isset($formula)
**Etiqueta:** {{ $formula->nombre_etiqueta }}
@endisset

**Paciente (referencial):** {{ $receta->paciente }}

@component('mail::panel')
**Composición:**  
@foreach(($items ?? collect()) as $it)
- {{ $it->activo }} — {{ number_format((float)$it->cantidad,2) }} {{ $it->unidad ?? 'mg' }}
@endforeach
@endcomponent

@component('mail::button', ['url' => $linkReceta])
Ver receta
@endcomponent

Gracias,  
**{{ config('app.name') }}**
@endcomponent
