@extends('layouts.app')
@section('title','Resumen – Cápsulas')

@section('content')
<div class="card">
  <div class="card-body">
    <h4 class="mb-3">Resumen de Fórmula en Cápsulas</h4>

    @php
      // ==== PRE-CÁLCULOS PARA PRECIOS ====
      // Recorremos filas para armar totales de la fórmula según tu nueva lógica.
      $totalGeneral = 0.0;

      // Helpers
      $isUnidadDiaria = function($u) {
        return in_array($u, ['g','mg','mcg','UI'], true);
      };
    @endphp

    @if(isset($rows) && count($rows))
      @foreach($rows as $r)
        @php
          $unidad = $r['unidad'] ?? null;
          $valor  = (float)($r['valor_costo'] ?? 0);
          $subtotal = 0.0;

          if ($isUnidadDiaria($unidad)) {
            // ACTIVO: mg/día -> g/mes
            $mg_dia = (float)($r['mg_dia'] ?? 0);
            $g_mes  = ($mg_dia * 30.0) / 1000.0;
            $subtotal = $g_mes * $valor;
          } else {
            // UND (cápsulas / pastillero)
            $und_mes = (float)($r['cantidad'] ?? 0);
            $subtotal = $und_mes * $valor;
          }

          $totalGeneral += $subtotal;
        @endphp
      @endforeach
    @endif

    @php
      // ==== PRECIOS SEGÚN TU FÓRMULA ====
      $base = $totalGeneral + 0.10 + 0.10 + 3.80; // +4.00
      $pvp  = ceil($base + ($base*2.5));                  // subir picos
      $precio_med_v = round($pvp * 0.80, 2);
      $precio_dis_v = round($pvp * 0.65, 2);
      $precio_pvp_v = (float)$pvp;
    @endphp

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

      {{-- Guardamos precios y tomas diarias ya calculadas aquí --}}
      <input type="hidden" name="precio_medico"       value="{{ $precio_med_v }}">
      <input type="hidden" name="precio_publico"      value="{{ $precio_pvp_v }}">
      <input type="hidden" name="precio_distribuidor" value="{{ $precio_dis_v }}">
      <input type="hidden" name="tomas_diarias"       value="{{ $capsDia ?? 0 }}">

      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guardar fórmula</button>
        <a href="{{ route('formulas.nuevas') }}" class="btn btn-outline-secondary">Volver</a>
      </div>
    </form>

    <div class="alert alert-info mb-3">
      Tratamiento: <strong>30 días</strong><br>
      Cápsulas por día: <strong>{{ $capsDia ?? 0 }}</strong><br>
      Total 30 días: <strong>{{ $capsMes ?? 0 }}</strong>
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
        <tbody>
          @foreach($rows as $i => $r)
            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] }}</td>
              <td>{{ $r['activo'] }}</td>
              <td class="text-end">
                @if(!is_null($r['cantidad']))
                  {{ rtrim(rtrim(number_format((float)$r['cantidad'], 3), '0'), '.') }}
                @else
                  —
                @endif
              </td>
              <td>{{ $r['unidad'] ?? '—' }}</td>
              <td class="text-end">
                @php $mgdia = $r['mg_dia'] ?? null; @endphp
                @if(!is_null($mgdia))
                  {{ rtrim(rtrim(number_format((float)$mgdia, 2), '0'), '.') }}
                @else
                  —
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ===== Detalle de precios (sin factor de venta) ===== --}}
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
        <tbody>
          @php $totalGeneral = 0.0; @endphp
          @foreach($rows as $i => $r)
            @php
              $unidad = $r['unidad'] ?? null;
              $valor  = (float)($r['valor_costo'] ?? 0);
              $baseCalcTxt = '—';
              $subtotal = 0.0;

              if (in_array($unidad, ['g','mg','mcg','UI'], true)) {
                // ACTIVO: mg/día → g/mes
                $mg_dia = (float)($r['mg_dia'] ?? 0);
                $g_mes  = ($mg_dia * 30.0) / 1000.0;
                $baseCalcTxt = rtrim(rtrim(number_format($g_mes, 4), '0'), '.') . ' g/mes';
                $subtotal = $g_mes * $valor;
              } else {
                // UND: cápsulas / pastillero
                $und_mes = (float)($r['cantidad'] ?? 0);
                $baseCalcTxt = rtrim(rtrim(number_format($und_mes, 3), '0'), '.') . ' und/mes';
                $subtotal = $und_mes * $valor;
              }

              $totalGeneral += $subtotal;
            @endphp
            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] }}</td>
              <td>{{ $r['activo'] }}</td>
              <td class="text-end">
                {{ rtrim(rtrim(number_format($valor, 6), '0'), '.') }}
              </td>
              <td class="text-end">{{ $baseCalcTxt }}</td>
              <td class="text-end">{{ number_format($subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th colspan="5" class="text-end">Total fórmula</th>
            <th class="text-end">{{ number_format($totalGeneral, 2) }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

{{-- ===== Precio de la Fórmula ===== --}}
<div class="card mt-3">
  <div class="card-body">
    <h4 class="mb-3">Precio de la Fórmula</h4>
    <p><strong>Precio Público (PVP):</strong> {{ number_format($precio_pvp_v, 2) }}</p>
    <p><strong>Precio Médico (20% PVP):</strong> {{ number_format($precio_med_v, 2) }}</p>
    <p><strong>Precio Distribuidor (35% PVP):</strong> {{ number_format($precio_dis_v, 2) }}</p>
  </div>
</div>

{{-- ===== Detalle de pesaje (simple) ===== --}}
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
        <tbody>
          @foreach($rows as $i => $r)
            @php
              $unidad = $r['unidad'] ?? null;
              $mg_dia = $r['mg_dia'] ?? null;
              $g_mes  = null;
              $und_mes = null;

              if (in_array($unidad, ['g','mg','mcg','UI'], true)) {
                $mgd = (float)($mg_dia ?? 0);
                $g_mes = ($mgd * 30.0) / 1000.0;
              } else {
                $und_mes = (float)($r['cantidad'] ?? 0);
              }
            @endphp
            <tr>
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] }}</td>
              <td>{{ $r['activo'] }}</td>
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
            <th colspan="6" class="text-end">
              Caps/día: {{ $capsDia ?? 0 }} | Total 30d: {{ $capsMes ?? 0 }}
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

{{-- Limpieza de scripts: “medico” es campo libre; sin autocompletar --}}
@endsection
