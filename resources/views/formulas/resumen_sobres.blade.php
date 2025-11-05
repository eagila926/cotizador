@extends('layouts.app')
@section('title','Resumen – Sobres | Ortomed')

@section('content')
<div class="card">
  <div class="card-body">
    <h4 class="mb-3">Resumen de Fórmula en Sobres</h4>

    {{-- === Formulario de cabecera (guardar) === --}}
    <form action="{{ route('formulas.guardar_sobres') }}" method="POST" class="mb-3">
      @csrf
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Código de fórmula</label>
          <input type="text" name="cod_formula" class="form-control" value="{{ $codFormula ?? '' }}" readonly>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Nombre etiqueta</label>
          <input type="text" name="nombre_etiqueta" class="form-control" value="{{ old('nombre_etiqueta') }}" placeholder="Ej. SUEÑO PROFUNDO">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Médico</label>
          <input type="text" name="medico" class="form-control" value="{{ old('medico') }}" placeholder="Dr(a). NOMBRE APELLIDO">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1">Paciente</label>
          <input type="text" name="paciente" class="form-control" value="{{ old('paciente') }}" placeholder="NOMBRE DEL PACIENTE">
        </div>
      </div>

      {{-- Enviar precios y tomas por compatibilidad; aquí van fijos/0 para sobres --}}
      <input type="hidden" name="precio_medico"       value="{{ $precio_med ?? 0 }}">
      <input type="hidden" name="precio_publico"      value="{{ $precio_pvp ?? 0 }}">
      <input type="hidden" name="precio_distribuidor" value="{{ $precio_dis ?? 0 }}">
      <input type="hidden" name="tomas_diarias"       value="{{ $tomasDiarias ?? 1 }}">

      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Guardar fórmula (Sobres)</button>
        <a href="{{ route('formulas.nuevas') }}" class="btn btn-outline-secondary">Volver</a>
      </div>
    </form>

    {{-- === Alertas de tratamiento === --}}
    <div class="alert alert-info mb-3">
      Tratamiento: <strong>{{ $diasTratamiento ?? 30 }} días</strong><br>
      Sobres por día: <strong>{{ $tomasDiarias ?? 1 }}</strong><br>
    </div>

    {{-- === Tabla principal === --}}
    <div class="table-responsive">
      <table class="table" id="tabla-resumen">
        <thead>
          <tr>
            <th class="d-none d-md-table-cell">#</th>
            <th class="d-none d-md-table-cell">Cod. Odoo</th>
            <th>Activo</th>
            <th class="text-end">Cant.</th>
            <th>Unidad</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $r)
            @php
              $fijos = [70299, 70256, 9585]; // CAJA, SOBRES, CLIGHT, SUCARALOSA (SOBRES/CAJA comparten 70299)
              $isFijo = in_array((int)($r['cod_odoo'] ?? 0), $fijos, true);
            @endphp
            <tr class="{{ $isFijo ? 'table-light' : '' }}">
              <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
              <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] }}</td>
              <td>{{ $r['activo'] }}</td>
              <td class="text-end">
                @if(isset($r['cantidad']))
                  {{ rtrim(rtrim(number_format((float)$r['cantidad'], 3), '0'), '.') }}
                @else
                  —
                @endif
              </td>
              <td>{{ $r['unidad'] ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- ===== Precios de la formula (igual que cápsulas) ===== --}}
    <div class="card mt-3">
      <div class="card-body">
        <h4 class="mb-3">Precio de la Formula</h4>

        {{-- Si quieres recalcular aquí, puedes usar $totalGeneral que viene del controlador: --}}
        @php
          $precio_med = ($precio_med ?? (($totalGeneral ?? 0) * 30)) + 2;
          $precio_dis = ($precio_dis ?? ($precio_med * 0.65)) + 2;
          $precio_pvp = ($precio_pvp ?? ($precio_med * 1.33)) + 2;
        @endphp

        <p><strong>Precio Médico:</strong> {{ number_format(round($precio_med, 2), 2) }}</p>
        <p><strong>Precio Público:</strong> {{ number_format(round($precio_pvp, 2), 2) }}</p>
        <p><strong>Precio Distribuidor:</strong> {{ number_format(round($precio_dis, 2), 2) }}</p>
      </div>
    </div>

    {{-- ===== Detalle de la fórmula – PESAJE (Sobres) ===== --}}
    <div class="card mt-3">
      <div class="card-body">
        <h4 class="mb-3">Detalle de pesaje</h4>

        <div class="table-responsive">
          <table class="table" id="tabla-pesaje">
            <thead>
              <tr>
                <th class="d-none d-md-table-cell">#</th>
                <th class="d-none d-md-table-cell">Cod. Odoo</th>
                <th>Activo</th>
                <th class="text-end">Cant.</th>
                <th>Unidad</th>
                <th class="text-end">Cant. total</th>
                <th class="text-end">Densidad</th>
                <th class="text-end">Vol ml</th>
                <th class="text-end">Masa f Mes</th>
              </tr>
            </thead>

            <tbody>
              @foreach($rows as $i => $r)
                @php
                  // Resalta los fijos
                  $isFijo = in_array((int)($r['cod_odoo'] ?? 0), [70299, 70256, 9585], true);
                @endphp
                <tr class="{{ $isFijo ? 'table-light' : '' }}">
                  <td class="d-none d-md-table-cell">{{ $i+1 }}</td>
                  <td class="d-none d-md-table-cell">{{ $r['cod_odoo'] }}</td>
                  <td>{{ $r['activo'] }}</td>

                  {{-- Cant. / Unidad (muestra lo que se ve en resumen: und o mg totales) --}}
                  <td class="text-end">
                    @if(!is_null($r['cantidad']))
                      {{ rtrim(rtrim(number_format((float)$r['cantidad'], 3), '0'), '.') }}
                    @else
                      —
                    @endif
                  </td>
                  <td>{{ $r['unidad'] ?? '—' }}</td>

                  {{-- Cant. total (g/día para insumos; — para und) --}}
                  <td class="text-end">
                    @if(!is_null($r['cant_total_pesaje']))
                      {{ rtrim(rtrim(number_format($r['cant_total_pesaje'], 4), '0'), '.') }}
                    @else
                      —
                    @endif
                  </td>

                  {{-- Densidad (g/ml) --}}
                  <td class="text-end">
                    @if(!is_null($r['densidad']))
                      {{ rtrim(rtrim(number_format($r['densidad'], 6), '0'), '.') }}
                    @else
                      —
                    @endif
                  </td>

                  {{-- Volumen ml/día --}}
                  <td class="text-end">
                    @if(!is_null($r['vol_ml']))
                      {{ rtrim(rtrim(number_format($r['vol_ml'], 4), '0'), '.') }}
                    @else
                      —
                    @endif
                  </td>

                  {{-- Masa f Mes (g/mes) --}}
                  <td class="text-end">
                    @if(!is_null($r['masa_mes']))
                      {{ rtrim(rtrim(number_format($r['masa_mes'], 4), '0'), '.') }}
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>

            <tfoot>
              <tr>
                <th colspan="7" class="text-end">Totales</th>
                <th class="text-end">{{ number_format($totalVolMl ?? 0, 3) }}</th>
                <th class="text-end">{{ number_format($totalMasaMes ?? 0, 3) }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>



  </div>
</div>

{{-- Validadores front (mismas reglas de mayúsculas que cápsulas) --}}
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const soloMayusculas = (input, permitirNumeros = false) => {
      if (!input) return;
      input.addEventListener("input", () => {
        let valor = input.value
          .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // quitar acentos
          .toUpperCase();                                   // a mayúsculas
        valor = permitirNumeros ? valor.replace(/[^A-Z0-9\s]/g, "") : valor.replace(/[^A-Z\s]/g, "");
        input.value = valor;
      });
    };
    soloMayusculas(document.querySelector("[name='nombre_etiqueta']"), true);
    soloMayusculas(document.querySelector("[name='medico']"));
    soloMayusculas(document.querySelector("[name='paciente']"));
  });
</script>
@endsection
