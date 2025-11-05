@extends('layouts.app')

@section('title','Recetas')

@section('content')
<div class="container py-3">

  @if (session('recetas_guardadas'))
    <div class="alert alert-success">
      Se guardaron {{ session('recetas_guardadas') }} receta(s) correctamente.
    </div>
  @endif

  <h3 class="mb-3">Recetas</h3>

  <form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
      <label class="form-label">Buscar</label>
      <input name="q" value="{{ $q }}" class="form-control"
             placeholder="SO, Código, Cédula o Paciente">
    </div>
    <div class="col-md-3">
      <label class="form-label">Desde</label>
      <input type="date" name="desde" value="{{ $desde }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Hasta</label>
      <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-primary w-100">Filtrar</button>
      <a href="{{ route('recetas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>SO</th>
          <th>Código fórmula</th>
          <th>Fecha</th>
          <th>Cédula médico</th>
          <th>Paciente</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      @forelse($recetas as $r)
        <tr>
          <td>{{ $r->id_receta }}</td>
          <td>{{ $r->so }}</td>
          <td>{{ $r->codigo_formula }}</td>
          <td>{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
          <td>{{ $r->cedula_medico }}</td>
          <td>{{ $r->paciente }}</td>
          <td class="text-end">
            <a href="{{ route('recetas.show', $r) }}" class="btn btn-sm btn-outline-primary">Ver</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center text-muted py-4">No hay recetas.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center mt-2">
    <div class="text-muted small">Página {{ $recetas->currentPage() }}</div>
    <div>{{ $recetas->links('pagination::bootstrap-5') }}</div>
  </div>

</div>
@endsection
