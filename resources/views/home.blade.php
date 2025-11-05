@extends('layouts.app')

@section('title', 'Inicio | Ortomed')

@section('content')
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-2">Hola {{ Auth::user()->nombre }}!</h5>
      <p class="text-muted mb-0">Bienvenido al Cotizador.</p>
    </div>
  </div>
@endsection
