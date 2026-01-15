@extends('layouts.app')
@section('title', 'Últimas Fórmulas')

@push('styles')
<style>
  /* ====== SCOPE SOLO PARA ESTA VISTA ====== */
  .recientes-scope .table th,
  .recientes-scope .table td { vertical-align: middle; }

  .recientes-scope .text-nowrap { white-space: nowrap; }

  /* Botón compacto solo-ícono */
  .recientes-scope .btn-icon{
    width:28px;height:28px;
    display:inline-flex;align-items:center;justify-content:center;
    padding:0;line-height:1;
  }

  /* Bootstrap Icons normalizados */
  .recientes-scope .btn-icon i.bi{
    font-size:16px !important;
    line-height:1 !important;
    width:16px !important;
    height:16px !important;
    display:inline-block !important;
  }
  .recientes-scope .btn-icon i.bi::before{
    line-height:1 !important;
    vertical-align:middle !important;
  }

  /* Compacta filas */
  .recientes-scope .table td,
  .recientes-scope .table th{
    padding-top:.45rem; padding-bottom:.45rem;
  }
</style>
@endpush

@section('content')
<div class="card recientes-scope">
  <div class="card-body">

    <h4 class="mb-3">Últimas Fórmulas</h4>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Código Fórmula</th>
            <th>Nombre Fórmula</th>

            <th class="text-end">Precio Público</th>
            <th class="text-end">Precio Médico</th>

            {{-- Precio Distribuidor: solo ADMIN --}}
            @can('can-see-distributor-price')
              <th class="text-end">Precio Distribuidor</th>
            @endcan

            <th>Fecha</th>
            <th style="width:120px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($formulas as $i => $f)
            <tr>
              <td>{{ ($formulas->firstItem() ?? 1) + $i }}</td>

              <td>{{ $f->codigo }}</td>

              <td>{{ $f->nombre_etiqueta }}</td>

              <td class="text-end">
                {{ number_format((float)($f->precio_publico ?? 0), 2) }}
              </td>

              <td class="text-end">
                {{ number_format((float)($f->precio_medico ?? 0), 2) }}
              </td>

              {{-- Precio Distribuidor: solo ADMIN --}}
              @can('can-see-distributor-price')
                <td class="text-end">
                  {{ number_format((float)($f->precio_distribuidor ?? 0), 2) }}
                </td>
              @endcan

              <td>
                {{ optional($f->created_at)->format('Y-m-d') }}
              </td>

              <td class="text-nowrap">
                {{-- Editar --}}
                <a href="{{ route('formulas.editar.cargar', $f->id) }}"
                   class="btn btn-outline-primary btn-icon"
                   title="Editar fórmula"
                   aria-label="Editar fórmula">
                  <i class="bi bi-pencil"></i>
                </a>

                {{-- Ver ítems / Exportar --}}
                <a href="{{ route('fe.items', $f->id) }}"
                   class="btn btn-success btn-icon"
                   title="Ver ítems"
                   aria-label="Ver ítems">
                  <i class="bi bi-file-earmark-excel"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td
                @can('can-see-distributor-price')
                  colspan="8"
                @else
                  colspan="7"
                @endcan
                class="text-center text-muted py-4"
              >
                No tienes fórmulas registradas aún.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="text-muted small">
        Página {{ $formulas->currentPage() }}
      </div>
      <div>
        {{ $formulas->links() }}
      </div>
    </div>

  </div>
</div>
@endsection
