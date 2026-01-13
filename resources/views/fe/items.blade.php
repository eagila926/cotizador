@extends('layouts.app')
@section('title','Ítems de la fórmula '.$f->codigo.' | Ortomed')

@section('content')
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Ítems de la fórmula</h4>
        <div class="text-muted"><strong>{{ $f->codigo }}</strong> — {{ $f->nombre_etiqueta }}</div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Regresar</a>
        <a href="{{ route('fe.items.export', $f->id) }}" class="btn btn-success">Exportar</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
        <tr>
          <th>Cod. Odoo</th>
          <th>Activo</th>
          <th class="text-end">Cantidad</th>
          <th>Unidad</th>
          <th>Masa G</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $it)
          <tr>
            <td>{{ $it->cod_odoo }}</td>
            <td>{{ $it->activo }}</td>
            <td class="text-end">{{ rtrim(rtrim(number_format((float)$it->cantidad, 6, '.', ''), '0'), '.') }}</td>
            <td>{{ $it->unidad }}</td>
            <td class="text-end">{{ rtrim(rtrim(number_format((float)$it->masa_mes, 6, '.', ''), '0'), '.') }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted">No hay ítems para este código.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>


    <h4 class="mb-0">Tabla de Exportación Odoo</h4>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
        <tr>
          <th>Líneas de LdM/Componente/Id. de la BD</th>
          <th>Líneas de LdM/Cantidad</th>
          <th>Líneas de lista de materiales/Unidad de medida del producto</th>
        </tr>
        </thead>
        <tbody>
          @forelse($items as $it)
            @php
              // Determinar si es magnitud de masa (mg/mcg/UI/g) para forzar unidad "g"
              $u = strtolower((string)$it->unidad);
              $esMasa = in_array($u, ['mg','mcg','ui','g']);

              // La cantidad a exportar para LdM:
              // - Si es masa, usamos masa_mes (que está en g/mes) y formateamos a 4 decimales
              // - Si no es masa (ej. 'und'), usamos cantidad como está (sin convertir la unidad)
              $cantidadExport = $esMasa
                  ? (float)($it->masa_mes ?? 0)
                  : (float)($it->cantidad ?? 0);

              // Unidad a mostrar:
              $unidadExport = $esMasa ? 'g' : ($it->unidad ?? '');

              // Normalización de unidades no-masa
              $unidadMap = [
                'und' => 'Unidades',
              ];

              $unidadExport = $esMasa
                  ? 'g'
                  : ($unidadMap[$u] ?? ($it->unidad ?? ''));
            @endphp
            <tr>
              <td>{{ $it->cod_odoo }}</td>
              <td class="text-end">{{ number_format($cantidadExport, 4, '.', '') }}</td>
              <td>{{ $unidadExport }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted">No hay ítems para este código.</td></tr>
          @endforelse
          </tbody>

      </table>
    </div>
  </div>
</div>
@endsection
