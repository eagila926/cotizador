@extends('layouts.app')

@section('title','Receta #'.$receta->id_receta)

@section('content')
<div class="container py-3">
  <a href="{{ route('recetas.index') }}" class="btn btn-link">&larr; Volver</a>

  <div class="card">
    <div class="card-header"><strong>Receta #{{ $receta->id_receta }}</strong></div>
    <div class="card-body">
      <dl class="row">
        <dt class="col-sm-3">SO</dt>
        <dd class="col-sm-9">{{ $receta->so }}</dd>

        <dt class="col-sm-3">Código fórmula</dt>
        <dd class="col-sm-9">{{ $receta->codigo_formula }}</dd>

        <dt class="col-sm-3">Fecha</dt>
        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($receta->fecha)->format('d/m/Y') }}</dd>

        <dt class="col-sm-3">Cédula médico</dt>
        <dd class="col-sm-9">{{ $receta->cedula_medico }}</dd>

        <dt class="col-sm-3">Paciente</dt>
        <dd class="col-sm-9">{{ $receta->paciente }}</dd>
      </dl>
    </div>
  </div>
</div>
@endsection
