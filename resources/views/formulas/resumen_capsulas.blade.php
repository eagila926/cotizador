@extends('layouts.app')
@section('title','Resumen – Cápsulas')

@section('content')
<div class="card">
  <div class="card-body">
    <h4 class="mb-3">Resumen de Fórmula en Cápsulas</h4>

    {{-- FORM GUARDAR --}}
    <form action="{{ route('formulas.guardar') }}" method="POST" class="mb-3">
      @csrf
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Código de fórmula</label>
          <input type="text" name="cod_formula" class="form-control" value="{{ $codFormula ?? '' }}" readonly>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Nombre etiqueta</label>
          <input type="text" name="nombre_etiqueta" class="form-control" value="{{ old('nombre_etiqueta') }}" placeholder="Ej. SUEÑO PROFUNDO" autocomplete="off">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Médico</label>
          <input type="text" name="medico" id="medico" class="form-control" value="{{ old('medico') }}" placeholder="Nombre del médico (campo libre)" autocomplete="off">
        </div>
      </div>

      {{-- IMPORTANTE: precios se calculan en backend, no se mandan por hidden --}}
      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guardar fórmula</button>
        <a href="{{ route('formulas.nuevas') }}" class="btn btn-outline-secondary">Volver</a>
      </div>
    </form>

    {{-- Tratamiento + Precios (misma altura) --}}
    <div class="row g-3 mb-3">
      <div class="col-12 col-lg-6">
        <div class="alert alert-info h-100 mb-0">
          Tratamiento: <strong>30 días</strong><br>
          Cápsulas por día: <strong>{{ $capsDia ?? 0 }}</strong><br>
          Total 30 días: <strong>{{ $capsMes ?? 0 }}</strong>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="alert alert-success h-100 mb-0">
          <div><strong>Precio Público (PVP):</strong> {{ number_format((float)($precio_pvp_v ?? 0), 2) }}</div>
          <div><strong>Precio Médico (20% PVP):</strong> {{ number_format((float)($precio_med_v ?? 0), 2) }}</div>

          {{-- Precio distribuidor: solo ADMIN --}}
          @can('can-see-distributor-price')
            <div><strong>Precio Distribuidor (35% PVP):</strong> {{ number_format((float)($precio_dis_v ?? 0), 2) }}</div>
          @endcan
        </div>
      </div>
    </div>

    {{-- ===== Resumen simple ===== --}}
    <div class="table-responsive">
      <table class="table" id="tabla-resumen">
        <thead>
          <tr>
            <th class="d-none d-md-table-cell">#</th>
            <th class="d-none d-md-table-cell">Cod. Odoo</th>
            <th>Activo</th>
            <th class="text-end">Cant.</th>
            <th>Unidad</th>
            <th class="text-end">Equiv. (mg/día)</th>
          </tr>
        </thead>

        @php
          $isUnidadDiaria = fn($u) => in_array($u, ['g','mg','mcg','UI'], true);

          // Totales (separando diarios vs UND)
          $sumCantDiaria = 0.0; // suma cantidad (referencial) para diarios
          $sumMgDia      = 0.0; // suma mg/día
          $sumUndMes     = 0.0; // suma und/mes (cantidad para UND)
        @endphp

        <tbody>
          @foreach(($rows ?? []) as $i => $r)
            @php
              $unidad = $r['unidad'] ?? null;
              $cant   = $r['cantidad'] ?? null;
              $mgdia  = $r['mg_dia'] ?? null;

              if ($isUnidadDiaria($unidad)) {
                if (!is_null($cant))  $sumCantDiaria += (float)$cant;
                if (!is_null($mgdia)) $sumMgDia      += (float)$mgdia;
              } else {
                if (!is_null($cant))  $sumUndMes += (float)$cant;
              }
            @endphp

            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] ?? '' }}</td>
              <td>{{ $r['activo'] ?? '' }}</td>
              <td class="text-end">
                @if(!is_null($cant))
                  {{ rtrim(rtrim(number_format((float)$cant, 3), '0'), '.') }}
                @else
                  —
                @endif
              </td>
              <td>{{ $unidad ?? '—' }}</td>
              <td class="text-end">
                @if(!is_null($mgdia))
                  {{ rtrim(rtrim(number_format((float)$mgdia, 2), '0'), '.') }}
                @else
                  —
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>

        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Totales (ítems diarios)</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumCantDiaria, 3), '0'), '.') }}</th>
            <th>—</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumMgDia, 2), '0'), '.') }}</th>
          </tr>
          <!-- <tr>
            <th colspan="5" class="text-end">Total und/mes (ítems UND)</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumUndMes, 0), '0'), '.') }}</th>
          </tr> -->
        </tfoot>
      </table>
    </div>

  </div>
</div>

{{-- ===== Detalle de precios (solo ADMIN) ===== --}}
@can('can-see-price-tables')
<div class="card mt-3">
  <div class="card-body">
    <h4 class="mb-3">Detalle de precios</h4>

    <div class="table-responsive">
      <table class="table" id="tabla-detalle">
        <thead>
          <tr>
            <th class="d-none d-md-table-cell">#</th>
            <th class="d-none d-md-table-cell">Cod. Odoo</th>
            <th>Ítem</th>
            <th class="text-end">Valor costo</th>
            <th class="text-end">Base cálculo</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>

        @php
          $totalGeneral2 = 0.0; // total de subtotales
          $sumBaseGmes   = 0.0; // total base g/mes
          $sumBaseUnd    = 0.0; // total base und/mes
        @endphp

        <tbody>
          @foreach(($rows ?? []) as $i => $r)
            @php
              $unidad = $r['unidad'] ?? null;
              $valor  = (float)($r['valor_costo'] ?? 0);
              $baseCalcTxt = '—';
              $subtotal = 0.0;

              if ($isUnidadDiaria($unidad)) {
                $mg_dia = (float)($r['mg_dia'] ?? 0);
                $g_mes  = ($mg_dia * 30.0) / 1000.0;

                $baseCalcTxt = rtrim(rtrim(number_format($g_mes, 4), '0'), '.') . ' g/mes';
                $subtotal = $g_mes * $valor;

                $sumBaseGmes += $g_mes;
              } else {
                $und_mes = (float)($r['cantidad'] ?? 0);

                $baseCalcTxt = rtrim(rtrim(number_format($und_mes, 3), '0'), '.') . ' und/mes';
                $subtotal = $und_mes * $valor;

                $sumBaseUnd += $und_mes;
              }

              $totalGeneral2 += $subtotal;
            @endphp

            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] ?? '' }}</td>
              <td>{{ $r['activo'] ?? '' }}</td>
              <td class="text-end">{{ rtrim(rtrim(number_format($valor, 6), '0'), '.') }}</td>
              <td class="text-end">{{ $baseCalcTxt }}</td>
              <td class="text-end">{{ number_format($subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>

        <tfoot>
          <!-- <tr>
            <th colspan="5" class="text-end">Total base g/mes (ítems diarios)</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumBaseGmes, 4), '0'), '.') }}</th>
          </tr>
          <tr>
            <th colspan="5" class="text-end">Total base und/mes (ítems UND)</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumBaseUnd, 0), '0'), '.') }}</th>
          </tr> -->
          <tr>
            <th colspan="5" class="text-end">Total fórmula</th>
            <th class="text-end">{{ number_format($totalGeneral2, 2) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endcan

{{-- ===== Detalle de pesaje (solo ADMIN) ===== --}}
@can('can-see-price-tables')
<div class="card mt-3">
  <div class="card-body">
    <h4 class="mb-3">Detalle de pesaje</h4>

    <div class="table-responsive">
      <table class="table" id="tabla-pesaje">
        <thead>
          <tr>
            <th class="d-none d-md-table-cell">#</th>
            <th class="d-none d-md-table-cell">Cod. Odoo</th>
            <th>Ítem</th>
            <th class="text-end">mg/día</th>
            <th class="text-end">Masa mes (g)</th>
            <th class="text-end">Unidades (mes)</th>
          </tr>
        </thead>

        @php
          $sumMgDiaPesaje   = 0.0;
          $sumGmesPesaje    = 0.0;
          $sumUndMesPesaje  = 0.0;
        @endphp

        <tbody>
          @foreach(($rows ?? []) as $i => $r)
            @php
              $unidad  = $r['unidad'] ?? null;
              $mg_dia  = $r['mg_dia'] ?? null;
              $g_mes   = null;
              $und_mes = null;

              if ($isUnidadDiaria($unidad)) {
                $mgd  = (float)($mg_dia ?? 0);
                $g_mes = ($mgd * 30.0) / 1000.0;

                $sumMgDiaPesaje += $mgd;
                $sumGmesPesaje  += $g_mes;
              } else {
                $und_mes = (float)($r['cantidad'] ?? 0);
                $sumUndMesPesaje += $und_mes;
              }
            @endphp

            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] ?? '' }}</td>
              <td>{{ $r['activo'] ?? '' }}</td>

              <td class="text-end">
                @if(!is_null($mg_dia))
                  {{ rtrim(rtrim(number_format((float)$mg_dia, 3), '0'), '.') }}
                @else
                  —
                @endif
              </td>

              <td class="text-end">
                @if(!is_null($g_mes))
                  {{ rtrim(rtrim(number_format((float)$g_mes, 4), '0'), '.') }}
                @else
                  —
                @endif
              </td>

              <td class="text-end">
                @if(!is_null($und_mes))
                  {{ rtrim(rtrim(number_format((float)$und_mes, 3), '0'), '.') }}
                @else
                  —
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>

        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Totales</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumMgDiaPesaje, 3), '0'), '.') }}</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumGmesPesaje, 4), '0'), '.') }}</th>
            <th class="text-end">{{ rtrim(rtrim(number_format($sumUndMesPesaje, 0), '0'), '.') }}</th>
          </tr>
          <tr>
            <th colspan="6" class="text-end">
              Caps/día: {{ $capsDia ?? 0 }} | Total 30d: {{ $capsMes ?? 0 }}
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endcan

@endsection
